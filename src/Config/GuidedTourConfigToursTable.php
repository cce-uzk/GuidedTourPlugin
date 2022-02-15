<?php
namespace uzk\gtour\Config;
use ilAdvancedSelectionListGUI;

/**
 * Class GuidedTourConfigToursTable
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 */
class GuidedTourConfigToursTable extends \ilTable2GUI
{
    function __construct($a_parent_obj, $a_parent_cmd)
    {
        global $DIC, $ilCtrl;

        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->plugin = $a_parent_obj->getPluginObject();

        $this->setId('gtour_tours_tbl');
        parent::__construct($a_parent_obj, $a_parent_cmd);

        $this->addColumn($this->plugin->txt('tour_title'), '', '25%');
        $this->addColumn($this->plugin->txt('tour_type'), '', '25%');
        $this->addColumn($this->plugin->txt('tour_active'), '', '25%');
        $this->addColumn('');

        $this->setEnableHeader(true);
        $this->setFormAction($ilCtrl->getFormAction($a_parent_obj));

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
     * Get selectable columns
     */
    public function getSelectableColumns()
    {
        return array();
    }

    /**
     * Get data and put it into an array
     */
    function getToursFromDb()
    {
        $data = [];
        foreach (\ilGuidedTour::getTours() as $tour)
        {
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
        global $DIC;
        $this->ctrl->setParameter($this->parent_obj, 'tour_id', $a_set['tour_id']);

        $list = new ilAdvancedSelectionListGUI();
        $list->setSelectionHeaderClass('small');
        $list->setItemLinkClass('small');
        $list->setId('gtour_tours_tbl_row_actions');
        $list->setListTitle($this->lng->txt('actions'));

        $list->addItem($this->lng->txt('edit'), '', $this->ctrl->getLinkTarget($this->parent_obj, 'editTour'));
        $list->addItem($this->lng->txt('activate'), $a_set['tour_id'], $this->ctrl->getLinkTarget($this->parent_obj, 'activateTour'));
        $list->addItem($this->lng->txt('deactivate'), $a_set['tour_id'], $this->ctrl->getLinkTarget($this->parent_obj, 'deactivateTour'));
        $list->addItem($this->lng->txt('delete'), '', $DIC->ctrl()->getLinkTarget($this->parent_obj, 'confirmDeleteTour'));

        $this->tpl->setVariable('TOUR_ID', $a_set['tour_id']);
        $this->tpl->setVariable('TITLE', $a_set['title']);
        $this->tpl->setVariable('TYPE', $a_set['type']);
        $this->tpl->setVariable('ACTIVE', $a_set['is_active'] ? $this->lng->txt('yes') : $this->lng->txt('no'));
        $this->tpl->setVariable('ACTIONS', $list->getHTML());
    }
}