<?php

namespace uzk\gtour\Config;

use ilAdvancedSelectionListGUI;

/**
 * Class GuidedTourConfigToursTable
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class GuidedTourConfigToursTable extends \ilTable2GUI
{
    protected $ctrl;
    protected $plugin;
    protected $lng;
    protected $parentObject;

    /**
     * GuidedTourConfigToursTable constructor
     */
    function __construct($a_parent_obj, $a_parent_cmd)
    {
        parent::__construct($a_parent_obj, $a_parent_cmd);
        global $DIC;

        $this->lng = $DIC->language();
        $this->ctrl = $this->ctrl;
        $this->parentObject = $a_parent_obj;
        $this->plugin = $a_parent_obj->getPluginObject();

        $this->createTable();
    }

    /**
     * Creates GuidedTour configuration table
     */
    protected function createTable()
    {
        $this->setId('gtour_tours_tbl');

        $this->addColumn($this->plugin->txt('tour_title'), '', '25%');
        $this->addColumn($this->plugin->txt('tour_type'), '', '25%');
        $this->addColumn($this->plugin->txt('tour_active'), '', '25%');
        $this->addColumn('');

        $this->setEnableHeader(true);
        $this->setFormAction($this->ctrl->getFormAction($this->parentObject));

        $this->setRowTemplate('tpl.gtour_tours_tbl_row.html', $this->plugin->getDirectory());
        $this->setEnableAllCommand(false);
        $this->setEnableHeader(true);
        $this->setEnableNumInfo(false);
        $this->setEnableTitle(false);

        $this->addCommandButton('addTour', $this->plugin->txt('add_tour'));
        //$this->addCommandButton('confirmDeleteTour', $this->plugin->txt('delete_tour'));

        $this->getToursFromDb();

        $this->setTitle($this->plugin->txt('Guided Tours'));
    }

    /**
     * Get data and put it into an array
     */
    function getToursFromDb()
    {
        $data = [];
        foreach (\ilGuidedTour::getTours() as $tour) {
            $data[] = $tour->toDataArray();
        }

        $this->setDefaultOrderField('title');
        $this->setDefaultOrderDirection('asc');
        $this->setData($data);
    }

    /**
     * Fill a single data row.
     */
    protected function fillRow($a_set)
    {
        $this->ctrl->setParameter($this->parentObject, 'tour_id', $a_set['tour_id']);

        $list = new ilAdvancedSelectionListGUI();
        $list->setSelectionHeaderClass('small');
        $list->setItemLinkClass('small');
        $list->setId('gtour_tours_tbl_row_actions');
        $list->setListTitle($this->lng->txt('actions'));

        $list->addItem($this->lng->txt('edit'), '',
            $this->ctrl->getLinkTarget($this->parentObject, 'editTour')
        );
        $list->addItem($this->lng->txt('activate'), $a_set['tour_id'],
            $this->ctrl->getLinkTarget($this->parentObject, 'activateTour')
        );
        $list->addItem($this->lng->txt('deactivate'), $a_set['tour_id'],
            $this->ctrl->getLinkTarget($this->parentObject, 'deactivateTour')
        );
        $list->addItem($this->lng->txt('delete'), '',
            $this->ctrl->getLinkTarget($this->parentObject, 'confirmDeleteTour')
        );

        $this->tpl->setVariable('TOUR_ID', $a_set['tour_id']);
        $this->tpl->setVariable('TITLE', $a_set['title']);
        $this->tpl->setVariable('TYPE', $a_set['type']);
        $this->tpl->setVariable('ACTIVE', $a_set['is_active'] ? $this->lng->txt('yes') : $this->lng->txt('no'));
        $this->tpl->setVariable('ACTIONS', $list->getHTML());
    }
}