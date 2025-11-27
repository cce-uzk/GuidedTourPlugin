<?php declare(strict_types=1);

namespace uzk\gtour\Table;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\UI\Renderer;
use ILIAS\UI\Factory;
use ILIAS\Data\Factory as DataFactory;
use ilLanguage;
use ilCtrl;
use ILIAS\HTTP\Services;
use ILIAS\Refinery\Factory as RefineryFactory;
use ilGuidedTourPlugin;

/**
 * Modern ILIAS UI Table for Guided Tour Steps
 *
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version 2.0.0
 */
class GuidedTourStepsTableGUI
{
    protected Renderer $renderer;
    protected Factory $ui_factory;
    protected DataFactory $data_factory;
    protected ilLanguage $lng;
    protected ilCtrl $ctrl;
    protected Services $http;
    protected RefineryFactory $refinery;
    protected ?object $parent_obj = null;
    protected ilGuidedTourPlugin $plugin;
    protected int $tour_id;

    public function __construct(?object $a_parent_obj, int $tour_id)
    {
        global $DIC;

        $this->renderer = $DIC->ui()->renderer();
        $this->ui_factory = $DIC->ui()->factory();
        $this->lng = $DIC->language();
        $this->ctrl = $DIC->ctrl();
        $this->http = $DIC->http();
        $this->refinery = $DIC->refinery();
        $this->data_factory = new DataFactory();

        $this->parent_obj = $a_parent_obj;
        $this->plugin = ilGuidedTourPlugin::getInstance();
        $this->tour_id = $tour_id;
    }

    /**
     * Get HTML for the steps table
     */
    public function getHTML(): string
    {
        $data_retrieval = new GuidedTourStepsTableDataRetrieval($this->tour_id);
        $table = $this->createTable($data_retrieval);

        return $this->renderer->render($table);
    }

    /**
     * Create the steps table
     */
    protected function createTable(GuidedTourStepsTableDataRetrieval $data_retrieval): \ILIAS\UI\Component\Table\Data
    {
        // Define columns
        $columns = [
            'sort_order' => $this->ui_factory->table()->column()->number($this->plugin->txt('step_sort_order'))
                ->withIsSortable(true),
            'title' => $this->ui_factory->table()->column()->text($this->plugin->txt('step_title'))
                ->withIsSortable(true),
            'element' => $this->ui_factory->table()->column()->text($this->plugin->txt('step_element'))
                ->withIsSortable(true),
            'placement' => $this->ui_factory->table()->column()->text($this->plugin->txt('step_placement'))
                ->withIsOptional(true)
                ->withIsSortable(true),
            'orphan' => $this->ui_factory->table()->column()->statusIcon($this->plugin->txt('step_orphan'))
                ->withIsOptional(true)
                ->withIsSortable(true)
        ];

        // Build table with actions
        $table = $this->ui_factory->table()->data(
            $this->plugin->txt('tour_steps'),
            $columns,
            $data_retrieval
        );

        // Add actions
        $actions = $this->createActions();
        if (!empty($actions)) {
            $table = $table->withActions($actions);
        }

        return $table->withRequest($this->http->request());
    }

    /**
     * Create table actions
     */
    protected function createActions(): array
    {
        $query_params_namespace = ['step'];
        $url_builder = new \ILIAS\UI\URLBuilder(
            new \ILIAS\Data\URI(
                ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTarget(
                    $this->parent_obj,
                    'handleStepTableActions'
                )
            )
        );

        list($url_builder, $action_parameter_token, $row_id_token) = $url_builder->acquireParameters(
            $query_params_namespace,
            'table_action',
            'ids'
        );

        return [
            'edit' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('edit'),
                $url_builder->withParameter($action_parameter_token, 'edit'),
                $row_id_token
            ),
            'delete_single' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('delete'),
                $url_builder->withParameter($action_parameter_token, 'delete'),
                $row_id_token
            ),
            'delete' => $this->ui_factory->table()->action()->multi(
                $this->lng->txt('delete'),
                $url_builder->withParameter($action_parameter_token, 'delete'),
                $row_id_token
            )
        ];
    }
}

/**
 * Data retrieval for steps table
 */
class GuidedTourStepsTableDataRetrieval implements \ILIAS\UI\Component\Table\DataRetrieval
{
    protected \uzk\gtour\Data\GuidedTourStepRepository $stepRepo;
    protected int $tour_id;
    protected \ILIAS\UI\Factory $ui_factory;
    protected \ilLanguage $lng;

    public function __construct(int $tour_id)
    {
        global $DIC;

        $this->stepRepo = new \uzk\gtour\Data\GuidedTourStepRepository();
        $this->tour_id = $tour_id;
        $this->ui_factory = $DIC->ui()->factory();
        $this->lng = $DIC->language();
    }

    public function getRows(
        \ILIAS\UI\Component\Table\DataRowBuilder $row_builder,
        array $visible_column_ids,
        \ILIAS\Data\Range $range,
        \ILIAS\Data\Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): \Generator {
        // Get steps for this tour
        $steps = $this->stepRepo->getStepsByTourId($this->tour_id);

        // Apply sorting
        $steps = $this->sortSteps($steps, $order);

        // Apply range (pagination)
        $offset = $range->getStart();
        $length = $range->getLength();
        $steps = array_slice($steps, $offset, $length);

        // Generate rows
        foreach ($steps as $step) {
            $step_id = (string)$step->getId();

            // Create orphan status icon
            $orphan_icon = $this->ui_factory->symbol()->icon()->custom(
                $step->isOrphan() ?
                    \ilUtil::getImagePath('standard/icon_ok.svg') :
                    \ilUtil::getImagePath('standard/icon_not_ok.svg'),
                $step->isOrphan() ? $this->lng->txt('active') : $this->lng->txt('inactive'),
                \ILIAS\UI\Component\Symbol\Icon\Icon::SMALL
            );

            // Build row data
            $row_data = [
                'sort_order' => $step->getSortOrder(),
                'title' => $step->getTitle() ?: '-',
                'element' => $step->getElement() ?: '-',
                'placement' => ucfirst($step->getPlacement()),
                'orphan' => $orphan_icon
            ];

            yield $row_builder->buildDataRow($step_id, $row_data);
        }
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        $steps = $this->stepRepo->getStepsByTourId($this->tour_id);
        return count($steps);
    }

    /**
     * Sort steps based on order
     */
    protected function sortSteps(array $steps, \ILIAS\Data\Order $order): array
    {
        $aspect = $order->join('', fn($i, $k, $v) => $k);
        $direction = $order->join('', fn($i, $k, $v) => $v);

        if (empty($aspect)) {
            return $steps;
        }

        usort($steps, function ($a, $b) use ($aspect, $direction) {
            $val_a = $this->getStepValue($a, $aspect);
            $val_b = $this->getStepValue($b, $aspect);

            if ($val_a === $val_b) {
                return 0;
            }

            $result = $val_a < $val_b ? -1 : 1;

            return $direction === 'DESC' ? -$result : $result;
        });

        return $steps;
    }

    /**
     * Get step value for sorting
     */
    protected function getStepValue($step, string $aspect)
    {
        switch ($aspect) {
            case 'sort_order':
                return $step->getSortOrder();
            case 'title':
                return strtolower($step->getTitle() ?: '');
            case 'element':
                return strtolower($step->getElement() ?: '');
            case 'placement':
                return strtolower($step->getPlacement());
            case 'orphan':
                return $step->isOrphan() ? 1 : 0;
            default:
                return '';
        }
    }
}
