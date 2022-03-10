<?php
require_once __DIR__ . "/../vendor/autoload.php";

use uzk\gtour\MainBar\GuidedTourMainBarProvider;

/**
 * Class ilGuidedTourPlugin
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class ilGuidedTourPlugin extends ilUserInterfaceHookPlugin
{
    /** @var string */
    const PLUGIN_CLASS_NAME = self::class;
    /** @var string */
    const PLUGIN_ID = "gtour";
    /** @var string */
    const PLUGIN_NAME = 'GuidedTour';
    /** @var string */
    const CTYPE = 'Services';
    /** @var string */
    const CNAME = 'UIComponent';
    /** @var string */
    const SLOT_ID = 'uihk';

    /**
     * @var self|null
     */
    protected static $instance = null;

    /**
     * ilGuidedTourPlugin constructor
     */
    public function __construct()
    {
        parent::__construct();
        global $DIC;

        // Add GuidedTourMainBarProvider to MainBar-Provide-Collection
        $this->provider_collection->setMainBarProvider(new GuidedTourMainBarProvider($DIC, $this));
    }

    /**
     * @return self
     */
    public static function getInstance() : self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @inheritDoc
     */
    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * @inheritDoc
     */
    public function updateLanguages($a_lang_keys = null)
    {
        parent::updateLanguages($a_lang_keys);
    }

    /**
     * Execute before uninstall Plug-In
     */
    protected function beforeUninstall() : bool
    {
        self::dropTables();
        return parent::beforeUninstall();
    }

    /**
     * Drop GuidedTour tables
     */
    protected function dropTables()
    {
        global $ilDB;
        $ilDB->dropTable('gtour_tours');
    }

}