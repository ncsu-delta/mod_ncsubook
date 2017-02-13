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

require_once("$CFG->libdir/externallib.php");

/**
 * NC State Book external functions
 *
 * @package    mod_ncsubook
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class mod_ncsubook_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function view_ncsubook_parameters() {
        return new external_function_parameters(
            array(
                'ncsubookid' => new external_value(PARAM_INT, 'ncsubook instance id'),
                'chapterid' => new external_value(PARAM_INT, 'chapter id', VALUE_DEFAULT, 0)
            )
        );
    }

    /**
     * Simulate the ncsubook/view.php web interface page: trigger events, completion, etc...
     *
     * @param int $ncsubookid the ncsubook instance id
     * @param int $chapterid the ncsubook chapter id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function view_ncsubook($ncsubookid, $chapterid = 0) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/mod/ncsubook/lib.php");
        require_once($CFG->dirroot . "/mod/ncsubook/locallib.php");

        $params             = self::validate_parameters(self::view_ncsubook_parameters(), ['ncsubookid' => $ncsubookid, 'chapterid' => $chapterid]);
        $ncsubookid         = $params['ncsubookid'];
        $chapterid          = $params['chapterid'];
        $warnings           = [];
        // Request and permission validation.
        $ncsubook           = $DB->get_record('ncsubook', ['id' => $ncsubookid], '*', MUST_EXIST);
        list($course, $cm)  = get_course_and_cm_from_instance($ncsubook, 'ncsubook');
        $context            = context_module::instance($cm->id);
        $chapters           = ncsubook_preload_chapters($ncsubook);
        $firstchapterid     = 0;
        $lastchapterid      = 0;

        self::validate_context($context);
        require_capability('mod/ncsubook:read', $context);

        foreach ($chapters as $ch) {
            if ($ch->hidden) {
                continue;
            }
            if (!$firstchapterid) {
                $firstchapterid = $ch->id;
            }
            $lastchapterid = $ch->id;
        }

        if (!$chapterid) {
            // Trigger the module viewed events since we are displaying the ncsubook.
            ncsubook_view($ncsubook, null, false, $course, $cm, $context);
            $chapterid      = $firstchapterid;
        }

        // Check if ncsubook is empty (warning).
        if (!$chapterid) {
            $warnings       = [
                                'item' => 'ncsubook',
                                'itemid' => $ncsubook->id,
                                'warningcode' => '1',
                                'message' => get_string('nocontent', 'mod_ncsubook')
                              ];
        } else {
            $chapter        = $DB->get_record('ncsubook_chapters', ['id' => $chapterid, 'ncsubookid' => $ncsubook->id]);
            $viewhidden     = has_capability('mod/ncsubook:viewhiddenchapters', $context);

            if (!$chapter or ($chapter->hidden and !$viewhidden)) {
                throw new moodle_exception('errorchapter', 'mod_ncsubook');
            }

            // Trigger the chapter viewed event.
            $islastchapter  = ($chapter->id == $lastchapterid) ? true : false;
            ncsubook_view($ncsubook, $chapter, $islastchapter, $course, $cm, $context);
        }

        $result['status']   = true;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function view_ncsubook_returns() {
        return new external_single_structure(
                [
                 'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                 'warnings' => new external_warnings()
                ]
        );
    }

    /**
     * Describes the parameters for get_ncsubooks_by_courses.
     *
     * @return external_external_function_parameters
     * @since Moodle 3.0
     */
    public static function get_ncsubooks_by_courses_parameters() {
        return new external_function_parameters (
                [
                  'courseids' => new external_multiple_structure(new external_value(PARAM_INT, 'course id'), 'Array of course ids', VALUE_DEFAULT, []),
                ]
        );
    }

    /**
     * Returns a list of ncsubooks in a provided list of courses,
     * if no list is provided all ncsubooks that the user can view will be returned.
     *
     * @param array $courseids the course ids
     * @return array of ncsubooks details
     * @since Moodle 3.0
     */
    public static function get_ncsubooks_by_courses($courseids = []) {
        global $CFG;

        $returnedncsubooks      = [];
        $warnings               = [];
        $params                 = self::validate_parameters(self::get_ncsubooks_by_courses_parameters(), ['courseids' => $courseids]);

        if (empty($params['courseids'])) {
            $params['courseids'] = array_keys(enrol_get_my_courses());
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids']);

            // Get the ncsubooks in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $ncsubooks          = get_all_instances_in_courses("ncsubook", $courses);
            foreach ($ncsubooks as $ncsubook) {
                $context = context_module::instance($ncsubook->coursemodule);
                // Entry to return.
                $ncsubookdetails = array();
                // First, we return information that any user can see in the web interface.
                $ncsubookdetails['id'] = $ncsubook->id;
                $ncsubookdetails['coursemodule']      = $ncsubook->coursemodule;
                $ncsubookdetails['course']            = $ncsubook->course;
                $ncsubookdetails['name']              = external_format_string($ncsubook->name, $context->id);
                // Format intro.
                list($ncsubookdetails['intro'], $ncsubookdetails['introformat']) = external_format_text($ncsubook->intro,
                                                                                                        $ncsubook->introformat,
                                                                                                        $context->id,
                                                                                                        'mod_ncsubook',
                                                                                                        'intro',
                                                                                                        null
                                                                                                       );
                $ncsubookdetails['numbering']         = $ncsubook->numbering;
                $ncsubookdetails['navstyle']          = $ncsubook->navstyle;
                $ncsubookdetails['customtitles']      = $ncsubook->customtitles;

                if (has_capability('moodle/course:manageactivities', $context)) {
                    $ncsubookdetails['revision']      = $ncsubook->revision;
                    $ncsubookdetails['timecreated']   = $ncsubook->timecreated;
                    $ncsubookdetails['timemodified']  = $ncsubook->timemodified;
                    $ncsubookdetails['section']       = $ncsubook->section;
                    $ncsubookdetails['visible']       = $ncsubook->visible;
                    $ncsubookdetails['groupmode']     = $ncsubook->groupmode;
                    $ncsubookdetails['groupingid']    = $ncsubook->groupingid;
                }
                $returnedncsubooks[] = $ncsubookdetails;
            }
        }
        $result['ncsubooks']    = $returnedncsubooks;
        $result['warnings']     = $warnings;
        return $result;
    }

    /**
     * Describes the get_ncsubooks_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function get_ncsubooks_by_courses_returns() {
        return new external_single_structure(
            [
                'ncsubooks'     => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id'            => new external_value(PARAM_INT, 'NC State Book id'),
                            'coursemodule'  => new external_value(PARAM_INT, 'Course module id'),
                            'course'        => new external_value(PARAM_INT, 'Course id'),
                            'name'          => new external_value(PARAM_RAW, 'NC State Book name'),
                            'intro'         => new external_value(PARAM_RAW, 'The NC State Book intro'),
                            'introformat'   => new external_format_value('intro'),
                            'numbering'     => new external_value(PARAM_INT, 'NC State Book numbering configuration'),
                            'navstyle'      => new external_value(PARAM_INT, 'NC State Book navigation style configuration'),
                            'customtitles'  => new external_value(PARAM_INT, 'NC State Book custom titles type'),
                            'revision'      => new external_value(PARAM_INT, 'NC State Book revision', VALUE_OPTIONAL),
                            'timecreated'   => new external_value(PARAM_INT, 'Time of creation', VALUE_OPTIONAL),
                            'timemodified'  => new external_value(PARAM_INT, 'Time of last modification', VALUE_OPTIONAL),
                            'section'       => new external_value(PARAM_INT, 'Course section id', VALUE_OPTIONAL),
                            'visible'       => new external_value(PARAM_BOOL, 'Visible', VALUE_OPTIONAL),
                            'groupmode'     => new external_value(PARAM_INT, 'Group mode', VALUE_OPTIONAL),
                            'groupingid'    => new external_value(PARAM_INT, 'Group id', VALUE_OPTIONAL),
                        ], 'NC State Books'
                    )
                ),
                'warnings'      => new external_warnings(),
            ]
        );
    }

}
