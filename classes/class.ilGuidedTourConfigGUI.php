<?php
require_once __DIR__ . "/../vendor/autoload.php";

use uzk\gtour\Config\GuidedTourConfigToursTable;

/**
 * Class ilGuidedTourConfigGUI
 * Plug-In Configuration interface class
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class ilGuidedTourConfigGUI extends ilPluginConfigGUI
{
    protected $ctrl;
    protected $tpl;
    protected $user;
    protected $tabs;
    protected $plugin;

    /**
     * ilGuidedTourConfigGUI constructor
     */
    function __construct()
    {
        global $DIC, $tpl;

        $this->ctrl = $DIC->ctrl();
        $this->tpl = $tpl;
        $this->user = $DIC->user();
        $this->tabs = $DIC->tabs();

        $this->setPluginObject(ilGuidedTourPlugin::getInstance());
    }

    /**
     * Handles all commands, default is "configure"
     */
    function performCommand($cmd)
    {
        switch ($cmd) {
            case "configure":
            case "showTourList":
            case "activateTour":
            case "deactivateTour":
            case "confirmDeleteTour":
            case 'addTour':
            case 'editTour':
            case "saveTour":
                $this->$cmd();
                break;
        }
    }

    /**
     * Load configure-screen
     */
    public function configure()
    {
        $this->showTourList();
    }

    /**
     * Load GuidedTour-Tours-Configuration Table
     */
    public function showTourList()
    {
        global $tpl;

        $table_gui = new GuidedTourConfigToursTable($this, "configure");
        $tpl->setContent($table_gui->getHTML());
    }

    /**
     * Initialize process setting tour activation to active
     */
    public function activateTour()
    {
        $this->changeTourActivation(true);
    }

    /**
     * Initialize process setting tour activation to de-active
     */
    public function deactivateTour()
    {
        $this->changeTourActivation(false);
    }

    /**
     * Change tour activation status
     */
    protected function changeTourActivation($active)
    {
        global $DIC;
        $ctrl = $DIC->ctrl();
        $plugin = ilGuidedTourPlugin::getInstance();

        if (isset($_POST['tour_id'])) {
            $tour_ids = (array) $_POST['tour_id'];
        } elseif (isset($_GET['tour_id'])) {
            $tour_ids = (array) $_GET['tour_id'];
        }

        if (empty($tour_ids)) {
            ilUtil::sendFailure($plugin->txt('no_tour_selected'), true);
        } else {
            foreach ($tour_ids as $tour_id) {
                $tour = ilGuidedTour::getTourById($tour_id);
                if (isset($tour)) {
                    $tour->setActive($active);
                    $tour->saveAll();
                }
            }

            if (count($tour_ids) == 1) {
                ilUtil::sendSuccess($plugin->txt($active ? 'tour_activated' : 'tour_deactivated'), true);
            } else {
                ilUtil::sendSuccess($plugin->txt($active ? 'tours_activated' : 'tours_deactivated'), true);
            }
        }
        $ctrl->redirect($this, 'showTourList');
    }

    /**
     * Delete tour(s) by GET 'tour_id'
     * @throws ilDatabaseException
     */
    public function confirmDeleteTour()
    {
        global $DIC;
        $ctrl = $DIC->ctrl();
        $plugin = ilGuidedTourPlugin::getInstance();

        if (isset($_POST['tour_id'])) {
            $tour_ids = (array) $_POST['tour_id'];
        } elseif (isset($_GET['tour_id'])) {
            $tour_ids = (array) $_GET['tour_id'];
        }

        if (empty($tour_ids)) {
            ilUtil::sendFailure($plugin->txt('no_tour_selected'), true);
        } else {
            ilGuidedTour::deleteTours($tour_ids);
        }
        $ctrl->redirect($this, 'showTourList');
    }

    /**
     * Load add form
     */
    public function addTour()
    {
        global $DIC, $tpl;
        $tabs = $DIC->tabs();

        $tabs->activateSubTab('tour_configuration');

        $form = $this->getTourForm();
        $tpl->setContent($form->getHTML());
    }

    /**
     * Load edit form by GET 'tour id'
     */
    public function editTour()
    {
        global $DIC, $tpl;
        $ctrl = $DIC->ctrl();
        $tabs = $DIC->tabs();

        $tabs->activateSubTab('tour_configuration');
        $ctrl->setParameter($this, 'tour_id', $_GET['tour_id']);

        $form = $this->getTourForm($_GET['tour_id']);
        $tpl->setContent($form->getHTML());
    }

    /**
     * Save tour from form input
     * @throws Exception
     */
    public function saveTour()
    {
        global $DIC, $tpl;
        $ctrl = $DIC->ctrl();
        $plugin = ilGuidedTourPlugin::getInstance();

        $form = $this->getTourForm($_GET['tour_id']);

        if ($form->checkInput()) {
            // Valid form - edit or create tour from form input

            // get tour id (edit) by form input or get a new tour id (create)
            if (isset($_GET['tour_id'])) {
                $tour = ilGuidedTour::getTourById($_GET['tour_id']);
            } else {
                $tour = ilGuidedTour::getDefaultTour();
            }

            // set and save all tour attributes by form input
            $tour->setTitle($form->getInput('title'));
            $tour->setType($form->getInput('type'));
            $tour->setActive($form->getInput('active'));
            $tour->setScript($form->getInput('script'));
            $tour->setRolesIds($form->getInput('roles'));
            $icon = $form->getItemByPostVar('icon');
            $tour->updateIcon($icon->getDeletionFlag());
            $tour->save();

            ilUtil::sendSuccess($plugin->txt('tour_saved'), true);
            $ctrl->redirect($this, 'showTourList');
        } else {
            // Invalid form - return form to view
            $form->setValuesByPost();
            $tpl->setContent($form->getHTML());
        }
    }

    /**
     * Tour configuration form.
     * @return object form object
     */
    public function getTourForm($a_tour_id = null)
    {
        global $DIC, $lng;
        $ctrl = $DIC->ctrl();
        $plugin = ilGuidedTourPlugin::getInstance();

        if (isset($a_tour_id) && $a_tour_id > 0) {
            $tour = ilGuidedTour::getTourById($a_tour_id);
            $title = $plugin->txt('edit_tour');
            $ctrl->setParameter($this, 'tour_id', $a_tour_id);
        } else {
            $tour = ilGuidedTour::getDefaultTour();
            $title = $plugin->txt('add_tour');
        }

        $form = new ilPropertyFormGUI();
        $form->setTitle($title);
        $form->setFormAction($ctrl->getFormAction($this));

        // title
        $title = new ilTextInputGUI($plugin->txt('tour_title'), 'title');
        $title->setInfo($plugin->txt('tour_title_info'));
        $title->setRequired(true);
        $title->setValue($tour->getTitle());
        $form->addItem($title);

        // types
        $options = [];
        foreach (ilGuidedTour::getTypes() as $type) {
            $options[$type] = $plugin->txt('tour_type_' . $type);
        }
        $type = new ilSelectInputGUI($plugin->txt('tour_type'), 'type');
        $type->setInfo($plugin->txt('tour_type_info'));
        $type->setRequired(true);
        $type->setOptions($options);
        $type->setValue($tour->getType());
        $form->addItem($type);

        // activation
        $active = new ilCheckboxInputGUI($plugin->txt('tour_active'), 'active');
        $active->setInfo($plugin->txt('tour_active_info'));
        $active->setChecked($tour->isActive());
        $form->addItem($active);

        // roles
        $access = new ilObjMainMenuAccess();
        $roles = new ilMultiSelectInputGUI($plugin->txt('tour_roles'), 'roles');
        $roles->setOptions($access->getGlobalRoles());
        $roles->setInfo($plugin->txt('tour_roles_info'));
        if (!empty($tour->getRolesIds())) {
            $roles->setValue($tour->getRolesIds());
            $roles->enableSelectAll(true);
        }
        $form->addItem($roles);

        // icon
        $icon = new ilFileInputGUI($plugin->txt('tour_icon'), 'icon');
        $icon->setInfo($plugin->txt('tour_icon_info'));
        $icon->setSuffixes([ilMimeTypeUtil::IMAGE__SVG_XML, 'svg']);
        $icon->setAllowDeletion(true);
        if (!empty($tour->getIconId())) {
            $icon->setFilename($tour->getIconId());
            $icon->setValue($tour->getIconTitle());
        }
        $form->addItem($icon);

        // tour script
        $script = new ilTextAreaInputGUI($plugin->txt('tour_script'), 'script');
        $script->setInfo($plugin->txt('tour_script_info'));
        $script->setValue($tour->getScript());
        $script->setRows(20);
        $form->addItem($script);

        // command buttons
        $form->addCommandButton('saveTour', $lng->txt('save'));
        $form->addCommandButton('showTourList', $lng->txt('cancel'));

        return $form;
    }
}
