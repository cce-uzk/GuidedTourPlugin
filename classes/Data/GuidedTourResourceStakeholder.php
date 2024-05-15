<?php declare(strict_types=1);

namespace uzk\gtour\Data;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\ResourceStorage\Stakeholder\AbstractResourceStakeholder;
use ILIAS\ResourceStorage\Stakeholder\ResourceStakeholder;
use ilGuidedTourPlugin;

/**
 * Class ilGuidedTourResourceStakeholder
 * Required Class for Integrated-Ressource-Storage-Service (IRSS) usage
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class GuidedTourResourceStakeholder extends AbstractResourceStakeholder implements ResourceStakeholder
{
    public function __construct()
    {

    }

    /**
     * Get IRSS-ProviderId (in this case: PluginId)
     * @return string
     */
    public function getId() : string
    {
        return ilGuidedTourPlugin::PLUGIN_ID;
    }

    /**
     * Get RessourceOwnerId (in this case: System-User-Id)
     * @return int
     */
    public function getOwnerOfNewResources() : int
    {
        return 6;
    }
}