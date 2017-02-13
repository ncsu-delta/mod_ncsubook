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

$id                                         = required_param('id', PARAM_INT);        // Course Module ID.
$chapterid                                  = required_param('chapterid', PARAM_INT); // Chapter ID.
$confirm                                    = optional_param('confirm', 0, PARAM_BOOL);
$cm                                         = get_coursemodule_from_id('ncsubook', $id, 0, false, MUST_EXIST);
$course                                     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook                                   = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);
$context                                    = context_module::instance($cm->id);
$chapter                                    = $DB->get_record('ncsubook_chapters', ['id' => $chapterid, 'ncsubookid' => $ncsubook->id], '*', MUST_EXIST);

require_login($course, false, $cm);
require_sesskey();
require_capability('mod/ncsubook:edit', $context);

$PAGE->set_url('/mod/ncsubook/delete.php', ['id' => $id, 'chapterid' => $chapterid]);
// Header and strings.
$PAGE->set_title($ncsubook->name);
$PAGE->set_heading($course->fullname);

// Form processing.
if ($confirm) {
    $fs                                     = get_file_storage();
    if (!$chapter->subchapter) {
        // Delete all its sub-chapters if any.

        $chapters                           = $DB->get_records('ncsubook_chapters', ['ncsubookid' => $ncsubook->id], 'pagenum', 'id, subchapter');
        $found                              = false;
        foreach ($chapters as $chp) {
            if ($chp->id == $chapter->id) {
                $found                      = true;
            } else if ($found and $chp->subchapter) {
                $fs->delete_area_files($context->id, 'mod_ncsubook', 'chapter', $chp->id);

                // When deleting a sub-chapter, first delete the associated blocks.
                $DB->delete_records('ncsubook_blocks', ['chapterid' => $chp->id]);

                // Now delete the sub-chapter itself.
                $DB->delete_records('ncsubook_chapters', ['id' => $chp->id]);

                $params                     = ['context' => $context, 'objectid' => $chp->id];
                $event                      = \mod_ncsubook\event\chapter_deleted::create($params);
                $event->add_record_snapshot('ncsubook_chapters', $chp);
                $event->trigger();
            } else if ($found) {
                unset($chapters, $params, $event);
                break;
            }
        }
        unset($chapters, $params, $event);
    }
    $params                                 = ['context' => $context, 'objectid' => $chapter->id];
    $fs->delete_area_files($context->id, 'mod_ncsubook', 'chapter', $chapter->id);

    // When deleting a chapter, first delete the asociated blocks.
    $DB->delete_records('ncsubook_blocks', ['chapterid' => $chapter->id]);

    // Now delete the chapter itself.
    $DB->delete_records('ncsubook_chapters', ['id' => $chapter->id]);

    $event                                  = \mod_ncsubook\event\chapter_deleted::create($params);
    $event->add_record_snapshot('ncsubook_chapters', $chapter);
    $event->set_legacy_logdata([$course->id, 'ncsubook', 'update', 'view.php?id=' . $cm->id, $ncsubook->id, $cm->id]);
    $event->trigger();

    ncsubook_preload_chapters($ncsubook); // Fix structure.
    $DB->set_field('ncsubook', 'revision', $ncsubook->revision + 1, ['id' => $ncsubook->id]);

    redirect('view.php?id=' . $cm->id);
}

echo $OUTPUT->header();

// The operation has not been confirmed yet so ask the user to do so.
if ($chapter->subchapter) {
    $strconfirm                             = get_string('confchapterdelete', 'mod_ncsubook');
} else {
    $strconfirm                             = get_string('confchapterdeleteall', 'mod_ncsubook');
}
echo '<br />';
$continue                                   = new moodle_url('/mod/ncsubook/delete.php', ['id' => $cm->id, 'chapterid' => $chapter->id, 'confirm' => 1]);
$cancel                                     = new moodle_url('/mod/ncsubook/view.php', ['id' => $cm->id, 'chapterid' => $chapter->id]);

echo $OUTPUT->confirm("<strong>$chapter->title</strong><p>$strconfirm</p>", $continue, $cancel);
echo $OUTPUT->footer();

