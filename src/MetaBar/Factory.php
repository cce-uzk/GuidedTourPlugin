<?php
namespace uzk\gtour\MetaBar;

use ILIAS\UI\Component\MainControls as IMainControls;
use ILIAS\UI\Implementation\Component\MainControls\Factory as DefaultFactory;

/**
 * Class Factory
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 */
class Factory extends DefaultFactory
{
    /**
     * @inheritdoc
     */
    public function metaBar(): IMainControls\MetaBar
    {
        return new GuidedTourMetaBar($this->signal_generator);
    }

}