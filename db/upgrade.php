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
 * Book module upgrade code
 *
 * @package    mod_ncsubook
 * @copyright  2009-2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Book module upgrade task
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool always true
 */
function xmldb_ncsubook_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Moodle v2.2.0 release upgrade line
    // Put any upgrade step following this

    // Moodle v2.3.0 release upgrade line
    // Put any upgrade step following this

    // Note: The next steps (up to 2012061710 included, are a "replay" of old upgrade steps,
    // because some sites updated to Moodle 2.3 didn't have the latest contrib mod_ncsubook
    // installed, so some required changes were missing.
    //
    // All the steps are run conditionally so sites upgraded from latest contrib mod_ncsubook or
    // new (2.3 and upwards) sites won't get affected.
    //
    // See MDL-35297 and commit msg for more information.

    if ($oldversion < 2012061703) {
        // Rename field summary on table ncsubook to intro
        $table = new xmldb_table('ncsubook');
        $field = new xmldb_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');

        // Launch rename field summary
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'intro');
        }

        // ncsubook savepoint reached
        upgrade_mod_savepoint(true, 2012061703, 'ncsubook');
    }

    if ($oldversion < 2012061704) {
        // Define field introformat to be added to ncsubook
        $table = new xmldb_table('ncsubook');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'intro');

        // Launch add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            // Conditionally migrate to html format in intro
            // Si está activo el htmleditor!!!!!
            if ($CFG->texteditors !== 'textarea') {
                $rs = $DB->get_recordset('ncsubook', array('introformat'=>FORMAT_MOODLE), '', 'id,intro,introformat');
                foreach ($rs as $b) {
                    $b->intro       = text_to_html($b->intro, false, false, true);
                    $b->introformat = FORMAT_HTML;
                    $DB->update_record('ncsubook', $b);
                    upgrade_set_timeout();
                }
                unset($b);
                $rs->close();
            }
        }

        // ncsubook savepoint reached
        upgrade_mod_savepoint(true, 2012061704, 'ncsubook');
    }

    if ($oldversion < 2012061705) {
        // Define field introformat to be added to ncsubook
        $table = new xmldb_table('ncsubook_chapters');
        $field = new xmldb_field('contentformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'content');

        // Launch add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            $DB->set_field('ncsubook_chapters', 'contentformat', FORMAT_HTML, array());
        }

        // ncsubook savepoint reached
        upgrade_mod_savepoint(true, 2012061705, 'ncsubook');
    }

    if ($oldversion < 2012061706) {
        require_once("$CFG->dirroot/mod/ncsubook/db/upgradelib.php");

        $sqlfrom = "FROM {ncsubook} b
                    JOIN {modules} m ON m.name = 'ncsubook'
                    JOIN {course_modules} cm ON (cm.module = m.id AND cm.instance = b.id)";

        $count = $DB->count_records_sql("SELECT COUNT('x') $sqlfrom");

        if ($rs = $DB->get_recordset_sql("SELECT b.id, b.course, cm.id AS cmid $sqlfrom ORDER BY b.course, b.id")) {

            $pbar = new progress_bar('migratencsubookfiles', 500, true);

            $i = 0;
            foreach ($rs as $ncsubook) {
                $i++;
                upgrade_set_timeout(360); // set up timeout, may also abort execution
                $pbar->update($i, $count, "Migrating ncsubook files - $i/$count.");

                $context = context_module::instance($ncsubook->cmid);

                mod_ncsubook_migrate_moddata_dir_to_legacy($ncsubook, $context, '/');

                // remove dirs if empty
                @rmdir("$CFG->dataroot/$ncsubook->course/$CFG->moddata/ncsubook/$ncsubook->id/");
                @rmdir("$CFG->dataroot/$ncsubook->course/$CFG->moddata/ncsubook/");
                @rmdir("$CFG->dataroot/$ncsubook->course/$CFG->moddata/");
                @rmdir("$CFG->dataroot/$ncsubook->course/");
            }
            $rs->close();
        }

        // ncsubook savepoint reached
        upgrade_mod_savepoint(true, 2012061706, 'ncsubook');
    }

    if ($oldversion < 2012061707) {
        // Define field disableprinting to be dropped from ncsubook
        $table = new xmldb_table('ncsubook');
        $field = new xmldb_field('disableprinting');

        // Conditionally launch drop field disableprinting
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // ncsubook savepoint reached
        upgrade_mod_savepoint(true, 2012061707, 'ncsubook');
    }

    if ($oldversion < 2012061708) {
        unset_config('ncsubook_tocwidth');

        // ncsubook savepoint reached
        upgrade_mod_savepoint(true, 2012061708, 'ncsubook');
    }

    if ($oldversion < 2012061709) {
        require_once("$CFG->dirroot/mod/ncsubook/db/upgradelib.php");

        mod_ncsubook_migrate_all_areas();

        upgrade_mod_savepoint(true, 2012061709, 'ncsubook');
    }

    if ($oldversion < 2012061710) {

        // Define field revision to be added to ncsubook
        $table = new xmldb_table('ncsubook');
        $field = new xmldb_field('revision', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'numbering');

        // Conditionally launch add field revision
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // ncsubook savepoint reached
        upgrade_mod_savepoint(true, 2012061710, 'ncsubook');
    }
    // End of MDL-35297 "replayed" steps.

    if ($oldversion < 2012061711) {

        // Define field revision to be added to ncsubook
        $table = new xmldb_table('ncsubook_chapters');
        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'ncsubookid');

        // Conditionally launch add field revision
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // ncsubook savepoint reached
        upgrade_mod_savepoint(true, 2012061711, 'ncsubook');
    }

    if ($oldversion < 2013071101) {

        // Define field showparenttitle to be added to ncsubook
        $table = new xmldb_table('ncsubook_chapters');
        $field = new xmldb_field('showparenttitle', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'importsrc');

        // Conditionally launch add field showparenttitle
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field customtitles to be dropped from ncsubook
        $table = new xmldb_table('ncsubook');
        $field = new xmldb_field('customtitles');

        // Conditionally launch drop field customtitles
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // ncsubook savepoint reached
        upgrade_mod_savepoint(true, 2013071101, 'ncsubook');
    }

    return true;
}
