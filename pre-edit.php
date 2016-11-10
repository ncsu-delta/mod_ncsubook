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

require(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/edit_form.php');

$cmid       = required_param('cmid', PARAM_INT);  // Book Course Module ID
$pagenum    = optional_param('pagenum', 0, PARAM_INT);

$cm = get_coursemodule_from_id('ncsubook', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$ncsubook = $DB->get_record('ncsubook', array('id'=>$cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);

$PAGE->set_url('/mod/ncsubook/pre-edit.php', array('cmid'=>$cmid));
$PAGE->set_pagelayout('admin'); // TODO: Something. This is a bloody hack!

// Otherwise fill and print the form.
$PAGE->set_title($ncsubook->name);
$PAGE->set_heading($course->fullname);

if ($pagenum == 0) {
    $last_pagenum = ncsubook_get_latest_pagenum_from_bookid($ncsubook->id);
    $pagenum = $last_pagenum + 1;
} else {
    $pagenum = $pagenum + 1;
}

$chapter = new stdClass();
$chapter->cmid = $cmid;
$chapter->pagenum = $pagenum;
$chapter->course = $course->id;
$chapter->ncsubookid = $ncsubook->id;

// Get a list of the chapter types for the select menu on the form
$chaptertypes = $DB->get_records_select('ncsubook_chaptertype', 'sortorder > 0',array(),'sortorder ASC','id,name');

// Initialize the form
$mform_add_chapter = new ncsubook_add_chapter_form(null, array('chaptertypes'=>$chaptertypes, 'chapter'=>$chapter));

if ($mform_add_chapter->is_cancelled()) {
    redirect("view.php?id=$cm->id");
}

if ( $data_add_chapter = $mform_add_chapter->get_data() ) {
     $sql = "UPDATE {ncsubook_chapters}
             SET pagenum = pagenum + 1
             WHERE ncsubookid = ? AND pagenum >= ?";
     $DB->execute($sql, array($data_add_chapter->ncsubookid, $data_add_chapter->pagenum));

    // Insert the chapter type and info into the DB
    if (!isset($data_add_chapter->showparenttitle)) {
        $data_add_chapter->showparenttitle = '';
    }
    if (!isset($data_add_chapter->subchapter) || empty($data_add_chapter->subchapter)) {
        $data_add_chapter->subchapter = '';
        $data_add_chapter->showparenttitle = '';
    }
    $data_add_chapter->timecreated = time();
    $data_add_chapter->timemodified = time();
    $data_add_chapter->id = $DB->insert_record( 'ncsubook_chapters', $data_add_chapter );

    // SMBADER (6/18/2014):  Adding events for NC State Book
    $params = array(
        'context' => $context,
        'objectid' => $data_add_chapter->id
    );
    $event_chapter = $DB->get_record('ncsubook_chapters', array('id' => $data_add_chapter->id));
    $event = \mod_ncsubook\event\chapter_created::create($params);
    $event->add_record_snapshot('ncsubook_chapters', $event_chapter);
    $event->trigger();

    // Insert the default blocks for the chapter
    $blocks = ncsubook_add_default_blocks($data_add_chapter);

    // Move on to the edit screen
    redirect("edit.php?cmid=$data_add_chapter->cmid&chapterid=$data_add_chapter->id&pagenum=$data_add_chapter->pagenum&subchapter=$data_add_chapter->subchapter&type=$data_add_chapter->type");
}

// Printing the page
$chapters = ncsubook_preload_chapters($ncsubook);
$edit = $USER->editing;
$chapter->id = '';
ncsubook_add_fake_block($chapters, $chapter, $ncsubook, $cm, $edit);

echo $OUTPUT->header();

echo '<script type="text/javascript">
function ncsubook_getSelectedValue () {
    var selectedVal = document.getElementById("id_type");
    document.getElementById("id_title").value = selectedVal.options[selectedVal.selectedIndex].text;
}
</script>';

echo $OUTPUT->heading(get_string('editingchaptertype', 'mod_ncsubook'));

$mform_add_chapter->display();

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
