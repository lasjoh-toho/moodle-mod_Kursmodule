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
