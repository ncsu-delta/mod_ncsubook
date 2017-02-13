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
 * Returns list of available numbering types
 * @return array
 */
function ncsubook_get_numbering_types() {
    global $CFG; // Required for the include.

    require_once(dirname(__FILE__) . '/locallib.php');

    return                                  [NCSU_BOOK_NUM_NONE => get_string('numbering0', 'mod_ncsubook'),
                                             NCSU_BOOK_NUM_NUMBERS => get_string('numbering1', 'mod_ncsubook'),
                                             NCSU_BOOK_NUM_BULLETS => get_string('numbering2', 'mod_ncsubook'),
                                             NCSU_BOOK_NUM_INDENTED => get_string('numbering3', 'mod_ncsubook')
                                            ];
}

/**
 * Returns all other caps used in module
 * @return array
 */
function ncsubook_get_extra_capabilities() {
    // Used for group-members-only.
    return array('moodle/site:accessallgroups');
}

/**
 * Add ncsubook instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return int new ncsubook instance id
 */
function ncsubook_add_instance($data, $mform) {
    global $DB;

    $data->timecreated                      = time();
    $data->timemodified                     = $data->timecreated;

    return $DB->insert_record('ncsubook', $data);
}

/**
 * Update ncsubook instance.
 *
 * @param stdClass $data
 * @param stdClass $mform
 * @return bool true
 */
function ncsubook_update_instance($data, $mform) {
    global $DB;

    $data->timemodified                     = time();
    $data->id                               = $data->instance;

    $DB->update_record('ncsubook', $data);

    $ncsubook                               = $DB->get_record('ncsubook', ['id' => $data->id]);

    $DB->set_field('ncsubook', 'revision', $ncsubook->revision + 1, ['id' => $ncsubook->id]);

    return true;
}

/**
 * Delete ncsubook instance by activity id
 *
 * @param int $id
 * @return bool success
 */
function ncsubook_delete_instance($id) {
    global $DB;

    if (!$ncsubook = $DB->get_record('ncsubook', ['id' => $id])) {
        return false;
    }

    // Gary Harris - 4/8/2013.
    // Added the following delete statement to delete the blocks when deleting a whole NC State Book.
    // End GDH.
    $sql = 'DELETE
                FROM {ncsubook_blocks}
                WHERE  chapterid IN (SELECT id
                                        FROM  {ncsubook_chapters}
                                        WHERE ncsubookid = ?)';
    $DB->execute($sql, [$ncsubook->id]);

    $DB->delete_records('ncsubook_chapters', ['ncsubookid' => $ncsubook->id]);
    $DB->delete_records('ncsubook', ['id' => $ncsubook->id]);

    return true;
}

/**
 * Return use outline
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param object $ncsubook
 * @return object|null
 */
function ncsubook_user_outline($course, $user, $mod, $ncsubook) {
    global $DB;

    if ($logs = $DB->get_records('log', ['userid' => $user->id, 'module' => 'ncsubook', 'action' => 'view', 'info' => $ncsubook->id], 'time ASC')) {

        $numviews                           = count($logs);
        $lastlog                            = array_pop($logs);
        $result                             = new stdClass();
        $result->info                       = get_string('numviews', '', $numviews);
        $result->time                       = $lastlog->time;

        return $result;
    }
    return null;
}

/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param stdClass $mod
 * @param stdClass $ncsubook
 * @return bool
 */
function ncsubook_user_complete($course, $user, $mod, $ncsubook) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in ncsubook activities and print it out.
 *
 * @param stdClass $course
 * @param bool $viewfullnames
 * @param int $timestart
 * @return bool true if there was output, or false is there was none
 */
function ncsubook_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  // True if anything was printed, otherwise false.
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function ncsubook_reset_userdata($data) {
    return array();
}

/**
 * No cron in ncsubook.
 *
 * @return bool
 */
function ncsubook_cron () {
    return true;
}

/**
 * No grading in ncsubook.
 *
 * @param int $ncsubookid
 * @return null
 */
function ncsubook_grades($ncsubookid) {
    return null;
}

/**
 * This function returns if a scale is being used by one ncsubook
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See ncsubook, glossary or journal modules
 * as reference.
 *
 * @param int $ncsubookid
 * @param int $scaleid
 * @return boolean True if the scale is used by any journal
 */
function ncsubook_scale_used($ncsubookid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of ncsubook
 *
 * This is used to find out if scale used anywhere
 *
 * @param int $scaleid
 * @return bool true if the scale is used by any ncsubook
 */
function ncsubook_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Return read actions.
 * @return array
 */
function ncsubook_get_view_actions() {
    global $CFG;

    $return                                 = ['view', 'view all'];
    $plugins                                = get_plugin_list('ncsubooktool');

    foreach ($plugins as $plugin => $dir) {
        if (file_exists($dir . '/lib.php')) {
            require_once($dir . '/lib.php');
        }
        $function                           = 'ncsubooktool_' . $plugin . '_get_view_actions';
        if (function_exists($function)) {
            if ($actions = $function()) {
                $return                     = array_merge($return, $actions);
            }
        }
    }

    return $return;
}

/**
 * Return write actions.
 * @return array
 */
function ncsubook_get_post_actions() {
    global $CFG;

    $return                                 = ['update'];

    $plugins                                = get_plugin_list('ncsubooktool');
    foreach ($plugins as $plugin => $dir) {
        if (file_exists($dir . '/lib.php')) {
            require_once($dir . '/lib.php');
        }
        $function                           = 'ncsubooktool_' . $plugin . '_get_post_actions';
        if (function_exists($function)) {
            if ($actions = $function()) {
                $return                     = array_merge($return, $actions);
            }
        }
    }

    return $return;
}

/**
 * Supported features
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function ncsubook_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE :
            return MOD_ARCHETYPE_RESOURCE;
        break;
        case FEATURE_GROUPS :
            return false;
        break;
        case FEATURE_GROUPINGS :
            return false;
        break;
        case FEATURE_GROUPMEMBERSONLY :
            return true;
        break;
        case FEATURE_MOD_INTRO :
            return true;
        break;
        case FEATURE_COMPLETION_TRACKS_VIEWS :
            return true;
        break;
        case FEATURE_GRADE_HAS_GRADE :
            return false;
        break;
        case FEATURE_GRADE_OUTCOMES :
            return false;
        break;
        case FEATURE_BACKUP_MOODLE2 :
            return true;
        break;
        case FEATURE_SHOW_DESCRIPTION :
            return true;
        break;
        default :
            return null;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settingsnav The settings navigation object
 * @param navigation_node $ncsubooknode The node to add module settings to
 * @return void
 */
function ncsubook_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $ncsubooknode) {
    global $USER, $PAGE;

    $plugins = get_plugin_list('ncsubooktool');
    foreach ($plugins as $plugin => $dir) {
        if (file_exists("$dir/lib.php")) {
            require_once("$dir/lib.php");
        }
        $function = 'ncsubooktool_'.$plugin.'_extend_settings_navigation';
        if (function_exists($function)) {
            $function($settingsnav, $ncsubooknode);
        }
    }
}


/**
 * Lists all browsable file areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @return array
 */
function ncsubook_get_file_areas($course, $cm, $context) {
    $areas                                  = array();
    $areas['chapter']                       = get_string('chapters', 'mod_ncsubook');
    return $areas;
}

/**
 * File browsing support for ncsubook module chapter area.
 * @param object $browser
 * @param object $areas
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return object file_info instance or null if not found
 */
function ncsubook_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB;

    // Note: 'intro' area is handled in file_browser automatically.

    if (!has_capability('mod/ncsubook:read', $context)) {
        return null;
    }

    if ($filearea !== 'chapter') {
        return null;
    }

    require_once(dirname(__FILE__).'/locallib.php');

    if (is_null($itemid)) {
        return new ncsubook_file_info($browser, $course, $cm, $context, $areas, $filearea);
    }

    $fs                                     = get_file_storage();
    $filepath                               = is_null($filepath) ? '/' : $filepath;
    $filename                               = is_null($filename) ? '.' : $filename;

    if (!$storedfile = $fs->get_file($context->id, 'mod_ncsubook', $filearea, $itemid, $filepath, $filename)) {
        return null;
    }

    // Modifications may be tricky - may cause caching problems.
    $canwrite                               = has_capability('mod/ncsubook:edit', $context);

    $chaptername                            = $DB->get_field('ncsubook_chapters', 'title', ['ncsubookid' => $cm->instance, 'id' => $itemid]);
    $chaptername                            = format_string($chaptername, true, ['context' => $context]);
    $urlbase                                = $CFG->wwwroot.'/pluginfile.php';

    return new file_info_stored($browser, $context, $storedfile, $urlbase, $chaptername, true, true, $canwrite, false);
}

/**
 * Serves the ncsubook attachments. Implements needed access control ;-)
 *
 * @param stdClass $course course object
 * @param cm_info $cm course module object
 * @param context $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function ncsubook_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    if ($filearea !== 'chapter') {
        return false;
    }

    if (!has_capability('mod/ncsubook:read', $context)) {
        return false;
    }

    $chid                                   = (int) array_shift($args);

    if (!$ncsubook = $DB->get_record('ncsubook', ['id' => $cm->instance])) {
        return false;
    }

    if (!$chapter = $DB->get_record('ncsubook_chapters', ['id' => $chid, 'ncsubookid' => $ncsubook->id])) {
        return false;
    }

    if ($chapter->hidden and !has_capability('mod/ncsubook:viewhiddenchapters', $context)) {
        return false;
    }

    $fs                                     = get_file_storage();
    $relativepath                           = implode('/', $args);
    $fullpath                               = "/$context->id/mod_ncsubook/chapter/$chid/$relativepath";

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        // Dont give up yet, check the legacy files.
        $coursecontext                     = get_context_instance(CONTEXT_COURSE, $course->id);
        $pathhash                           = $fs->get_pathname_hash($coursecontext->id, "course", "legacy", "0", "/".$relativepath, "");

        if (!$file = $fs->get_file_by_hash($pathhash) or $file->is_directory()) {
            return false;
        }
    }

    // Finally send the file.
    send_stored_file($file, 360, 0, $forcedownload, $options);
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function ncsubook_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $modulepagetype                         = ['mod-ncsubook-*' => get_string('page-mod-ncsubook-x', 'mod_ncsubook')];
    return $modulepagetype;
}
