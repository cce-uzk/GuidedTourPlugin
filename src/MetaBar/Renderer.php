<?php
namespace uzk\gtour\MetaBar;

use ILIAS\UI\Renderer as RendererInterface;
use ILIAS\UI\Component\MainControls\MetaBar;
use ILIAS\UI\Implementation\Component\MainControls\Renderer as DefaultRenderer;

/**
 * Class Renderer
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 */
class Renderer extends DefaultRenderer {

    /**
     * @inheritdoc
     */
    protected function renderMetabar(MetaBar $component, RendererInterface $default_renderer) {
        return "<div class='gtour_meta'>".parent::renderMetabar($component,$default_renderer)."</div>";
    }

    /**
     * Get the path to the template of this component.
     *
     * @param	string	$name
     * @return	string
     */
    protected function getTemplatePath($name) {
        if($name == "tpl.metabar.html"){
            return "src/UI/templates/default/MainControls/$name";
        }else{
            return parent::getTemplatePath($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function registerResources(\ILIAS\UI\Implementation\Render\ResourceRegistry $registry) {
        parent::registerResources($registry);
        //$registry->register('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/GuidedTour/js/gtour_meta.js');
        //$registry->register('Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/GuidedTour/js/gtour_meta.css');
    }
}