<?php
namespace uzk\gtour\MetaBar;

use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;
use ILIAS\UI\Implementation\Component\MainControls\Metabar;

/**
 * Class GuidedTourMetaBar
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 */
class GuidedTourMetaBar extends Metabar
{
    public function __construct(SignalGeneratorInterface $signal_generator) {
        parent::__construct($signal_generator);

        global $DIC;

        $this->entries["CustomEntry"] = $DIC->ui()->factory()->mainControls()->slate()->legacy(
            "GuidedTour",
            $DIC->ui()->factory()->symbol()->icon()->standard("-","gtour"),
            $DIC->ui()->factory()->legacy("<div>Some Cool new Feature</div>"));
    }
}