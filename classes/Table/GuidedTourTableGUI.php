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
 * Modern ILIAS UI Table for Guided Tours
 *
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version 2.0.0
 */
class GuidedTourTableGUI
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

    public function __construct(?object $a_parent_obj)
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
    }

    /**
     * Get HTML for the tours table
     */
    public function getHTML(): string
    {
        $data_retrieval = new GuidedTourTableDataRetrieval();
        $table = $this->createTable($data_retrieval);

        return $this->renderer->render($table);
    }

    /**
     * Create the tours table
     */
    protected function createTable(GuidedTourTableDataRetrieval $data_retrieval): \ILIAS\UI\Component\Table\Data
    {
        // Define columns
        $columns = [
            'title' => $this->ui_factory->table()->column()->text($this->plugin->txt('tour_title'))
                ->withIsSortable(true),
            'type' => $this->ui_factory->table()->column()->text($this->plugin->txt('tour_type'))
                ->withIsSortable(true),
            'active' => $this->ui_factory->table()->column()->statusIcon($this->plugin->txt('tour_active'))
                ->withIsSortable(true),
            'automatic_triggered' => $this->ui_factory->table()->column()->statusIcon($this->plugin->txt('tour_automatic_triggered'))
                ->withIsOptional(true)
                ->withIsSortable(true),
            'steps_count' => $this->ui_factory->table()->column()->number($this->plugin->txt('tour_steps_count'))
                ->withIsOptional(true)
                ->withIsSortable(true)
        ];

        // Build table with actions
        $table = $this->ui_factory->table()->data(
            $this->plugin->txt('guided_tours'),
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
        $query_params_namespace = ['tour'];
        $url_builder = new \ILIAS\UI\URLBuilder(
            new \ILIAS\Data\URI(
                ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTarget(
                    $this->parent_obj,
                    'handleTableActions'
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
            'reset_statistics_single' => $this->ui_factory->table()->action()->single(
                $this->plugin->txt('reset_statistics'),
                $url_builder->withParameter($action_parameter_token, 'reset_statistics'),
                $row_id_token
            ),
            'reset_completion_single' => $this->ui_factory->table()->action()->single(
                $this->plugin->txt('reset_completion_status'),
                $url_builder->withParameter($action_parameter_token, 'reset_completion'),
                $row_id_token
            ),
            'delete_single' => $this->ui_factory->table()->action()->single(
                $this->lng->txt('delete'),
                $url_builder->withParameter($action_parameter_token, 'delete'),
                $row_id_token
            ),
            'reset_statistics' => $this->ui_factory->table()->action()->multi(
                $this->plugin->txt('reset_statistics'),
                $url_builder->withParameter($action_parameter_token, 'reset_statistics'),
                $row_id_token
            ),
            'reset_completion' => $this->ui_factory->table()->action()->multi(
                $this->plugin->txt('reset_completion_status'),
                $url_builder->withParameter($action_parameter_token, 'reset_completion'),
                $row_id_token
            ),
            'activate' => $this->ui_factory->table()->action()->multi(
                $this->lng->txt('activate'),
                $url_builder->withParameter($action_parameter_token, 'activate'),
                $row_id_token
            ),
            'deactivate' => $this->ui_factory->table()->action()->multi(
                $this->lng->txt('deactivate'),
                $url_builder->withParameter($action_parameter_token, 'deactivate'),
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
