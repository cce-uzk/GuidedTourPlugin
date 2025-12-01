<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";
use uzk\gtour\Data\GuidedTourRepository;
use uzk\gtour\Data\GuidedTourUserFinishedRepository;
use ILIAS\DI\RBACServices;

/**
 * Class ilGuidedTourUIHookGUI
 * GuidedTour Userinterface-Hook class
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 * @ilCtrl_isCalledBy ilGuidedTourUIHookGUI: ilUIPluginRouterGUI
 * @ilCtrl_Calls ilGuidedTourUIHookGUI: ilGuidedTourGUI
 */
class ilGuidedTourUIHookGUI extends ilUIHookPluginGUI
{
    protected ilGuidedTourPlugin $plugin;
    protected ilCtrl $ctrl;
    protected ilObjUser $user;
    protected RBACServices $rbac;
    protected GuidedTourRepository $guidedTourRepository;
    protected GuidedTourUserFinishedRepository $finishedRepo;
    protected static bool $mappingsProvided = false;

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

        // Initialize the plugin property
        $pluginObject = $this->getPluginObject();
        if (!$pluginObject instanceof ilGuidedTourPlugin) {
            throw new \UnexpectedValueException('Expected an instance of ilGuidedTourPlugin');
        }
        $this->plugin = $pluginObject;

        // Initialize repositories
        $this->guidedTourRepository = new GuidedTourRepository();
        $this->finishedRepo = new GuidedTourUserFinishedRepository();

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

        // Always provide internal ID mappings (needed for recording and playback)
        if (isset($DIC['global_screen']) && !$DIC->http()->agent()->isMobile()) {
            $this->initInternalIdMapping();
        }

        if (isset($DIC['global_screen']) && !$DIC->http()->agent()->isMobile() && $this->plugin->isLoaded() === false) {
            $globalScreen = $DIC['global_screen'];
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
            $config->tpl->progress_of = $this->plugin_object->txt('tour_progress_of');

            // Terminate URL for AJAX calls (called when tour is closed)
            try {
                $this->ctrl->setParameterByClass('ilGuidedTourGUI', 'tour_id', '__TOUR_ID__');
                $config->terminateUrl = $this->ctrl->getLinkTargetByClass(['ilUIPluginRouterGUI', 'ilGuidedTourGUI'], 'terminateTour', '', true);
                $config->updateProgressUrl = $this->ctrl->getLinkTargetByClass(['ilUIPluginRouterGUI', 'ilGuidedTourGUI'], 'updateProgress', '', true);
            } catch (ilCtrlException $e) {
                error_log('Failed to generate terminate URL: ' . $e->getMessage());
            }

            if (isset($config->name)) {
                // Get requested tour
                $tourId = $this->getGtourIdByTriggerInformation($config->name);
                if (isset($tourId)) {
                    $tour = $this->guidedTourRepository->getTourById($tourId);
                    if (isset($tour)) {
                        // Get current user language
                        $userLanguage = $this->user->getLanguage();

                        // Check if tour matches user language (or has no language restriction)
                        $languageMatches = ($tour->getLanguageCode() === null || $tour->getLanguageCode() === '' || $tour->getLanguageCode() === $userLanguage);

                        // Check if user has already finished this tour (for manual triggers we still allow it)
                        // Manually triggered tours can be replayed
                        if ($tour->isActive() && $languageMatches && $this->isUserEligibleForTour($userGlobalRoles, $tour)) {
                            $jsonString = $this->cleanJsonString($tour->getEffectiveScript());
                            if ($this->isValidJson($jsonString)) {
                                $config->steps = $jsonString;
                            }
                        }
                    }
                }
            }
            else {
                // Get context sensitive autostart tour

                // Context data - try multiple methods to get object type
                $contextObjType = $this->ctrl->getContextObjType();
                $cmdClass = $this->ctrl->getCmdClass();

                // Try to get refId from multiple sources
                $refId = $this->getCurrentRefId();

                // Fallback: Read ref_id directly from URL if previous method failed
                if ($refId <= 0 && isset($_GET['ref_id'])) {
                    $refId = (int)$_GET['ref_id'];
                }

                $typeFromRefId = $this->getObjectTypeByRefId($refId);

                // Use whichever is not null
                $objType = $contextObjType ?? $typeFromRefId;

                // Get all tours
                $tours = $this->guidedTourRepository->getTours();

                // Get current user language
                $userLanguage = $this->user->getLanguage();

                // Check for context-, role-sensitive, and language-specific autostart tours
                $logger = $DIC->logger()->root();
                $logger->debug('GuidedTour: Checking autostart for ' . count($tours) . ' tours, refId: ' . $refId . ', contextObjType: ' . ($contextObjType ?? 'NULL') . ', typeFromRefId: ' . ($typeFromRefId ?? 'NULL') . ', cmdClass: ' . ($cmdClass ?? 'NULL') . ', final objType: ' . ($objType ?? 'NULL'));

                foreach ($tours as $tour) {
                    $logger->debug('GuidedTour: Checking tour ' . $tour->getId() . ' "' . $tour->getTitle() . '"');

                    // Check if user has already finished this tour
                    $hasFinished = $this->finishedRepo->hasFinished($tour->getId(), $userId);
                    $logger->debug('GuidedTour: hasFinished check for tour ' . $tour->getId() . ': ' . ($hasFinished ? 'TRUE (skip)' : 'FALSE (continue)'));
                    if ($hasFinished) {
                        $logger->debug('GuidedTour: User has already finished tour ' . $tour->getId());
                        continue; // Skip tours that user has already completed
                    }

                    // Check if tour matches user language (or has no language restriction)
                    $languageMatches = ($tour->getLanguageCode() === null || $tour->getLanguageCode() === '' || $tour->getLanguageCode() === $userLanguage);

                    // Check if tour type matches current context (same logic as MainBar!)
                    // Type "any" means tour should be shown on all pages
                    $typeMatches = ($tour->getType() === 'any' || $tour->getType() === $objType || $tour->getType() === $cmdClass);

                    // Check if tour ref_id matches current ref_id (if set)
                    $refIdMatches = ($tour->getRefId() === null || $tour->getRefId() === $refId);

                    // Tour matches if: (type matches OR ref_id is set) AND ref_id matches
                    $contextMatches = ($typeMatches || $tour->getRefId() !== null) && $refIdMatches;

                    $logger->debug('GuidedTour: Tour ' . $tour->getId() . ' checks - active: ' . (int)$tour->isActive() .
                        ', autostart: ' . (int)$tour->isAutomaticTriggered() .
                        ', lang_match: ' . (int)$languageMatches .
                        ', type: ' . $tour->getType() .
                        ', type_match: ' . (int)$typeMatches .
                        ', ref_id: ' . ($tour->getRefId() ?? 'NULL') .
                        ', ref_id_match: ' . (int)$refIdMatches .
                        ', context_match: ' . (int)$contextMatches .
                        ', eligible: ' . (int)$this->isUserEligibleForTour($userGlobalRoles, $tour));

                    if ($tour->isActive() && $tour->isAutomaticTriggered() && $languageMatches && $contextMatches && $this->isUserEligibleForTour($userGlobalRoles, $tour)) {
                        $jsonString = $this->cleanJsonString($tour->getEffectiveScript());
                        if ($this->isValidJson($jsonString)) {
                            $config->steps = $jsonString;
                            $config->name = 'gtour-' . $tour->getId();
                            $logger->debug('GuidedTour: Selected tour ' . $tour->getId() . ' for autostart');

                            // todo: implement always forced start to gtour object as new option
                            $forceStartAlways = false;
                            if($forceStartAlways) {
                                $config->forceStart = true;
                            }
                            break;
                        } else {
                            $logger->warning('GuidedTour: Tour ' . $tour->getId() . ' has invalid JSON');
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
            $meta_content = $globalScreen->layout()->meta();
            $meta_content->addOnloadCode("il.Plugins.GuidedTour.init(" . $jsonConfig . ");", 1);

            $this->plugin->setIsLoaded(true);
        }
    }

    /**
     * Get name of the current triggered tour otherwise returns `null` if there is no currently triggered tour
     * @return string|null
     */
    protected function getTriggeredGuidedTour(): ?string
    {
        // Check if 'REQUEST_URI' is set in $_SERVER
        if (isset($_SERVER["REQUEST_URI"])) {
            $uri = parse_url($_SERVER["REQUEST_URI"]);
            if ($uri !== false && isset($uri["query"])) {
                parse_str($uri["query"], $params);
                if (array_key_exists("triggerTour", $params)) {
                    return $params["triggerTour"];
                }
            }
        }

        // Return null if 'REQUEST_URI' is not set or any other conditions fail
        return null;
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

        if (isset($DIC['global_screen'])) {
            // Attempt to retrieve the refId directly
            $refId = $DIC->globalScreen()->tool()->context()->current()->getReferenceId()->toInt();

            // Return refId if it's a valid positive integer, otherwise, fetch using alternative method
            return ($refId > 0) ? $refId : $this->getRefIdByCurrentClassPathParameter();
        } else {
            return -1;
        }
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

    /**
     * Initialize Internal ID Mapping system
     * Injects mapping code into GlobalScreen components using ComponentDecorators
     * Works independently of ILIAS version
     *
     * @return void
     */
    protected function initInternalIdMapping(): void
    {
        global $DIC;

        // Only initialize once per request (prevent recursion!)
        if (self::$mappingsProvided) {
            return;
        }

        try {
            // Check if template is available (may not be during early initialization)
            if (!isset($DIC['tpl'])) {
                return; // Template not available yet, will retry on next call
            }

            // Mark as provided to prevent recursion (only AFTER template check)
            self::$mappingsProvided = true;

            // Add internal-id-mapper.js
            $tpl = $DIC->ui()->mainTemplate();
            $tpl->addJavaScript($this->plugin->getDirectory() . '/js/internal-id-mapper.js');

            // Inject mapping code into GlobalScreen components
            $this->injectMappingIntoGlobalScreen();

        } catch (\Exception $e) {
            // Silently fail
            $DIC->logger()->root()->debug('GuidedTour: Could not initialize internal ID mapping: ' . $e->getMessage());
        }
    }

    /**
     * Inject mapping registration code into GlobalScreen components
     * Uses ComponentDecorator to add JavaScript that registers internal_id → frontend_id mappings
     *
     * @return void
     */
    protected function injectMappingIntoGlobalScreen(): void
    {
        global $DIC;

        if (!isset($DIC['global_screen'])) {
            $DIC->logger()->root()->debug('GuidedTour Mapping: GlobalScreen not available');
            return;
        }

        $gs = $DIC['global_screen'];

        // MainBar items
        try {
            $gs->collector()->mainmenu()->collectOnce();
            $mainMenuItems = $gs->collector()->mainmenu()->getRawItems();

            $DIC->logger()->root()->debug('GuidedTour Mapping: Iterating MainBar items...');

            $count = 0;
            foreach ($mainMenuItems as $item) {
                $count++;
                if (!method_exists($item, 'getProviderIdentification')) {
                    continue;
                }

                $provider = $item->getProviderIdentification();
                if (!method_exists($provider, 'getInternalIdentifier')) {
                    continue;
                }

                $internal_id = $provider->getInternalIdentifier();
                if (empty($internal_id)) {
                    continue;
                }

                $DIC->logger()->root()->debug("GuidedTour Mapping: MainBar item with ID: $internal_id");

                // Add ComponentDecorator to inject mapping registration
                if (method_exists($item, 'addComponentDecorator')) {
                    $DIC->logger()->root()->debug("GuidedTour Mapping: Adding decorator for: $internal_id");

                    $item->addComponentDecorator(function(\ILIAS\UI\Component\Component $component) use ($internal_id, $DIC): \ILIAS\UI\Component\Component {
                        // Check if component supports withAdditionalOnLoadCode
                        if (method_exists($component, 'withAdditionalOnLoadCode')) {
                            $DIC->logger()->root()->debug("GuidedTour Mapping: Injecting JS for: $internal_id");
                            return $component->withAdditionalOnLoadCode(function(string $id) use ($internal_id): string {
                                return "il.Plugins.GuidedTour.registerMapping('$internal_id', '$id');";
                            });
                        }
                        return $component;
                    });
                } else {
                    $DIC->logger()->root()->debug("GuidedTour Mapping: Item does not support addComponentDecorator: $internal_id");
                }
            }

            $DIC->logger()->root()->debug("GuidedTour Mapping: Processed $count MainBar items");

        } catch (\Exception $e) {
            // Log error
            $DIC->logger()->root()->debug('GuidedTour Mapping: MainBar error: ' . $e->getMessage());
        }

        // MetaBar items (similar approach)
        try {
            $DIC->logger()->root()->debug('GuidedTour Mapping: Starting MetaBar collection...');
            $metaBarCollector = $gs->collector()->metaBar();

            // Debug: Log available methods
            $methods = get_class_methods($metaBarCollector);
            $DIC->logger()->root()->debug('GuidedTour Mapping: MetaBar methods: ' . implode(', ', $methods));

            $metaBarCollector->collectOnce();

            // MetaBar has different API than MainBar!
            // Use getItemsForUIRepresentation() instead
            $metaBarItems = [];
            if (method_exists($metaBarCollector, 'getItemsForUIRepresentation')) {
                $metaBarItems = $metaBarCollector->getItemsForUIRepresentation();
                $DIC->logger()->root()->debug('GuidedTour Mapping: Using getItemsForUIRepresentation() for MetaBar');

                // Check type
                if (is_array($metaBarItems)) {
                    $DIC->logger()->root()->debug('GuidedTour Mapping: MetaBar returned array with ' . count($metaBarItems) . ' items');
                } else {
                    $itemType = get_class($metaBarItems);
                    $DIC->logger()->root()->debug("GuidedTour Mapping: MetaBar returned: $itemType");
                }
            } else {
                $DIC->logger()->root()->debug('GuidedTour Mapping: MetaBar has no getItemsForUIRepresentation()!');
            }

            $count = 0;
            foreach ($metaBarItems as $item) {
                $count++;
                if (!method_exists($item, 'getProviderIdentification')) {
                    $DIC->logger()->root()->debug("GuidedTour Mapping: MetaBar item $count has no getProviderIdentification()");
                    continue;
                }

                $provider = $item->getProviderIdentification();
                if (!method_exists($provider, 'getInternalIdentifier')) {
                    $DIC->logger()->root()->debug("GuidedTour Mapping: MetaBar item $count provider has no getInternalIdentifier()");
                    continue;
                }

                $internal_id = $provider->getInternalIdentifier();
                if (empty($internal_id)) {
                    $DIC->logger()->root()->debug("GuidedTour Mapping: MetaBar item $count has empty internal_id");
                    continue;
                }

                $DIC->logger()->root()->debug("GuidedTour Mapping: Adding decorator for MetaBar: $internal_id");

                // Add ComponentDecorator to inject mapping registration
                if (method_exists($item, 'addComponentDecorator')) {
                    $item->addComponentDecorator(function(\ILIAS\UI\Component\Component $component) use ($internal_id): \ILIAS\UI\Component\Component {
                        if (method_exists($component, 'withAdditionalOnLoadCode')) {
                            return $component->withAdditionalOnLoadCode(function(string $id) use ($internal_id): string {
                                return "il.Plugins.GuidedTour.registerMapping('$internal_id', '$id');";
                            });
                        }
                        return $component;
                    });
                }
            }

            $DIC->logger()->root()->debug("GuidedTour Mapping: Processed $count MetaBar items");
        } catch (\Exception $e) {
            // Silently fail
            $DIC->logger()->root()->debug('GuidedTour Mapping: MetaBar error: ' . $e->getMessage());
        }
    }

    /**
     * Build internal ID mappings from ILIAS GlobalScreen collectors
     * Collects stable internal identifiers from MainBar and MetaBar items
     *
     * @return array Array of mappings with 'type' and 'internal_id' keys
     */
    protected function buildInternalIdMappings(): array
    {
        global $DIC;

        $mappings = [];

        try {
            if (!isset($DIC['global_screen'])) {
                return $mappings;
            }

            $gs = $DIC->globalScreen();

            // Collect MainBar items
            try {
                $gs->collector()->mainmenu()->collectOnce();
                $mainMenuItems = $gs->collector()->mainmenu()->getRawItems();

                foreach ($mainMenuItems as $item) {
                    if (method_exists($item, 'getProviderIdentification')) {
                        $provider = $item->getProviderIdentification();
                        if (method_exists($provider, 'getInternalIdentifier')) {
                            $internal_id = $provider->getInternalIdentifier();
                            if (!empty($internal_id)) {
                                $mappings[] = [
                                    'type' => 'mainbar',
                                    'internal_id' => $internal_id
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue
                $DIC->logger()->root()->debug('GuidedTour: Error collecting MainBar items: ' . $e->getMessage());
            }

            // Collect MetaBar items
            try {
                $gs->collector()->metaBar()->collectOnce();
                $metaBarItems = $gs->collector()->metaBar()->getRawItems();

                foreach ($metaBarItems as $item) {
                    if (method_exists($item, 'getProviderIdentification')) {
                        $provider = $item->getProviderIdentification();
                        if (method_exists($provider, 'getInternalIdentifier')) {
                            $internal_id = $provider->getInternalIdentifier();
                            if (!empty($internal_id)) {
                                $mappings[] = [
                                    'type' => 'metabar',
                                    'internal_id' => $internal_id
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue
                $DIC->logger()->root()->debug('GuidedTour: Error collecting MetaBar items: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            // Log general error
            $DIC->logger()->root()->debug('GuidedTour: Error building internal ID mappings: ' . $e->getMessage());
        }

        return $mappings;
    }
}