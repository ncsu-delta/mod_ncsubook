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
require_once(dirname(__FILE__).'/edit_form.php');

$cmid                                       = required_param('cmid', PARAM_INT);        // Book Course Module ID.
$chapterid                                  = required_param('chapterid', PARAM_INT);   // Chapter ID.
$cm                                         = get_coursemodule_from_id('ncsubook', $cmid, 0, false, MUST_EXIST);
$course                                     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook                                   = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);
$context                                    = context_module::instance($cm->id);
$blocks                                     = ncsubook_get_block_list($chapterid);
$chapter                                    = $DB->get_record('ncsubook_chapters', ['id' => $chapterid, 'ncsubookid' => $ncsubook->id], '*', MUST_EXIST);
$chapter->cmid                              = $cm->id;
$chapter->chapterid                         = $chapter->id;

require_login($course, false, $cm);
require_capability('mod/ncsubook:edit', $context);

$options                                    = ['noclean'    => true,
                                               'subdirs'    => true,
                                               'maxfiles'   => -1,
                                               'maxbytes'   => 0,
                                               'context'    => $context
                                              ];
$editblockoptions                           = ['chapter'    => $chapter,
                                               'blockdata'  => $blocks,
                                               'options'    => $options,
                                               'blockid'    => '',
                                               'context'    => $context
                                              ];

// Setup the page display.
$PAGE->set_title($ncsubook->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_url('/mod/ncsubook/edit.php', ['cmid' => $cmid, 'chapterid' => $chapterid]);
$PAGE->set_pagelayout('admin'); // TODO: Something. This is a bloody hack!

$chapters                                   = ncsubook_preload_chapters($ncsubook);
$edit                                       = $USER->editing;

                                              // Get a list of the chapter types for the select menu on the form.
$chaptertypes                               = $DB->get_records_select('ncsubook_chaptertype', 'sortorder > 0', [], 'sortorder ASC', 'id,name');

$mformbook                                  = new ncsubook_chapter_edit_form(null, ['chapter' => $chapter, 'options' => $options, 'chaptertypes' => $chaptertypes]);
$mformmanageblocks                          = new ncsubook_manage_block_form( null, ['chapter' => $chapter, 'blockdata' => $blocks, 'options' => $options] );

// The block_edit_form is only loaded to check if there is a submission, after we handle the data we will unset it because the edit will be gone.
$mformeditblock                             = new ncsubook_block_edit_form( null, $editblockoptions);
$mformaddblock                              = new ncsubook_add_block_form(null, ['chapter' => $chapter]);

if ($mformbook->is_cancelled()) {
    redirect("view.php?id=$cm->id");
}

// Let's process some data.
if ($databook = $mformbook->get_data()) {
   
    // Update the database with the new title and additional label.
    if (!isSet($databook->showparenttitle)) {
        // I really don't think this is necessary if not set to begin with -- drl.
        $databook->showparenttitle         = '';
    }
    if (!isSet($databook->subchapter) || empty($databook->subchapter)) {
        $databook->subchapter              = '';
        $databook->showparenttitle         = '';
    }

    $fields                                 = ['id'                 => $databook->chapterid,
                                               'title'              => $databook->title,
                                               'additionaltitle'    => $databook->additionaltitle,
                                               'subchapter'         => $databook->subchapter,
                                               'type'               => $databook->chaptertype,
                                               'showparenttitle'    => $databook->showparenttitle
                                              ];

    ncsubook_update_chapter_info($fields);

    $chapter->id                            = $databook->chapterid;
    $chapter->title                         = $databook->title;
    $chapter->additionaltitle               = $databook->additionaltitle;
    $chapter->subchapter                    = $databook->subchapter;
    $chapter->type                          = $databook->chaptertype;
    $chapter->showparenttitle               = $databook->showparenttitle;
    $params                                 = ['context' => $context, 'objectid' => $fields['id']];
    $event                                  = \mod_ncsubook\event\chapter_updated::create($params);
    $event->add_record_snapshot('ncsubook_chapters', $chapter);
    $event->trigger();

    isSet($databook->submitbutton2) ? redirect('../../course/view.php?id=' . $course->id) : '';
    isSet($databook->submitbutton) ? redirect('view.php?id=' . $cm->id . '&chapterid=' . $databook->chapterid) : '';

} else if ($datamanageblocks = $mformmanageblocks->get_data()) {
      
    if (!empty($_POST['displayChapterPage'])) {
        redirect('view.php?id=' . $datamanageblocks->cmid . '&chapterid=' . $datamanageblocks->chapterid);
    }
    // the form is submitted but the buttons don't actually pass databack.
    // search the POST variable
    // this needs to be refactored. no need for two for loops or perhaps no need for a loop at all -- drl.

    // Let's see if someone pushed a delete block button.
    
    foreach ($_POST as $key => $value) {
        $pos                                = strpos($key , "deleteblock-");
        if ($pos === 0) {
            $deleteblockid                  = substr($key, 12);
        }
    }

    // Let's see if someone pushed an edit block button.
    foreach ($_POST as $key => $value) {
        $pos                                = strpos($key , "editblock-");
        if ($pos === 0) {
            $editblockid                    = substr($key, 10);
        }
    }

    if (isSet($deleteblockid)) {
        $deleteblocktype                    = ncsubook_get_blocktype($deleteblockid);

        foreach ($deleteblocktype as $value) {
            $delblocktype                   = $value->type;
        }

        // We're getting the number of content type blocks so we can make sure that we don't delete.
        // the last one. Seen previous comments above.
        $numcontentblocks                   = ncsubook_count_content_blocks($chapter->chapterid);

        if ($delblocktype == 1 && $numcontentblocks <= 1) {
             // DRL - This was an empty if statement with only the comment below. Not sure what the intent was here yet.
            // Print error: Can't delete the last content block.
        } else {
            $DB->delete_records('ncsubook_blocks', ['id' => $deleteblockid]);
            $result                         = ncsubook_reorder_blocks($chapterid);
        }
    }

} else if ($datablock = $mformaddblock->get_data() ) {

    $blocktype                              = $datablock->addnewblock;
    ncsubook_add_block($blocktype, $chapter->chapterid);

}

// All the data processing is done.  We no longer need this old form (if it existed).
unset($mformeditblock);

ncsubook_add_fake_block($chapters, $chapter, $ncsubook, $cm, $edit);

isSet($editblockid) ? redirect('edit_block.php?cmid=' . $cm->id . '&chapterid=' . $chapterid . '&blockid=' . $editblockid) : '';

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editingchapter', 'mod_ncsubook'));

$mformbook->display();

$blocks                                     = ncsubook_get_block_list($chapterid);

echo $OUTPUT->heading(get_string('editingchapterpagecontent', 'mod_ncsubook'));

$mformmanageblocks                          = new ncsubook_manage_block_form( null, ['chapter' => $chapter, 'blockdata' => $blocks, 'options' => $options]);

$mformmanageblocks->display();

$mformaddblock->display();

?>
<script>

    YUI().use(node, function(Y){
       var subchapter_checkbox = Y.one(input[id="id_subchapter"]);
       var parentchapter_checkbox = Y.one(input[id="id_showparenttitle"]);
       var parentchapter_label = Y.one(label[for="id_showparenttitle"]);
       checkSubchapter();
       subchapter_checkbox.on(change,checkSubchapter);
       function checkSubchapter(){
           if (subchapter_checkbox.get("checked")) {
               parentchapter_label.show();
               parentchapter_checkbox.show();
           } else {
               parentchapter_label.hide();
               parentchapter_checkbox.hide();
               parentchapter_checkbox.set(checked,false);
           }
       }
    });

</script>
<?php
echo $OUTPUT->footer();
