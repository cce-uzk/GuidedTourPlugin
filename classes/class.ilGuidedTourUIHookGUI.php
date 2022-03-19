<?php
require_once __DIR__ . "/../vendor/autoload.php";

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
include_once("./Services/Init/classes/class.ilStartUpGUI.php");

/**
 * Class ilGuidedTourUIHookGUI
 * GuidedTour Userinterface-Hook class
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 * @ingroup ServicesUIComponent
 */
class ilGuidedTourUIHookGUI extends ilUIHookPluginGUI
{
    protected $ctrl;
    protected $tpl;
    protected $rbac;
    protected $user;
    protected $plugin;

    /**
     * ilGuidedTourUIHookGUI constructor
     */
    public function __construct()
    {
        global $DIC, $tpl;

        $this->ctrl = $DIC->ctrl();
        $this->tpl = $tpl;
        $this->rbac = $DIC->rbac();
        $this->user = $DIC->user();
        $this->plugin = ilGuidedTourPlugin::getInstance();

        $this->setPluginObject(ilGuidedTourPlugin::getInstance());
    }

    /**
     * Modify HTML output of (all) GUI elements.
     * Adds
     * @param string $a_comp component
     * @param string $a_part string that identifies the part of the UI that is handled
     * @param string $a_par  array of parameters (depend on $a_comp and $a_part)
     * @return array array with entries "mode" => modification mode, "html" => your html
     */
    public function getHTML($a_comp, $a_part, $a_par = array()) : array
    {
        $userId = $this->user->getId();
        $is_logged_in = ($userId && $userId != ANONYMOUS_USER_ID);

        // If user is logged in (not anonymous)
        if ($is_logged_in) {
            // If this method is triggered by loading a 'template' (whole page) and this is not an async call
            if ($a_part == "template_load" && !$this->ctrl->isAsynch()) {
                // If this method is triggered by loading a the 'main' template
                if (strpos(strtolower($a_par['html']), "</body>") !== false) {
                    $saveStatus = 'window.sessionStorage';
                    $tourStart = 'false';
                    $tourName = 'GTOUR';

                    // Create string with all relevant tours-scripts (activated tours only, user has role of tour)
                    $tourScript = '';
                    $tours = ilGuidedTour::getTours();
                    $userGlobalRoles = $this->rbac->review()->assignedGlobalRoles($userId);
                    if (isset($tours)) {
                        foreach ($tours as $tour) {
                            if ($tour->isActive() && count(array_intersect($userGlobalRoles,
                                    $tour->getRolesIds())) > 0) {
                                $tourScript = $tourScript . '\'' . $this->getPluginObject()->getId() . '-' . $tour->getTourId() . '\': [' . $tour->getScript() . '],';
                            }
                        }
                    }

                    // Forces start of GuidedTour, if a GuidedTour was triggered
                    if ($this->isGuidedTourTriggered()) {
                        $tourStart = 'true';
                    }

                    // Add js of GuidedTour to output
                    $this->tpl->addJavaScript($this->plugin->getDirectory() . "/js/ilGuidedTour.js");
                    $this->tpl->addJavaScript($this->plugin->getDirectory() . "/vendor/bootstrap-tourist/bootstrap-tourist.js");

                    // Add GuidedTour HTML to output
                    $html = $a_par['html'];
                    $index = strripos($html, "</body>", -7);
                    if ($index !== false) {
                        try {
                            // Set all GuidedTour HTML Template Variables
                            $tmpl = $this->plugin->getTemplate("tpl.guidedtour.html", true, true);
                            $tmpl->setVariable("GTOUR_STORAGE", "$saveStatus");
                            $tmpl->setVariable("GTOUR_BTN_PREV", $this->plugin->txt("tour_btn_previous"));
                            $tmpl->setVariable("GTOUR_BTN_NEXT", $this->plugin->txt("tour_btn_next"));
                            $tmpl->setVariable("GTOUR_BTN_STOP", $this->plugin->txt("tour_btn_stop"));
                            $tmpl->setVariable("GTOUR_START", "$tourStart");
                            $tmpl->setVariable("GTOUR_NAME", "$tourName");
                            $tmpl->setVariable("GTOUR_TOURS", "$tourScript");

                            $currentTourName = $this->getTriggeredGuidedTour();
                            if (isset($currentTourName)) {
                                $tmpl->setVariable("GTOUR_CURRENT_NAME", "$currentTourName");
                            }
                            $html = substr($html, 0, $index) . $tmpl->get() . substr($html, $index);
                            return array("mode" => ilUIHookPluginGUI::REPLACE, "html" => $html);
                        } catch (Exception $ex) {

                        }
                    }

                }
            }
        }

        // Return without changes
        return array("mode" => ilUIHookPluginGUI::KEEP, "html" => "");
    }

    /**
     * Check if a GuidedTour is currently triggered
     * @return bool
     */
    protected function isGuidedTourTriggered() : bool
    {
        $uri = parse_url($_SERVER["REQUEST_URI"]);
        if (isset($uri)) {
            $params = null;
            parse_str($uri["query"], $params);
            if (isset($params) && array_key_exists("triggerTour", $params)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get name of the current triggered tour
     * Null if there is no currently triggered tour
     * @return mixed|null
     */
    protected function getTriggeredGuidedTour()
    {
        $uri = parse_url($_SERVER["REQUEST_URI"]);
        if (isset($uri)) {
            $params = null;
            parse_str($uri["query"], $params);
            if (isset($params) && array_key_exists("triggerTour", $params)) {
                return $params["triggerTour"];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    /**
     * Modify GUI objects, before they generate output
     * @param string $a_comp component
     * @param string $a_part string that identifies the part of the UI that is handled
     * @param string $a_par  array of parameters (depend on $a_comp and $a_part)
     */
    public function modifyGUI($a_comp, $a_part, $a_par = array())
    {

    }
}