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
 * IMSCP export lib
 *
 * @package    ncsubooktool_exportimscp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @modified   for the NC State Book plugin.
 * @copyright 2014 Gary Harris, Amanda Robertson, Cathi Phillips Dunnagan, Jeff Webster, David Lanier
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $node The node to add module settings to
 */
function ncsubooktool_exportimscp_extend_settings_navigation(settings_navigation $settings, navigation_node $node) {
    global $PAGE;

    if (has_capability('ncsubooktool/exportimscp:export', $PAGE->cm->context)) {
        $url    = new moodle_url('/mod/ncsubook/tool/exportimscp/index.php', ['id' => $PAGE->cm->id]);
        $icon   = new pix_icon('generate', '', 'ncsubooktool_exportimscp', ['class' => 'icon']);

        // Gary Harris - 4/23/2013
        // Commented out the following line for the Generate IMS CP link in the navigation sidebar because
        // we're not allowing it for the NC State Book.
        // End GDH
        // $node->add(get_string('generateimscp', 'ncsubooktool_exportimscp'), $url, navigation_node::TYPE_SETTING, null, null, $icon);
    }
}
