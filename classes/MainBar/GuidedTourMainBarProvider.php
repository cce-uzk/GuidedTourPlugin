<?php declare(strict_types=1);

namespace uzk\gtour\MainBar;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\TopItem\TopParentItem;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticMainMenuPluginProvider;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\MainMenuItemFactory;
use ILIAS\DI\UIServices;
use uzk\gtour\Model\GuidedTour;
use Exception;
use uzk\gtour\Data\GuidedTourRepository;
use Closure;

/**
 * Class GuidedTourMainBarProvider
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class GuidedTourMainBarProvider extends AbstractStaticMainMenuPluginProvider
{
    /**
     * @return TopParentItem[]
     * This Method return all TopItems for the MainMenu.
     * Make sure you use the same Identifier for all subitems as well,
     * @see getParentIdentifier().
     * Using $this->if-> (if stands for IdentificationFactory) you will already
     * get a PluginIdentification for your Plugin-Instance.
     */
    public function getStaticTopItems() : array
    {
        global $DIC;

        if (isset($DIC['global_screen'])) {
            $globalScreen = $DIC['global_screen'];
            $ui = $DIC->ui();

            // Function to create a unique identifier for UI elements
            $identificationInterface = function ($id) : IdentificationInterface {
                return $this->if->identifier($id);
            };

            $mainBar = $globalScreen->mainBar();

            // Creating an icon for the menu items
            $icon = $ui->factory()->symbol()->icon()
                       ->custom(
                           $this->plugin->getDirectory() . "/templates/images/" . "signpost-split-sm.svg",
                           $this->plugin->txt('guided_tour'));

            // Initialize the top parent item for the main bar
            $item[] = $mainBar->topParentItem($identificationInterface($this->getPluginID()))
                              ->withTitle($this->plugin->txt('guided_tour'))
                              ->withSymbol($icon)
                              ->withPosition(100)
                              ->withVisibilityCallable(
                                  function () {
                                      return $this->isUserLoggedIn();
                                  }
                              );

            // Adding a top link item to the main bar
            $mainBar->topLinkItem($identificationInterface($this->getPluginID()))->withAction("action")->withSymbol($icon);

            return $item;
        } else {
            return [];
        }
    }

    /**
     * Accordingly this method provides the Subitems.
     * By using $this->mainmenu->custom(...) you can even use your own Types.
     * Make sure you provide special information and rendering for won types if
     * needed, @throws Exception
     * @see provideTypeInformation()
     * @inheritdoc
     */
    public function getStaticSubItems() : array
    {
        global $DIC;
        $user = $DIC->user();
        $ctrl = $DIC->ctrl();
        $ui = $DIC->ui();

        if (isset($DIC['global_screen'])) {
            $globalScreen = $DIC['global_screen'];

            // Instantiate repository to retrieve guided tours
            $guidedTourRepository = new GuidedTourRepository();

            // Retrieve all global roles assigned to the user
            $userGlobalRoles = $DIC->rbac()->review()->assignedGlobalRoles($user->getId());
            $mainBar = $globalScreen->mainBar();

            // Function to create a unique identifier for UI elements
            $identificationInterface = function ($id) : IdentificationInterface {
                return $this->if->identifier($id);
            };

            $subItems = array();
            $tours = $guidedTourRepository->getTours();
            $countDefaultTours = 0;

            // Get current user language
            $userLanguage = $user->getLanguage();

            // Process default tours and add them to the navbar
            foreach ($tours as $tour) {
                // Check if tour matches user language (or has no language restriction)
                $languageMatches = ($tour->getLanguageCode() === null || $tour->getLanguageCode() === '' || $tour->getLanguageCode() === $userLanguage);

                if ($tour->getType() == GuidedTour::TYPE_DEFAULT && $tour->isActive()
                    && $languageMatches
                    && count(array_intersect($userGlobalRoles, $tour->getRolesIds())) > 0) {

                    $countDefaultTours++;
                    if ($countDefaultTours == 1) {
                        // Adds a separator before adding tours
                        $subItems[] = $mainBar->separator($identificationInterface($this->getPluginID() . '-sep-1'))
                                              ->withTitle($this->plugin->txt('default_tours'))
                                              ->withParent($identificationInterface($this->getPluginID()));
                    }

                    // Extracts tour items and adds to the subItems array
                    list($item, $icon, $subItems) = $this->extracted($mainBar, $identificationInterface, $tour, $ui,
                        $subItems);
                }
            }

            // Get current ref_id for ref_id-based tours
            $currentRefId = null;
            if (isset($_GET['ref_id'])) {
                $currentRefId = (int)$_GET['ref_id'];
            }

            // Process context-sensitive tours and add them to the navbar
            $countContextTours = 0;
            foreach ($tours as $tour) {
                // Check if tour matches user language (or has no language restriction)
                $languageMatches = ($tour->getLanguageCode() === null || $tour->getLanguageCode() === '' || $tour->getLanguageCode() === $userLanguage);

                // Check if tour matches current context (type OR ref_id)
                $typeMatches = ($tour->getType() == $ctrl->getContextObjType() || $tour->getType() == $ctrl->getCmdClass());
                $refIdMatches = ($tour->getRefId() === null || $tour->getRefId() === $currentRefId);

                if ($tour->isActive()
                    && $languageMatches
                    && ($typeMatches || $tour->getRefId() !== null) // Show if type matches OR ref_id is set
                    && $refIdMatches // Must match ref_id if set
                    && count(array_intersect($userGlobalRoles, $tour->getRolesIds())) > 0) {

                    $countContextTours++;
                    if ($countContextTours == 1) {
                        // Adds a separator for context-sensitive tours
                        $subItems[] = $mainBar->separator($identificationInterface($this->getPluginID() . '-sep-2'))
                                              ->withTitle($this->plugin->txt('context_tours'))
                                              ->withParent($identificationInterface('gtour'));
                    }

                    // Reuse the extracted function to handle tour items
                    list($item, $icon, $subItems) = $this->extracted($mainBar, $identificationInterface, $tour, $ui,
                        $subItems);
                }
            }

            return $subItems;
        } else {
            return [];
        }
    }

    /**
     * Check if current user is logged in
     * @return bool
     */
    private function isUserLoggedIn() : bool
    {
        global $DIC;
        $user = $DIC->user();
        return (!$user->isAnonymous() && $user->getId() != 0);
    }

    /**
     * Get GuidedTour-Trigger URL
     * Get current Full Url with the start trigger of a tour
     * @param $object
     * @return string
     */
    private function getGuidedTourFullUrl($object): string
    {
        $root = $this->getRootUrl();
        $uri = parse_url($_SERVER["REQUEST_URI"]);
        $path = $uri["path"] ?? '/'; // Provide a default path if none is specified.

        // Initialize params array and check if the query exists
        $params = [];
        if (!empty($uri["query"])) {
            parse_str($uri["query"], $params);
        }

        // Safely add or replace the 'triggerTour' parameter
        $params["triggerTour"] = htmlspecialchars($object, ENT_QUOTES, 'UTF-8');
        $query_result = http_build_query($params);

        return $root . $path . '?' . $query_result;
    }

    /**
     * Get the current root url
     * @return string
     */
    private function getRootUrl(): string
    {
        $protocol = (!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== 'off') ? 'https' : 'http';
        $port = ($_SERVER["SERVER_PORT"] == "80" || $_SERVER["SERVER_PORT"] == "443") ? "" : (":" . $_SERVER["SERVER_PORT"]);
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port;
    }

    /**
     * Creates a navigation link for a guided tour and adds it to the menu items.
     *
     * @param MainMenuItemFactory $mainBar The factory used to create menu items.
     * @param Closure $identificationInterface A closure that generates unique identifiers.
     * @param GuidedTour $tour The guided tour object containing the details for the link.
     * @param UIServices $ui UI Services to create UI components like icons.
     * @param array $subItems An array of existing sub-items to which the new item will be added.
     * @return array Returns an array containing the new item, optionally an icon, and the updated sub-items list.
     */
    public function extracted(
        MainMenuItemFactory $mainBar,
        Closure $identificationInterface,
        guidedTour $tour,
        UIServices $ui,
        array $subItems
    ) : array {
        // Create a new link item for the guided tour using a unique identifier.
        $item = $mainBar->link(
            $identificationInterface($this->getPluginID() . '-' . $tour->getId())
        );

        // Check if the tour provides a custom icon source and adds icon to item
        $icon = null;
        try {
            if (!empty($tour->getIconSrc())) {
                $icon = $ui->factory()->symbol()->icon()
                           ->custom($tour->getIconSrc(), $tour->getTitle());
                // Associate the created icon with the menu item.
                $item = $item->withSymbol($icon);
            }
        } catch (Exception) {

        }

        // Set the action (URL) for the menu item, which is generated based on the tour's ID.
        // Also set the parent menu and the title from the tour data.
        $item = $item
            ->withTitle($tour->getTitle())
            ->withAltText($tour->getTitle())
            ->withParent($identificationInterface($this->getPluginID()))
            ->withAction($this->getGuidedTourFullUrl('gtour-' . $tour->getId()));

        // Add the newly created item to the list of sub-items.
        $subItems[] = $item;

        // Return the newly created item, the icon (if any), and the updated list of sub-items.
        return array($item, $icon, $subItems);
    }
}