<?php
require_once __DIR__ . "/../vendor/autoload.php";

use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;
use uzk\gtour\Config\GuidedTourConfigToursTable;

/**
 * Example configuration user interface class
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 */
class ilGuidedTourConfigGUI extends ilPluginConfigGUI
{
    const PLUGIN_CLASS_NAME = ilGuidedTourPlugin::class;

    /**
     * Handles all commmands, default is "configure"
     */
    function performCommand($cmd)
    {
        switch ($cmd)
        {
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
     * Configure screen
     */
    function configure()
    {
        global $tpl;

        //$form = $this->initConfigurationForm();
        //$tpl->setContent($form->getHTML());
        $this->showTourList();

    }

    public function showTourList()
    {
        global $tpl;
        $table_gui = new GuidedTourConfigToursTable($this, "configure");
        $tpl->setContent($table_gui->getHTML());
    }

    /**
     * Init configuration form.
     *
     * @return object form object
     */
    public function initConfigurationForm()
    {
        global $lng, $ilCtrl;

        $pl = $this->getPluginObject();

        include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
        $form = new ilPropertyFormGUI();

        // setting 1 (a checkbox)
        $cb = new ilCheckboxInputGUI($pl->txt("setting_1"), "setting_1");
        $form->addItem($cb);

        // setting 2 (text)
        $ti = new ilTextInputGUI($pl->txt("setting_2"), "setting_2");
        $ti->setRequired(true);
        $ti->setMaxLength(10);
        $ti->setSize(10);
        $form->addItem($ti);

        $form->addCommandButton("save", $lng->txt("save"));

        $form->setTitle($pl->txt("example_plugin_configuration"));
        $form->setFormAction($ilCtrl->getFormAction($this));

        return $form;
    }

    /**
     * Save form input
     *
     */
    public function save()
    {
        global $tpl, $lng, $ilCtrl;

        $pl = $this->getPluginObject();

        $form = $this->initConfigurationForm();
        if ($form->checkInput())
        {
            $set1 = $form->getInput("setting_1");
            $set2 = $form->getInput("setting_2");

            // @todo: implement saving to db

            ilUtil::sendSuccess($pl->txt("saving_invoked"), true);
            $ilCtrl->redirect($this, "configure");
        }
        else
        {
            $form->setValuesByPost();
            $tpl->setContent($form->getHtml());
        }
    }

    public function activateTour()
    {
        $this->changeTourActivation(true);
    }

    public function deactivateTour()
    {
        $this->changeTourActivation(false);
    }

    protected function changeTourActivation($active)
    {
        global $DIC;
        $pl = $this->getPluginObject();

        if (isset($_POST['tour_id']))
        {
            $tour_ids = (array)$_POST['tour_id'];
        } elseif (isset($_GET['tour_id']))
        {
            $tour_ids = (array)$_GET['tour_id'];
        }

        if (empty($tour_ids))
        {
            ilUtil::sendFailure($pl->txt('no_tour_selected'), true);
        } else
        {
            foreach ($tour_ids as $tour_id)
            {
                var_dump($tour_id);
                $tour = ilGuidedTour::getTourById($tour_id);
                if(isset($tour))
                {
                    $tour->setActive($active);
                    $tour->save();
                }
            }

            if (count($tour_ids) == 1)
            {
                ilUtil::sendSuccess($pl->txt($active ? 'tour_activated' : 'tour_deactivated'), true);
            } else
            {
                ilUtil::sendSuccess($pl->txt($active ? 'tours_activated' : 'tours_deactivated'), true);
            }
        }
        $DIC->ctrl()->redirect($this, 'showTourList');
    }

    public function confirmDeleteTour()
    {
        global $DIC;
        $pl = $this->getPluginObject();

        if (isset($_POST['tour_id']))
        {
            $tour_ids = (array)$_POST['tour_id'];
        } elseif (isset($_GET['tour_id']))
        {
            $tour_ids = (array)$_GET['tour_id'];
        }

        if (empty($tour_ids))
        {
            ilUtil::sendFailure($pl->txt('no_tour_selected'), true);
        } else
        {
            ilGuidedTour::deleteTours($tour_ids);
        }
        $DIC->ctrl()->redirect($this, 'showTourList');
    }

    public function addTour()
    {
        global $DIC, $tpl;
        $tabs = $DIC->tabs();
        $tabs->activateSubTab('tour_configuration');

        $form = $this->getTourForm();
        $tpl->setContent($form->getHTML());
    }

    public function editTour()
    {
        global $DIC, $tpl;
        $tabs = $DIC->tabs();
        $tabs->activateSubTab('tour_configuration');

        $DIC->ctrl()->setParameter($this, 'tour_id', $_GET['tour_id']);

        $form = $this->getTourForm($_GET['tour_id']);
        $tpl->setContent($form->getHTML());
    }

    /**
     * @throws Exception
     */
    public function saveTour()
    {
        global $DIC, $tpl;
        $form = $this->getTourForm($_GET['tour_id']);
        if ($form->checkInput())
        {
            if (isset($_GET['tour_id']))
            {
                $tour = ilGuidedTour::getTourById($_GET['tour_id']);
            } else
            {
                $tour = ilGuidedTour::getDefaultTour();
            }
            $tour->setTitle($form->getInput('title'));
            $tour->setType($form->getInput('type'));
            $tour->setActive($form->getInput('active'));
            $tour->setScript($form->getInput('script'));

            $icon = $form->getItemByPostVar('icon');
            $tour->updateIcon($icon->getDeletionFlag());
            $tour->save();

            ilUtil::sendSuccess($this->plugin_object->txt('tour_saved'), true);
            $DIC->ctrl()->redirect($this, 'showTourList');
        } else
        {
            $form->setValuesByPost();
            $tpl->setContent($form->getHTML());
        }
    }

    /**
     * Tour configuration form.
     *
     * @return object form object
     */
    public function getTourForm($a_tour_id = null)
    {
        global $DIC;
        $ctrl = $DIC->ctrl();
        $lng = $DIC->language();

        if (isset($a_tour_id) && $a_tour_id > 0)
        {
            $tour = ilGuidedTour::getTourById($a_tour_id);
            $title = $this->plugin_object->txt('edit_tour');
            $ctrl->setParameter($this, 'tour_id', $a_tour_id);
        } else
        {
            $tour = ilGuidedTour::getDefaultTour();
            $title = $this->plugin_object->txt('add_tour');
        }

        $form = new ilPropertyFormGUI();
        $form->setTitle($title);
        $form->setFormAction($ctrl->getFormAction($this));

        // types
        $options = [];
        foreach (ilGuidedTour::getTypes() as $type)
        {
            $options[$type] = $this->plugin_object->txt('tour_type_' . $type);
        }
        $type = new ilSelectInputGUI($this->plugin_object->txt('tour_type'), 'type');
        $type->setInfo($this->plugin_object->txt('tour_type_info'));
        $type->setRequired(true);
        $type->setOptions($options);
        $type->setValue($tour->getType());
        $form->addItem($type);

        $active = new ilCheckboxInputGUI($this->plugin_object->txt('tour_active'), 'active');
        $active->setInfo($this->plugin_object->txt('tour_active_info'));
        $active->setChecked($tour->isActive());
        $form->addItem($active);

        $title = new ilTextInputGUI($this->plugin_object->txt('tour_title'), 'title');
        $title->setInfo($this->plugin_object->txt('tour_title_info'));
        $title->setRequired(true);
        $title->setValue($tour->getTitle());
        $form->addItem($title);

        $icon = new ilFileInputGUI($this->plugin_object->txt('tour_icon'), 'icon');
        $icon->setInfo($this->plugin_object->txt('tour_icon_info'));
        $icon->setSuffixes([ilMimeTypeUtil::IMAGE__SVG_XML, 'svg']);
        $icon->setAllowDeletion(true);
        if (!empty($tour->getIconId())) {
            $icon->setFilename($tour->getIconId());
            $icon->setValue($tour->getIconTitle());
        }
        $form->addItem($icon);

        $script = new ilTextAreaInputGUI($this->plugin_object->txt('tour_script'), 'script');
        $script->setInfo($this->plugin_object->txt('tour_script_info'));
        $script->setValue($tour->getScript());
        $form->addItem($script);

        $form->addCommandButton('saveTour', $lng->txt('save'));
        $form->addCommandButton('showTourList', $lng->txt('cancel'));

        return $form;
    }
}
