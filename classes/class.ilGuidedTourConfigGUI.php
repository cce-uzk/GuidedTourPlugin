<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use ILIAS\DI\Container;
use ILIAS\DI\UIServices;
use uzk\gtour\Data\GuidedTourRepository;
use uzk\gtour\Config\GuidedTourConfigToursTable;
use uzk\gtour\Model\GuidedTour;

/**
 * Class ilGuidedTourConfigGUI
 *
 * Plug-In Configuration interface class
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilGuidedTourConfigGUI: ilObjComponentSettingsGUI
 * @ilCtrl_Calls ilGuidedTourConfigGUI: ilGuidedTourStepPageGUI
 */
class ilGuidedTourConfigGUI extends ilPluginConfigGUI
{
    private const CMD_CONFIGURE = 'configure';
    private const CMD_SHOWTOURLIST = 'showTourList';
    private const CMD_ACTIVATETOUR = 'activateTour';
    private const CMD_DEACTIVATETOUR = 'deactivateTour';
    private const CMD_CONFIRMDELETETOUR = 'confirmDeleteTour';
    private const CMD_ADDTOUR = 'addTour';
    private const CMD_EDITTOUR = 'editTour';
    private const CMD_SAVETOUR = 'saveTour';
    private const CMD_HANDLETABLEACTIONS = 'handleTableActions';
    private const CMD_SHOWTOURSTEPS = 'showTourSteps';
    private const CMD_HANDLESTEPTABLEACTIONS = 'handleStepTableActions';
    private const CMD_ADDSTEP = 'addStep';
    private const CMD_EDITSTEP = 'editStep';
    private const CMD_SAVESTEP = 'saveStep';
    private const CMD_DELETESTEP = 'deleteStep';
    private const CMD_REDIRECTTOEDITOR = 'redirectToPageEditor';
    private const CMD_REMOVERICHCONTENT = 'removeRichContent';
    private const CMD_STARTRECORDING = 'startRecording';
    private const CMD_PAUSERECORDING = 'pauseRecording';
    private const CMD_DISCARDRECORDING = 'discardRecording';
    private const CMD_SAVEANDEXITRECORDING = 'saveAndExitRecording';
    private const CMD_ADDRECORDINGSTEP = 'addRecordingStep';
    private const CMD_SYNCRECORDINGSTEPS = 'syncRecordingSteps';
    private const CMD_FINISHTOUR = 'finishTour';
    private const CMD_RESETTOURPROGRESS = 'resetTourProgress';
    private const CMD_SHOWSTATISTICS = 'showStatistics';
    private const CMD_RESETTOURSTATISTICS = 'resetTourStatistics';

    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected ilTabsGUI $tabs;
    protected ilToolbarGUI $toolbar;
    protected UIServices $ui;
    protected \ILIAS\HTTP\Services $http;
    protected \ilGlobalTemplateInterface $tpl;
    protected \ILIAS\Data\Factory $data_factory;
    protected \ILIAS\Refinery\Factory $refinery;
    protected GuidedTourRepository $guidedTourRepository;
    protected GuidedTour $tour;

    /**
     * ilGuidedTourConfigGUI constructor
     * @throws Exception
     */
    function __construct()
    {
        /** @var Container $DIC */
        global $DIC;

        // General Dependencies
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->tabs = $DIC->tabs();
        $this->toolbar = $DIC->toolbar();
        $this->ui = $DIC->ui();
        $this->http = $DIC->http();
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->data_factory = new \ILIAS\Data\Factory();
        $this->refinery = $DIC->refinery();

        // Repositories
        $this->guidedTourRepository = new GuidedTourRepository();

        $this->setPluginObject(ilGuidedTourPlugin::getInstance());
    }

    /**
     * @throws Exception
     */
    function performCommand(string $cmd) : void
    {
        // Handle forwarding to page editor GUI
        // This must be done before any other command processing
        $next_class = $this->ctrl->getNextClass($this);
        if ($next_class === 'ilguidedtoursteppagegui') {
            $this->forwardToPageEditor();
            return;
        }

        // Set up main tabs
        $this->setTabs();

        // Set page title, description and icon
        $this->tpl->setTitle($this->plugin_object->txt('plugin_title'));
        $this->tpl->setDescription($this->plugin_object->txt('plugin_description'));
        $this->tpl->setTitleIcon(
            $this->plugin_object->getDirectory() . '/templates/images/signpost-split.svg',
            $this->plugin_object->txt('plugin_title')
        );

        switch ($this->ctrl->getCmd(self::CMD_SHOWTOURLIST)) {
            case self::CMD_CONFIGURE:
            case self::CMD_SHOWTOURLIST:
                $this->tabs->activateTab('tours');
                $this->showTourList();
                break;

            case self::CMD_ACTIVATETOUR:
                $this->tabs->activateTab('tours');
                $this->activateTour();
                break;

            case self::CMD_DEACTIVATETOUR:
                $this->tabs->activateTab('tours');
                $this->deactivateTour();
                break;

            case self::CMD_CONFIRMDELETETOUR:
                $this->tabs->activateTab('tours');
                $this->confirmDeleteTour();
                break;

            case self::CMD_ADDTOUR:
                $this->tabs->clearTargets();
                $this->tabs->setBackTarget(
                    $this->plugin_object->txt('back_to_list'),
                    $this->ctrl->getLinkTarget($this, self::CMD_SHOWTOURLIST)
                );
                $this->tabs->addTab(
                    'add_tour',
                    $this->plugin_object->txt('add_tour'),
                    $this->ctrl->getLinkTarget($this, self::CMD_ADDTOUR)
                );
                $this->tabs->setTabActive('add_tour');
                $this->addTour();
                break;

            case self::CMD_EDITTOUR:
                $query_params = $this->http->request()->getQueryParams();
                $tour_id = $query_params['tour_id'] ?? null;
                if ($tour_id) {
                    $this->setEditTourTabs((int)$tour_id);
                    $this->tabs->activateSubTab('settings');
                }
                $this->editTour();
                break;

            case self::CMD_SHOWTOURSTEPS:
                $query_params = $this->http->request()->getQueryParams();
                $tour_id = $query_params['tour_id'] ?? null;
                if ($tour_id) {
                    $this->setEditTourTabs((int)$tour_id);
                    $this->tabs->activateSubTab('steps');
                }
                $this->showTourSteps();
                break;

            case self::CMD_SAVETOUR:
                $this->saveTour();
                break;

            case self::CMD_HANDLETABLEACTIONS:
                $this->handleTableActions();
                break;

            case self::CMD_HANDLESTEPTABLEACTIONS:
                $this->handleStepTableActions();
                break;

            case self::CMD_ADDSTEP:
                $query_params = $this->http->request()->getQueryParams();
                $tour_id = $query_params['tour_id'] ?? null;
                if ($tour_id) {
                    $this->setEditTourTabs((int)$tour_id);
                    $this->tabs->activateSubTab('steps');
                }
                $this->addStep();
                break;

            case self::CMD_EDITSTEP:
                $query_params = $this->http->request()->getQueryParams();
                $tour_id = $query_params['tour_id'] ?? null;
                if ($tour_id) {
                    $this->setEditTourTabs((int)$tour_id);
                    $this->tabs->activateSubTab('steps');
                }
                $this->editStep();
                break;

            case self::CMD_SAVESTEP:
                $this->saveStep();
                break;

            case self::CMD_DELETESTEP:
                // Delete is handled via handleStepTableActions
                $this->showTourSteps();
                break;

            case self::CMD_REDIRECTTOEDITOR:
                $this->redirectToPageEditor();
                break;

            case self::CMD_REMOVERICHCONTENT:
                $this->removeRichContent();
                break;

            case self::CMD_STARTRECORDING:
                $this->startRecording();
                break;

            case self::CMD_PAUSERECORDING:
                $this->pauseRecording();
                break;

            case self::CMD_DISCARDRECORDING:
                $this->discardRecording();
                break;

            case self::CMD_SAVEANDEXITRECORDING:
                $this->saveAndExitRecording();
                break;

            case self::CMD_SHOWSTATISTICS:
                $this->tabs->activateTab('statistics');
                $this->showStatistics();
                break;

            case self::CMD_RESETTOURSTATISTICS:
                $this->resetTourStatistics();
                break;
        }
    }

    /**
     * Set up navigation tabs
     */
    protected function setTabs(): void
    {
        // Main tab - Guided Tours
        $this->tabs->addTab(
            'tours',
            $this->plugin_object->txt('guided_tours'),
            $this->ctrl->getLinkTarget($this, self::CMD_SHOWTOURLIST)
        );

        // Statistics tab
        $this->tabs->addTab(
            'statistics',
            $this->plugin_object->txt('tab_statistics'),
            $this->ctrl->getLinkTarget($this, self::CMD_SHOWSTATISTICS)
        );
    }

    /**
     * Set up tabs for editing a tour (Settings and Steps)
     * @param int $tour_id
     */
    protected function setEditTourTabs(int $tour_id): void
    {
        $this->tabs->clearTargets();
        $this->tabs->setBackTarget(
            $this->plugin_object->txt('back_to_list'),
            $this->ctrl->getLinkTarget($this, self::CMD_SHOWTOURLIST)
        );

        try {
            $this->ctrl->setParameter($this, 'tour_id', $tour_id);
        } catch (ilCtrlException) {
        }

        // Main tab "Tour bearbeiten"
        $this->tabs->addTab(
            'edit_tour',
            $this->plugin_object->txt('edit_tour'),
            $this->ctrl->getLinkTarget($this, self::CMD_EDITTOUR)
        );
        $this->tabs->activateTab('edit_tour');

        // Settings sub-tab
        $this->tabs->addSubTab(
            'settings',
            $this->plugin_object->txt('tour_settings'),
            $this->ctrl->getLinkTarget($this, self::CMD_EDITTOUR)
        );

        // Steps sub-tab
        $this->tabs->addSubTab(
            'steps',
            $this->plugin_object->txt('tour_steps'),
            $this->ctrl->getLinkTarget($this, self::CMD_SHOWTOURSTEPS)
        );
    }

    /**
     * Load configure-screen
     */
    public function configure() : void
    {
        $this->showTourList();
    }

    /**
     * Load GuidedTour-Tours-Configuration Table
     */
    public function showTourList() : void
    {
        $this->toolbar->addButton(
            $this->plugin_object->txt('add_tour'),
            $this->ctrl->getLinkTarget($this, self::CMD_ADDTOUR)
        );

        require_once __DIR__ . '/Table/GuidedTourTableGUI.php';
        $table_gui = new \uzk\gtour\Table\GuidedTourTableGUI($this);
        $this->ui->mainTemplate()->setContent($table_gui->getHTML());
    }

    /**
     * Initialize process setting tour activation to active
     */
    public function activateTour() : void
    {
        try {
            $this->changeTourActivation(true);
        } catch (Exception) {
        }
    }

    /**
     * Initialize process setting tour activation to de-active
     */
    public function deactivateTour() : void
    {
        try {
            $this->changeTourActivation(false);
        } catch (Exception) {
        }
    }

    /**
     * Change tour activation status
     * @throws Exception
     */
    protected function changeTourActivation($active) : void
    {
        $plugin = ilGuidedTourPlugin::getInstance();

        // Get tour IDs from POST or GET using HTTP wrapper
        $query_params = $this->http->request()->getQueryParams();
        $post_params = $this->http->request()->getParsedBody();

        if (isset($post_params['tour_id'])) {
            $tour_ids = (array) $post_params['tour_id'];
        } elseif (isset($query_params['tour_id'])) {
            $tour_ids = (array) $query_params['tour_id'];
        } else {
            $tour_ids = [];
        }

        if (empty($tour_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $plugin->txt('no_tour_selected'));
        } else {
            foreach ($tour_ids as $tour_id) {
                $tour = $this->guidedTourRepository->getTourById($tour_id);
                if (isset($tour)) {
                    $tour->setActive($active);
                    $this->guidedTourRepository->updateTour($tour);
                }
            }

            if (count($tour_ids) == 1) {
                $this->ui->mainTemplate()->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS, $plugin->txt($active ? 'tour_activated' : 'tour_deactivated'));
            } else {
                $this->ui->mainTemplate()->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS, $plugin->txt($active ? 'tours_activated' : 'tours_deactivated'));
            }
        }
        try {
            $this->ctrl->redirect($this, 'showTourList');
        } catch (ilCtrlException) {

        }
    }

    /**
     * Delete tour(s) by GET 'tour_id'
     */
    public function confirmDeleteTour() : void
    {
        // Get tour IDs from POST or GET using HTTP wrapper
        $query_params = $this->http->request()->getQueryParams();
        $post_params = $this->http->request()->getParsedBody();

        if (isset($post_params['tour_id'])) {
            $tour_ids = (array) $post_params['tour_id'];
        } elseif (isset($query_params['tour_id'])) {
            $tour_ids = (array) $query_params['tour_id'];
        } else {
            $tour_ids = [];
        }

        if (empty($tour_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE, $this->plugin_object->txt('no_tour_selected'));
        } else {
            $this->guidedTourRepository->deleteToursByIds($tour_ids);
        }
        try {
            $this->ctrl->redirect($this, 'showTourList');
        } catch (ilCtrlException) {

        }
    }

    /**
     * Handle table actions from modern UI table
     * @throws Exception
     */
    public function handleTableActions(): void
    {
        // Extract action and tour IDs from URL parameters (flat structure)
        $query_params = $this->http->request()->getQueryParams();

        // URLBuilder creates flat parameters: tour_table_action and tour_ids
        $action = $query_params['tour_table_action'] ?? '';
        $tour_ids = $query_params['tour_ids'] ?? [];

        // Convert tour_ids to array if needed
        if (!empty($tour_ids) && !is_array($tour_ids)) {
            $tour_ids = [$tour_ids];
        }
        $tour_ids = array_map('intval', $tour_ids);

        // Route to appropriate method based on action
        switch ($action) {
            case 'edit':
                // For edit, we need a single tour ID
                if (count($tour_ids) === 1) {
                    $this->editTourById($tour_ids[0]);
                } else {
                    $this->showTourList();
                }
                break;
            case 'activate':
                $this->changeTourActivationByIds($tour_ids, true);
                break;
            case 'deactivate':
                $this->changeTourActivationByIds($tour_ids, false);
                break;
            case 'reset_statistics':
                $this->resetTourStatisticsByIds($tour_ids);
                break;
            case 'reset_completion':
                $this->resetCompletionStatusByIds($tour_ids);
                break;
            case 'delete':
                $this->deleteToursByIds($tour_ids);
                break;
            default:
                // Unknown action, redirect to list
                try {
                    $this->ctrl->redirect($this, 'showTourList');
                } catch (ilCtrlException) {
                }
        }
    }

    /**
     * Edit tour by ID (for modern table actions)
     * @throws Exception
     */
    protected function editTourById(int $tour_id): void
    {
        // Set up tabs with Settings and Steps sub-tabs
        $this->setEditTourTabs($tour_id);
        $this->tabs->activateSubTab('settings');

        $form_html = $this->buildTourForm($tour_id);
        $this->ui->mainTemplate()->setContent($form_html);
    }

    /**
     * Change tour activation by IDs (for modern table actions)
     * @throws Exception
     */
    protected function changeTourActivationByIds(array $tour_ids, bool $active): void
    {
        $plugin = ilGuidedTourPlugin::getInstance();

        if (empty($tour_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $plugin->txt('no_tour_selected')
            );
        } else {
            foreach ($tour_ids as $tour_id) {
                $tour = $this->guidedTourRepository->getTourById((int)$tour_id);
                if (isset($tour)) {
                    $tour->setActive($active);
                    $this->guidedTourRepository->updateTour($tour);
                }
            }

            if (count($tour_ids) == 1) {
                $this->ui->mainTemplate()->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                    $plugin->txt($active ? 'tour_activated' : 'tour_deactivated')
                );
            } else {
                $this->ui->mainTemplate()->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                    $plugin->txt($active ? 'tours_activated' : 'tours_deactivated')
                );
            }
        }

        try {
            $this->ctrl->redirect($this, 'showTourList');
        } catch (ilCtrlException) {
        }
    }

    /**
     * Delete tours by IDs (for modern table actions)
     */
    protected function deleteToursByIds(array $tour_ids): void
    {
        if (empty($tour_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('no_tour_selected')
            );
        } else {
            $this->guidedTourRepository->deleteToursByIds($tour_ids);
        }

        try {
            $this->ctrl->redirect($this, 'showTourList');
        } catch (ilCtrlException) {
        }
    }

    /**
     * Reset tour statistics by IDs (deletes all usage data - history and state)
     */
    protected function resetTourStatisticsByIds(array $tour_ids): void
    {
        if (empty($tour_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('no_tour_selected')
            );
        } else {
            require_once __DIR__ . '/Data/GuidedTourUserFinishedRepository.php';
            $usageRepo = new \uzk\gtour\Data\GuidedTourUserFinishedRepository();

            foreach ($tour_ids as $tour_id) {
                $usageRepo->resetTour((int)$tour_id);
            }

            if (count($tour_ids) == 1) {
                $this->ui->mainTemplate()->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                    $this->plugin_object->txt('statistics_reset_success')
                );
            } else {
                $this->ui->mainTemplate()->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                    $this->plugin_object->txt('statistics_reset_success_multiple')
                );
            }
        }

        try {
            $this->ctrl->redirect($this, 'showTourList');
        } catch (ilCtrlException) {
        }
    }

    /**
     * Reset completion status by IDs (keeps statistics, allows autostart tours to show again)
     */
    protected function resetCompletionStatusByIds(array $tour_ids): void
    {
        if (empty($tour_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('no_tour_selected')
            );
        } else {
            require_once __DIR__ . '/Data/GuidedTourUserFinishedRepository.php';
            $usageRepo = new \uzk\gtour\Data\GuidedTourUserFinishedRepository();

            foreach ($tour_ids as $tour_id) {
                $usageRepo->resetCompletionStatus((int)$tour_id);
            }

            if (count($tour_ids) == 1) {
                $this->ui->mainTemplate()->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                    $this->plugin_object->txt('completion_reset_success')
                );
            } else {
                $this->ui->mainTemplate()->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                    $this->plugin_object->txt('completion_reset_success_multiple')
                );
            }
        }

        try {
            $this->ctrl->redirect($this, 'showTourList');
        } catch (ilCtrlException) {
        }
    }

    /**
     * Load add form
     * @throws Exception
     */
    public function addTour() : void
    {
        $form_html = $this->buildTourForm(null);
        $this->ui->mainTemplate()->setContent($form_html);
    }

    /**
     * Show tour steps table
     * @throws Exception
     */
    public function showTourSteps(): void
    {
        $query_params = $this->http->request()->getQueryParams();
        $tour_id = $query_params['tour_id'] ?? null;

        if ($tour_id === null) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('no_tour_selected')
            );
            $this->showTourList();
            return;
        }

        // Set tour_id parameter for all subsequent actions
        try {
            $this->ctrl->setParameter($this, 'tour_id', $tour_id);
        } catch (ilCtrlException) {
        }

        // Add "Schritt hinzufügen" button to toolbar
        $this->toolbar->addButton(
            $this->plugin_object->txt('add_step'),
            $this->ctrl->getLinkTarget($this, self::CMD_ADDSTEP)
        );

        // Add "Tour aufnehmen" button to toolbar as primary button
        $record_button = $this->ui->factory()->button()->primary(
            $this->plugin_object->txt('record_tour'),
            $this->ctrl->getLinkTarget($this, self::CMD_STARTRECORDING)
        );
        $this->toolbar->addComponent($record_button);

        require_once __DIR__ . '/Table/GuidedTourStepsTableGUI.php';
        $table_gui = new \uzk\gtour\Table\GuidedTourStepsTableGUI($this, (int)$tour_id);
        $this->ui->mainTemplate()->setContent($table_gui->getHTML());
    }

    /**
     * Handle step table actions
     * @throws Exception
     */
    public function handleStepTableActions(): void
    {
        $query_params = $this->http->request()->getQueryParams();

        // URLBuilder creates flat parameters: step_table_action and step_ids
        $action = $query_params['step_table_action'] ?? '';
        $step_ids = $query_params['step_ids'] ?? [];
        $tour_id = $query_params['tour_id'] ?? null;

        if (!empty($step_ids) && !is_array($step_ids)) {
            $step_ids = [$step_ids];
        }
        $step_ids = array_map('intval', $step_ids);

        switch ($action) {
            case 'edit':
                if (count($step_ids) === 1 && $tour_id) {
                    $this->editStepById((int)$tour_id, $step_ids[0]);
                } else {
                    $this->showTourSteps();
                }
                break;
            case 'delete':
                $this->deleteStepsByIds($step_ids, (int)$tour_id);
                break;
            default:
                $this->showTourSteps();
        }
    }

    /**
     * Edit step by ID (for table actions)
     * @throws Exception
     */
    protected function editStepById(int $tour_id, int $step_id): void
    {
        $this->setEditTourTabs($tour_id);
        $this->tabs->activateSubTab('steps');

        $form_html = $this->buildStepForm($tour_id, $step_id);
        $this->ui->mainTemplate()->setContent($form_html);
    }

    /**
     * Delete steps by IDs
     * @throws Exception
     */
    protected function deleteStepsByIds(array $step_ids, int $tour_id): void
    {
        if (empty($step_ids)) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('no_tour_selected')
            );
        } else {
            $stepRepo = new \uzk\gtour\Data\GuidedTourStepRepository();
            foreach ($step_ids as $step_id) {
                $stepRepo->deleteStep($step_id);
            }
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                $this->plugin_object->txt('step_deleted')
            );
        }

        try {
            $this->ctrl->setParameter($this, 'tour_id', $tour_id);
            $this->ctrl->redirect($this, self::CMD_SHOWTOURSTEPS);
        } catch (ilCtrlException) {
        }
    }

    /**
     * Add new step form
     * @throws Exception
     */
    public function addStep(): void
    {
        $query_params = $this->http->request()->getQueryParams();
        $tour_id = $query_params['tour_id'] ?? null;

        if ($tour_id === null) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('no_tour_selected')
            );
            $this->showTourList();
            return;
        }

        $form_html = $this->buildStepForm((int)$tour_id, null);
        $this->ui->mainTemplate()->setContent($form_html);
    }

    /**
     * Edit existing step form
     * @throws Exception
     */
    public function editStep(): void
    {
        $query_params = $this->http->request()->getQueryParams();
        $tour_id = $query_params['tour_id'] ?? null;
        $step_id = $query_params['step_id'] ?? null;

        if ($tour_id === null || $step_id === null) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('no_tour_selected')
            );
            $this->showTourList();
            return;
        }

        $form_html = $this->buildStepForm((int)$tour_id, (int)$step_id);
        $this->ui->mainTemplate()->setContent($form_html);
    }

    /**
     * Build modern step form using ILIAS 9 UI components
     * @param int $tour_id Tour ID this step belongs to
     * @param int|null $step_id Step ID for editing, null for creating new step
     * @return string Rendered HTML form
     * @throws Exception
     */
    protected function buildStepForm(int $tour_id, ?int $step_id): string
    {
        $ui_factory = $this->ui->factory();
        $ui_renderer = $this->ui->renderer();

        // Load step data if editing
        $step = null;
        if ($step_id !== null && $step_id > 0) {
            $stepRepo = new \uzk\gtour\Data\GuidedTourStepRepository();
            $step = $stepRepo->getStepById($step_id);
            try {
                $this->ctrl->setParameter($this, 'step_id', $step_id);
            } catch (ilCtrlException) {
            }
        }

        try {
            $this->ctrl->setParameter($this, 'tour_id', $tour_id);
        } catch (ilCtrlException) {
        }

        $inputs = [];
        $modals = []; // Array to collect modals for rendering at the end

        // Basic Step Information Section
        $inputs[] = $ui_factory->input()->field()->section(
            $this->buildStepBasicInputs($step),
            $this->plugin_object->txt('step_title'),
            ''
        );

        // Step Content Section
        $inputs[] = $ui_factory->input()->field()->section(
            $this->buildStepContentInputs($step, $modals),
            $this->plugin_object->txt('step_content'),
            ''
        );

        // Step Actions Section (advanced)
        $inputs[] = $ui_factory->input()->field()->section(
            $this->buildStepActionsInputs($step),
            $this->plugin_object->txt('step_actions'),
            ''
        );

        // Create form
        $form = $ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'saveStep'),
            $inputs
        );

        $components = [$form];

        // Add any modals that were collected during form building
        // (e.g., remove rich content modal from buildStepContentInputs)
        foreach ($modals as $modal) {
            $components[] = $modal;
        }

        return $ui_renderer->render($components);
    }

    /**
     * Build basic step input fields
     * @param \uzk\gtour\Model\GuidedTourStep|null $step
     * @return array UI input components
     */
    protected function buildStepBasicInputs(?\uzk\gtour\Model\GuidedTourStep $step): array
    {
        $ui_factory = $this->ui->factory();
        $inputs = [];

        // Sort order
        $inputs['sort_order'] = $ui_factory->input()->field()->numeric(
            $this->plugin_object->txt('step_sort_order')
        )->withRequired(true)
         ->withValue($step ? $step->getSortOrder() : 0);

        // Element (CSS selector)
        $inputs['element'] = $ui_factory->input()->field()->text(
            $this->plugin_object->txt('step_element')
        )->withValue($step ? $step->getElement() : '');

        // Title
        $inputs['title'] = $ui_factory->input()->field()->text(
            $this->plugin_object->txt('step_title')
        )->withValue($step ? $step->getTitle() : '');

        // Placement selection
        $placement_options = [];
        foreach (\uzk\gtour\Model\GuidedTourStep::PLACEMENTS as $placement) {
            $placement_options[$placement] = $this->plugin_object->txt('placement_' . $placement);
        }
        $inputs['placement'] = $ui_factory->input()->field()->select(
            $this->plugin_object->txt('step_placement'),
            $placement_options
        )->withRequired(true)
         ->withValue($step ? $step->getPlacement() : 'right');

        // Orphan (floating step)
        $inputs['orphan'] = $ui_factory->input()->field()->checkbox(
            $this->plugin_object->txt('step_orphan')
        )->withValue($step ? $step->isOrphan() : false);

        return $inputs;
    }

    /**
     * Build step content input fields
     * @param \uzk\gtour\Model\GuidedTourStep|null $step
     * @param array &$modals Reference to array where modals should be stored for later rendering
     * @return array UI input components
     */
    protected function buildStepContentInputs(?\uzk\gtour\Model\GuidedTourStep $step, array &$modals = []): array
    {
        $ui_factory = $this->ui->factory();
        $inputs = [];

        // Check if step has rich content (page)
        $hasRichContent = $step && $step->getContentPageId() !== null && $step->getContentPageId() > 0;

        if ($hasRichContent) {
            // Render the formatted page content
            require_once __DIR__ . '/Page/class.ilGuidedTourStepPage.php';
            $page = new \ilGuidedTourStepPage($step->getContentPageId());

            if ($page->_exists('gtst', $step->getContentPageId())) {
                $page->read();
                $page->buildDom();
                $rendered_content = $page->getXMLContent();

                // Create edit button and remove button with modal
                $this->ctrl->setParameter($this, 'step_id', $step->getId());
                $this->ctrl->setParameter($this, 'tour_id', $step->getTourId());
                $edit_url = $this->ctrl->getLinkTarget($this, self::CMD_REDIRECTTOEDITOR);
                $remove_url = $this->ctrl->getFormAction($this, self::CMD_REMOVERICHCONTENT);

                // Create modal for remove confirmation
                $message = $this->plugin_object->txt('confirm_remove_rich_content');

                $modal = $ui_factory->modal()->interruptive(
                    $this->plugin_object->txt('remove_rich_content'),
                    $message,
                    $remove_url
                );

                // Create remove button with modal signal
                $remove_button = $ui_factory->button()->standard(
                    $this->plugin_object->txt('remove_rich_content'),
                    '#'
                )->withOnClick($modal->getShowSignal());

                // Render only the button to HTML (modal will be rendered at the end)
                $ui_renderer = $this->ui->renderer();
                $remove_button_html = $ui_renderer->render($remove_button);

                // Store modal for later rendering
                $modals[] = $modal;

                $buttons_html = '<div style="margin-top: 10px;">'
                    . '<a class="btn btn-default" href="' . htmlspecialchars($edit_url) . '">'
                    . $this->lng->txt('edit') . ' ' . $this->plugin_object->txt('step_rich_content')
                    . '</a> '
                    . $remove_button_html
                    . '</div>';

                // Display rendered content in a readonly panel
                $content_html = '<div style="border: 1px solid #ddd; padding: 15px; background: #f9f9f9; margin-bottom: 10px;">'
                    . '<strong>' . $this->plugin_object->txt('formatted_content_preview') . ':</strong><br/><br/>'
                    . $rendered_content
                    . '</div>'
                    . $buttons_html;

                // Use a disabled field to maintain compatibility with form processing
                $inputs['content'] = $ui_factory->input()->field()->text(
                    $this->plugin_object->txt('step_content'),
                    $content_html
                )->withValue('')->withDisabled(true);
            } else {
                $hasRichContent = false;
            }
        }

        if (!$hasRichContent) {
            // Content (plain textarea)
            $content_description = $this->plugin_object->txt('step_content');

            // Add link to Page Editor if step exists
            if ($step && $step->getId()) {
                // Link to redirect method which will strip conflicting parameters
                // before forwarding to the page editor
                $this->ctrl->setParameter($this, 'step_id', $step->getId());
                $this->ctrl->setParameter($this, 'tour_id', $step->getTourId());
                $page_editor_url = $this->ctrl->getLinkTarget($this, self::CMD_REDIRECTTOEDITOR);

                $content_description .= '<br/><br/><a href="' . $page_editor_url . '">'
                    . $this->lng->txt('edit') . ' ' . $this->plugin_object->txt('step_rich_content')
                    . ' &raquo;</a><br/><em>' . $this->plugin_object->txt('step_rich_content_info') . '</em>';
            }

            $inputs['content'] = $ui_factory->input()->field()->textarea(
                $this->plugin_object->txt('step_content'),
                $content_description
            )->withValue($step ? $step->getContent() : '');
        }

        return $inputs;
    }

    /**
     * Build step actions input fields (callbacks and path)
     * @param \uzk\gtour\Model\GuidedTourStep|null $step
     * @return array UI input components
     */
    protected function buildStepActionsInputs(?\uzk\gtour\Model\GuidedTourStep $step): array
    {
        $ui_factory = $this->ui->factory();
        $inputs = [];

        // JavaScript callback functions
        $inputs['on_next'] = $ui_factory->input()->field()->text(
            $this->plugin_object->txt('step_on_next')
        )->withValue($step ? $step->getOnNext() : '');

        $inputs['on_prev'] = $ui_factory->input()->field()->text(
            $this->plugin_object->txt('step_on_prev')
        )->withValue($step ? $step->getOnPrev() : '');

        $inputs['on_show'] = $ui_factory->input()->field()->text(
            $this->plugin_object->txt('step_on_show')
        )->withValue($step ? $step->getOnShow() : '');

        $inputs['on_shown'] = $ui_factory->input()->field()->text(
            $this->plugin_object->txt('step_on_shown')
        )->withValue($step ? $step->getOnShown() : '');

        $inputs['on_hide'] = $ui_factory->input()->field()->text(
            $this->plugin_object->txt('step_on_hide')
        )->withValue($step ? $step->getOnHide() : '');

        // Path for multi-page tours
        $inputs['path'] = $ui_factory->input()->field()->text(
            $this->plugin_object->txt('step_path')
        )->withValue($step ? $step->getPath() : '');

        return $inputs;
    }

    /**
     * Save step from modern form input
     * @throws Exception
     */
    public function saveStep(): void
    {
        $ui_factory = $this->ui->factory();
        $request = $this->http->request();
        $query_params = $request->getQueryParams();
        $tour_id = $query_params['tour_id'] ?? null;
        $step_id = $query_params['step_id'] ?? null;

        if ($tour_id === null) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('no_tour_selected')
            );
            $this->showTourList();
            return;
        }

        try {
            // Load step if editing
            $stepRepo = new \uzk\gtour\Data\GuidedTourStepRepository();
            $step = null;
            if ($step_id !== null && $step_id > 0) {
                $step = $stepRepo->getStepById($step_id);
            }

            // Build form structure matching buildStepForm()
            $inputs = [];
            $inputs[] = $ui_factory->input()->field()->section(
                $this->buildStepBasicInputs($step),
                $this->plugin_object->txt('step_title'),
                ''
            );
            $inputs[] = $ui_factory->input()->field()->section(
                $this->buildStepContentInputs($step),
                $this->plugin_object->txt('step_content'),
                ''
            );
            $inputs[] = $ui_factory->input()->field()->section(
                $this->buildStepActionsInputs($step),
                $this->plugin_object->txt('step_actions'),
                ''
            );

            $form = $ui_factory->input()->container()->form()->standard(
                $this->ctrl->getFormAction($this, 'saveStep'),
                $inputs
            );

            // Process form data
            $form = $form->withRequest($request);
            $data = $form->getData();

            if ($data !== null) {
                // Extract data from sections
                $basic_data = $data[0] ?? [];
                $content_data = $data[1] ?? [];
                $actions_data = $data[2] ?? [];

                // Create or load step
                if ($step_id !== null && $step_id > 0) {
                    $step = $stepRepo->getStepById($step_id);
                } else {
                    $step = new \uzk\gtour\Model\GuidedTourStep();
                    $step->setTourId((int)$tour_id);
                }

                // Set step attributes from form data
                if (isset($basic_data['sort_order'])) {
                    $step->setSortOrder((int)$basic_data['sort_order']);
                }
                if (isset($basic_data['element'])) {
                    $step->setElement($basic_data['element']);
                }
                if (isset($basic_data['title'])) {
                    $step->setTitle($basic_data['title']);
                }
                if (isset($basic_data['placement'])) {
                    $step->setPlacement($basic_data['placement']);
                }
                if (isset($basic_data['orphan'])) {
                    $step->setOrphan($basic_data['orphan']);
                }
                if (isset($content_data['content'])) {
                    $step->setContent($content_data['content']);
                }
                if (isset($actions_data['on_next'])) {
                    $step->setOnNext($actions_data['on_next']);
                }
                if (isset($actions_data['on_prev'])) {
                    $step->setOnPrev($actions_data['on_prev']);
                }
                if (isset($actions_data['on_show'])) {
                    $step->setOnShow($actions_data['on_show']);
                }
                if (isset($actions_data['on_shown'])) {
                    $step->setOnShown($actions_data['on_shown']);
                }
                if (isset($actions_data['on_hide'])) {
                    $step->setOnHide($actions_data['on_hide']);
                }
                if (isset($actions_data['path'])) {
                    $step->setPath($actions_data['path']);
                }

                // Save step
                if ($step_id !== null && $step_id > 0) {
                    $stepRepo->updateStep($step);
                } else {
                    $stepRepo->createStep($step);
                }

                $this->ui->mainTemplate()->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                    $this->plugin_object->txt('step_saved')
                );

                try {
                    $this->ctrl->setParameter($this, 'tour_id', $tour_id);
                    $this->ctrl->redirect($this, self::CMD_SHOWTOURSTEPS);
                } catch (ilCtrlException) {
                }
            } else {
                // Invalid form - show error and re-display form
                $this->ui->mainTemplate()->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                    $this->lng->txt('form_input_not_valid')
                );

                if ($step_id !== null && $step_id > 0) {
                    $this->editStepById((int)$tour_id, (int)$step_id);
                } else {
                    $this->addStep();
                }
            }
        } catch (\Exception $e) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('save_failure') . ': ' . $e->getMessage()
            );
            try {
                $this->ctrl->setParameter($this, 'tour_id', $tour_id);
                $this->ctrl->redirect($this, self::CMD_SHOWTOURSTEPS);
            } catch (ilCtrlException) {
            }
        }
    }



    /**
     * Load edit form by GET 'tour id'
     * @throws Exception
     */
    public function editTour() : void
    {
        $query_params = $this->http->request()->getQueryParams();
        $tour_id = $query_params['tour_id'] ?? null;

        if ($tour_id !== null) {
            $this->editTourById((int)$tour_id);
        } else {
            $this->showTourList();
        }
    }

    /**
     * Save tour from modern form input
     * @throws Exception
     */
    public function saveTour() : void
    {
        $ui_factory = $this->ui->factory();
        $request = $this->http->request();
        $query_params = $request->getQueryParams();
        $tourId = $query_params['tour_id'] ?? null;

        try {
            // Build form structure matching buildTourForm()
            $tour = null;
            if ($tourId !== null && $tourId > 0) {
                $tour = $this->guidedTourRepository->getTourById($tourId);
            }

            $inputs = [];

            // Section 0: Activation & Trigger Settings
            $inputs[] = $ui_factory->input()->field()->section(
                $this->buildTourActivationInputs($tour),
                $this->plugin_object->txt('tour_activation_section'),
                ''
            );

            // Section 1: Context & Target Group
            $inputs[] = $ui_factory->input()->field()->section(
                $this->buildTourContextInputs($tour),
                $this->plugin_object->txt('tour_context_section'),
                ''
            );

            // Section 2: Content (Title, Description, Scenario)
            $inputs[] = $ui_factory->input()->field()->section(
                $this->buildTourContentInputs($tour),
                $this->plugin_object->txt('tour_content_section'),
                ''
            );

            // Section 3: Script
            $inputs[] = $ui_factory->input()->field()->section(
                $this->buildTourScriptInputs($tour),
                $this->plugin_object->txt('tour_script_section'),
                ''
            );

            $form = $ui_factory->input()->container()->form()->standard(
                $this->ctrl->getFormAction($this, 'saveTour'),
                $inputs
            );

            // Process form data
            $form = $form->withRequest($request);
            $data = $form->getData();

            if ($data !== null) {
                // Extract data from sections
                $activation_data = $data[0] ?? [];  // Section 0: Activation
                $context_data = $data[1] ?? [];     // Section 1: Context
                $content_data = $data[2] ?? [];     // Section 2: Content
                $script_data = $data[3] ?? [];      // Section 3: Script

                // Create or load tour
                if ($tourId !== null && $tourId > 0) {
                    $tour = $this->guidedTourRepository->getTourById($tourId);
                } else {
                    $tour = new GuidedTour();
                }

                // Set activation attributes
                if (isset($activation_data['active'])) {
                    $tour->setActive($activation_data['active']);
                }
                if (isset($activation_data['automatic_trigger'])) {
                    $trigger_config = $activation_data['automatic_trigger'];
                    $tour->setAutomaticTriggered($trigger_config['enabled'] ?? false);
                    $tour->setTriggerMode($trigger_config['trigger_mode'] ?? GuidedTour::TRIGGER_MODE_NORMAL);
                }

                // Set context attributes
                if (isset($context_data['type'])) {
                    $tour->setType($context_data['type']);
                }
                // Use array_key_exists instead of isset to allow null values
                if (array_key_exists('ref_id', $context_data)) {
                    // Transformation already returns null or int, no further conversion needed
                    $tour->setRefId($context_data['ref_id']);
                }
                if (isset($context_data['language_code'])) {
                    $tour->setLanguageCode($context_data['language_code'] === '' ? null : $context_data['language_code']);
                }
                if (isset($context_data['roles'])) {
                    $tour->setRolesIds($context_data['roles']);
                }

                // Set content attributes
                if (isset($content_data['title'])) {
                    $tour->setTitle($content_data['title']);
                }
                if (isset($content_data['description'])) {
                    $tour->setDescription($content_data['description']);
                }
                if (isset($content_data['scenario'])) {
                    $tour->setScenario($content_data['scenario']);
                }

                // Set script
                if (isset($script_data['script'])) {
                    $tour->setScript($script_data['script']);
                }

                // Save tour
                if ($tourId !== null && $tourId > 0) {
                    $this->guidedTourRepository->updateTour($tour);
                } else {
                    $this->guidedTourRepository->createTour($tour);
                }

                $this->ui->mainTemplate()->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
                    $this->plugin_object->txt('tour_saved')
                );

                try {
                    $this->ctrl->redirect($this, 'showTourList');
                } catch (ilCtrlException) {
                }
            } else {
                // Invalid form - show error and re-display form
                $this->ui->mainTemplate()->setOnScreenMessage(
                    ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                    $this->lng->txt('form_input_not_valid')
                );

                if ($tourId !== null) {
                    $this->editTourById($tourId);
                } else {
                    $this->addTour();
                }
            }
        } catch (\Exception $e) {
            $this->ui->mainTemplate()->setOnScreenMessage(
                ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('tour_saved') . ': ' . $e->getMessage()
            );
            $this->showTourList();
        }
    }

    /**
     * Build modern tour configuration form using ILIAS 9 UI components
     * @param int|null $tour_id Tour ID for editing, null for creating new tour
     * @return string Rendered HTML form
     * @throws Exception
     */
    protected function buildTourForm(?int $tour_id): string
    {
        $ui_factory = $this->ui->factory();
        $ui_renderer = $this->ui->renderer();

        // Load tour data if editing
        $tour = null;
        if ($tour_id !== null && $tour_id > 0) {
            $tour = $this->guidedTourRepository->getTourById($tour_id);
            try {
                $this->ctrl->setParameter($this, 'tour_id', $tour_id);
            } catch (ilCtrlException) {
            }
        }

        $inputs = [];

        // Section 0: Activation & Trigger Settings
        $inputs[] = $ui_factory->input()->field()->section(
            $this->buildTourActivationInputs($tour),
            $this->plugin_object->txt('tour_activation_section'),
            ''
        );

        // Section 1: Context & Target Group
        $inputs[] = $ui_factory->input()->field()->section(
            $this->buildTourContextInputs($tour),
            $this->plugin_object->txt('tour_context_section'),
            ''
        );

        // Section 2: Content (Title, Description, Scenario)
        $inputs[] = $ui_factory->input()->field()->section(
            $this->buildTourContentInputs($tour),
            $this->plugin_object->txt('tour_content_section'),
            ''
        );

        // Section 3: Script
        $inputs[] = $ui_factory->input()->field()->section(
            $this->buildTourScriptInputs($tour),
            $this->plugin_object->txt('tour_script_section'),
            ''
        );

        // Create form
        $form = $ui_factory->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, 'saveTour'),
            $inputs
        );

        return $ui_renderer->render($form);
    }

    /**
     * Build tour content input fields (title, description, scenario)
     * @param GuidedTour|null $tour
     * @return array UI input components
     */
    protected function buildTourContentInputs(?GuidedTour $tour): array
    {
        $ui_factory = $this->ui->factory();
        $inputs = [];

        // Title
        $inputs['title'] = $ui_factory->input()->field()->text(
            $this->plugin_object->txt('tour_title'),
            $this->plugin_object->txt('tour_title_info')
        )->withRequired(true)
         ->withValue($tour ? $tour->getTitle() : '');

        // Description (public)
        $inputs['description'] = $ui_factory->input()->field()->textarea(
            $this->plugin_object->txt('tour_description'),
            $this->plugin_object->txt('tour_description_info')
        )->withValue($tour && $tour->getDescription() ? $tour->getDescription() : '');

        // Scenario (internal description for admins)
        $inputs['scenario'] = $ui_factory->input()->field()->textarea(
            $this->plugin_object->txt('tour_scenario'),
            $this->plugin_object->txt('tour_scenario_info')
        )->withValue($tour && $tour->getScenario() ? $tour->getScenario() : '');

        return $inputs;
    }

    /**
     * Build tour context and target group input fields (type, ref_id, language, roles)
     * @param GuidedTour|null $tour
     * @return array UI input components
     */
    protected function buildTourContextInputs(?GuidedTour $tour): array
    {
        $ui_factory = $this->ui->factory();
        $refinery = $this->refinery;
        $inputs = [];

        // Type selection
        $type_options = [];
        foreach (GuidedTour::getTypes() as $type) {
            $type_options[$type] = $this->plugin_object->txt('tour_type_' . $type);
        }
        $inputs['type'] = $ui_factory->input()->field()->select(
            $this->plugin_object->txt('tour_type'),
            $type_options,
            $this->plugin_object->txt('tour_type_info')
        )->withRequired(true)
         ->withValue($tour ? $tour->getType() : 'any');

        // Ref-ID (optional - bind tour to specific object)
        // Using text field instead of numeric to allow clearing the value
        $ref_id_transformation = $refinery->custom()->transformation(
            function ($value) {
                // Empty string or null -> return null
                if ($value === '' || $value === null) {
                    return null;
                }

                // Validate that it's a valid integer
                if (!is_numeric($value) || (int)$value != $value || (int)$value <= 0) {
                    throw new \ilException($this->plugin_object->txt('tour_ref_id_must_be_number'));
                }

                return (int)$value;
            }
        );

        $inputs['ref_id'] = $ui_factory->input()->field()->text(
            $this->plugin_object->txt('tour_ref_id'),
            $this->plugin_object->txt('tour_ref_id_info')
        )->withValue($tour && $tour->getRefId() ? (string)$tour->getRefId() : '')
         ->withAdditionalTransformation($ref_id_transformation);

        // Language selection
        $language_options = ['' => '- ' . $this->plugin_object->txt('all_languages') . ' -'];
        foreach ($this->lng->getInstalledLanguages() as $lang_key) {
            $language_options[$lang_key] = $this->lng->txt('meta_l_' . $lang_key);
        }
        $inputs['language_code'] = $ui_factory->input()->field()->select(
            $this->plugin_object->txt('tour_language'),
            $language_options,
            $this->plugin_object->txt('tour_language_info')
        )->withValue($tour && $tour->getLanguageCode() ? $tour->getLanguageCode() : '');

        // Roles multi-select
        $access = new ilObjMainMenuAccess();
        $role_options = $access->getGlobalRoles();

        // Get roles and ensure it's a proper numeric array (not associative)
        $selected_roles = [];
        if ($tour && !empty($tour->getRolesIds())) {
            $roles = $tour->getRolesIds();
            // Convert to numeric array if it's an associative array from json_decode
            $selected_roles = is_array($roles) ? array_values($roles) : [];
        }

        $inputs['roles'] = $ui_factory->input()->field()->multiSelect(
            $this->plugin_object->txt('tour_roles'),
            $role_options,
            $this->plugin_object->txt('tour_roles_info')
        )->withValue($selected_roles);

        return $inputs;
    }

    /**
     * Build activation and trigger settings input fields
     * @param GuidedTour|null $tour
     * @return array UI input components
     */
    protected function buildTourActivationInputs(?GuidedTour $tour): array
    {
        $ui_factory = $this->ui->factory();
        $refinery = $this->refinery;
        $inputs = [];

        // Active checkbox
        $inputs['active'] = $ui_factory->input()->field()->checkbox(
            $this->plugin_object->txt('tour_active'),
            $this->plugin_object->txt('tour_active_info')
        )->withValue($tour ? $tour->isActive() : false);

        // Automatic trigger as optional group with trigger_mode as sub-input
        $trigger_sub_inputs = [];

        // Trigger Mode (only visible when automatic trigger is enabled)
        $trigger_mode_options = [
            GuidedTour::TRIGGER_MODE_NORMAL => $this->plugin_object->txt('trigger_mode_normal'),
            GuidedTour::TRIGGER_MODE_ALWAYS => $this->plugin_object->txt('trigger_mode_always'),
            GuidedTour::TRIGGER_MODE_UNTIL_COMPLETED => $this->plugin_object->txt('trigger_mode_until_completed')
        ];
        $trigger_sub_inputs['trigger_mode'] = $ui_factory->input()->field()->select(
            $this->plugin_object->txt('tour_trigger_mode'),
            $trigger_mode_options,
            $this->plugin_object->txt('tour_trigger_mode_info')
        )->withRequired(true)
         ->withValue($tour ? $tour->getTriggerMode() : GuidedTour::TRIGGER_MODE_NORMAL);

        // Create optional group transformation to extract data properly
        $trigger_trafo = $refinery->custom()->transformation(
            static function (?array $vs): array {
                if ($vs === null) {
                    return ['enabled' => false, 'trigger_mode' => GuidedTour::TRIGGER_MODE_NORMAL];
                }

                return [
                    'enabled' => true,
                    'trigger_mode' => $vs['trigger_mode'] ?? GuidedTour::TRIGGER_MODE_NORMAL
                ];
            }
        );

        // Create the optional group
        $automatic_trigger_group = $ui_factory->input()->field()->optionalGroup(
            $trigger_sub_inputs,
            $this->plugin_object->txt('automatic_trigger'),
            $this->plugin_object->txt('tour_automatic_trigger_info')
        );

        // Set value for the optional group
        if ($tour && $tour->isAutomaticTriggered()) {
            $automatic_trigger_group = $automatic_trigger_group->withValue([
                'trigger_mode' => $tour->getTriggerMode()
            ]);
        } else {
            $automatic_trigger_group = $automatic_trigger_group->withValue(null);
        }

        $automatic_trigger_group = $automatic_trigger_group->withAdditionalTransformation($trigger_trafo);

        $inputs['automatic_trigger'] = $automatic_trigger_group;

        return $inputs;
    }

    /**
     * Build tour script input field
     * @param GuidedTour|null $tour
     * @return array UI input components
     */
    protected function buildTourScriptInputs(?GuidedTour $tour): array
    {
        $ui_factory = $this->ui->factory();
        $inputs = [];

        // Tour script (JSON or steps will be managed separately later)
        $inputs['script'] = $ui_factory->input()->field()->textarea(
            $this->plugin_object->txt('tour_script'),
            $this->plugin_object->txt('tour_script_info')
        )->withValue($tour ? $tour->getScript() : '');

        return $inputs;
    }

    /**
     * Tour configuration form (DEPRECATED - use buildTourForm instead)
     * @deprecated Use buildTourForm() with modern UI components
     * @throws Exception
     */
    public function getTourForm($a_tour_id = null) : ilPropertyFormGUI
    {
        if (isset($a_tour_id) && $a_tour_id > 0) {
            $this->tour = $this->guidedTourRepository->getTourById($a_tour_id);
            $title = $this->plugin_object->txt('edit_tour');
            try {
                $this->ctrl->setParameter($this, 'tour_id', $a_tour_id);
            } catch (ilCtrlException) {

            }
        } else {
            $this->tour = new GuidedTour();
            $title = $this->plugin_object->txt('add_tour');
        }

        $form = new ilPropertyFormGUI();
        $form->setTitle($title);
        try {
            $form->setFormAction($this->ctrl->getFormAction($this));
        } catch (ilCtrlException) {

        }

        // title
        $title = new ilTextInputGUI($this->plugin_object->txt('tour_title'), 'title');
        $title->setInfo($this->plugin_object->txt('tour_title_info'));
        $title->setRequired(true);
        $title->setValue($this->tour->getTitle());
        $form->addItem($title);

        // icon
        $icon = new ilFileInputGUI($this->plugin_object->txt('tour_icon'), 'icon');
        $icon->setInfo($this->plugin_object->txt('tour_icon_info'));
        $icon->setSuffixes(['image/svg+xml', 'svg']);
        $icon->setAllowDeletion(true);
        if (!empty($this->tour->getIconId())) {
            $icon->setFilename($this->tour->getIconId());
            $icon->setValue($this->tour->getIconTitle());
        }
        $form->addItem($icon);

        // types
        $options = [];
        foreach (guidedTour::getTypes() as $type) {
            $options[$type] = $this->plugin_object->txt('tour_type_' . $type);
        }
        $type = new ilSelectInputGUI($this->plugin_object->txt('tour_type'), 'type');
        $type->setInfo($this->plugin_object->txt('tour_type_info'));
        $type->setRequired(true);
        $type->setOptions($options);
        $type->setValue($this->tour->getType());
        $form->addItem($type);

        // tour script
        $script = new ilTextAreaInputGUI($this->plugin_object->txt('tour_script'), 'script');
        $script->setInfo($this->plugin_object->txt('tour_script_info'));
        $script->setValue($this->tour->getScript());
        $script->setUseRte(true);
        $script->setRteTagSet("full");
        $form->addItem($script);

        // activation
        $active = new ilCheckboxInputGUI($this->plugin_object->txt('tour_active'), 'active');
        $active->setInfo($this->plugin_object->txt('tour_active_info'));
        $active->setChecked($this->tour->isActive());
        $form->addItem($active);

        // automatic trigger
        $autotrigger = new ilCheckboxInputGUI($this->plugin_object->txt('automatic_trigger'), 'automatic_trigger');
        $autotrigger->setInfo($this->plugin_object->txt('tour_automatic_trigger_info'));
        $autotrigger->setChecked($this->tour->isAutomaticTriggered());
        $form->addItem($autotrigger);

        // roles
        $access = new ilObjMainMenuAccess();
        $roles = new ilMultiSelectInputGUI($this->plugin_object->txt('tour_roles'), 'roles');
        $roles->setOptions($access->getGlobalRoles());
        $roles->setInfo($this->plugin_object->txt('tour_roles_info'));
        if (!empty($this->tour->getRolesIds())) {
            $roles->setValue($this->tour->getRolesIds());
            $roles->enableSelectAll(true);
        }
        $form->addItem($roles);

        // command buttons
        $form->addCommandButton('saveTour', $this->lng->txt('save'));
        $form->addCommandButton('showTourList', $this->lng->txt('cancel'));

        return $form;
    }

    /**
     * Redirect to page editor with clean URL (without plugin path parameters)
     * This prevents the page editor from interpreting ctype/cname as Page Component types
     *
     * @return void
     * @throws Exception
     */
    protected function redirectToPageEditor(): void
    {
        // Get parameters
        $query_params = $this->http->request()->getQueryParams();
        $step_id = (int)($query_params['step_id'] ?? 0);
        $tour_id = (int)($query_params['tour_id'] ?? 0);

        if ($step_id <= 0 || $tour_id <= 0) {
            $this->tpl->setOnScreenMessage('failure', 'Invalid step or tour ID', true);
            $this->ctrl->redirect($this, self::CMD_SHOWTOURSTEPS);
        }

        // Load step
        $stepRepo = new \uzk\gtour\Data\GuidedTourStepRepository();
        $step = $stepRepo->getStepById($step_id);

        if (!$step) {
            $this->tpl->setOnScreenMessage('failure', 'Step not found', true);
            $this->ctrl->redirect($this, self::CMD_SHOWTOURSTEPS);
        }

        // Get or create page - use step_id as page_id for simplicity
        $page_id = $step->getContentPageId();

        if ($page_id === null || $page_id <= 0) {
            // Create new page for this step using step_id as page_id
            require_once __DIR__ . '/Page/class.ilGuidedTourStepPage.php';

            // Check if page already exists with this step_id
            // Use 'lm' as parent_type since it's supported by all Page Component Plugins
            if (!\ilGuidedTourStepPage::_exists('gtst', $step_id)) {
                $page_object = new \ilGuidedTourStepPage();
                $page_object->setParentId($step->getTourId());
                $page_object->setId($step_id);
                $page_object->createFromXML();
            }

            $page_id = $step_id;
            $step->setContentPageId($page_id);
            $stepRepo->updateStep($step);
        }

        // Redirect to page editor
        // The URL will contain plugin path parameters (ctype, cname, slot_id)
        // but our custom ilGuidedTourPageEditorGUI with ilGuidedTourEditGUIRequest
        // filters them out so the page editor doesn't see them as PC types
        $this->ctrl->setParameter($this, 'step_id', $step_id);
        $this->ctrl->setParameter($this, 'tour_id', $tour_id);

        $this->ctrl->redirectByClass(
            'ilGuidedTourStepPageGUI',
            'edit'
        );
    }

    /**
     * Remove rich content (page) from a step and return to plain text editing
     *
     * @return void
     */
    protected function removeRichContent(): void
    {
        // Get parameters
        $query_params = $this->http->request()->getQueryParams();
        $step_id = (int)($query_params['step_id'] ?? 0);
        $tour_id = (int)($query_params['tour_id'] ?? 0);

        if ($step_id <= 0 || $tour_id <= 0) {
            $this->tpl->setOnScreenMessage('failure', 'Invalid step or tour ID', true);
            $this->ctrl->redirect($this, self::CMD_SHOWTOURSTEPS);
            return;
        }

        // Load step
        $stepRepo = new \uzk\gtour\Data\GuidedTourStepRepository();
        $step = $stepRepo->getStepById($step_id);

        if (!$step) {
            $this->tpl->setOnScreenMessage('failure', 'Step not found', true);
            $this->ctrl->redirect($this, self::CMD_SHOWTOURSTEPS);
            return;
        }

        $page_id = $step->getContentPageId();

        // Delete the page if it exists
        if ($page_id !== null && $page_id > 0) {
            require_once __DIR__ . '/Page/class.ilGuidedTourStepPage.php';

            if (\ilGuidedTourStepPage::_exists('gtst', $page_id)) {
                $page = new \ilGuidedTourStepPage($page_id);
                $page->delete();
            }

            // Remove page reference from step
            $step->setContentPageId(null);
            $stepRepo->updateStep($step);

            $this->tpl->setOnScreenMessage('success', $this->plugin_object->txt('rich_content_removed'), true);
        }

        // Redirect back to step editing
        $this->ctrl->setParameter($this, 'step_id', $step_id);
        $this->ctrl->setParameter($this, 'tour_id', $tour_id);
        $this->ctrl->redirect($this, self::CMD_EDITSTEP);
    }

    /**
     * Forward to page editor GUI for editing step rich content
     * This is called AFTER the redirect with clean parameters
     *
     * @return void
     * @throws Exception
     */
    protected function forwardToPageEditor(): void
    {
        // Get parameters
        $query_params = $this->http->request()->getQueryParams();
        $step_id = (int)($query_params['step_id'] ?? 0);
        $tour_id = (int)($query_params['tour_id'] ?? 0);

        if ($step_id <= 0 || $tour_id <= 0) {
            $this->tpl->setOnScreenMessage('failure', 'Invalid step or tour ID');
            $this->ctrl->redirect($this, self::CMD_SHOWTOURSTEPS);
        }

        // Load step to get page ID
        $stepRepo = new \uzk\gtour\Data\GuidedTourStepRepository();
        $step = $stepRepo->getStepById($step_id);

        if (!$step || !$step->getContentPageId()) {
            $this->tpl->setOnScreenMessage('failure', 'Page not found');
            $this->ctrl->redirect($this, self::CMD_SHOWTOURSTEPS);
        }

        $page_id = $step->getContentPageId();

        // Preserve tour_id and step_id for return navigation to edit form
        $this->ctrl->setParameter($this, 'tour_id', $tour_id);
        $this->ctrl->setParameter($this, 'step_id', $step_id);

        // Build explicit return URL with tour_id and step_id to return to step edit form
        $return_url = $this->ctrl->getLinkTarget($this, self::CMD_EDITSTEP);

        // Set up tabs with back link
        $this->tabs->clearTargets();
        $this->tabs->setBackTarget(
            $this->lng->txt('back'),
            $return_url
        );

        // IMPORTANT: Set return path BEFORE forwarding to page editor
        // This tells ilCtrl where to return when page editing is finished (step edit form)
        $this->ctrl->setReturnByClass(self::class, self::CMD_EDITSTEP);
        $this->ctrl->saveParameterByClass(self::class, 'tour_id');
        $this->ctrl->saveParameterByClass(self::class, 'step_id');

        // Create page GUI - uses 'lm' parent type for wide Page Component Plugin support
        require_once __DIR__ . '/Page/class.ilGuidedTourStepPageGUI.php';
        $page_gui = new \ilGuidedTourStepPageGUI('gtst', $page_id);

        // Set explicit return URL on page GUI
        $page_gui->setReturnUrl($return_url);

        // Preserve our custom parameters
        $this->ctrl->saveParameter($page_gui, 'step_id');
        $this->ctrl->saveParameter($page_gui, 'tour_id');

        // Forward command to page GUI
        $html = $this->ctrl->forwardCommand($page_gui);

        // Render
        $this->tpl->setContent($html);
    }

    /**
     * Start or resume recording mode for a tour
     * @throws Exception
     */
    protected function startRecording(): void
    {
        $query_params = $this->http->request()->getQueryParams();
        $tour_id = (int)($query_params['tour_id'] ?? 0);

        if ($tour_id <= 0) {
            $this->tpl->setOnScreenMessage('failure', 'Invalid tour ID');
            $this->showTourList();
            return;
        }

        // Initialize or resume recording session
        require_once __DIR__ . '/Recording/class.ilGuidedTourRecordingSettings.php';
        $recordingSettings = ilGuidedTourRecordingSettings::getInstance();

        if ($recordingSettings->getTourId() === $tour_id && $recordingSettings->hasRecordedSteps()) {
            // Resume existing recording
            $recordingSettings->resumeRecording();
        } else {
            // Start new recording
            $recordingSettings->startRecording($tour_id);
        }

        // Render recording frame
        $this->ctrl->setParameter($this, 'tour_id', $tour_id);
        $this->renderRecordingFrame($tour_id);
    }

    /**
     * Pause recording (stop capturing but keep steps)
     * @throws Exception
     */
    protected function pauseRecording(): void
    {
        require_once __DIR__ . '/Recording/class.ilGuidedTourRecordingSettings.php';
        $recordingSettings = ilGuidedTourRecordingSettings::getInstance();

        $tour_id = $recordingSettings->getTourId();
        $recordingSettings->pauseRecording();

        if ($tour_id) {
            $this->ctrl->setParameter($this, 'tour_id', $tour_id);
            $this->renderRecordingFrame($tour_id);
        } else {
            $this->showTourList();
        }
    }

    /**
     * Discard all recorded steps
     * @throws Exception
     */
    protected function discardRecording(): void
    {
        require_once __DIR__ . '/Recording/class.ilGuidedTourRecordingSettings.php';
        $recordingSettings = ilGuidedTourRecordingSettings::getInstance();

        $tour_id = $recordingSettings->getTourId();
        $recordingSettings->discardSteps();
        $recordingSettings->pauseRecording();

        if ($tour_id) {
            $this->tpl->setOnScreenMessage('info', $this->plugin_object->txt('record_discarded'));
            $this->ctrl->setParameter($this, 'tour_id', $tour_id);
            $this->renderRecordingFrame($tour_id);
        } else {
            $this->showTourList();
        }
    }

    /**
     * Add a single step to the recording session (AJAX endpoint)
     */
    protected function addRecordingStep(): void
    {
        // Clear any output buffers to prevent non-JSON output
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json');

        try {
            require_once __DIR__ . '/Recording/class.ilGuidedTourRecordingSettings.php';
            $recordingSettings = ilGuidedTourRecordingSettings::getInstance();

            // Check if recording is active
            if (!$recordingSettings->isActive()) {
                echo json_encode(['success' => false, 'message' => 'Recording not active']);
                exit;
            }

            // Get step data from POST
            $request_body = $this->http->request()->getBody()->getContents();
            $step_data = json_decode($request_body, true);

            if (!$step_data) {
                echo json_encode(['success' => false, 'message' => 'Invalid step data']);
                exit;
            }

            // Add step to session
            $recordingSettings->addRecordedStep($step_data);

            echo json_encode([
                'success' => true,
                'message' => 'Step added to session',
                'step_count' => count($recordingSettings->getRecordedSteps())
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        exit;
    }

    /**
     * Sync all recording steps to session (AJAX endpoint for updates/deletes)
     */
    protected function syncRecordingSteps(): void
    {
        try {
            // Clear ALL output buffers aggressively
            while (@ob_end_clean());

            // Start fresh output buffer
            ob_start();

            require_once __DIR__ . '/Recording/class.ilGuidedTourRecordingSettings.php';
            $recordingSettings = ilGuidedTourRecordingSettings::getInstance();

            // Get steps from POST data
            $request_body = $this->http->request()->getBody()->getContents();
            $data = json_decode($request_body, true);

            if (!isset($data['steps']) || !is_array($data['steps'])) {
                $response = json_encode(['success' => false, 'message' => 'Invalid steps data']);
            } else {
                // Clear existing steps and add all new ones
                $recordingSettings->discardSteps();
                foreach ($data['steps'] as $step) {
                    $recordingSettings->addRecordedStep($step);
                }

                $response = json_encode([
                    'success' => true,
                    'message' => 'Steps synced to session',
                    'step_count' => count($recordingSettings->getRecordedSteps())
                ]);
            }
        } catch (Exception $e) {
            $response = json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        // Clear buffer, send headers, output JSON and die
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Length: ' . strlen($response));
        echo $response;
        die();
    }

    /**
     * Save recorded steps to database and exit recording mode
     */
    protected function saveAndExitRecording(): void
    {
        // Clear any output buffers to prevent non-JSON output
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json');

        try {
            require_once __DIR__ . '/Recording/class.ilGuidedTourRecordingSettings.php';
            $recordingSettings = ilGuidedTourRecordingSettings::getInstance();

            // Check if we have a tour_id (recording session exists)
            if (!$recordingSettings->getTourId()) {
                echo json_encode(['success' => false, 'message' => 'No recording session']);
                exit;
            }

            // Get steps from POST data
            $request_body = $this->http->request()->getBody()->getContents();
            $data = json_decode($request_body, true);

            if (!isset($data['steps']) || !is_array($data['steps'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid data: ' . json_last_error_msg()]);
                exit;
            }

            $tour_id = $recordingSettings->getTourId();
            if (!$tour_id) {
                echo json_encode(['success' => false, 'message' => 'No tour ID in session']);
                exit;
            }

            $stepRepo = new \uzk\gtour\Data\GuidedTourStepRepository();

            // Get current max sort order for this tour
            $existing_steps = $stepRepo->getStepsByTourId($tour_id);
            $max_sort = 0;
            foreach ($existing_steps as $step) {
                if ($step->getSortOrder() > $max_sort) {
                    $max_sort = $step->getSortOrder();
                }
            }

            // Save each recorded step
            $saved_count = 0;
            foreach ($data['steps'] as $step_data) {
                $max_sort++;

                // Generate onNext JavaScript if click action is registered
                $onNext = '';
                if (!empty($step_data['popover_on_next_click']) && !empty($step_data['element'])) {
                    // Generate JavaScript code to click the element when "Next" is pressed
                    // Use il.Plugins.GuidedTour.findElement() to support both internal IDs and CSS selectors
                    $onNext = sprintf(
                        "const targetElement = il.Plugins.GuidedTour.findElement('%s'); if (targetElement) { targetElement.click(); }",
                        addslashes($step_data['element'])
                    );
                }

                $step = new \uzk\gtour\Model\GuidedTourStep(
                    id: 0,
                    tourId: $tour_id,
                    sortOrder: $max_sort,
                    element: $step_data['element'] ?? '',
                    title: $step_data['title'] ?? 'Step ' . $max_sort,
                    content: $step_data['content'] ?? '',
                    contentPageId: null,
                    placement: $step_data['placement'] ?? 'right',
                    orphan: false,
                    onNext: $onNext,
                    onPrev: '',
                    onShow: '',
                    onShown: '',
                    onHide: '',
                    path: $step_data['path'] ?? '',
                    elementType: $step_data['element_type'] ?? null,
                    elementName: $step_data['element_name'] ?? null
                );

                $stepRepo->createStep($step);
                $saved_count++;
            }

            // Stop recording
            $recordingSettings->stopRecording();

            // Build redirect URL
            $this->ctrl->setParameter($this, 'tour_id', $tour_id);
            $redirect_url = $this->ctrl->getLinkTarget($this, self::CMD_SHOWTOURSTEPS);

            echo json_encode([
                'success' => true,
                'message' => sprintf('%d steps saved', $saved_count),
                'redirect_url' => $redirect_url
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        exit;
    }

    /**
     * Render the recording frame overlay
     * @param int $tour_id
     * @throws Exception
     */
    protected function renderRecordingFrame(int $tour_id): void
    {
        // Get recording settings
        require_once __DIR__ . '/Recording/class.ilGuidedTourRecordingSettings.php';
        $recordingSettings = ilGuidedTourRecordingSettings::getInstance();

        // Load recording template
        $tpl = new ilTemplate(__DIR__ . '/../templates/recording_frame.html', true, true);

        // Set template variables
        $tpl->setVariable('RECORD_INSTRUCTION', $this->plugin_object->txt('record_instruction'));
        $tpl->setVariable('RECORD_START', $this->plugin_object->txt('record_start'));
        $tpl->setVariable('RECORD_RESUME', $this->plugin_object->txt('record_resume'));
        $tpl->setVariable('RECORD_PAUSE', $this->plugin_object->txt('record_pause'));
        $tpl->setVariable('RECORD_DISCARD', $this->plugin_object->txt('record_discard'));
        $tpl->setVariable('RECORD_SAVE_AND_EXIT', $this->plugin_object->txt('record_save_and_exit'));
        $tpl->setVariable('RECORD_BACK_TO_EDIT', $this->plugin_object->txt('record_back_to_edit'));
        $tpl->setVariable('RECORD_ACTIVE', $this->plugin_object->txt('record_active'));
        $tpl->setVariable('RECORD_PAUSED', $this->plugin_object->txt('record_paused'));
        $tpl->setVariable('RECORD_RESUMED', $this->plugin_object->txt('record_resumed'));
        $tpl->setVariable('RECORD_STEPS_IN_MEMORY', $this->plugin_object->txt('record_steps_in_memory'));
        $tpl->setVariable('RECORD_STEP_CAPTURED', $this->plugin_object->txt('record_step_captured'));
        $tpl->setVariable('RECORD_SHOW_STEPS', $this->plugin_object->txt('record_show_steps'));
        $tpl->setVariable('RECORD_HIDE_STEPS', $this->plugin_object->txt('record_hide_steps'));
        $tpl->setVariable('RECORD_STEPS_LIST', $this->plugin_object->txt('record_steps_list'));
        $tpl->setVariable('RECORD_DELETE_STEP', $this->plugin_object->txt('record_delete_step'));
        $tpl->setVariable('RECORD_CONFIRM_DISCARD', $this->plugin_object->txt('record_confirm_discard'));
        $tpl->setVariable('RECORD_CLICK_REGISTERED', $this->plugin_object->txt('record_click_registered'));
        $tpl->setVariable('RECORD_CLICK_HINT', $this->plugin_object->txt('record_click_hint'));

        // Set URLs
        $this->ctrl->setParameter($this, 'tour_id', $tour_id);
        $save_url = $this->ctrl->getLinkTarget($this, self::CMD_SAVEANDEXITRECORDING, '', true);
        $add_step_url = $this->ctrl->getLinkTarget($this, self::CMD_ADDRECORDINGSTEP, '', true);
        $pause_url = $this->ctrl->getLinkTarget($this, self::CMD_PAUSERECORDING);
        $discard_url = $this->ctrl->getLinkTarget($this, self::CMD_DISCARDRECORDING);
        $back_url = $this->ctrl->getLinkTarget($this, self::CMD_SHOWTOURSTEPS);

        $tpl->setVariable('SAVE_STEPS_URL', $save_url);
        $tpl->setVariable('ADD_STEP_URL', $add_step_url);
        $tpl->setVariable('PAUSE_URL', $pause_url);
        $tpl->setVariable('DISCARD_URL', $discard_url);
        $tpl->setVariable('BACK_URL', $back_url);
        $tpl->setVariable('TOUR_ID', $tour_id);
        $tpl->setVariable('RECORDING_ACTIVE', $recordingSettings->isActive() ? '1' : '0');
        $tpl->setVariable('HAS_STEPS', $recordingSettings->hasRecordedSteps() ? '1' : '0');
        $tpl->setVariable('RECORDED_STEPS_JSON', htmlspecialchars(json_encode($recordingSettings->getRecordedSteps()), ENT_QUOTES, 'UTF-8'));

        // Add JavaScript for recording
        $this->tpl->addJavaScript($this->plugin_object->getDirectory() . '/templates/js/recording.js');
        $this->tpl->addCss($this->plugin_object->getDirectory() . '/templates/css/recording.css');

        // Add inline JavaScript to set iframe src after load
        $dashboard_url = ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilDashboardGUI';
        $this->tpl->addOnLoadCode("
            document.getElementById('gtour-content-frame').src = '" . $dashboard_url . "';
        ");

        $this->tpl->setContent($tpl->get());
    }

    /**
     * Show tour usage statistics
     * @throws Exception
     */
    protected function showStatistics(): void
    {
        require_once __DIR__ . '/Data/GuidedTourUserFinishedRepository.php';
        $usageRepo = new \uzk\gtour\Data\GuidedTourUserFinishedRepository();

        // Get selected tour from request (both POST and GET)
        $request = $this->http->request();
        $post_params = $request->getParsedBody();
        $query_params = $request->getQueryParams();
        $selected_tour_id = $post_params['tour_id'] ?? $query_params['tour_id'] ?? 'all';

        // Create tour selector
        $tour_selector = $this->createTourSelector($selected_tour_id);

        $panels = [];

        if ($selected_tour_id === 'all') {
            // Show comparison view for all tours
            $panels = $this->createTourComparisonView($usageRepo);
        } else {
            // Show detailed view for selected tour
            $panels = $this->createTourDetailView($usageRepo, (int)$selected_tour_id);
        }

        // Render tour selector and panels
        $content = [
            $tour_selector,
            $this->ui->renderer()->render($panels)
        ];

        $this->tpl->setContent(implode('', $content));
    }

    /**
     * Create tour selector dropdown
     * @param string $selectedTourId
     * @return string
     * @throws Exception
     */
    protected function createTourSelector(string $selectedTourId): string
    {
        $tours = $this->guidedTourRepository->getTours();

        $tour_options = ['all' => $this->plugin_object->txt('stats_all_tours_comparison')];
        foreach ($tours as $tour) {
            $tour_options[$tour->getId()] = $tour->getTitle();
        }

        // Create modern ILIAS 9 UI select input
        $select_input = $this->ui->factory()->input()->field()->select(
            $this->plugin_object->txt('stats_select_tour'),
            $tour_options
        )->withValue($selectedTourId);

        // Create form with the select input
        $form = $this->ui->factory()->input()->container()->form()->standard(
            $this->ctrl->getFormAction($this, self::CMD_SHOWSTATISTICS),
            ['tour_id' => $select_input]
        );

        // Handle form submission
        $request = $this->http->request();
        if ($request->getMethod() === 'POST') {
            $form = $form->withRequest($request);
            $result = $form->getData();
            if ($result !== null && isset($result['tour_id'])) {
                // Redirect with new tour ID to avoid POST resubmission
                $this->ctrl->setParameter($this, 'tour_id', $result['tour_id']);
                $this->ctrl->redirect($this, self::CMD_SHOWSTATISTICS);
            }
        }

        return $this->ui->renderer()->render($form);
    }

    /**
     * Create comparison view for all tours
     * @param \uzk\gtour\Data\GuidedTourUserFinishedRepository $usageRepo
     * @return array
     * @throws Exception
     */
    protected function createTourComparisonView(\uzk\gtour\Data\GuidedTourUserFinishedRepository $usageRepo): array
    {
        $panels = [];
        $all_stats = $usageRepo->getAllToursStatistics();
        $tours = $this->guidedTourRepository->getTours();

        if (empty($all_stats)) {
            $panels[] = $this->ui->factory()->messageBox()->info(
                $this->plugin_object->txt('stats_no_data')
            );
            return $panels;
        }

        // Create comparison data for charts
        $tour_names = [];
        $starts_data = [];
        $completion_data = [];
        $avg_step_data = [];

        foreach ($tours as $tour) {
            $tour_id = $tour->getId();
            if (isset($all_stats[$tour_id])) {
                $stats = $all_stats[$tour_id];
                $tour_names[$tour_id] = $tour->getTitle();
                $starts_data[$tour_id] = $stats['total_starts'];

                // Calculate completion rate percentage (based on unique users, not runs)
                $completion_rate = $stats['unique_users'] > 0
                    ? round(($stats['users_completed'] / $stats['unique_users']) * 100, 1)
                    : 0;
                $completion_data[$tour_id] = $completion_rate;
                $avg_step_data[$tour_id] = round($stats['avg_step_reached'], 1);
            }
        }

        // Total starts comparison chart
        if (!empty($starts_data)) {
            $chart = $this->createComparisonChart(
                $tour_names,
                $starts_data,
                $this->plugin_object->txt('stats_total_starts')
            );
            $panels[] = $this->ui->factory()->panel()->standard(
                $this->plugin_object->txt('stats_total_starts_comparison'),
                [$chart]
            );
        }

        // Completion rate comparison chart
        if (!empty($completion_data)) {
            $chart = $this->createComparisonChart(
                $tour_names,
                $completion_data,
                $this->plugin_object->txt('stats_completion_rate')
            );
            $panels[] = $this->ui->factory()->panel()->standard(
                $this->plugin_object->txt('stats_completion_rate_comparison'),
                [$chart]
            );
        }

        // Average step reached comparison chart
        if (!empty($avg_step_data)) {
            $chart = $this->createComparisonChart(
                $tour_names,
                $avg_step_data,
                $this->plugin_object->txt('stats_avg_step_reached')
            );
            $panels[] = $this->ui->factory()->panel()->standard(
                $this->plugin_object->txt('stats_avg_step_comparison'),
                [$chart]
            );
        }

        // Detailed comparison table
        $table_content = $this->createComparisonTable($tours, $all_stats);
        $panels[] = $this->ui->factory()->panel()->standard(
            $this->plugin_object->txt('stats_detailed_comparison'),
            [$this->ui->factory()->legacy($table_content)]
        );

        return $panels;
    }

    /**
     * Create detailed view for a single tour
     * @param \uzk\gtour\Data\GuidedTourUserFinishedRepository $usageRepo
     * @param int $tour_id
     * @return array
     * @throws Exception
     */
    protected function createTourDetailView(\uzk\gtour\Data\GuidedTourUserFinishedRepository $usageRepo, int $tour_id): array
    {
        $panels = [];
        $stats = $usageRepo->getTourStatistics($tour_id);
        $tour = $this->guidedTourRepository->getTourById($tour_id);

        if (!$tour) {
            $panels[] = $this->ui->factory()->messageBox()->failure(
                $this->plugin_object->txt('stats_tour_not_found')
            );
            return $panels;
        }

        // Add reset button if there's data
        if ($stats['total_starts'] > 0) {
            $this->ctrl->setParameter($this, 'tour_id', $tour_id);
            $reset_button = $this->ui->factory()->button()->standard(
                $this->plugin_object->txt('stats_reset_button'),
                $this->ctrl->getLinkTarget($this, self::CMD_RESETTOURSTATISTICS)
            );
            $this->toolbar->addComponent($reset_button);
        }

        if ($stats['total_starts'] === 0) {
            $panels[] = $this->ui->factory()->messageBox()->info(
                $this->plugin_object->txt('stats_no_data_for_tour')
            );
            return $panels;
        }

        // Overview Statistics
        // Completion rate = percentage of users who completed at least once
        $completion_rate = $stats['unique_users'] > 0
            ? round(($stats['users_completed'] / $stats['unique_users']) * 100, 1)
            : 0;

        $overview_items = [
            $this->plugin_object->txt('stats_total_starts') => (string)$stats['total_starts'],
            $this->plugin_object->txt('stats_unique_users') => (string)$stats['unique_users'],
            $this->plugin_object->txt('stats_completed') => $stats['users_completed'] . ' (' . $completion_rate . '%)',
            $this->plugin_object->txt('stats_partial') => (string)$stats['partial_count'],
            $this->plugin_object->txt('stats_avg_step_reached') => (string)round($stats['avg_step_reached']),
            $this->plugin_object->txt('stats_first_usage') => $stats['first_usage'] ? date('Y-m-d H:i', $stats['first_usage']) : '-',
            $this->plugin_object->txt('stats_last_usage') => $stats['last_usage'] ? date('Y-m-d H:i', $stats['last_usage']) : '-',
        ];

        $panels[] = $this->ui->factory()->panel()->standard(
            $this->plugin_object->txt('stats_overview') . ': ' . $tour->getTitle(),
            [$this->ui->factory()->listing()->descriptive($overview_items)]
        );

        // Recent activity chart
        $activity_data = [
            $this->plugin_object->txt('stats_last_7_days') => $stats['usage_last_7_days'],
            $this->plugin_object->txt('stats_last_30_days') => $stats['usage_last_month'],
        ];

        $chart = $this->createActivityChart($activity_data);
        $panels[] = $this->ui->factory()->panel()->standard(
            $this->plugin_object->txt('stats_recent_activity'),
            [$chart]
        );

        // Completion vs Partial chart
        $completion_chart_data = [
            $this->plugin_object->txt('stats_completed') => $stats['completed_count'],
            $this->plugin_object->txt('stats_partial') => $stats['partial_count'],
        ];

        $chart = $this->createActivityChart($completion_chart_data);
        $panels[] = $this->ui->factory()->panel()->standard(
            $this->plugin_object->txt('stats_completion_breakdown'),
            [$chart]
        );

        return $panels;
    }

    /**
     * Create comparison chart for multiple tours
     * @param array $tour_names
     * @param array $data
     * @param string $label
     * @return \ILIAS\UI\Component\Chart\Bar\Bar
     */
    protected function createComparisonChart(array $tour_names, array $data, string $label): \ILIAS\UI\Component\Chart\Bar\Bar
    {
        $c_dimension = $this->data_factory->dimension()->cardinal();
        $dataset = $this->data_factory->dataset([$label => $c_dimension]);

        foreach ($data as $tour_id => $value) {
            $tour_name = $tour_names[$tour_id] ?? 'Tour ' . $tour_id;
            $dataset = $dataset->withPoint($tour_name, [$label => $value]);
        }

        return $this->ui->factory()->chart()->bar()->horizontal($label, $dataset);
    }

    /**
     * Create activity chart
     * @param array $data
     * @return \ILIAS\UI\Component\Chart\Bar\Bar
     */
    protected function createActivityChart(array $data): \ILIAS\UI\Component\Chart\Bar\Bar
    {
        $c_dimension = $this->data_factory->dimension()->cardinal();
        $count_label = $this->plugin_object->txt('stats_count');
        $dataset = $this->data_factory->dataset([$count_label => $c_dimension]);

        foreach ($data as $label => $count) {
            $dataset = $dataset->withPoint($label, [$count_label => $count]);
        }

        return $this->ui->factory()->chart()->bar()->horizontal(
            $this->plugin_object->txt('stats_activity'),
            $dataset
        );
    }

    /**
     * Create comparison table HTML
     * @param array $tours
     * @param array $all_stats
     * @return string
     */
    protected function createComparisonTable(array $tours, array $all_stats): string
    {
        $html = '<table class="table table-striped" style="width: 100%; border-collapse: collapse;">';
        $html .= '<thead><tr style="background: #f8f9fa;">';
        $html .= '<th style="padding: 12px; border-bottom: 2px solid #dee2e6;">' . $this->plugin_object->txt('tour_title') . '</th>';
        $html .= '<th style="padding: 12px; border-bottom: 2px solid #dee2e6; text-align: right;">' . $this->plugin_object->txt('stats_total_starts') . '</th>';
        $html .= '<th style="padding: 12px; border-bottom: 2px solid #dee2e6; text-align: right;">' . $this->plugin_object->txt('stats_unique_users') . '</th>';
        $html .= '<th style="padding: 12px; border-bottom: 2px solid #dee2e6; text-align: right;">' . $this->plugin_object->txt('stats_completion_rate') . '</th>';
        $html .= '<th style="padding: 12px; border-bottom: 2px solid #dee2e6; text-align: right;">' . $this->plugin_object->txt('stats_avg_step_reached') . '</th>';
        $html .= '<th style="padding: 12px; border-bottom: 2px solid #dee2e6; text-align: center;">' . $this->plugin_object->txt('stats_last_7_days') . '</th>';
        $html .= '<th style="padding: 12px; border-bottom: 2px solid #dee2e6; text-align: center;">' . $this->plugin_object->txt('stats_last_30_days') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($tours as $tour) {
            $tour_id = $tour->getId();
            $stats = $all_stats[$tour_id] ?? null;

            if (!$stats || $stats['total_starts'] === 0) {
                continue;
            }

            // Calculate completion rate based on unique users, not runs
            $completion_rate = $stats['unique_users'] > 0
                ? round(($stats['users_completed'] / $stats['unique_users']) * 100, 1)
                : 0;

            $html .= '<tr>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #dee2e6;"><strong>' . htmlspecialchars($tour->getTitle()) . '</strong></td>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right;">' . $stats['total_starts'] . '</td>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right;">' . $stats['unique_users'] . '</td>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right;">' . $completion_rate . '%</td>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: right;">' . round($stats['avg_step_reached'], 1) . '</td>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">' . $stats['usage_last_7_days'] . '</td>';
            $html .= '<td style="padding: 10px; border-bottom: 1px solid #dee2e6; text-align: center;">' . $stats['usage_last_month'] . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Reset tour statistics (delete all usage data for a tour)
     * @throws Exception
     */
    protected function resetTourStatistics(): void
    {
        require_once __DIR__ . '/Data/GuidedTourUserFinishedRepository.php';
        $usageRepo = new \uzk\gtour\Data\GuidedTourUserFinishedRepository();

        $query_params = $this->http->request()->getQueryParams();
        $tour_id = $query_params['tour_id'] ?? null;

        if ($tour_id === null || $tour_id === 'all') {
            $this->tpl->setOnScreenMessage(
                \ilGlobalTemplateInterface::MESSAGE_TYPE_FAILURE,
                $this->plugin_object->txt('stats_reset_failed_no_tour')
            );
            $this->ctrl->redirect($this, self::CMD_SHOWSTATISTICS);
            return;
        }

        $tour_id = (int)$tour_id;

        // Reset all usage data for this tour
        $usageRepo->resetTour($tour_id);

        $this->tpl->setOnScreenMessage(
            \ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS,
            $this->plugin_object->txt('stats_reset_success')
        );

        $this->ctrl->setParameter($this, 'tour_id', $tour_id);
        $this->ctrl->redirect($this, self::CMD_SHOWSTATISTICS);
    }
}
