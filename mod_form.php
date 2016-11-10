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
 * Instance add/edit form
 *
 * @package    mod_ncsubook
 * @copyright  2004-2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/locallib.php');
require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_ncsubook_mod_form extends moodleform_mod {

    function definition() {
        global $CFG;

        $data   = $this->_customdata['data'];

        $mform = $this->_form;

        $config = get_config('ncsubook');

        // Gary Harris - 4/4/2013
        // The following line simply adds the "General" section heading to the form.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Gary Harris - 4/4/2013
        // The following section adds the "Name" field to the "General" section of the form.
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        // End Add Name field (GH)

        // Gary Harris - 4/4/2013
        // The following line of code adds the "Descriptiion" field with the WYSIWYG editor to
        // the "General" section of the form.
        $this->standard_intro_elements(get_string('moduleintro'));

        // Gary Harris - 4/4/2013
        // The following section adds the "Chapter formatting" drop-down list to the "General"
        // section of the form. It is referred to as "numbering", which is confusing. It should
        // be changed to "formatting", "chapterformatting", or something similar.
        $alloptions = ncsubook_get_numbering_types();
        $allowed = explode(',', $config->numberingoptions);
        $options = array();
        foreach ($allowed as $type) {
            if (isset($alloptions[$type])) {
                $options[$type] = $alloptions[$type];
            }
        }
        if ($this->current->instance) {
            if (!isset($options[$this->current->numbering])) {
                if (isset($alloptions[$this->current->numbering])) {
                    $options[$this->current->numbering] = $alloptions[$this->current->numbering];
                }
            }
        }
        $mform->addElement('select', 'numbering', get_string('numbering', 'ncsubook'), $options);
        $mform->addHelpButton('numbering', 'numbering', 'mod_ncsubook');
        $mform->setDefault('numbering', $config->numbering);
        // End Add Chapter Formatiing field

        $this->standard_coursemodule_elements();

        // var_dump($this->current->add);

       // If we are adding a new book, we want the submit buttons to say "Create Book" and "Cancel".
       // If we are editing a book, we want them to say the normal "Save and Return to Course", "Save and Display",
       // and "Cancel".
       if (isset($this->current->add)) {
            // $buttonarray[] = &$mform->createElement('submit', 'submit', 'Save and Continue');
            $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Create Book');
            $buttonarray[] = &$mform->createElement('cancel');
            $mform->addGroup($buttonarray, 'buttonar', get_string('actionbuttons', 'form'), array(' '), false);
            $mform->setType('buttonar', PARAM_RAW);
            $mform->closeHeaderBefore('buttonar');
        } else {
            $this->add_action_buttons();
        }

    }
}
