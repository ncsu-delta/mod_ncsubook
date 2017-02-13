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

function xmldb_ncsubook_install() {
    global $DB;

    // Insert default chapter types
    $record = new stdClass();

    $record->name               = 'Introduction';
    $record->cssclassname       = 'ncsubook_introduction_chapter';
    $record->sortorder          = '100';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_chaptertype', $record);

    $record->name               = 'Learning Objectives';
    $record->cssclassname       = 'ncsubook_learningobjectives_chapter';
    $record->sortorder          = '200';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_chaptertype', $record);

    $record->name               = 'Basic';
    $record->cssclassname       = 'ncsubook_basic_chapter';
    $record->sortorder          = '300';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_chaptertype', $record);

    $record->name               = 'Assignment';
    $record->cssclassname       = 'ncsubook_assignment_chapter';
    $record->sortorder          = '400';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_chaptertype', $record);

    $record->name               = 'Summary';
    $record->cssclassname       = 'ncsubook_summary_chapter';
    $record->sortorder          = '500';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_chaptertype', $record);

    // Insert default block types
    $record = new stdClass();

    $record->name               = 'Content';
    $record->cssclassname       = 'ncsubook_content_block';
    $record->csselementtype     = 'content';
    $record->headertext         = '';
    $record->sortorder          = '100';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Learning Objectives';
    $record->cssclassname       = 'ncsubook_learningobjectives_block';
    $record->csselementtype     = 'float';
    $record->headertext         = 'Learning Objectives';
    $record->sortorder          = '200';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Activity Wide';
    $record->cssclassname       = 'ncsubook_activitywide_block';
    $record->csselementtype     = 'non-float';
    $record->headertext         = 'Activity';
    $record->sortorder          = '300';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Activity Narrow';
    $record->cssclassname       = 'ncsubook_activitynarrow_block';
    $record->csselementtype     = 'float';
    $record->headertext         = 'Activity';
    $record->sortorder          = '400';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Call Out 1';
    $record->cssclassname       = 'ncsubook_callout1_block';
    $record->csselementtype     = 'float';
    $record->headertext         = 'Call Out 1';
    $record->sortorder          = '500';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Call Out 2';
    $record->cssclassname       = 'ncsubook_callout2_block';
    $record->csselementtype     = 'float';
    $record->headertext         = 'Call Out 2';
    $record->sortorder          = '600';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Assessment Wide';
    $record->cssclassname       = 'ncsubook_assessmentwide_block';
    $record->csselementtype     = 'non-float';
    $record->headertext         = 'Assessment';
    $record->sortorder          = '700';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Assessment Narrow';
    $record->cssclassname       = 'ncsubook_assessmentnarrow_block';
    $record->csselementtype     = 'float';
    $record->headertext         = 'Assessment';
    $record->sortorder          = '800';
    $record->timecreated        = time();
    $record->timemodified       = time();

    $DB->insert_record('ncsubook_blocktype', $record);

    // default the config to disabled
    // set_config('oauthenabled', 0, 'local_oauth');

}