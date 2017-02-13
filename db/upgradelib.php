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

/**
 * Migrate ncsubook files stored in moddata folders.
 *
 * Please note it was a big mistake to store the files there in the first place!
 *
 * @param stdClass $ncsubook
 * @param stdClass $context
 * @param string $path
 * @return void
 */
function mod_ncsubook_migrate_moddata_dir_to_legacy($ncsubook, $context, $path) {
    global $OUTPUT, $CFG;

    $base       = $CFG->dataroot. DIRECTORY_SEPARATOR .$ncsubook->course . DIRECTORY_SEPARATOR
                . $CFG->moddata . DIRECTORY_SEPARATOR . 'ncsubook' . DIRECTORY_SEPARATOR . $ncsubook->id;
    $fulldir    = $base.$path;

    if (!is_dir($fulldir)) {
        // does not exist
        return;
    }
    $fs         = get_file_storage();
    $items      = new DirectoryIterator($fulldir);

    foreach ($items as $item) {
        if ($item->isDot()) {
            unset($item); // release file handle
            continue;
        }

        if ($item->isLink()) {
            // do not follow symlinks - they were never supported in moddata, sorry
            unset($item); // release file handle
            continue;
        }

        if ($item->isFile()) {
            if (!$item->isReadable()) {
                echo $OUTPUT->notification(' File not readable, skipping: ' . $fulldir . $item->getFilename());
                unset($item); // release file handle
                continue;
            }

            $filepath = clean_param(DIRECTORY_SEPARATOR . $CFG->moddata . DIRECTORY_SEPARATOR . 'ncsubook' . DIRECTORY_SEPARATOR . $ncsubook->id . $path, PARAM_PATH);
            $filename = clean_param($item->getFilename(), PARAM_FILE);

            if ($filename === '') {
                // unsupported chars, sorry
                unset($item); // release file handle
                continue;
            }

            if (textlib::strlen($filepath) > 255) {
                echo $OUTPUT->notification(' File path longer than 255 chars, skipping: ' . $fulldir . $item->getFilename());
                unset($item); // release file handle
                continue;
            }

            if (!$fs->file_exists($context->id, 'course', 'legacy', '0', $filepath, $filename)) {
                $filerecord  = ['contextid'     => $context->id,
                                'component'     => 'course',
                                'filearea'      => 'legacy',
                                'itemid'        => 0,
                                'filepath'      => $filepath,
                                'filename'      => $filename,
                                'timecreated'   => $item->getCTime(),
                                'timemodified'  => $item->getMTime()
                               ];
                $fs->create_file_from_pathname($filerecord, $fulldir.$item->getFilename());
            }
            $oldpathname = $fulldir.$item->getFilename();
            unset($item); // release file handle
            @unlink($oldpathname);

        } else {
            // migrate recursively all subdirectories
            $oldpathname = $base . $item->getFilename() . DIRECTORY_SEPARATOR;
            $subpath     = $path . $item->getFilename() . DIRECTORY_SEPARATOR;
            unset($item);  // release file handle
            mod_ncsubook_migrate_moddata_dir_to_legacy($ncsubook, $context, $subpath);
            @rmdir($oldpathname); // deletes dir if empty
        }
    }
    unset($items); // release file handles
}

/**
 * Migrate legacy files in intro and chapters
 * @return void
 */
function mod_ncsubook_migrate_all_areas() {
    global $DB;

    $rsncsubooks = $DB->get_recordset('ncsubook');
    foreach ($rsncsubooks as $ncsubook) {
        upgrade_set_timeout(360); // set up timeout, may also abort execution
        $cm         = get_coursemodule_from_instance('ncsubook', $ncsubook->id);
        $context    = context_module::instance($cm->id);
        mod_ncsubook_migrate_area($ncsubook, 'intro', 'ncsubook', $ncsubook->course, $context, 'mod_ncsubook', 'intro', 0);
        $rschapters = $DB->get_recordset('ncsubook_chapters', ['ncsubookid' => $ncsubook->id]);
        foreach ($rschapters as $chapter) {
            mod_ncsubook_migrate_area($chapter, 'content', 'ncsubook_chapters', $ncsubook->course, $context, 'mod_ncsubook', 'chapter', $chapter->id);
        }
        $rschapters->close();
    }
    $rsncsubooks->close();
}

/**
 * Migrate one area, this should be probably part of moodle core...
 *
 * @param stdClass $record object to migrate files (ncsubook, chapter)
 * @param string $field field in the record we are going to migrate
 * @param string $table DB table containing the information to migrate
 * @param int $courseid id of the course the ncsubook module belongs to
 * @param context_module $context context of the ncsubook module
 * @param string $component component to be used for the migrated files
 * @param string $filearea filearea to be used for the migrated files
 * @param int $itemid id to be used for the migrated files
 * @return void
 */
function mod_ncsubook_migrate_area($record, $field, $table, $courseid, $context, $component, $filearea, $itemid) {
    global $CFG, $DB;

    $fs = get_file_storage();

    foreach (array(get_site()->id, $courseid) as $cid) {
        $matches = null;
        $ooldcontext = context_course::instance($cid);
        if (preg_match_all("|$CFG->wwwroot/file.php(\?file=)?/$cid(/[^\s'\"&\?#]+)|", $record->$field, $matches)) {
            $filerecord     = ['contextid'  => $context->id,
                               'component'  => $component,
                               'filearea'   => $filearea,
                               'itemid'     => $itemid
                              ];
            foreach ($matches[2] as $i => $filepath) {
                if (!$file = $fs->get_file_by_hash(sha1('/' . $ooldcontext->id . '/course/legacy/0' . $filepath))) {
                    continue;
                }
                try {
                    if (!$newfile = $fs->get_file_by_hash(sha1('/' . $context->id . '/' . $component . '/' . $filearea . '/' .$itemid . $filepath))) {
                        $fs->create_file_from_storedfile($filerecord, $file);
                    }
                    $record->$field = str_replace($matches[0][$i], '@@PLUGINFILE@@' . $filepath, $record->$field);
                } catch (Exception $ex) {
                    echo '<pre>';
                    var_dump($ex);
                    echo '>/pre>';
                    return;
                }
                $DB->set_field($table, $field, $record->$field, ['id' => $record->id]);
            }
        }
    }
}
