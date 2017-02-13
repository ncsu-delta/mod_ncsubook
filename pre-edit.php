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

$cmid           = required_param('cmid', PARAM_INT);  // Book Course Module ID
$pagenum        = optional_param('pagenum', 0, PARAM_INT);
$cm             = get_coursemodule_from_id('ncsubook', $cmid, 0, false, MUST_EXIST);
$context        = context_module::instance($cm->id);
$course         = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook       = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);

// Fill and print the form.
$PAGE->set_url('/mod/ncsubook/pre-edit.php', ['cmid' => $cmid]);
$PAGE->set_pagelayout('admin'); // TODO: Something. This is a bloody hack!
$PAGE->set_title($ncsubook->name);
$PAGE->set_heading($course->fullname);

if ($pagenum == 0) {
    $lastpagenum    = ncsubook_get_latest_pagenum_from_bookid($ncsubook->id);
    $pagenum        = $lastpagenum + 1;
} else {
    $pagenum        = $pagenum + 1;
}

$chapter            = new stdClass();
$chapter->cmid      = $cmid;
$chapter->pagenum   = $pagenum;
$chapter->course    = $course->id;
$chapter->ncsubookid = $ncsubook->id;

// Get a list of the chapter types for the select menu on the form
$chaptertypes       = $DB->get_records_select('ncsubook_chaptertype', 'sortorder > 0', [], 'sortorder ASC', 'id,name');

// Initialize the form.
$mformaddchapter = new ncsubook_add_chapter_form(null, ['chaptertypes' => $chaptertypes, 'chapter' => $chapter]);

if ($mformaddchapter->is_cancelled()) {
    redirect("view.php?id=$cm->id");
}

if ( $dataaddchapter = $mformaddchapter->get_data() ) {
     $sql = 'UPDATE {ncsubook_chapters}
             SET pagenum = pagenum + 1
             WHERE ncsubookid = ?
             AND pagenum >= ?';
     $DB->execute($sql, [$dataaddchapter->ncsubookid, $dataaddchapter->pagenum]);

    // Insert the chapter type and info into the DB
    if (!isset($dataaddchapter->showparenttitle)) {
        $dataaddchapter->showparenttitle = '';
    }
    if (!isset($dataaddchapter->subchapter) || empty($dataaddchapter->subchapter)) {
        $dataaddchapter->subchapter         = '';
        $dataaddchapter->showparenttitle    = '';
    }
    $dataaddchapter->timecreated    = time();
    $dataaddchapter->timemodified   = time();
    $dataaddchapter->id             = $DB->insert_record( 'ncsubook_chapters', $dataaddchapter );

    // SMBADER (6/18/2014):  Adding events for NC State Book.
    $params         = [
                        'context' => $context,
                        'objectid' => $dataaddchapter->id
                      ];

    $eventchapter  = $DB->get_record('ncsubook_chapters', ['id' => $dataaddchapter->id]);
    $event          = \mod_ncsubook\event\chapter_created::create($params);

    $event->add_record_snapshot('ncsubook_chapters', $eventchapter);
    $event->trigger();

    // Insert the default blocks for the chapter.
    $blocks                     = ncsubook_add_default_blocks($dataaddchapter);
    $qrystring                  = 'edit.php?cmid=' . $dataaddchapter->cmid . '&chapterid=' . $dataaddchapter->id . '&pagenum=' . $dataaddchapter->pagenum
                                . '&subchapter=' . $dataaddchapter->subchapter . '&type=' . $dataaddchapter->type;
    // Move on to the edit screen
    redirect($qrystring);
}

// Printing the page
$chapters           = ncsubook_preload_chapters($ncsubook);
$edit               = $USER->editing;
$chapter->id        = '';
ncsubook_add_fake_block($chapters, $chapter, $ncsubook, $cm, $edit);

echo $OUTPUT->header();

?>
<script>
function ncsubook_getSelectedValue () {
    var selectedVal = document.getElementById("id_type");
    document.getElementById("id_title").value = selectedVal.options[selectedVal.selectedIndex].text;
}
</script>
<?php

echo $OUTPUT->heading(get_string('editingchaptertype', 'mod_ncsubook'));
$mformaddchapter->display();

?>
<script>
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
</script>
<?php

echo $OUTPUT->footer();
