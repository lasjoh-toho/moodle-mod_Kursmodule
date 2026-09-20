<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_kursmodule;

defined('MOODLE_INTERNAL') || die();

/**
 * Rendert die Kursverknuepfungen als uebersichtliche Tabelle mit Bild,
 * Titel und Kursinfos. Wird sowohl auf der Aktivitaetsseite (view.php)
 * als auch direkt auf der Kursseite (kursmodule_cm_info_view()) genutzt,
 * damit Lernende und Trainer/innen die Verknuepfungen sehen, ohne die
 * Aktivitaet erst oeffnen zu muessen.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class link_renderer {

    /** @var array<int, string> Cache fuer bereits aufgeloeste Kategorienamen. */
    protected static array $categorynamecache = [];

    /**
     * @param int $kursmoduleid
     * @param \context_module $context
     * @param int $cmid
     * @param bool $activeonly
     * @param bool $showmanagebutton "Verwalten"-Button unterhalb anzeigen (nur fuer Trainer/innen)
     * @param bool $includestyles Inline-<style> mit ausgeben (fuer Einbindung auf der Kursseite noetig,
     *                            da dort $PAGE->requires->css() zu spaet greifen wuerde)
     * @return string
     */
    public static function render_table(
        int $kursmoduleid,
        \context_module $context,
        int $cmid,
        bool $activeonly,
        bool $showmanagebutton,
        bool $includestyles = false
    ): string {
        global $DB;

        $links = link_manager::get_links($kursmoduleid, $activeonly);

        $out = $includestyles ? self::inline_styles() : '';

        if (empty($links)) {
            $out .= \html_writer::div(get_string('viewnolinks', 'mod_kursmodule'), 'alert alert-info kursmodule-empty');
            if ($showmanagebutton) {
                $out .= self::managebutton($cmid);
            }
            return $out;
        }

        $out .= \html_writer::start_div('kursmodule-table');
        foreach ($links as $link) {
            $targetcourse = $DB->get_record(
                'course',
                ['id' => $link->courseid],
                'id, fullname, shortname, category, visible'
            );
            if (!$targetcourse) {
                continue;
            }

            $title = $link->title !== null && $link->title !== '' ? $link->title : format_string($targetcourse->fullname);
            $meta = self::get_course_meta($targetcourse);

            $imageurl = self::get_link_image_url($context, $link, (int) $targetcourse->id);

            $courseurl = new \moodle_url('/mod/kursmodule/go.php', ['id' => $cmid, 'linkid' => $link->id]);
            $rowclass = 'kursmodule-row';
            $rowclass .= $link->iscurrent ? ' kursmodule-row-current' : ' kursmodule-row-dim';
            if (!$targetcourse->visible) {
                $rowclass .= ' kursmodule-row-hidden';
            }

            $out .= \html_writer::start_tag('a', ['href' => $courseurl, 'class' => $rowclass]);

            if ($imageurl) {
                $out .= \html_writer::empty_tag('img', ['src' => $imageurl, 'class' => 'kursmodule-row-img', 'alt' => '']);
            } else {
                $initial = \core_text::strtoupper(\core_text::substr($title, 0, 1));
                $out .= \html_writer::div(s($initial), 'kursmodule-row-img kursmodule-row-img-placeholder');
            }

            $out .= \html_writer::start_div('kursmodule-row-info');
            $out .= \html_writer::div(s($title), 'kursmodule-row-title');
            if ($meta !== '') {
                $out .= \html_writer::div(s($meta), 'kursmodule-row-meta');
            }
            $out .= \html_writer::end_div();

            if ($link->iscurrent) {
                $out .= \html_writer::span(get_string('currentbadge', 'mod_kursmodule'), 'kursmodule-row-badge');
            }

            $out .= \html_writer::end_tag('a');
        }
        $out .= \html_writer::end_div();

        if ($showmanagebutton) {
            $out .= self::managebutton($cmid);
        }

        return $out;
    }

    /**
     * @param int $cmid
     * @return string
     */
    protected static function managebutton(int $cmid): string {
        return \html_writer::div(
            \html_writer::link(
                new \moodle_url('/mod/kursmodule/manage.php', ['id' => $cmid]),
                get_string('managelinks', 'mod_kursmodule'),
                ['class' => 'btn btn-secondary btn-sm']
            ),
            'kursmodule-managebutton mt-2'
        );
    }

    /**
     * Kurzinfo zu einem Kurs (Kategorie + Kurzname), so wie sie auch in
     * "Meine Kurse" unter dem Kursnamen erscheint.
     *
     * @param \stdClass $course
     * @return string
     */
    protected static function get_course_meta(\stdClass $course): string {
        global $DB;

        $categoryname = '';
        if (!empty($course->category)) {
            if (!isset(self::$categorynamecache[$course->category])) {
                $categoryname = $DB->get_field('course_categories', 'name', ['id' => $course->category]);
                self::$categorynamecache[$course->category] = $categoryname ? format_string($categoryname) : '';
            }
            $categoryname = self::$categorynamecache[$course->category];
        }

        $parts = array_filter([$categoryname, $course->shortname]);
        return implode(' · ', $parts);
    }

    /**
     * Bild fuer eine Verknuepfung: eigenes Banner-Bild, sonst Kursbild des
     * Zielkurses, sonst null (Platzhalter).
     *
     * @param \context_module $context
     * @param \stdClass $link
     * @param int $targetcourseid
     * @return \moodle_url|null
     */
    protected static function get_link_image_url(\context_module $context, \stdClass $link, int $targetcourseid): ?\moodle_url {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_kursmodule', 'linkimage', $link->id, 'itemid', false);
        foreach ($files as $file) {
            return \moodle_url::make_pluginfile_url(
                $context->id, 'mod_kursmodule', 'linkimage', $link->id, $file->get_filepath(), $file->get_filename()
            );
        }

        return kursmodule_get_courseimage_url($targetcourseid);
    }

    /**
     * Inline-Styles fuer die Tabellenansicht, damit die Darstellung auch
     * dann korrekt ist, wenn die Verknuepfungen direkt auf der Kursseite
     * (ueber kursmodule_cm_info_view()) eingebunden werden - dort ist es
     * fuer $PAGE->requires->css() bereits zu spaet.
     *
     * @return string
     */
    protected static function inline_styles(): string {
        return '<style>' .
            '.kursmodule-table{display:flex;flex-direction:column;border:1px solid rgba(0,0,0,.125);' .
            'border-radius:6px;overflow:hidden;margin-bottom:.5rem;}' .
            '.kursmodule-row{display:flex;align-items:stretch;text-decoration:none;color:inherit;' .
            'border-bottom:1px solid rgba(0,0,0,.125);background:transparent;}' .
            '.kursmodule-row:last-child{border-bottom:none;}' .
            '.kursmodule-row:hover{text-decoration:none;background:rgba(0,0,0,.035);}' .
            '.kursmodule-row-img{width:120px;height:68px;object-fit:cover;flex-shrink:0;display:block;margin:0;padding:0;}' .
            '.kursmodule-row-img-placeholder{align-items:center;justify-content:center;display:flex;' .
            'font-size:1.4em;font-weight:600;background:#3d3d6b;color:#fff;}' .
            '.kursmodule-row-info{padding:8px 14px;display:flex;flex-direction:column;justify-content:center;min-width:0;}' .
            '.kursmodule-row-title{font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}' .
            '.kursmodule-row-meta{font-size:.85em;opacity:.7;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}' .
            '.kursmodule-row-badge{align-self:center;margin-right:14px;font-size:.75em;text-transform:uppercase;' .
            'letter-spacing:.04em;background:#2f6feb;color:#fff;border-radius:10px;padding:2px 10px;white-space:nowrap;}' .
            '.kursmodule-row-current{box-shadow:inset 0 0 0 2px rgba(79,140,255,.65);}' .
            '.kursmodule-row-dim{filter:brightness(.92);}' .
            '.kursmodule-row-hidden{opacity:.6;}' .
            '.kursmodule-managebutton{margin-top:.5rem;}' .
            '</style>';
    }
}
