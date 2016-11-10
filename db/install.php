<?php

// This file replaces:
//   * STATEMENTS section in db/install.xml
//   * lib.php/modulename_install() post installation hook
//   * partially defaults.php

function xmldb_ncsubook_install() {
    global $DB;

    // Insert default chapter types
    $record = new object();

    $record->name               = 'Introduction';
    $record->cssclassname       = 'ncsubook_introduction_chapter';
    $record->sortorder          = '100';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_chaptertype', $record);

    $record->name               = 'Learning Objectives';
    $record->cssclassname       = 'ncsubook_learningobjectives_chapter';
    $record->sortorder          = '200';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_chaptertype', $record);

    $record->name               = 'Basic';
    $record->cssclassname       = 'ncsubook_basic_chapter';
    $record->sortorder          = '300';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_chaptertype', $record);

    $record->name               = 'Assignment';
    $record->cssclassname       = 'ncsubook_assignment_chapter';
    $record->sortorder          = '400';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_chaptertype', $record);

    $record->name               = 'Summary';
    $record->cssclassname       = 'ncsubook_summary_chapter';
    $record->sortorder          = '500';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_chaptertype', $record);

    // Insert default block types
    $record = new object();

    $record->name               = 'Content';
    $record->cssclassname       = 'ncsubook_content_block';
    $record->csselementtype     = 'content';
    $record->headertext         = '';
    $record->sortorder          = '100';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Learning Objectives';
    $record->cssclassname       = 'ncsubook_learningobjectives_block';
    $record->csselementtype     = 'float';
    $record->headertext         = 'Learning Objectives';
    $record->sortorder          = '200';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Activity Wide';
    $record->cssclassname       = 'ncsubook_activitywide_block';
    $record->csselementtype     = 'non-float';
    $record->headertext         = 'Activity';
    $record->sortorder          = '300';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Activity Narrow';
    $record->cssclassname       = 'ncsubook_activitynarrow_block';
    $record->csselementtype     = 'float';
    $record->headertext         = 'Activity';
    $record->sortorder          = '400';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Call Out 1';
    $record->cssclassname       = 'ncsubook_callout1_block';
    $record->csselementtype     = 'float';
    $record->headertext         = 'Call Out 1';
    $record->sortorder          = '500';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Call Out 2';
    $record->cssclassname       = 'ncsubook_callout2_block';
    $record->csselementtype     = 'float';
    $record->headertext         = 'Call Out 2';
    $record->sortorder          = '600';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Assessment Wide';
    $record->cssclassname       = 'ncsubook_assessmentwide_block';
    $record->csselementtype     = 'non-float';
    $record->headertext         = 'Assessment';
    $record->sortorder          = '700';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_blocktype', $record);

    $record->name               = 'Assessment Narrow';
    $record->cssclassname       = 'ncsubook_assessmentnarrow_block';
    $record->csselementtype     = 'float';
    $record->headertext         = 'Assessment';
    $record->sortorder          = '800';
    $record->timecreated        = '1365262734';
    $record->timemodified       = '1365262734';

    $DB->insert_record('ncsubook_blocktype', $record);

    // default the config to disabled
    // set_config('oauthenabled', 0, 'local_oauth');

}