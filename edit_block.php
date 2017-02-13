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

require(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/edit_form.php');

$cmid                                       = required_param('cmid', PARAM_INT);            // Book Course Module ID.
$chapterid                                  = required_param('chapterid', PARAM_INT);       // Chapter ID.
$blockid                                    = required_param('blockid', PARAM_INT);         // Block ID.
$cm                                         = get_coursemodule_from_id('ncsubook', $cmid, 0, false, MUST_EXIST);
$course                                     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook                                   = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);
$context                                    = context_module::instance($cm->id);
$blocks                                     = ncsubook_get_block_list($chapterid);
$chapter                                    = $DB->get_record('ncsubook_chapters', ['id' => $chapterid, 'ncsubookid' => $ncsubook->id], '*', MUST_EXIST);
$chapter->cmid                              = $cm->id;
$chapter->chapterid                         = $chapter->id;
$chapters                                   = ncsubook_preload_chapters($ncsubook);

require_login($course, false, $cm);
require_capability('mod/ncsubook:edit', $context);

$options                                    = ['noclean'    => true,
                                               'subdirs'    => true,
                                               'maxfiles'   => -1,
                                               'maxbytes'   => 0,
                                               'context'    => $context
                                              ];
$editoptions                                = ['chapter'    => $chapter,
                                               'blockdata'  => $blocks,
                                               'options'    => $options,
                                               'blockid'    => '',
                                               'context'    => $context
                                              ];

$mformeditblock                             = new ncsubook_block_edit_form(null, $editoptions);

// Push the display to the page
$PAGE->set_title($ncsubook->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_url('/mod/ncsubook/edit.php', ['cmid' => $cmid, 'chapterid' => $chapterid]);
$PAGE->set_pagelayout('admin');                 // TODO: Something. This is a bloody hack!




if ($mformeditblock->is_cancelled()) {
    redirect('edit.php?cmid=' . $cm->id . '&chapterid=' . $chapterid);
}

$edit                                       = $USER->editing;

// Let's process some data.
if ($datablock = $mformeditblock->get_data()) {
    $datablock                              = file_postupdate_standard_editor($datablock, 'content', $options, $context, 'mod_ncsubook', 'chapter', $datablock->chapterid);
    // echo '<pre>'.htmlspecialchars(var_export($datablock,true)).'</pre>';
    $updatedtext                            = $datablock->content;
    $editedblockid                          = $datablock->blockid;
    $DB->update_record('ncsubook_blocks', ['id' => $editedblockid, 'content' => $updatedtext, 'title' => $datablock->block_title]);
    redirect('edit.php?cmid=' . $cm->id . '&chapterid=' . $chapterid);
}

// All the data processing is done.  We no longer need this old form (if it existed).
unset($mformeditblock);

ncsubook_add_fake_block($chapters, $chapter, $ncsubook, $cm, $edit);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editingblock', 'mod_ncsubook'));

$editoptions['blockid']                     = $blockid;

$mformeditblock                             = new ncsubook_block_edit_form(null, $editoptions);
$mformeditblock->display();

echo $OUTPUT->footer();
