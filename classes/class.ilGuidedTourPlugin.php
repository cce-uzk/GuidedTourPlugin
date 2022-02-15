<?php
require_once __DIR__ . "/../vendor/autoload.php";

use uzk\gtour\MetaBar\GuidedTourMetaBarProvider;
use uzk\gtour\MainBar\GuidedTourMainBarProvider;

/**
 * Class ilGuidedTourPlugin
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
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

        //$this->provider_collection->setMetaBarProvider(new GuidedTourMetaBarProvider($DIC, $this));
        $this->provider_collection->setMainBarProvider(new GuidedTourMainBarProvider($DIC, $this));

    }

    /**
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Handles all commmands, default is "configure"
     */
    function performCommand($cmd)
    {

        switch ($cmd) {
            case "gtour":
                echo "<h1>Guided Tour clicked!</h1>";
            case "save":
                $this->$cmd();
                break;

        }
    }

    /*
    public function exchangeUIRendererAfterInitialization(Container $dic) : Closure
    {
        return CustomInputGUIsLoaderDetector::exchangeUIRendererAfterInitialization();
    }*/

    /**
     * @inheritDoc
     */
    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * @inheritDoc
     */
    public function updateLanguages(/*?array*/ $a_lang_keys = null)
    {
        parent::updateLanguages($a_lang_keys);

        //$this->installRemovePluginDataConfirmLanguages();
    }

    protected function beforeUninstall()
    {
        self::dropTables();
        return parent::beforeUninstall();
    }

    protected function dropTables()
    {
        global $ilDB;

        $ilDB->dropTable('gtour_tours');
    }

    /**
     * @inheritDoc
     */
    protected function shouldUseOneUpdateStepOnly(): bool
    {
        return false;
    }
}