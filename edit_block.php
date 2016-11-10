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
 * Edit ncsubook chapter
 *
 * @package    mod_ncsubook
 * @copyright  2004-2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//var_dump($_POST);
//die;

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/edit_form.php');

$cmid       = required_param('cmid', PARAM_INT);  // Book Course Module ID
$chapterid  = required_param('chapterid', PARAM_INT); // Chapter ID
$blockid  = required_param('blockid', PARAM_INT); // Block ID

$cm = get_coursemodule_from_id('ncsubook', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$ncsubook = $DB->get_record('ncsubook', array('id'=>$cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/ncsubook:edit', $context);

$PAGE->set_url('/mod/ncsubook/edit.php', array('cmid'=>$cmid, 'chapterid'=>$chapterid));
$PAGE->set_pagelayout('admin'); // TODO: Something. This is a bloody hack!

$blocks = ncsubook_get_block_list($chapterid);

if ($chapterid) {
    $chapter = $DB->get_record('ncsubook_chapters', array('id'=>$chapterid, 'ncsubookid'=>$ncsubook->id), '*', MUST_EXIST);
}

$chapter->cmid = $cm->id;
if (isset($chapter->id)) {
    $chapter->chapterid = $chapter->id;
}

$options = array('noclean'=>true, 'subdirs'=>true, 'maxfiles'=>-1, 'maxbytes'=>0, 'context'=>$context);
$mform_edit_block = new ncsubook_block_edit_form( null, array( 'chapter' => $chapter, 'blockdata' => $blocks, 'options' => $options, 'blockid' => '', 'context' => $context ) );

if ($mform_edit_block->is_cancelled()) {
    redirect("edit.php?cmid=$cm->id&chapterid=$chapterid");
}

// Let's process some data
if ( $data_block = $mform_edit_block->get_data() ) {
    $data_block = file_postupdate_standard_editor($data_block, 'content', $options, $context, 'mod_ncsubook', 'chapter', $data_block->chapterid);
    // echo '<pre>'.htmlspecialchars(var_export($data_block,true)).'</pre>';
    $updated_text = $data_block->content;
    $editted_block_id = $data_block->blockid;
    $DB->update_record('ncsubook_blocks', array('id'=>$editted_block_id, 'content'=>$updated_text, 'title'=>$data_block->block_title));
    redirect("edit.php?cmid=$cm->id&chapterid=$chapterid");
}

// All the data processing is done.  We no longer need this old form (if it existed);
unset($mform_edit_block);

// Push the display to the page
$PAGE->set_title($ncsubook->name);
$PAGE->set_heading($course->fullname);

$chapters = ncsubook_preload_chapters($ncsubook);
$edit = $USER->editing;
ncsubook_add_fake_block($chapters, $chapter, $ncsubook, $cm, $edit);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editingblock', 'mod_ncsubook'));

$mform_edit_block = new ncsubook_block_edit_form( null, array( 'chapter' => $chapter, 'blockdata' => $blocks, 'options' => $options, 'blockid' => $blockid, 'context' => $context ) );
$mform_edit_block->display();

echo $OUTPUT->footer();
