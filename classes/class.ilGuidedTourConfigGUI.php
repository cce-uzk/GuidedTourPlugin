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

    protected ilCtrl $ctrl;
    protected ilLanguage $lng;
    protected ilTabsGUI $tabs;
    protected UIServices $ui;
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
        $this->ui = $DIC->ui();

        // Repositories
        $this->guidedTourRepository = new GuidedTourRepository();

        $this->setPluginObject(ilGuidedTourPlugin::getInstance());
    }

    /**
     * @throws Exception
     */
    function performCommand(string $cmd) : void
    {
        switch ($this->ctrl->getCmd(self::CMD_SHOWTOURLIST)) {
            case self::CMD_CONFIGURE:
                $this->configure();
                break;
            case self::CMD_SHOWTOURLIST:
                $this->showTourList();
                break;
            case self::CMD_ACTIVATETOUR:
                $this->activateTour();
                break;
            case self::CMD_DEACTIVATETOUR:
                $this->deactivateTour();
                break;
            case self::CMD_CONFIRMDELETETOUR:
                $this->confirmDeleteTour();
                break;
            case self::CMD_ADDTOUR:
                $this->addTour();
                break;
            case self::CMD_EDITTOUR:
                $this->editTour();
                break;
            case self::CMD_SAVETOUR:
                $this->saveTour();
                break;
        }
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
        $table_gui = new GuidedTourConfigToursTable($this, "configure");
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

        if (isset($_POST['tour_id'])) {
            $tour_ids = (array) $_POST['tour_id'];
        } elseif (isset($_GET['tour_id'])) {
            $tour_ids = (array) $_GET['tour_id'];
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
        if (isset($_POST['tour_id'])) {
            $tour_ids = (array) $_POST['tour_id'];
        } elseif (isset($_GET['tour_id'])) {
            $tour_ids = (array) $_GET['tour_id'];
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
     * Load add form
     * @throws Exception
     */
    public function addTour() : void
    {
        $this->tabs->activateSubTab('tour_configuration');

        $form = $this->getTourForm();
        $this->ui->mainTemplate()->setContent($form->getHTML());
    }

    /**
     * Load edit form by GET 'tour id'
     * @throws Exception
     */
    public function editTour() : void
    {
        $this->tabs->activateSubTab('tour_configuration');
        try {
            $this->ctrl->setParameter($this, 'tour_id', $_GET['tour_id']);
        } catch (ilCtrlException) {

        }

        $form = $this->getTourForm($_GET['tour_id']);
        $this->ui->mainTemplate()->setContent($form->getHTML());
    }

    /**
     * Save tour from form input
     * @throws Exception
     */
    public function saveTour() : void
    {
        $form = $this->getTourForm($_GET['tour_id']);
        if ($form->checkInput()) {
            // Valid form - edit or create tour from form input

            // get tour id (edit) by form input or get a new tour id (create)
            $tourId = $_GET['tour_id'];
            if (isset($tourId)) {
                $tour = $this->guidedTourRepository->getTourById($tourId);
            } else {
                $tour = new GuidedTour();
            }

            // set and save all tour attributes by form input
            $tour->setTitle($form->getInput('title'));
            $tour->setType($form->getInput('type'));
            $tour->setScript($form->getInput('script'));
            $tour->setActive($form->getInput('active'));
            $tour->setAutomaticTriggered($form->getInput('automatic_trigger'));
            $icon = $form->getInput('icon');
            //$tour->updateIcon($icon->getValue(), $icon->getDeletionFlag());

            $tour->setRolesIds($form->getInput('roles'));

            if (isset($tourId)) {
                $this->guidedTourRepository->updateTour($tour);
            } else {
                $this->guidedTourRepository->createTour($tour);
            }

            $this->ui->mainTemplate()->setOnScreenMessage(ilGlobalTemplateInterface::MESSAGE_TYPE_SUCCESS, $this->plugin_object->txt('tour_saved'));
            try {
                $this->ctrl->redirect($this, 'showTourList');
            } catch (ilCtrlException) {
            }
        } else {
            // Invalid form - return form to view
            $form->setValuesByPost();
            $this->ui->mainTemplate()->setContent($form->getHTML());
        }
    }

    /**
     * Tour configuration form.
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
}
