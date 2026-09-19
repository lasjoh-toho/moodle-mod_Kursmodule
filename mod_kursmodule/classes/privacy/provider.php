<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy-Provider fuer mod_kursmodule.
 *
 * Die Aktivitaet selbst speichert keine personenbezogenen Daten in
 * Modul-Kontexten (die Konfiguration der Links ist Lehrpersonen-Daten).
 * Die einzigen personenbezogenen Daten sind Eintraege in
 * {kursmodule_enrol}, die nachverfolgen, wer ueber welchen Link
 * automatisch in welchem (Ziel-)Kurs eingeschrieben wurde.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('kursmodule_enrol', [
            'userid' => 'privacy:metadata:kursmodule_enrol:userid',
            'courseid' => 'privacy:metadata:kursmodule_enrol:courseid',
            'roleid' => 'privacy:metadata:kursmodule_enrol:roleid',
            'timecreated' => 'privacy:metadata:kursmodule_enrol:timecreated',
        ], 'privacy:metadata:kursmodule_enrol');

        return $collection;
    }

    /**
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $sql = "SELECT ctx.id
                  FROM {kursmodule_enrol} ke
                  JOIN {kursmodule_link} kl ON kl.id = ke.linkid
                  JOIN {kursmodule} k ON k.id = kl.kursmoduleid
                  JOIN {course_modules} cm ON cm.instance = k.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'kursmodule'
                  JOIN {context} ctx ON ctx.instanceid = cm.id AND ctx.contextlevel = :contextmodule
                 WHERE ke.userid = :userid";

        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, [
            'contextmodule' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        global $DB;
        $sql = "SELECT ke.userid
                  FROM {kursmodule_enrol} ke
                  JOIN {kursmodule_link} kl ON kl.id = ke.linkid
                  JOIN {kursmodule} k ON k.id = kl.kursmoduleid
                  JOIN {course_modules} cm ON cm.instance = k.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'kursmodule'
                 WHERE cm.id = :cmid";
        $userlist->add_from_sql('userid', $sql, ['cmid' => $context->instanceid]);
    }

    /**
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('kursmodule', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $records = $DB->get_records_sql(
                "SELECT ke.*
                   FROM {kursmodule_enrol} ke
                   JOIN {kursmodule_link} kl ON kl.id = ke.linkid
                  WHERE kl.kursmoduleid = :kursmoduleid AND ke.userid = :userid",
                ['kursmoduleid' => $cm->instance, 'userid' => $userid]
            );

            if (!$records) {
                continue;
            }

            $data = [];
            foreach ($records as $record) {
                $data[] = (object) [
                    'courseid' => $record->courseid,
                    'roleid' => $record->roleid,
                    'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                ];
            }

            writer::with_context($context)->export_data(
                [get_string('links', 'mod_kursmodule')],
                (object) ['enrolments' => $data]
            );
        }
    }

    /**
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('kursmodule', $context->instanceid);
        if (!$cm) {
            return;
        }

        $linkids = $DB->get_fieldset_select('kursmodule_link', 'id', 'kursmoduleid = ?', [$cm->instance]);
        if ($linkids) {
            list($insql, $params) = $DB->get_in_or_equal($linkids);
            $DB->delete_records_select('kursmodule_enrol', "linkid $insql", $params);
        }
    }

    /**
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('kursmodule', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $linkids = $DB->get_fieldset_select('kursmodule_link', 'id', 'kursmoduleid = ?', [$cm->instance]);
            if ($linkids) {
                list($insql, $params) = $DB->get_in_or_equal($linkids);
                $params[] = $userid;
                $DB->delete_records_select('kursmodule_enrol', "linkid $insql AND userid = ?", $params);
            }
        }
    }

    /**
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('kursmodule', $context->instanceid);
        if (!$cm) {
            return;
        }

        $linkids = $DB->get_fieldset_select('kursmodule_link', 'id', 'kursmoduleid = ?', [$cm->instance]);
        if (!$linkids) {
            return;
        }
        list($linksql, $linkparams) = $DB->get_in_or_equal($linkids);
        list($usersql, $userparams) = $DB->get_in_or_equal($userlist->get_userids());

        $DB->delete_records_select(
            'kursmodule_enrol',
            "linkid $linksql AND userid $usersql",
            array_merge($linkparams, $userparams)
        );
    }
}
