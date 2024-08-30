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
if ($db->tableExists('gtour_tours'))
{
    $db->addTableColumn("gtour_tours", 'is_automatic_triggered', array(
        'type' => 'integer',
        'length' => 1,
        'notnull' => false
    ));
}
?>
