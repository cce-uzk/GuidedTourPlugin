<?php
require_once __DIR__ . "/../vendor/autoload.php";

include_once("./Services/UIComponent/classes/class.ilUIHookPluginGUI.php");
include_once("./Services/Init/classes/class.ilStartUpGUI.php");

/**
 * User interface hook class
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
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
     * Modify HTML output of GUI elements. Modifications modes are:
     * - ilUIHookPluginGUI::KEEP (No modification)
     * - ilUIHookPluginGUI::REPLACE (Replace default HTML with your HTML)
     * - ilUIHookPluginGUI::APPEND (Append your HTML to the default HTML)
     * - ilUIHookPluginGUI::PREPEND (Prepend your HTML to the default HTML)
     *
     * @param string $a_comp component
     * @param string $a_part string that identifies the part of the UI that is handled
     * @param string $a_par array of parameters (depend on $a_comp and $a_part)
     *
     * @return array array with entries "mode" => modification mode, "html" => your html
     */
    function getHTML($a_comp, $a_part, $a_par = array())
    {
        global $DIC, $ilCtrl, $ilUser, $tpl;
        $usr_id = $ilUser->getId();
        $is_logged_in = ($usr_id && $usr_id != ANONYMOUS_USER_ID);

        // check: is logged in?
        if ($is_logged_in) {
            // check: is loading a template and this is NOT an async call?
            if ($a_part == "template_load" && !$ilCtrl->isAsynch()) {
                // check: is main template?
                if (strpos(strtolower($a_par['html']), "</body>") !== false) {
                    $saveStatus = 'window.sessionStorage';
                    $tourStart = 'false';
                    $tourName = 'GTOUR';

                    $toursString = '';
                    $tours = ilGuidedTour::getTours();
                    $userGlobalRoles = $DIC->rbac()->review()->assignedGlobalRoles($usr_id);
                    if(isset($tours)){
                        foreach ($tours as $tour){
                            if($tour->isActive() && count(array_intersect($userGlobalRoles, $tour->getRolesIds())) > 0) {
                                $toursString = $toursString . '\'' . $this->getPluginObject()->getId() . '-' . $tour->getTourId() . '\': [' . $tour->getScript() . '],';
                            }
                        }
                    }

                    if ($this->isGuidedTourTriggered()) {
                        $tourStart = 'true';
                    }

                    $plugin = ilGuidedTourPlugin::getInstance();
                    $tpl->addJavaScript($plugin->getDirectory() . "/js/ilGuidedTour.js");
                    $tpl->addJavaScript($plugin->getDirectory() . "/vendor/bootstrap-tourist/bootstrap-tourist.js");
                    $html = $a_par['html'];
                    $index = strripos($html, "</body>", -7);
                    if ($index !== false) {
                        try {
                            $tmpl = $plugin->getTemplate("tpl.guidedtour.html", true, true);
                            $tmpl->setVariable("GTOUR_STORAGE", "$saveStatus");
                            $tmpl->setVariable("GTOUR_BTN_PREV", $plugin->txt("tour_btn_previous"));
                            $tmpl->setVariable("GTOUR_BTN_NEXT", $plugin->txt("tour_btn_next"));
                            $tmpl->setVariable("GTOUR_BTN_STOP", $plugin->txt("tour_btn_stop"));
                            $tmpl->setVariable("GTOUR_START", "$tourStart");
                            $tmpl->setVariable("GTOUR_NAME", "$tourName");
                            $tmpl->setVariable("GTOUR_TOURS", "$toursString");

                            $tourCurrentName = $this->getTriggeredGuidedTour();
                            if(isset($tourCurrentName)) {
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
        return array("mode" => ilUIHookPluginGUI::KEEP, "html" => "");
    }

    private function isGuidedTourTriggered(): bool
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
     * Modify GUI objects, before they generate ouput
     *
     * @param string $a_comp component
     * @param string $a_part string that identifies the part of the UI that is handled
     * @param string $a_par array of parameters (depend on $a_comp and $a_part)
     */
    function modifyGUI($a_comp, $a_part, $a_par = array())
    {
        global $ilCtrl, $ilUser, $lng, $tpl;
        $usr_firstname = $ilUser->getFirstname();


        $plugin = ilGuidedTourPlugin::getInstance();

        /*if ($usr_firstname === 'Nadimo') {
            // Category
            if (in_array($ilCtrl->getCmdClass(), array('ilobjcategorygui'))) {
                if ($a_part == "tabs") {
                    //$a_par["tabs"]->addTab("gtour_tab_title", $plugin->txt("tab_title"), "");
                    $a_par["tabs"]->addTab("gtour_tab_title", "Einf&uuml;hrungstour Kategorie", "");
                }
            }

            // Course
            if (in_array($ilCtrl->getCmdClass(), array('ilobjcoursegui'))) {
                if ($a_part == "tabs") {
                    //$a_par["tabs"]->addTab("gtour_tab_title", $plugin->txt("tab_title"), "");
                    $a_par["tabs"]->addTab("gtour_tab_title", "Einf&uuml;hrungstour Kurs", "");
                }
            }
        }*/
    }
}