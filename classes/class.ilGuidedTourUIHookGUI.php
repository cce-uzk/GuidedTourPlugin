<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";
use uzk\gtour\Data\GuidedTourRepository;
use ILIAS\DI\RBACServices;

/**
 * Class ilGuidedTourUIHookGUI
 * GuidedTour Userinterface-Hook class
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 * @ilCtrl_isCalledBy ilGuidedTourUIHookGUI: ilUIPluginRouterGUI
 */
class ilGuidedTourUIHookGUI extends ilUIHookPluginGUI
{
    protected ilCtrl $ctrl;
    protected ilObjUser $user;
    protected RBACServices $rbac;
    protected GuidedTourRepository $guidedTourRepository;

    /**
     * ilGuidedTourUIHookGUI constructor
     * @throws Exception
     */
    public function __construct()
    {
        $this->setPluginObject(ilGuidedTourPlugin::getInstance());

        // Get global data
        global $DIC;
        $this->user = $DIC->user();
        $this->ctrl = $DIC->ctrl();
        $this->rbac = $DIC->rbac();

        // Initialize repository
        $this->guidedTourRepository = new GuidedTourRepository();

        // Initialize tour
        if (!$this->ctrl->isAsynch()) {
            $this->initGuidedTour();
        }
    }

    /**
     * @return void
     */
    protected function initGuidedTour(): void
    {
        global $DIC;

        // Retrieve all global roles assigned to the user once
        $userId = $this->user->getId();
        $userGlobalRoles = $this->rbac->review()->assignedGlobalRoles($userId);

        if ($DIC->offsetExists('global_screen') && !$DIC->http()->agent()->isMobile()){
            $config = new stdClass();
            $config->name = $this->getTriggeredGuidedTour();
            $config->forceStart = isset($config->name);
            $config->storage = 'sessionStorage';
            $config->steps = null;

            // Template placeholder
            $config->tpl = new stdClass();
            $config->tpl->btn_prev = $this->plugin_object->txt('tour_btn_previous');
            $config->tpl->btn_next = $this->plugin_object->txt('tour_btn_next');
            $config->tpl->btn_stop = $this->plugin_object->txt('tour_btn_stop');

            if (isset($config->name)) {
                // Get requested tour
                $tourId = $this->getGtourIdByTriggerInformation($config->name);
                if (isset($tourId)) {
                    $tour = $this->guidedTourRepository->getTourById($tourId);
                    if (isset($tour)) {
                        if ($tour->isActive() && $this->isUserEligibleForTour($userGlobalRoles, $tour)) {
                            $jsonString = $this->cleanJsonString($tour->getScript());
                            if ($this->isValidJson($jsonString)) {
                                $config->steps = $jsonString;
                            }
                        }
                    }
                }
            }
            else {
                // Get context sensitive autostart tour

                // Context data
                $contextIdentifier = $DIC->globalScreen()->tool()->context()->current()->getUniqueContextIdentifier();
                $refId = $this->getCurrentRefId();
                $type = $this->getObjectTypeByRefId($refId);
                $cmdClass = $this->ctrl->getCmdClass();

                // Ensure no null values are added by using array_filter
                $identifier = [
                    $contextIdentifier,
                    $type,
                    $cmdClass
                ];

                // Get all tours
                $tours = $this->guidedTourRepository->getTours();

                // Check for context-, role-sensitive autostart tours
                foreach ($tours as $tour) {
                    if ($tour->isActive() && $tour->isAutomaticTriggered() && in_array($tour->getType(), $identifier) && $this->isUserEligibleForTour($userGlobalRoles, $tour)) {
                        $jsonString = $this->cleanJsonString($tour->getScript());
                        if ($this->isValidJson($jsonString)) {
                            $config->steps = $jsonString;
                            $config->name = 'gtour-' . $tour->getId();

                            // todo: implement always forced start to gtour object as new option
                            $forceStartAlways = false;
                            if($forceStartAlways) {
                                $config->forceStart = true;
                            }
                            break;
                        }
                    }
                }

            }

            // Convert the object to JSON
            $jsonConfig = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            // Provides a default empty object to prevent JS errors by JSON encoding
            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonConfig = '{}';
            }

            // Initialize JS tour start
            $meta_content = $DIC->globalScreen()->layout()->meta();
            $meta_content->addOnloadCode("il.Plugins.GuidedTour.init(" . $jsonConfig . ");", 1);
        }
    }

    /**
     * Get name of the current triggered tour otherwise returns `null` if there is no currently triggered tour
     * @return string|null
     */
    protected function getTriggeredGuidedTour(): string|null
    {
        $uri = parse_url($_SERVER["REQUEST_URI"]);
        if ($uri !== false && isset($uri["query"])) {
            parse_str($uri["query"], $params);
            if (array_key_exists("triggerTour", $params)) {
                return $params["triggerTour"];
            }
        }
        return null; // Return null if any conditions fail
    }

    /**
     * Extracts the numeric ID from a formatted string representing a guided tour identifier.
     * @param string $string The string to parse, expected to be in the format "gtour-{id}".
     * @return int|null Returns the numeric ID as an integer if the string is correctly formatted,
     * otherwise returns `null` if the format is incorrect or the ID is not numeric.
     */
    protected function getGtourIdByTriggerInformation(string $string): int|null {
        // Split the string by '-'
        $parts = explode('-', $string);

        // Check if the format is correct (should have 2 parts and start with 'gtour')
        if (count($parts) === 2 && $parts[0] === 'gtour') {
            // Check if the second part is a numeric value
            if (is_numeric($parts[1])) {
                return (int) $parts[1];  // Return as integer
            }
        }

        // Return null if the format is incorrect or the ID isn't numeric
        return null;
    }

    /**
     * Checks if a given string is valid JSON.
     *
     * This function attempts to decode a string using json_decode and then checks if the operation
     * was successful by checking the error code from json_last_error. It returns true if the string
     * is valid JSON; otherwise, it returns false.
     *
     * @param string $string The string to test for JSON validity.
     * @return bool Returns true if the string is valid JSON, false otherwise.
     */
    protected function isValidJson(string $string): bool {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Cleans up a JSON string by removing unnecessary whitespace such as newlines, tabs, and redundant spaces.
     * Ensures the JSON string is wrapped in brackets if it appears to be an array of objects.
     *
     * @param string $jsonString The JSON string to be cleaned.
     * @return string The cleaned JSON string without unnecessary whitespace.
     */
    protected function cleanJsonString(string $jsonString): string {
        // Ensure all literal backslashes are properly escaped (avoid double-escaping)
        $jsonString = preg_replace('/(?<!\\\\)\\\\(?!["\\\\])/', '\\\\', $jsonString);

        // Escape unescaped double quotes inside JSON strings
        $jsonString = preg_replace_callback('/(?<=:|,)\s*"([^"]*)"/', function ($matches) {
            return '"' . str_replace('"', '\\"', $matches[1]) . '"';
        }, $jsonString);

        // Remove newlines, carriage returns, tabs, and collapse multiple spaces into one
        $jsonString = preg_replace("/\s+/", " ", $jsonString);

        // Remove invalid numeric values
        $jsonString = str_ireplace(["NaN", "Infinity", "-Infinity"], "null", $jsonString);

        // Correct boolean values
        $jsonString = str_ireplace(["True", "False"], ["true", "false"], $jsonString);

        // Remove spaces around brackets, braces, commas, and colons
        $jsonString = preg_replace("/\s?([\[\]{},:])\s?/", "$1", $jsonString);

        // Remove unwanted trailing commas before closing braces (objects) and brackets (arrays)
        $jsonString = preg_replace('/,\s*(\}|\])/', '$1', $jsonString);

        // Normalize to ensure the string forms a valid JSON array if it's not already encapsulated
        if (preg_match('/^\{.+\}$/', $jsonString)) {
            $jsonString = '[' . $jsonString . ']'; // Wrap single object into an array
        } else if (!preg_match('/^\[.*\]$/', $jsonString)) { // Broadened to accept any content within brackets
            // It's multiple objects not in a valid JSON array format, or improperly formatted
            $jsonString = preg_replace('/\}\s*\{/', '},{', $jsonString); // Ensure proper comma separation
            $jsonString = '[' . $jsonString . ']'; // Wrap them in an array
        }

        return $jsonString;
    }

    /**
     * Check if tour is eligible for current user.
     * @param $userGlobalRoles
     * @param $tour
     * @return bool
     */
    protected function isUserEligibleForTour($userGlobalRoles, $tour) : bool
    {
        return count(array_intersect($userGlobalRoles, $tour->getRolesIds())) > 0;
    }

    protected function getObjectTypeByRefId(int $refId): string|null
    {
        if (!$refId) {
            return null;
        }

        global $DIC;
        $cache = $DIC['ilObjDataCache'];

        if (isset($cache)) {
            $objId = $cache->lookupObjId($refId);
            if($objId) {
                return $cache->lookupType($objId);
            }
        }
        return null;
    }

    protected function getCurrentRefId(): int
    {
        global $DIC;
        // Attempt to retrieve the refId directly
        $refId = $DIC->globalScreen()->tool()->context()->current()->getReferenceId()->toInt();

        // Return refId if it's a valid positive integer, otherwise, fetch using alternative method
        return ($refId > 0) ? $refId : $this->getRefIdByCurrentClassPathParameter();
    }

    protected function getRefIdByCurrentClassPathParameter(): int
    {
        $currentClassPath = $this->ctrl->getCurrentClassPath();
        $parameter = [];
        if(count($currentClassPath) > 0) {
            $baseClass = $currentClassPath[0];
            try {
                $parameter = $this->ctrl->getParameterArrayByClass($baseClass);
            }
            catch (ilCtrlException) {
                return -1;
            }
        }

        if (isset($parameter['ref_id'])) {
            return (int) $parameter['ref_id'];
        } else {
            return -1;
        }
    }
}