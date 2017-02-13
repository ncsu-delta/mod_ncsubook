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
 * Book import
 *
 * @package    ncsubooktool_importhtml
 * @copyright  2004-2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @modified   for the NC State Book plugin.
 * @copyright 2014 Gary Harris, Amanda Robertson, Cathi Phillips Dunnagan, Jeff Webster, David Lanier
 */

require(dirname(__FILE__) . '/../../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/import_form.php');

$id         = required_param('id', PARAM_INT);           // Course Module ID
$chapterid  = optional_param('chapterid', 0, PARAM_INT); // Chapter ID
$cm         = get_coursemodule_from_id('ncsubook', $id, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook   = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);
$context    = context_module::instance($cm->id);

require_login($course, false, $cm);

require_capability('ncsubooktool/importhtml:import', $context);

$PAGE->set_url('/mod/ncsubook/tool/importhtml/index.php', ['id' => $id, 'chapterid' => $chapterid]);

if ($chapterid) {
    if (!$chapter = $DB->get_record('ncsubook_chapters', ['id' => $chapterid, 'ncsubookid' => $ncsubook->id])) {
        $chapterid = 0;
    }
} else {
    $chapter = false;
}

$PAGE->set_title($ncsubook->name);
$PAGE->set_heading($course->fullname);

// Prepare the page header.
$strncsubook    = get_string('modulename', 'mod_ncsubook');
$strncsubooks   = get_string('modulenameplural', 'mod_ncsubook');
$mform          = new ncsubooktool_importhtml_form(null, ['id' => $id, 'chapterid' => $chapterid]);

// If data submitted, then process and store.
if ($mform->is_cancelled()) {
    if (empty($chapter->id)) {
        redirect($CFG->wwwroot . '/mod/ncsubook/view.php?id=' . $cm->id);
    } else {
        redirect($CFG->wwwroot . '/mod/ncsubook/view.php?id=' . $cm->id . '&chapterid=' . $chapter->id);
    }

} else if ($data = $mform->get_data()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('importingchapters', 'ncsubooktool_importhtml'));

    // this is a bloody hack - children do not try this at home!
    $fs         = get_file_storage();
    $draftid    = file_get_submitted_draft_itemid('importfile');
    if (!$files = $fs->get_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $draftid, 'id DESC', false)) {
        redirect($PAGE->url);
    }
    $file = reset($files);
    toolncsubook_importhtml_import_chapters($file, $data->type, $ncsubook, $context);

    echo $OUTPUT->continue_button(new moodle_url('/mod/ncsubook/view.php', ['id' => $id]));
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('import', 'ncsubooktool_importhtml'));

$mform->display();

echo $OUTPUT->footer();
