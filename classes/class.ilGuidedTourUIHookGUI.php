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
    const PLUGIN_CLASS_NAME = ilGuidedTourPlugin::class;
    const PAGE_CONTENT = "tpl.page_content.html";
    private $plugin;

    /**
     * ilGuidedTourUIHookGUI constructor
     */
    public function __construct()
    {
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
    function getHTML($a_comp, $a_part, $a_par = array())
    {
        global $DIC, $ilCtrl, $ilUser, $tpl;
        $usr_id = $ilUser->getId();
        $is_logged_in = ($usr_id && $usr_id != ANONYMOUS_USER_ID);

        // If user is logged in (not anonymous)
        if ($is_logged_in) {
            // If this method is triggered by loading a 'template' (whole page) and this is not an async call
            if ($a_part == "template_load" && !$ilCtrl->isAsynch()) {
                // If this method is triggered by loading a the 'main' template
                if (strpos(strtolower($a_par['html']), "</body>") !== false) {
                    $saveStatus = 'window.sessionStorage';
                    $tourStart = 'false';
                    $tourName = 'GTOUR';

                    // Create string with all relevant tours-scripts (activated tours only, user has role of tour)
                    $toursString = '';
                    $tours = ilGuidedTour::getTours();
                    $userGlobalRoles = $DIC->rbac()->review()->assignedGlobalRoles($usr_id);
                    if (isset($tours)) {
                        foreach ($tours as $tour) {
                            if ($tour->isActive() && count(array_intersect($userGlobalRoles,
                                    $tour->getRolesIds())) > 0) {
                                $toursString = $toursString . '\'' . $this->getPluginObject()->getId() . '-' . $tour->getTourId() . '\': [' . $tour->getScript() . '],';
                            }
                        }
                    }

                    // Forces start of GuidedTour, if a GuidedTour was triggered
                    if ($this->isGuidedTourTriggered()) {
                        $tourStart = 'true';
                    }

                    // Add js of GuidedTour to output
                    $plugin = ilGuidedTourPlugin::getInstance();
                    $tpl->addJavaScript($plugin->getDirectory() . "/js/ilGuidedTour.js");
                    $tpl->addJavaScript($plugin->getDirectory() . "/vendor/bootstrap-tourist/bootstrap-tourist.js");

                    // Add GuidedTour HTML to output
                    $html = $a_par['html'];
                    $index = strripos($html, "</body>", -7);
                    if ($index !== false) {
                        try {
                            // Set all GuidedTour HTML Template Variables
                            $tmpl = $plugin->getTemplate("tpl.guidedtour.html", true, true);
                            $tmpl->setVariable("GTOUR_STORAGE", "$saveStatus");
                            $tmpl->setVariable("GTOUR_BTN_PREV", $plugin->txt("tour_btn_previous"));
                            $tmpl->setVariable("GTOUR_BTN_NEXT", $plugin->txt("tour_btn_next"));
                            $tmpl->setVariable("GTOUR_BTN_STOP", $plugin->txt("tour_btn_stop"));
                            $tmpl->setVariable("GTOUR_START", "$tourStart");
                            $tmpl->setVariable("GTOUR_NAME", "$tourName");
                            $tmpl->setVariable("GTOUR_TOURS", "$toursString");

                            $tourCurrentName = $this->getTriggeredGuidedTour();
                            if (isset($tourCurrentName)) {
                                $tmpl->setVariable("GTOUR_CURRENT_NAME", "$tourCurrentName");
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
    private function isGuidedTourTriggered() : bool
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
    private function getTriggeredGuidedTour()
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
    function modifyGUI($a_comp, $a_part, $a_par = array())
    {

    }
}