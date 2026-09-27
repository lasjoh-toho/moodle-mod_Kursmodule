<?php
// This file is part of Moodle - http://moodle.org/

/**
 * English language file for mod_kursmodule.
 *
 * @package     mod_kursmodule
 * @copyright   2026 Jan Johann Peter <lasjohtoho@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Kursmodule';
$string['modulenameplural'] = 'Kursmodule';
$string['modulename_help'] = 'The "Kursmodule" activity lets you link several courses as sortable banners. Learners are automatically enrolled in the linked courses, and automatically unenrolled again when a link is removed. One link can be highlighted as the "current" course.';
$string['pluginname'] = 'Kursmodule';
$string['pluginadministration'] = 'Kursmodule administration';
$string['kursmodulename'] = 'Activity name';
$string['kursmodulesettings'] = 'Settings';

$string['links'] = 'Course links';
$string['managelinks_hint'] = 'Once saved, you can manage linked courses, banner images, roles and ordering on the activity\'s own management page.';
$string['managelinks'] = 'Manage course links';
$string['manage_title'] = 'Manage course links: {$a}';
$string['backtoactivity'] = 'Back to activity';

$string['addlink'] = 'Link a course';
$string['editlink'] = 'Edit link';
$string['deletelink'] = 'Remove link';
$string['deletelink_confirm'] = 'Really remove the link to "{$a}"? All learners automatically enrolled through it will be unenrolled from that course again (unless they are enrolled there some other way).';

$string['course'] = 'Course';
$string['searchcourse'] = 'Search course';
$string['title'] = 'Display title (optional)';
$string['title_help'] = 'If no title is given, the banner shows the course full name.';
$string['image'] = 'Banner image';
$string['image_help'] = 'Shown before the title. Falls back to the course image if none is uploaded.';
$string['role'] = 'Role in target course';
$string['role_student'] = 'Participant';
$string['role_guest'] = 'Guest';
$string['role_help'] = 'Determines the role learners are automatically enrolled with. The role only ever applies to this single course.';
$string['active'] = 'Active';
$string['active_help'] = 'A deactivated link is no longer shown and every enrolment it created is automatically removed.';
$string['setcurrent'] = 'Mark as current';
$string['iscurrent'] = 'Current';
$string['currentbadge'] = 'Current course';

$string['savechanges'] = 'Save changes';
$string['cancel'] = 'Cancel';
$string['linkadded'] = 'Course link added.';
$string['linkupdated'] = 'Course link updated.';
$string['linkdeleted'] = 'Course link removed.';
$string['reordersaved'] = 'Order saved.';
$string['currentset'] = 'Marked as the current course.';

$string['copytoclipboard'] = 'Copy settings to clipboard';
$string['pastefromclipboard'] = 'Paste links from clipboard';
$string['clipboardcopied'] = '{$a} course link(s) copied to your clipboard. The clipboard is personal to you and works across all Kursmodule activities, even in other courses.';
$string['clipboardempty'] = 'The clipboard is empty. Copy the links of another Kursmodule activity first.';
$string['clipboardpasted'] = '{$a->added} of {$a->total} course link(s) pasted.';
$string['clipboardpastedskipped'] = '{$a->added} of {$a->total} course link(s) pasted, {$a->skipped} skipped (target course deleted or identical to this activity\'s main course).';

$string['errorcourseinvalid'] = 'Please choose a valid course.';
$string['errorcoursealreadylinked'] = 'This course is already linked.';

$string['errorcoursenotallowed'] = 'This course is not available to you for linking under the site administration\'s settings.';

$string['onlyteacherrole'] = 'Only show courses where I am a teacher';
$string['applyfilter'] = 'Apply filter';

$string['settings_coursefilters'] = 'Restrict course selection';
$string['settings_coursefilters_desc'] = 'Controls which courses teachers are allowed to link at all. Being an editing teacher in the target course is always the baseline requirement and cannot be turned off. The settings below optionally extend it. These restrictions apply automatically and cannot be turned off by teachers - only admins can configure them here. Site admins themselves are always exempt.';
$string['settings_restrictroleteacher'] = 'Also allow linking by non-editing teachers';
$string['settings_restrictroleteacher_desc'] = 'In addition to the baseline requirement (editing teacher), also allows linking courses where the person is enrolled as a non-editing teacher.';
$string['settings_restrictrolestudent'] = 'Also allow courses where the person is only a participant';
$string['settings_restrictrolestudent_desc'] = 'In addition to the baseline requirement (editing teacher), also allows linking courses where the person is only enrolled as a participant. Only when this is enabled do teachers get the optional display option "Only show courses where I am a teacher" in the link form.';
$string['settings_fieldfiltershortnames'] = 'Course custom field shortnames';
$string['settings_fieldfiltershortnames_desc'] = 'Comma-separated list of shortnames (not display names) of fields created under Site administration → Courses → Course default settings → Course custom fields, e.g. "school,site". A course may only be linked if it matches the main course of the activity on EVERY listed field (AND logic) - e.g. to limit selection to one\'s own school in a system with many schools. Works reliably for text and select fields. Cannot be turned off by teachers. Leave empty to disable this filter.';

$string['nolinksyet'] = 'No course links have been added yet.';
$string['viewnolinks'] = 'No course links have been set up for this activity yet.';

$string['tablinks'] = 'Course links';
$string['tabgrades'] = 'Grades';

$string['gradestitle'] = 'Grade overview';
$string['gradesexport'] = 'Export as Excel file';
$string['gradesmatrixtoggle'] = 'Show detailed grade items';
$string['gradesoverall'] = 'Course total';
$string['gradesnodata'] = 'No grades are available for these links yet.';
$string['gradesstudent'] = 'Student';
$string['gradesnogradeitem'] = '–';

$string['tasksync'] = 'Synchronise Kursmodule enrolments';

$string['privacy:metadata:kursmodule_enrol'] = 'Tracks which person was automatically enrolled in which course through which course link.';
$string['privacy:metadata:kursmodule_enrol:userid'] = 'The ID of the automatically enrolled person.';
$string['privacy:metadata:kursmodule_enrol:courseid'] = 'The target course of the automatic enrolment.';
$string['privacy:metadata:kursmodule_enrol:roleid'] = 'The assigned role (participant or guest).';
$string['privacy:metadata:kursmodule_enrol:timecreated'] = 'When the automatic enrolment was created.';

$string['kursmodule:addinstance'] = 'Add a new Kursmodule activity';
$string['kursmodule:view'] = 'View the Kursmodule activity';
$string['kursmodule:manage'] = 'Manage course links';
$string['kursmodule:viewgrades'] = 'View grade overview';
