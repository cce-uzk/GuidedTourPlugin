<?php
namespace uzk\gtour\MetaBar;

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\MetaBar\Provider\AbstractStaticMetaBarProvider;
use ILIAS\GlobalScreen\Scope\MetaBar\Provider\AbstractStaticMetaBarPluginProvider;

/**
 * Class GuidedTourMetaBarProvider
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 */
class GuidedTourMetaBarProvider extends AbstractStaticMetaBarPluginProvider
{
    public function getMetaBarItems(): array
    {
        $f = $this->dic->ui()->factory();
        $txt = function ($id) {
            return $this->dic->language()->txt($id);
        };
        $mb = $this->globalScreen()->metaBar();
        $id = function ($id): IdentificationInterface {
            return $this->if->identifier($id);
        };

        $children = array();

        $icon = $this->dic->ui()->factory()->symbol()->icon()
            ->custom($this->plugin->getImagePath("pin-map.svg"), "Guided Tour");

        // "User"-Menu
        $item[] = $mb->topParentItem($id('gtour'))
            ->withTitle("Guided Tour")
            ->withSymbol($icon)
            ->withPosition(1)
            ->withVisibilityCallable(
                function () {
                    return $this->isUserLoggedIn();
                }
            )
            ->withChildren($children);

        $contextType = $this->dic->ctrl()->getContextObjType();
        //var_dump($this->getGuidedTourFullUrl($contextType));

        $item[] = $mb->topLinkItem($id('gtour'))
            ->withTitle("Guided Tour")
            ->withSymbol($icon)
            ->withPosition(1)
            ->withVisibilityCallable(
                function () {
                    return $this->isUserLoggedIn();
                }
            )
            ->withAction($this->getGuidedTourFullUrl($contextType));//$this->getFullUrl());

        return $item;
    }


    private function isUserLoggedIn(): bool
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

    /**
     * @return string
     */
    private function getPath(): string
    {
        return ILIAS_WEB_DIR . "/" . CLIENT_ID . "/";
    }
}