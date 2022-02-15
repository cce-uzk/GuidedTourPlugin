<?php
namespace uzk\gtour\MainBar;

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\MainMenu\Factory\TopItem\TopParentItem;
use ILIAS\GlobalScreen\Scope\MainMenu\Provider\AbstractStaticMainMenuPluginProvider;

/**
 * Class GuidedTourMainBarProvider
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 */
class GuidedTourMainBarProvider extends AbstractStaticMainMenuPluginProvider
{
    /**
     * @var \ILIAS\DI\Container
     */
    protected $dic;

    /**
     * @var ilPlugin $plugin
     */
    protected $plugin;

    /**
     * @return TopParentItem[]
     *
     * This Method return all TopItems for the MainMenu.
     * Make sure you use the same Identifier for all subitems as well,
     * @see getParentIdentifier().
     * Using $this->if-> (if stands for IdentificationFactory) you will already
     * get a PluginIdentification for your Plugin-Instance.
     */
    public function getStaticTopItems(): array
    {
        $mainBar = $this->globalScreen()->mainBar();

        $identificationInterface = function ($id) : IdentificationInterface {
            return $this->if->identifier($id);
        };

        $icon = $this->dic->ui()->factory()->symbol()->icon()
            ->custom(
                $this->plugin->getImagePath("signpost-split-sm.svg"),
                "Guided Tour");

        // "Guided Tour"-Menu
        $item[] = $mainBar->topParentItem($identificationInterface($this->getPluginID()))
            ->withTitle('Guided Tour')
            ->withSymbol($icon)
            ->withPosition(100)
            ->withVisibilityCallable(
                function () {
                    return $this->isUserLoggedIn();
                }
            );

        $mainBar->topLinkItem($identificationInterface($this->getPluginID()))->withAction("action")->withSymbol($icon);

        return $item;
    }

    /**
     * Accordingly this method provides the Subitems.
     * By using $this->mainmenu->custom(...) you can even use your own Types.
     * Make sure you provide special information and rendering for won types if
     * needed, @see provideTypeInformation()
     *
     * @inheritdoc
     */
    public function getStaticSubItems(): array
    {
        global $DIC;
        $mainBar = $this->globalScreen()->mainBar();
        $identificationInterface = function ($id) : IdentificationInterface {
            return $this->if->identifier($id);
        };
        $subItems = array();
        $tours = \ilGuidedTour::getTours();

        if(isset($tours))
        {

            $countDefaultTours = 0;
            foreach ($tours as $tour){
                if($tour->getType() == \ilGuidedTour::TYPE_DEFAULT && $tour->isActive()) {

                    $countDefaultTours++;
                    if($countDefaultTours == 1){
                        array_push(
                            $subItems,
                            $mainBar->separator($identificationInterface( $this->getPluginID() .'-sep-1'))
                                ->withTitle($this->plugin->txt('default_tours'))
                                ->withParent($identificationInterface($this->getPluginID()))
                        );
                    }

                    $item = $mainBar->link(
                        $identificationInterface($this->getPluginID() . '-' . $tour->getTourId())
                    );

                    if (!empty($tour->getIconSrc())) {
                        $icon = $this->dic->ui()->factory()->symbol()->icon()
                            ->custom($tour->getIconSrc(), $tour->getTitle());
                        $item = $item->withSymbol($icon);
                    }

                    $item = $item
                        ->withAction($this->getGuidedTourFullUrl('gtour-' . $tour->getTourId()))
                        ->withParent($identificationInterface($this->getPluginID()))
                        ->withTitle($tour->getTitle());

                    array_push(
                        $subItems,
                        $item
                    );
                }
            }


            $countContextTours = 0;
            foreach ($tours as $tour){
                if(($tour->getType() == $this->dic->ctrl()->getContextObjType() || in_array($DIC->ctrl()->getCmdClass(), array($tour->getType()))) && $tour->isActive()) {

                    $countContextTours++;
                    if($countContextTours == 1) {
                        array_push(
                            $subItems,
                            $mainBar->separator($identificationInterface($this->getPluginID() . '-sep-2'))
                                ->withTitle($this->plugin->txt('context_tours'))
                                ->withParent($identificationInterface('gtour'))
                        );
                    }

                    $item = $mainBar->link(
                        $identificationInterface($this->getPluginID() . '-' . $tour->getTourId())
                    );

                    if (!empty($tour->getIconSrc())) {
                        $icon = $this->dic->ui()->factory()->symbol()->icon()
                            ->custom($tour->getIconSrc(), $tour->getTitle());
                        $item = $item->withSymbol($icon);
                    }

                    $item = $item
                        ->withAction($this->getGuidedTourFullUrl('gtour-' . $tour->getTourId()))
                        ->withParent($identificationInterface($this->getPluginID()))
                        ->withTitle($tour->getTitle());

                    array_push(
                        $subItems,
                        $item
                    );
                }
            }
        }

        return $subItems;
    }

    private function isUserLoggedIn() : bool
    {
        return (!$this->dic->user()->isAnonymous() && $this->dic->user()->getId() != 0);
    }

    private function getGuidedTourFullUrl($object): string
    {
        $root = $this->getRootUrl();
        $uri = parse_url($_SERVER["REQUEST_URI"]);
        $path = $uri["path"];

        $params = null;
        parse_str($uri["query"], $params);

        $params["triggerTour"] = $object;
        $query_result = http_build_query($params);

        return $root . $path . '?' . $query_result;
    }

    private function getRootUrl(): string
    {
        $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
        $sp = strtolower($_SERVER["SERVER_PROTOCOL"]);
        $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
        $port = ($_SERVER["SERVER_PORT"] == "80" || $_SERVER["SERVER_PORT"] == "443") ? "" : (":" . $_SERVER["SERVER_PORT"]);
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port;// . $_SERVER['REQUEST_URI'];
    }
}