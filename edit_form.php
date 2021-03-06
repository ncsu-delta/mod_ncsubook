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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

class ncsubook_chapter_edit_form extends moodleform {

    public function definition() {
        global $CFG, $DB;

        $chapter                                            = $this->_customdata['chapter'];
        $options                                            = $this->_customdata['options'];
        $chaptertypes                                       = $this->_customdata['chaptertypes'];
        $mform                                              = $this->_form;
        // Disabled subchapter option when editing first node.
        $disabledmsg                                        = '';

        if ($chapter->pagenum == 1) {
            $disabledmsg                                    = get_string('subchapternotice', 'ncsubook');
        }

        $mform->addElement('header', 'general', 'Page Settings');

        $mform->addElement('text', 'title', get_string('chaptertitle', 'mod_ncsubook'), ['size' => '30']);
        $mform->setType('title', PARAM_RAW);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->setDefault('title', $chapter->title);

        $mform->addElement('text', 'additionaltitle', get_string('alternatebooktitle', 'mod_ncsubook'), ['size' => '30']);
        $mform->setType('additionaltitle', PARAM_RAW);
        $mform->setDefault('additionaltitle', $chapter->additionaltitle);
        $mform->addHelpButton('additionaltitle', 'alternatebooktitle', 'mod_ncsubook');

        $mform->addElement('advcheckbox', 'subchapter', get_string('subchapter', 'mod_ncsubook'), $disabledmsg);
        $mform->addElement('advcheckbox', 'showparenttitle', get_string('showparenttitle', 'mod_ncsubook'));

        foreach ($chaptertypes as $chaptertype) {
            $addchapteroptions[$chaptertype->id]            = $chaptertype->name;
        }

        $mform->addElement('select', 'chaptertype', get_string('changingchaptertype', 'mod_ncsubook'), $addchapteroptions);
        $mform->setDefault('chaptertype', $chapter->type);
        $mform->addHelpButton('chaptertype', 'changingchaptertype', 'mod_ncsubook');

        $mform->addElement('hidden', 'chapterid');
        $mform->setType('chapterid', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'pagenum');
        $mform->setType('pagenum', PARAM_INT);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_INT);

        // Save and Display buttons
        $submitlabel                                        = get_string('savechangesanddisplay');
        $submit2label                                       = get_string('savechangesandreturntocourse');
        $mform                                              = $this->_form;

        // elements in a row need a group
        // $buttonarray[];
        $buttonarray[]                                      = &$mform->createElement('submit', 'submit', 'Save and Continue');
        $buttonarray[]                                      = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[]                                      = &$mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', get_string('actionbuttons', 'mod_ncsubook'), array(' '), false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
        // End Save and Display buttons

        // set the defaults
        $this->set_data($chapter);

    }

    public function definition_after_data() {
        $mform                                              = $this->_form;
        $pagenum                                            = $mform->getElement('pagenum');
        if ($pagenum->getValue() == 1) {
            $mform->hardFreeze('subchapter');
        }
    }
}

class ncsubook_manage_block_form extends moodleform {

    public function definition() {
        global $OUTPUT;

        $chapter                                            = $this->_customdata['chapter'];
        $blockdata                                          = $this->_customdata['blockdata'];
        $options                                            = $this->_customdata['options'];
        $mform                                              = $this->_form;

        $html                                               = '<table style="width: 40%; margin-right: 10%; min-width: 350px; float: left;">'
                                                            . '<tr><td colspan="2"><span style="">Click "Edit" on the elements below to edit page content.</span></td></tr>';
        $maxarrayelementcount                               = count($blockdata);
        $currentarrayelementcount                           = 0;

        foreach ($blockdata as $key => $block) {
            $currentarrayelementcount++;
            // Need to do count of blockdata outside of loop -- drl.
            if (count($blockdata) == 1) {
                $row                                        = '<td style="width: 50%; overflow: hidden">' . $block->title
                                                            . '</td><td><input class="btn btn-primary" type="submit" name="editblock-' . $key . '" value="Edit" /></td>';
            } else if ($currentarrayelementcount == 1) {
                // Only show the down arrow
                $row                                        = '<td style="width: 50%; overflow: hidden">' . $block->title
                                                            . '</td><td><input class="btn btn-primary" type="submit" name="editblock-'
                                                            . $key . '" value="Edit" /> | <input class="btn btn-primary" type="submit" name="deleteblock-'
                                                            . $key . '" value="Delete" /> | <a href="move_blocks.php?chapterid='
                                                            . $chapter->chapterid . '&id=' . $chapter->cmid . '&up=0&blockid='
                                                            . $key . '"><img src="' .$OUTPUT->pix_url('down', 'mod_ncsubook') . '"></a></td>';

            } else if ($currentarrayelementcount == $maxarrayelementcount) {
                // Only show the up arrow
                $row                                        = '<td style="width: 50%; overflow: hidden">' . $block->title
                                                            . '</td><td><input class="btn btn-primary" type="submit" name="editblock-' . $key
                                                            . '" value="Edit" /> | <input class="btn btn-primary" type="submit" name="deleteblock-' . $key
                                                            . '" value="Delete" /> | <a href="move_blocks.php?chapterid='
                                                            . $chapter->chapterid . '&id=' . $chapter->cmid . '&up=1&blockid='
                                                            . $key . '"><img src="' . $OUTPUT->pix_url('up', 'mod_ncsubook') . '"></a></td>';
            } else {
                // Show both arrows
                $row                                        = '<td style="width: 50%; overflow: hidden">' . $block->title
                                                            . '</td><td><input class="btn btn-primary" type="submit" name="editblock-' . $key
                                                            . '" value="Edit" /> | <input class="btn btn-primary" type="submit" name="deleteblock-'
                                                            . $key . '" value="Delete" /> | <a href="move_blocks.php?chapterid='
                                                            . $chapter->chapterid . '&id=' . $chapter->cmid . '&up=0&blockid='
                                                            . $key . '"><img src="' . $OUTPUT->pix_url('down', 'mod_ncsubook')
                                                            . '"></a> <a href="move_blocks.php?chapterid=' . $chapter->chapterid . '&id='
                                                            . $chapter->cmid . '&up=1&blockid=' . $key . '"><img src="'
                                                            . $OUTPUT->pix_url('up', 'mod_ncsubook') . '"></a></td>';
            }
            $html .= '<tr>' . $row . '</tr>';
        }
        $html                                               .= '<tr><td colspan="2" align=""> </td></tr>'
                                                            . '<tr><td colspan="2" align=""><input class="btn btn-primary" type="submit" name="displayChapterPage" value="Display This Page">'
                                                            . '</td></tr></table>'
                                                            . '<link rel="stylesheet" type="text/css" href="styles/preview.css">'
                                                            . '<div style="float: left; width: 40%"><div class="ncsu_book preview_pane">';
        // Display the preview area.
        $blocks = ncsubook_get_chapter_content($chapter->id);
        $blockarray = ncsubook_rearrange_content_blocks($blocks);

        foreach ($blockarray as $block) {
            if ($block->type == 1) {  // If it's a content block.
                $html                                       .= '<div class="' . $block->csselementtype . '">' . $block->title . '</div>';
            } else {  // If it's anything besides a content block.
                if (strlen($block->title) > 20) {
                    $html                                   .= '<div class="' . $block->csselementtype . '"><div class="previewtitle">'
                                                            . substr($block->title, 0, 20) . '...</div></div>';
                } else {
                    $html                                   .= '<div class="' . $block->csselementtype . '"><div class="previewtitle">' . $block->title . '</div></div>';
                }
            }
        }
        $html                                               .= '</div>'
                                                            . '<div style="text-align: center"><em>This is how your chapter page will look.</em></div></div>';

        $mform->addElement('header', 'general', 'Add/Edit Block Content');
        $mform->addElement('hidden', 'manage_block_form', 'true');
        $mform->setType('manage_block_form', PARAM_BOOL);

        $mform->addElement('html', $html);

        $mform->addElement('hidden', 'chapterid');
        $mform->setType('chapterid', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'pagenum');
        $mform->setType('pagenum', PARAM_INT);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_INT);

         // Set the defaults.
        $this->set_data($chapter);
    }
}

class ncsubook_block_edit_form extends moodleform {

    public function definition() {

        $chapter                                            = $this->_customdata['chapter'];
        $blockdata                                          = $this->_customdata['blockdata'];
        $options                                            = $this->_customdata['options'];
        $blockid                                            = $this->_customdata['blockid'];
        $context                                            = $this->_customdata['context'];
        $mform                                              = $this->_form;
        $blocktext                                          = '';
        $blocktitle                                         = '';
        $blocktype                                          = '';

        if (strlen($blockid)) {
            $loadedblock                                    = $this->get_block_content($blockid);

            foreach ($loadedblock as $key => $value) {
                $blocktext                                  = $value->content;
                $blocktitle                                 = $value->title;
                $blocktype                                  = $value->type;
            }
        }

        $mform->addElement('header', 'general', 'Add/Edit Block Content');

        $mform->addElement('text', 'block_title', get_string('blocktitle', 'mod_ncsubook'), ['size' => '30']);
        $mform->setType('block_title', PARAM_RAW);
        $mform->addRule('block_title', null, 'required', null, 'client');
        $mform->setDefault('block_title', $blocktitle);
        // if ( $blocktype == 1 ) {
            $mform->addHelpButton('block_title', 'blocktitle', 'mod_ncsubook');
        // }

        $mform->addElement('editor', 'content_editor', '', null, $options);
        $chapter->content = $blocktext;
        $chapter->contentformat = FORMAT_HTML;
        $chapter = file_prepare_standard_editor($chapter, 'content', $options, $context, 'mod_ncsubook', 'chapter', $chapter->id);

        $mform->addElement('hidden', 'blockid', $blockid);
        $mform->setType('blockid', PARAM_INT);

        $mform->addElement('hidden', 'chapterid');
        $mform->setType('chapterid', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'pagenum');
        $mform->setType('pagenum', PARAM_INT);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_INT);

        $this->add_action_buttons(true);

        // set the defaults
        $this->set_data($chapter);

    }

    public function get_block_content($blockid) {
        global $DB;
        $result                                             = $DB->get_records_sql('select title, content, type from {ncsubook_blocks} where id = ?', [$blockid]);
        return $result;
    }
}

class ncsubook_add_block_form extends moodleform {

    public function definition() {
        global $DB;

        $chapter                                            = $this->_customdata['chapter'];
        $mform                                              = $this->_form;

        $mform->addElement('header', 'general', 'Add a Block');

        $blocktypes                                         = $DB->get_records_select('ncsubook_blocktype', 'sortorder > 0', [], 'sortorder ASC', 'id,name');

        foreach ($blocktypes as $blocktype) {
            $addblockoptions[$blocktype->id]                = $blocktype->name;
        }
        $mform->addElement('select', 'addnewblock', get_string('addnewblock', 'mod_ncsubook'), $addblockoptions);
        $mform->addElement('submit', 'addblocktype', 'Add Block' );
        $mform->addHelpButton('addnewblock', 'addnewblock', 'mod_ncsubook');

        $mform->addElement('hidden', 'chapterid');
        $mform->setType('chapterid', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'pagenum');
        $mform->setType('pagenum', PARAM_INT);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_INT);

         // set the defaults
        $this->set_data($chapter);
    }

}

class ncsubook_add_chapter_form extends moodleform {

    public function definition() {
        global $DB;

        $chaptertypes                                       = $this->_customdata['chaptertypes'];
        $chapter                                            = $this->_customdata['chapter'];
        $mform                                              = $this->_form;

        // Disabled subchapter option when editing first node.
        $disabledmsg                                        = '';
        if ($chapter->pagenum == 1) {
            $disabledmsg = get_string('subchapternotice', 'ncsubook');
        }

        $mform->addElement('header', 'general', 'Add a Page');

        foreach ($chaptertypes as $chaptertype) {
            $addchapteroptions[$chaptertype->id]            = $chaptertype->name;
        }

        $mform->addElement('select', 'type', get_string('editingchaptertype', 'mod_ncsubook'), $addchapteroptions, ['onchange' => 'ncsubook_getSelectedValue()']);
        $mform->addHelpButton('type', 'editingchaptertype', 'mod_ncsubook');

        $mform->addElement('text', 'title', get_string('chaptertitle', 'mod_ncsubook'), ['size' => '30']);
        $mform->setType('title', PARAM_RAW);
        $mform->addRule('title', null, 'required', null, 'client');
        $mform->setDefault('title', 'Introduction');

        $mform->addElement('text', 'additionaltitle', get_string('alternatebooktitle', 'mod_ncsubook'), ['size' => '30']);
        $mform->setType('additionaltitle', PARAM_RAW);
        $mform->addHelpButton('additionaltitle', 'alternatebooktitle', 'mod_ncsubook');

        $mform->addElement('advcheckbox', 'subchapter', get_string('subchapter', 'mod_ncsubook'), $disabledmsg);
        $mform->addElement('advcheckbox', 'showparenttitle', get_string('showparenttitle', 'mod_ncsubook'));

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'pagenum');
        $mform->setType('pagenum', PARAM_INT);

        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);

        $mform->addElement('hidden', 'ncsubookid');
        $mform->setType('ncsubookid', PARAM_INT);

        // elements in a row need a group
        // $buttonarray[];
        // $buttonarray[] = &$mform->createElement('submit', 'submitbutton2', $submit2label);
        $buttonarray[]                                      = &$mform->createElement('submit', 'submitbutton', 'Next');
        $buttonarray[]                                      = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', get_string('actionbuttons', 'form'), [' '], false);
        $mform->setType('buttonar', PARAM_RAW);
        $mform->closeHeaderBefore('buttonar');
        // End Save and Display buttons

         // set the defaults
        $this->set_data($chapter);
    }

    public function definition_after_data() {
        $mform                                              = $this->_form;
        $pagenum                                            = $mform->getElement('pagenum');
        if ($pagenum->getValue() == 1) {
            $mform->hardFreeze('subchapter');
        }
    }
}
