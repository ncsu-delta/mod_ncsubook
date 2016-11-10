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

// Get a list of the chapter types for the select menu on the form
$chaptertypes = $DB->get_records_select('ncsubook_chaptertype', 'sortorder > 0',array(),'sortorder ASC','id,name');

$mform_book = new ncsubook_chapter_edit_form(null, array('chapter'=>$chapter, 'options'=>$options, 'chaptertypes'=>$chaptertypes));
$mform_manage_blocks = new ncsubook_manage_block_form( null, array( 'chapter' => $chapter, 'blockdata' => $blocks, 'options' => $options ) );
// The block_edit_form is only loaded to check if there is a submission, after we handle the data we will unset it because the edit will be gone
$mform_edit_block = new ncsubook_block_edit_form( null, array( 'chapter' => $chapter, 'blockdata' => $blocks, 'options' => $options, 'blockid' => '', 'context' => $context ) );
$mform_add_block = new ncsubook_add_block_form(null, array('chapter'=>$chapter));

if ($mform_book->is_cancelled()) {
    redirect("view.php?id=$cm->id");
}

// Let's process some data
if ( $data_book = $mform_book->get_data() ) {
    // Update the database with the new title and additional label.
    if (!isset($data_book->showparenttitle)) {
        $data_book->showparenttitle = '';
    }
    if (!isset($data_book->subchapter) || empty($data_book->subchapter)) {
        $data_book->subchapter = '';
        $data_book->showparenttitle = '';
    }
    $fields = array( "id" => $data_book->chapterid, "title" => $data_book->title, "additionaltitle" => $data_book->additionaltitle, "subchapter" => $data_book->subchapter, "type" => $data_book->chaptertype, "showparenttitle" => $data_book->showparenttitle );
    ncsubook_update_chapter_info($fields);

    $chapter->id = $data_book->chapterid;
    $chapter->title = $data_book->title;
    $chapter->additionaltitle = $data_book->additionaltitle;
    $chapter->subchapter = $data_book->subchapter;
    $chapter->type = $data_book->chaptertype;
    $chapter->showparenttitle = $data_book->showparenttitle;

    $params = array(
        'context' => $context,
        'objectid' => $fields['id']
    );
    $event = \mod_ncsubook\event\chapter_updated::create($params);
    $event->add_record_snapshot('ncsubook_chapters', (object)$chapter);
    $event->trigger();

    if (isset($data_book->submitbutton2)) {
        redirect("../../course/view.php?id=$course->id");
    }

    if (isset($data_book->submitbutton)) {
        redirect("view.php?id=$cm->id&chapterid=$data_book->chapterid");
    }

} elseif ( $data_manage_blocks = $mform_manage_blocks->get_data() ) {

    if (isset($_POST['displayChapterPage'])) {
        if ($_POST['displayChapterPage'] == 'Display This Page') {
            redirect("view.php?id=$data_manage_blocks->cmid&chapterid=$data_manage_blocks->chapterid");
        }
    }
    // the form is submitted but the buttons don't actually pass databack.
    // search the POST variable

    // Let's see if someone pushed a delete block button.
    foreach($_POST as $key => $value) {
        $pos = strpos($key , "deleteblock-");
        if ($pos === 0){
            $deleteblockid = substr($key,12);
        }
    }

    // Let's see if someone pushed an edit block button.
    foreach($_POST as $key => $value) {
        $pos = strpos($key , "editblock-");
        if ($pos === 0){
            $editblockid = substr($key,10);
        }
    }

    if (isset($deleteblockid)) {
        $deleteblocktype = ncsubook_get_blocktype($deleteblockid);
        foreach ($deleteblocktype as $value) {
            $delblocktype = $value->type;
        }
        // We're getting the number of content type blocks so we can make sure that we don't delete
        // the last one. Seen previous comments above.
        $numcontentblocks = ncsubook_count_content_blocks($chapter->chapterid);
        if ($delblocktype == 1 && $numcontentblocks <= 1) {
            //Print error: Can't delete the last content block
        } else {
            $DB->delete_records('ncsubook_blocks', array('id' => $deleteblockid));
            $result = ncsubook_reorder_blocks($chapterid);
        }
    }

} elseif ( $data_block = $mform_add_block->get_data() ) {

    $blocktype = $data_block->addnewblock;
    ncsubook_add_block($blocktype, $chapter->chapterid);

} else {

    // no forms have been submitted

}

// All the data processing is done.  We no longer need this old form (if it existed);
unset($mform_edit_block);

// Push the display to the page

$PAGE->set_title($ncsubook->name);
$PAGE->set_heading($course->fullname);

$chapters = ncsubook_preload_chapters($ncsubook);
$edit = $USER->editing;
ncsubook_add_fake_block($chapters, $chapter, $ncsubook, $cm, $edit);

if (isset($editblockid)) {
    redirect("edit_block.php?cmid=$cm->id&chapterid=$chapterid&blockid=$editblockid");
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editingchapter', 'mod_ncsubook'));

$mform_book->display();

$blocks = ncsubook_get_block_list($chapterid);

echo $OUTPUT->heading(get_string('editingchapterpagecontent', 'mod_ncsubook'));
$mform_manage_blocks = new ncsubook_manage_block_form( null, array( 'chapter' => $chapter, 'blockdata' => $blocks, 'options' => $options ) );

$mform_manage_blocks->display();

$mform_add_block->display();

echo '<script type="text/javascript">
<!--
YUI().use(\'node\', function(Y){
   var subchapter_checkbox = Y.one(\'input[id="id_subchapter"]\');
   var parentchapter_checkbox = Y.one(\'input[id="id_showparenttitle"]\');
   var parentchapter_label = Y.one(\'label[for="id_showparenttitle"]\');
   checkSubchapter();
   subchapter_checkbox.on(\'change\',checkSubchapter);
   function checkSubchapter(){
       if (subchapter_checkbox.get("checked")) {
           parentchapter_label.show();
           parentchapter_checkbox.show();
       } else {
           parentchapter_label.hide();
           parentchapter_checkbox.hide();
           parentchapter_checkbox.set(\'checked\',false);
       }
   }
});
-->
</script>';


echo $OUTPUT->footer();
