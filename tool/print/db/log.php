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
 * Print ncsubooktool log events definition
 *
 * @package    ncsubooktool_print
 * @copyright  2012 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @modified   for the NC State Book plugin.
 * @copyright 2014 Gary Harris, Amanda Robertson, Cathi Phillips Dunnagan, Jeff Webster, David Lanier
 */

defined('MOODLE_INTERNAL') || die();

$logs = [['module' => 'ncsubook', 'action' => 'print', 'mtable' => 'ncsubook', 'field' => 'name']];
