<?php declare(strict_types=1);

namespace uzk\gtour\Config;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\DI\Container;
use ilCtrlException;
use ilException;
use ILIAS\DI\UIServices;
use ilTable2GUI;
use ilGuidedTourPlugin;
use uzk\gtour\Data\GuidedTourRepository;
use ilTemplateException;

/**
 * Class GuidedTourConfigToursTable
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class GuidedTourConfigToursTable extends ilTable2GUI
{
    protected UIServices $ui;
    protected object $parentObject;
    protected ilGuidedTourPlugin $plugin;
    protected GuidedTourRepository $guidedTourRepository;

    /**
     * GuidedTourConfigToursTable constructor
     */
    public function __construct(
        ?object $a_parent_obj,
        string $a_parent_cmd = ""
    )
    {
        /** @var Container $DIC */
        global $DIC;

        // General Dependencies
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->ui = $DIC->ui();

        // Repositories
        $this->guidedTourRepository = new GuidedTourRepository();

        // Init ilTable2GUI
        parent::__construct($a_parent_obj, $a_parent_cmd);
        $this->parentObject = $a_parent_obj;
        $this->plugin = $a_parent_obj->getPluginObject();

        // Create table
        $this->createTable();
    }

    /**
     * Creates GuidedTour configuration table
     */
    protected function createTable(): void
    {
        try {
            $this->setId("gtour_tours_tbl");
        } catch (ilException $e) {

        }
        $this->setTitle($this->plugin->txt('guided_tours'));

        $this->setTopCommands(true);
        $this->setLimit(9999);

        $this->addColumn("", "TOUR_ID", "1", true);
        $this->addColumn($this->plugin->txt('tour_title'), "TOUR_TITLE", '20%');
        $this->addColumn($this->plugin->txt('tour_type'), 'TOUR_TYPE', '20%');
        $this->addColumn($this->plugin->txt('tour_active'), 'TOUR_ACTIVE_CHECKED', '20%');
        $this->addColumn($this->plugin->txt('tour_automatic_triggered'), 'TOUR_AUTOTRIGGER_CHECKED', '20%');
        $this->addColumn($this->lng->txt("actions"));

        try {
            $this->setFormAction($this->ctrl->getFormAction($this->parentObject));
        } catch (ilCtrlException $e) {

        }

        $this->setRowTemplate('tpl.gtour_tours_tbl_row.html', $this->plugin->getDirectory());
        $this->setEnableTitle(true);
        //$this->setSelectAllCheckbox("id");
        $this->setEnableAllCommand(false);
        $this->setEnableHeader(true);
        $this->setEnableNumInfo(false);
        $this->setDefaultOrderField("TOUR_TITLE");

        $this->addCommandButton('addTour', $this->plugin->txt('add_tour'));
        //$this->addCommandButton('confirmDeleteTour', $this->plugin->txt('delete_tour'));

        $this->getToursFromDb();
    }

    /**
     * Get data and put it into an array
     */
    function getToursFromDb(): void
    {
        $tours = $this->guidedTourRepository->getTours();
        $data = [];

        foreach ($tours as $tour) {
            $data[] = $tour->toDataArray();
        }

        $this->setDefaultOrderField('TOUR_TITLE');
        $this->setDefaultOrderDirection('asc');
        $this->setData($data);
    }

    /**
     * Fill a single data row.
     * @param array $a_set
     * @throws ilTemplateException
     */
    protected function fillRow(array $a_set): void
    {
        global $DIC;
        $factory = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();

        $this->tpl->setVariable('TOUR_ID', htmlspecialchars((string)$a_set['tour_id'], ENT_QUOTES, 'UTF-8'));
        $this->tpl->setVariable('TOUR_TITLE', htmlspecialchars($a_set['title'], ENT_QUOTES, 'UTF-8'));
        $this->tpl->setVariable('TOUR_TYPE', htmlspecialchars($a_set['type'], ENT_QUOTES, 'UTF-8'));

        $isActive = filter_var($a_set['is_active'], FILTER_VALIDATE_BOOLEAN);
        $this->tpl->setVariable('TOUR_ACTIVE', $isActive);
        $this->tpl->setVariable('TOUR_ACTIVE_CHECKED', $isActive ? " checked='checked' " : '');

        $isAutoTriggered = filter_var($a_set['is_automatic_triggered'], FILTER_VALIDATE_BOOLEAN);
        $this->tpl->setVariable('TOUR_AUTOTRIGGER', $isAutoTriggered);
        $this->tpl->setVariable('TOUR_AUTOTRIGGER_CHECKED', $isAutoTriggered ? " checked='checked' " : '');

        // Add action row
        $actionDropdownItems = [
            $factory->button()->shy(
                $this->lng->txt('edit'),
                $this->buildActionLink('editTour', (int)$a_set['tour_id'])
            ),
            $factory->button()->shy(
                $this->lng->txt('activate'),
                $this->buildActionLink('activateTour', (int)$a_set['tour_id'])
            ),
            $factory->button()->shy(
                $this->lng->txt('deactivate'),
                $this->buildActionLink('deactivateTour', (int)$a_set['tour_id'])
            ),
            $factory->button()->shy(
                $this->lng->txt('delete'),
                $this->buildActionLink('confirmDeleteTour', (int)$a_set['tour_id'])
            )
        ];
        $actionDropdown = $factory->dropdown()->standard($actionDropdownItems)->withLabel($this->lng->txt('actions'));

        $this->tpl->setCurrentBlock('actions');
        $this->tpl->setVariable('ACTIONS', $renderer->render($actionDropdown));
        $this->tpl->parseCurrentBlock();
    }

    protected function buildActionLink(string $command, int $dataId = null): string
    {
        $link = "";
        try {
            if ($dataId !== null) {
                $this->ctrl->setParameter($this->parent_obj, 'tour_id', $dataId);
            } else {
                $this->ctrl->setParameter($this->parent_obj, 'tour_id', '');
            }
            $link = $this->ctrl->getLinkTarget($this->parent_obj, $command);
        } catch (ilCtrlException $e) {
            error_log($e->getMessage());
        }
        return $link;
    }

}