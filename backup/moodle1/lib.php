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

defined('MOODLE_INTERNAL') || die();

/**
 * Book conversion handler
 */
class moodle1_mod_ncsubook_handler extends moodle1_mod_handler {

    /** @var moodle1_file_manager */
    protected $fileman = null;

    /** @var int cmid */
    protected $moduleid = null;

    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@link convert_path} instances. For each path returned,
     * at least one of on_xxx_start(), process_xxx() and on_xxx_end() methods must be
     * defined. The method process_xxx() is not executed if the associated path element is
     * empty (i.e. it contains none elements or sub-paths only).
     *
     * Note that the path /MOODLE_BACKUP/COURSE/MODULES/MOD/NCSU_BOOK does not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@link convert_path} instances
     */
    public function get_paths() {
        return array(
            new convert_path('ncsubook', '/MOODLE_BACKUP/COURSE/MODULES/MOD/NCSU_BOOK',
                    array(
                        'renamefields' => array(
                            'summary' => 'intro',
                        ),
                        'newfields' => array(
                            'introformat' => FORMAT_MOODLE,
                        ),
                        'dropfields' => array(
                            'disableprinting'
                        ),
                    )
                ),
            new convert_path('ncsubook_chapters', '/MOODLE_BACKUP/COURSE/MODULES/MOD/NCSU_BOOK/CHAPTERS/CHAPTER',
                    array(
                        'newfields' => array(
                            'contentformat' => FORMAT_HTML,
                        ),
                    )
                ),
        );
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/NCSU_BOOK
     * data available
     * @param array $data
     */
    public function process_ncsubook($data) {
        global $CFG;

        // Get the course module id and context id.
        $instanceid     = $data['id'];
        $cminfo         = $this->get_cminfo($instanceid);
        $this->moduleid = $cminfo['id'];
        $contextid      = $this->converter->get_contextid(CONTEXT_MODULE, $this->moduleid);

        // Replay the upgrade step 2009042006.
        if ($CFG->texteditors !== 'textarea') {
            $data['intro']       = text_to_html($data['intro'], false, false, true);
            $data['introformat'] = FORMAT_HTML;
        }

        // get a fresh new file manager for this instance
        $this->fileman = $this->converter->get_file_manager($contextid, 'mod_ncsubook');

        // convert course files embedded into the intro
        $this->fileman->filearea = 'intro';
        $this->fileman->itemid   = 0;
        $data['intro'] = moodle1_converter::migrate_referenced_files($data['intro'], $this->fileman);

        // start writing ncsubook.xml
        $this->open_xml_writer("activities/ncsubook_{$this->moduleid}/ncsubook.xml");
        $this->xmlwriter->begin_tag('activity', array('id' => $instanceid, 'moduleid' => $this->moduleid,
            'modulename' => 'ncsubook', 'contextid' => $contextid));
        $this->xmlwriter->begin_tag('ncsubook', array('id' => $instanceid));

        foreach ($data as $field => $value) {
            if ($field <> 'id') {
                $this->xmlwriter->full_tag($field, $value);
            }
        }
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/MOD/NCSU_BOOK/CHAPTERS/CHAPTER
     * data available
     * @param array $data
     */
    public function process_ncsubook_chapters($data) {
        $this->write_xml('chapter', $data, array('/chapter/id'));

        // convert chapter files
        $this->fileman->filearea = 'chapter';
        $this->fileman->itemid   = $data['id'];
        $data['content'] = moodle1_converter::migrate_referenced_files($data['content'], $this->fileman);
    }

    /**
     * This is executed when the parser reaches the <CHAPTERS> opening element
     */
    public function on_ncsubook_chapters_start() {
        $this->xmlwriter->begin_tag('chapters');
    }

    /**
     * This is executed when the parser reaches the closing </CHAPTERS> element
     */
    public function on_ncsubook_chapters_end() {
        $this->xmlwriter->end_tag('chapters');
    }

    /**
     * This is executed when we reach the closing </MOD> tag of our 'ncsubook' path
     */
    public function on_ncsubook_end() {
        // finalize ncsubook.xml
        $this->xmlwriter->end_tag('ncsubook');
        $this->xmlwriter->end_tag('activity');
        $this->close_xml_writer();

        // write inforef.xml
        $this->open_xml_writer("activities/ncsubook_{$this->moduleid}/inforef.xml");
        $this->xmlwriter->begin_tag('inforef');
        $this->xmlwriter->begin_tag('fileref');
        foreach ($this->fileman->get_fileids() as $fileid) {
            $this->write_xml('file', array('id' => $fileid));
        }
        $this->xmlwriter->end_tag('fileref');
        $this->xmlwriter->end_tag('inforef');
        $this->close_xml_writer();
    }
}
