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
 * Book print lib
 *
 * @package    ncsubooktool_print
 * @copyright  2011 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @modified   for the NC State Book plugin.
 * @copyright 2014 Gary Harris, Amanda Robertson, Cathi Phillips Dunnagan, Jeff Webster, David Lanier
 */

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->dirroot . '/mod/ncsubook/locallib.php');

/**
 * Generate toc structure and titles
 *
 * @param array $chapters
 * @param stdClass $ncsubook
 * @param stdClass $cm
 * @return array
 */
function ncsubooktool_print_get_toc($chapters, $ncsubook, $cm) {
    $first      = true;
    $titles     = [];
    $context    = context_module::instance($cm->id);
    $toc        = ''; // Representation of toc (HTML).

    switch ($ncsubook->numbering) {
        case NCSU_BOOK_NUM_NONE:
            $toc .= html_writer::start_tag('div', ['class' => 'ncsubook_toc_none']);
            break;
        case NCSU_BOOK_NUM_NUMBERS:
            $toc .= html_writer::start_tag('div', ['class' => 'ncsubook_toc_numbered']);
            break;
        case NCSU_BOOK_NUM_BULLETS:
            $toc .= html_writer::start_tag('div', ['class' => 'ncsubook_toc_bullets']);
            break;
        case NCSU_BOOK_NUM_INDENTED:
            $toc .= html_writer::start_tag('div', ['class' => 'ncsubook_toc_indented']);
            break;
    }

    $toc .= html_writer::tag('a', '', ['name' => 'toc']); // Representation of toc (HTML).
    $toc .= html_writer::tag('h2', get_string('toc', 'mod_ncsubook'), ['class' => 'ncsubook_chapter_title']);
    $toc .= html_writer::start_tag('ul');

    foreach ($chapters as $ch) {
        if (!$ch->hidden) {
            $title = ncsubook_get_chapter_title($ch->id, $chapters, $ncsubook, $context);
            if (!$ch->subchapter) {
                if ($first) {
                    $toc .= html_writer::start_tag('li');
                } else {
                    $toc .= html_writer::end_tag('ul');
                    $toc .= html_writer::end_tag('li');
                    $toc .= html_writer::start_tag('li');
                }
            } else {
                if ($first) {
                    $toc .= html_writer::start_tag('li');
                    $toc .= html_writer::start_tag('ul');
                    $toc .= html_writer::start_tag('li');
                } else {
                    $toc .= html_writer::start_tag('li');
                }
            }
            $titles[$ch->id] = $title;
            $toc .= html_writer::link(new moodle_url('#ch'.$ch->id), $title, ['title' => s($title)]);
            if (!$ch->subchapter) {
                $toc .= html_writer::start_tag('ul');
            } else {
                $toc .= html_writer::end_tag('li');
            }
            $first = false;
        }
    }

    $toc .= html_writer::end_tag('ul');
    $toc .= html_writer::end_tag('li');
    $toc .= html_writer::end_tag('ul');
    $toc .= html_writer::end_tag('div');
    $toc = str_replace('<ul></ul>', '', $toc); // Cleanup of invalid structures.

    return array($toc, $titles);
}
