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

require_once($CFG->dirroot.'/mod/ncsubook/backup/moodle2/backup_ncsubook_stepslib.php');    // Because it exists (must)
require_once($CFG->dirroot.'/mod/ncsubook/backup/moodle2/backup_ncsubook_settingslib.php'); // Because it exists (optional)

class backup_ncsubook_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     *
     * @return void
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     *
     * @return void
     */
    protected function define_my_steps() {
        // ncsubook only has one structure step
        $this->add_step(new backup_ncsubook_activity_structure_step('ncsubook_structure', 'ncsubook.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     *
     * @param string $content
     * @return string encoded content
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of ncsubooks
        $search  = "/($base\/mod\/ncsubook\/index.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@NCSUBOOKINDEX*$2@$', $content);

        // Link to ncsubook view by moduleid
        $search  = "/($base\/mod\/ncsubook\/view.php\?id=)([0-9]+)(&|&amp;)chapterid=([0-9]+)/";
        $content = preg_replace($search, '$@NCSUBOOKVIEWBYIDCH*$2*$4@$', $content);

        $search  = "/($base\/mod\/ncsubook\/view.php\?id=)([0-9]+)/";
        $content = preg_replace($search, '$@NCSUBOOKVIEWBYID*$2@$', $content);

        // Link to ncsubook view by ncsubookid
        $search  = "/($base\/mod\/ncsubook\/view.php\?b=)([0-9]+)(&|&amp;)chapterid=([0-9]+)/";
        $content = preg_replace($search, '$@NCSUBOOKVIEWBYBCH*$2*$4@$', $content);

        $search  = "/($base\/mod\/ncsubook\/view.php\?b=)([0-9]+)/";
        $content = preg_replace($search, '$@NCSUBOOKVIEWBYB*$2@$', $content);

        return $content;
    }
}
