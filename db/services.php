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

$functions = [
    'mod_ncsubook_view_ncsubook'            => [
                                                'classname'     => 'mod_ncsubook_external',
                                                'methodname'    => 'view_ncsubook',
                                                'description'   => 'Simulate the view.php web interface ncsubook: trigger events, completion, etc...',
                                                'type'          => 'write',
                                                'capabilities'  => 'mod/ncsubook:read',
                                              ],
    'mod_ncsubook_get_ncsubooks_by_courses' => [
                                                'classname'     => 'mod_ncsubook_external',
                                                'methodname'    => 'get_ncsubooks_by_courses',
                                                'description'   => 'Returns a list of ncsubook instances in a provided set of courses.'
                                                                .  'If no courses are provided then all the ncsubook instances the user has access to will be returned.',
                                                'type'          => 'read',
                                                'capabilities'  => '',
                                               ],
];
