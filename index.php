<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file is part of the NC State Book plugin
 *
 * The NC State Book plugin is an extension of mod_book with some additional
 * blocks to aid in organizing and presenting content. This plugin was originally
 * developed for North Carolina State University.
 *
 * @package mod_ncsubook
 * @copyright 2014 Gary Harris, Amanda Robertson, Cathi Phillips Dunnagan, Jeff Webster, David Lanier
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');

$id                                                     = required_param('id', PARAM_INT); // Course ID.

$course                                                 = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

unset($id);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

// Get all required strings
$strncsubooks                                           = get_string('modulenameplural', 'mod_ncsubook');
$strncsubook                                            = get_string('modulename', 'mod_ncsubook');
$strsectionname                                         = get_string('sectionname', 'format_'.$course->format);
$strname                                                = get_string('name');
$strintro                                               = get_string('moduleintro');
$strlastmodified                                        = get_string('lastmodified');

$PAGE->set_url('/mod/ncsubook/index.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname.': '.$strncsubooks);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strncsubooks);
echo $OUTPUT->header();

\mod_ncsubook\event\course_module_instance_list_viewed::create_from_course($course)->trigger();

// Get all the appropriate data
if (!$ncsubooks = get_all_instances_in_course('ncsubook', $course)) {
    notice(get_string('thereareno', 'moodle', $strncsubooks), $CFG->wwwroot . '/course/view.php?id=' . $course->id);
    die;
}

$usesections                                            = course_format_uses_sections($course->format);
if ($usesections) {
    $sections                                           = get_all_sections($course->id);
}

$table                                                  = new html_table();
$table->attributes['class']                             = 'generaltable mod_index';

if ($usesections) {
    $table->head                                        = [$strsectionname, $strname, $strintro];
    $table->align                                       = ['center', 'left', 'left'];
} else {
    $table->head                                        = [$strlastmodified, $strname, $strintro];
    $table->align                                       = ['left', 'left', 'left'];
}

$modinfo                                                = get_fast_modinfo($course);
$currentsection                                         = '';
foreach ($ncsubooks as $ncsubook) {
    $cm                                                 = $modinfo->get_cm($ncsubook->coursemodule);
    if ($usesections) {
        $printsection                                   = '';
        if ($ncsubook->section !== $currentsection) {
            if ($ncsubook->section) {
                $printsection                           = get_section_name($course, $sections[$ncsubook->section]);
            }
            if ($currentsection !== '') {
                $table->data[]                          = 'hr';
            }
            $currentsection                             = $ncsubook->section;
        }
    } else {
        $printsection                                   = html_writer::tag('span', userdate($ncsubook->timemodified),
        ['class' => 'smallinfo']);
    }

    $class                                              = $ncsubook->visible ? null : ['class' => 'dimmed']; // Hidden

    $table->data                                        = [$printsection,
                                                           html_writer::link(new moodle_url('view.php', ['id' => $cm->id]),
                                                                             format_string($ncsubook->name),
                                                                             $class
                                                                            ),
                                                          format_module_intro('ncsubook', $ncsubook, $cm->id)
                                                         ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
