<?php

use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;

class ilGuidedTourResourceStakeholder extends AbstractResourceStakeholder implements ResourceStakeholder
{

    public function getId(): string
    {
        return ilGuidedTourPlugin::PLUGIN_ID;
    }

    public function getOwnerOfNewResources(): int
    {
        return 6;
    }
}