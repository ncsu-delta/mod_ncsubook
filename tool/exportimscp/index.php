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
 * Book IMSCP export plugin
 *
 * @package    ncsubooktool_exportimscp
 * @copyright  2001-3001 Antonio Vicent          {@link http://ludens.es}
 * @copyright  2001-3001 Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @copyright  2011 Petr Skoda                   {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @modified   for the NC State Book plugin.
 * @copyright 2014 Gary Harris, Amanda Robertson, Cathi Phillips Dunnagan, Jeff Webster, David Lanier
 */

require(dirname(__FILE__) . '/../../../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->dirroot . '/mod/ncsubook/locallib.php');
require_once($CFG->dirroot . '/backup/lib.php');
require_once($CFG->libdir . '/filelib.php');

$id         = required_param('id', PARAM_INT);           // Course Module ID
$cm         = get_coursemodule_from_id('ncsubook', $id, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$ncsubook   = $DB->get_record('ncsubook', ['id' => $cm->instance], '*', MUST_EXIST);

$PAGE->set_url('/mod/ncsubook/tool/exportimscp/index.php', ['id' => $id]);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/ncsubook:read', $context);
require_capability('ncsubooktool/exportimscp:export', $context);

\ncsubooktool_exportimscp\event\book_exported::create_from_book($ncsubook, $context)->trigger();

$file = ncsubooktool_exportimscp_build_package($ncsubook, $context);

send_stored_file($file, 10, 0, true, ['filename' => clean_filename($ncsubook->name) . '.zip']);
