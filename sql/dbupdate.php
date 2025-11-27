<#1>
<?php
global $DIC;
$db = $DIC->database();
if (!$db->tableExists('gtour_tours'))
{
    $fields = array(
        'tour_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'title' => array(
            'type' => 'text',
            'length' => 50,
            'fixed' => false,
            'notnull' => true
        ),
        'type' => array(
            'type' => 'text',
            'length' => 25,
            'fixed' => false,
            'notnull' => false
        ),
        'is_active' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false
        ),
        'script' => array(
            'type' => 'clob',
            'notnull' => false
        ),
        'icon_id' => array(
            'type' => 'text',
            'length' => 250,
            'fixed' => false
        ),
        'roles_ids' => array(
            'type' => 'text',
            'length' => 1024,
            'fixed' => false
        )
    );

    $db->createTable("gtour_tours", $fields);
    $db->addPrimaryKey("gtour_tours", array("tour_id"));
    $db->createSequence("gtour_tours");
}
?>
<#2>
<?php
global $DIC;
$db = $DIC->database();
if ($db->tableExists('gtour_tours') && !$db->tableColumnExists('gtour_tours', 'is_automatic_triggered'))
{
    $db->addTableColumn("gtour_tours", 'is_automatic_triggered', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => false
    ));
}
?>
<#3>
<?php
global $DIC;
$db = $DIC->database();

// Create gtour_steps table for managing tour steps separately
if (!$db->tableExists('gtour_steps'))
{
    $fields = array(
        'step_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'tour_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'sort_order' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true,
            'default' => 0
        ),
        'element' => array(
            'type' => 'text',
            'length' => 500,
            'fixed' => false,
            'notnull' => false
        ),
        'title' => array(
            'type' => 'text',
            'length' => 255,
            'fixed' => false,
            'notnull' => false
        ),
        'content' => array(
            'type' => 'clob',
            'notnull' => false
        ),
        'placement' => array(
            'type' => 'text',
            'length' => 20,
            'fixed' => false,
            'notnull' => false,
            'default' => 'right'
        ),
        'orphan' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => false,
            'default' => 0
        ),
        'on_next' => array(
            'type' => 'clob',
            'notnull' => false
        ),
        'on_prev' => array(
            'type' => 'clob',
            'notnull' => false
        ),
        'on_show' => array(
            'type' => 'clob',
            'notnull' => false
        ),
        'on_shown' => array(
            'type' => 'clob',
            'notnull' => false
        ),
        'on_hide' => array(
            'type' => 'clob',
            'notnull' => false
        ),
        'path' => array(
            'type' => 'text',
            'length' => 500,
            'fixed' => false,
            'notnull' => false
        )
    );

    $db->createTable("gtour_steps", $fields);
    $db->addPrimaryKey("gtour_steps", array("step_id"));
    $db->createSequence("gtour_steps");
    $db->addIndex("gtour_steps", array("tour_id"), "i1");
    $db->addIndex("gtour_steps", array("tour_id", "sort_order"), "i2");
}
?>
<#4>
<?php
global $DIC;
$db = $DIC->database();

// Add content_page_id column to gtour_steps for ILIAS Page Object support
if ($db->tableExists('gtour_steps') && !$db->tableColumnExists('gtour_steps', 'content_page_id'))
{
    $db->addTableColumn("gtour_steps", 'content_page_id', array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => false
    ));
}
?>
<#5>
<?php
global $DIC;
$db = $DIC->database();

// Register Page Object Definition in copg_pobj_def table
if ($db->tableExists('copg_pobj_def'))
{
    // Check if entry already exists
    $query = "SELECT parent_type FROM copg_pobj_def WHERE parent_type = 'gtst'";
    $result = $db->query($query);

    if ($db->numRows($result) == 0) {
        // Insert page object definition for Guided Tour Steps
        // Note: We load classes manually, so paths don't matter, but we need valid values
        // Using shortened plugin path (full path would exceed varchar(40) limit)
        $db->insert('copg_pobj_def', array(
            'parent_type' => array('text', 'gtst'),
            'class_name' => array('text', 'ilGuidedTourStepPage'),
            'directory' => array('text', 'classes/Page')
        ));
    }
}
?>
<#6>
<?php
global $DIC;
$db = $DIC->database();

// Update component value for gtst parent type in copg_pobj_def table
// This ensures proper registration with plugin identifier
if ($db->tableExists('copg_pobj_def'))
{
    $query = "SELECT component FROM copg_pobj_def WHERE parent_type = 'gtst'";
    $result = $db->query($query);

    if ($db->numRows($result) > 0) {
        $row = $db->fetchAssoc($result);
        // Only update if component is NULL
        if ($row['component'] === null) {
            $db->update('copg_pobj_def',
                array('component' => array('text', 'plugin/gtour')),
                array('parent_type' => array('text', 'gtst'))
            );
        }
    }
}
?>
<#7>
<?php
global $DIC;
$db = $DIC->database();

// Add element_type and element_name columns for smart pattern recognition
// This allows hybrid approach: pattern-based (stable) + CSS selector (flexible)
if ($db->tableExists('gtour_steps'))
{
    if (!$db->tableColumnExists('gtour_steps', 'element_type'))
    {
        $db->addTableColumn("gtour_steps", 'element_type', array(
            'type' => 'text',
            'length' => 50,
            'fixed' => false,
            'notnull' => false
        ));
    }

    if (!$db->tableColumnExists('gtour_steps', 'element_name'))
    {
        $db->addTableColumn("gtour_steps", 'element_name', array(
            'type' => 'text',
            'length' => 255,
            'fixed' => false,
            'notnull' => false
        ));
    }
}
?>
<#8>
<?php
global $DIC;
$db = $DIC->database();

// Add language_code, description, and scenario columns to gtour_tours
// language_code: ISO language code (e.g., 'de', 'en') - tours are shown only for matching language
// description: Public description shown to users
// scenario: Internal description of the tour scenario for administrators
if ($db->tableExists('gtour_tours'))
{
    if (!$db->tableColumnExists('gtour_tours', 'language_code'))
    {
        $db->addTableColumn("gtour_tours", 'language_code', array(
            'type' => 'text',
            'length' => 10,
            'fixed' => false,
            'notnull' => false
        ));
    }

    if (!$db->tableColumnExists('gtour_tours', 'description'))
    {
        $db->addTableColumn("gtour_tours", 'description', array(
            'type' => 'clob',
            'notnull' => false
        ));
    }

    if (!$db->tableColumnExists('gtour_tours', 'scenario'))
    {
        $db->addTableColumn("gtour_tours", 'scenario', array(
            'type' => 'clob',
            'notnull' => false
        ));
    }
}
?>
<#9>
<?php
global $DIC;
$db = $DIC->database();

// Create gtour_usage table to track tour usage and completion per user
if (!$db->tableExists('gtour_usage'))
{
    $fields = array(
        'tour_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'user_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'started_ts' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false
        ),
        'finished_ts' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false
        ),
        'last_step_reached' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
            'default' => 0
        )
    );

    $db->createTable("gtour_usage", $fields);
    $db->addPrimaryKey("gtour_usage", array("tour_id", "user_id"));
    $db->addIndex("gtour_usage", array("user_id"), "i1");
    $db->addIndex("gtour_usage", array("tour_id"), "i2");
}
?>
<#10>
<?php
global $DIC;
$db = $DIC->database();

// Migrate existing gtour_usage table to new structure with state + history

// 1. Create new state table (current state per user/tour)
if (!$db->tableExists('gtour_user_state'))
{
    $fields = array(
        'tour_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'user_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'last_started_ts' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false
        ),
        'last_terminated_ts' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false
        ),
        'last_step_reached' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
            'default' => 0
        ),
        'times_started' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
            'default' => 0
        ),
        'times_completed' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
            'default' => 0
        ),
        'show_again' => array(
            'type' => 'integer',
            'length' => 1,
            'notnull' => true,
            'default' => 0
        )
    );

    $db->createTable("gtour_user_state", $fields);
    $db->addPrimaryKey("gtour_user_state", array("tour_id", "user_id"));
    $db->addIndex("gtour_user_state", array("user_id"), "i1");
    $db->addIndex("gtour_user_state", array("tour_id"), "i2");
}

// 2. Create history table (all tour runs)
if (!$db->tableExists('gtour_usage_history'))
{
    $fields = array(
        'id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'tour_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'user_id' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => true
        ),
        'started_ts' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false
        ),
        'terminated_ts' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false
        ),
        'last_step_reached' => array(
            'type' => 'integer',
            'length' => 4,
            'notnull' => false,
            'default' => 0
        )
    );

    $db->createTable("gtour_usage_history", $fields);
    $db->addPrimaryKey("gtour_usage_history", array("id"));
    $db->createSequence("gtour_usage_history");
    $db->addIndex("gtour_usage_history", array("tour_id"), "i1");
    $db->addIndex("gtour_usage_history", array("user_id"), "i2");
    $db->addIndex("gtour_usage_history", array("tour_id", "user_id"), "i3");
}

// 3. Migrate existing data from gtour_usage to new tables
if ($db->tableExists('gtour_usage'))
{
    // Migrate to state table
    $query = "SELECT * FROM gtour_usage";
    $result = $db->query($query);

    while ($row = $db->fetchAssoc($result)) {
        // Calculate times_started and times_completed
        $times_started = ($row['started_ts'] !== null) ? 1 : 0;
        $times_completed = ($row['finished_ts'] !== null) ? 1 : 0;

        // Insert into state table
        $db->insert('gtour_user_state', array(
            'tour_id' => array('integer', $row['tour_id']),
            'user_id' => array('integer', $row['user_id']),
            'last_started_ts' => array('integer', $row['started_ts']),
            'last_terminated_ts' => array('integer', $row['finished_ts']),
            'last_step_reached' => array('integer', $row['last_step_reached'] ?? 0),
            'times_started' => array('integer', $times_started),
            'times_completed' => array('integer', $times_completed)
        ));

        // Insert into history table (create one history entry per existing usage record)
        if ($row['started_ts'] !== null) {
            $history_id = $db->nextId('gtour_usage_history');
            $db->insert('gtour_usage_history', array(
                'id' => array('integer', $history_id),
                'tour_id' => array('integer', $row['tour_id']),
                'user_id' => array('integer', $row['user_id']),
                'started_ts' => array('integer', $row['started_ts']),
                'terminated_ts' => array('integer', $row['finished_ts']),
                'last_step_reached' => array('integer', $row['last_step_reached'] ?? 0)
            ));
        }
    }

    // Drop old table
    $db->dropTable('gtour_usage');
}
?>
<#11>
<?php
global $DIC;
$db = $DIC->database();

// Add show_again flag to state table for forcing tour to show again without affecting statistics
if (!$db->tableColumnExists('gtour_user_state', 'show_again')) {
    $db->addTableColumn('gtour_user_state', 'show_again', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => true,
        'default' => 0
    ));
}
?>
<#12>
<?php
global $DIC;
$db = $DIC->database();

// Add ref_id column to gtour_tours for binding tours to specific objects
// NULL = tour shown everywhere (based on type), otherwise only shown at specific ref_id
if (!$db->tableColumnExists('gtour_tours', 'ref_id')) {
    $db->addTableColumn('gtour_tours', 'ref_id', array(
        'type' => 'integer',
        'length' => 4,
        'notnull' => false,
        'default' => null
    ));
}

// Add trigger_mode column to gtour_tours for advanced autostart behavior
// Possible values:
// - 'normal' (default): Show once, then don't show again until admin resets
// - 'always': Always show again after termination (useful for reminders/help)
// - 'until_completed': Keep showing until user completes the tour (reaches last step)
if (!$db->tableColumnExists('gtour_tours', 'trigger_mode')) {
    $db->addTableColumn('gtour_tours', 'trigger_mode', array(
        'type' => 'text',
        'length' => 20,
        'notnull' => true,
        'default' => 'normal'
    ));
}
?>
