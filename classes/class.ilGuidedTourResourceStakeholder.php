<?php

use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;

/**
 * Class ilGuidedTourResourceStakeholder
 * Required Class for Integrated-Ressource-Storage-Service (IRSS) usage
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 */
class ilGuidedTourResourceStakeholder extends AbstractResourceStakeholder implements ResourceStakeholder
{
    /**
     * Get IRSS-ProviderId (in this case: PluginId)
     * @return string
     */
    public function getId(): string
    {
        return ilGuidedTourPlugin::PLUGIN_ID;
    }

    /**
     * Get RessourceOwnerId (in this case: System-User-Id)
     * @return int
     */
    public function getOwnerOfNewResources(): int
    {
        return 6;
    }
}