<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use uzk\gtour\MainBar\GuidedTourMainBarProvider;
use ILIAS\GlobalScreen\Provider\ProviderCollection;

/**
 * Class ilGuidedTourPlugin
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class ilGuidedTourPlugin extends ilUserInterfaceHookPlugin
{
    /** @var string */
    const PLUGIN_ID = "gtour";
    /** @var string */
    const PLUGIN_NAME = "GuidedTour";
    /** @var string
     * @noinspection SpellCheckingInspection
     */
    const CTYPE = "Services";
    /** @var string */
    const CNAME = "UIComponent";
    /** @var string */
    const SLOT_ID = "uihk";

    /** @var self|null */
    protected static ?ilGuidedTourPlugin $instance = null;
    protected ProviderCollection $provider_collection;
    protected bool $isLoaded = false;

    /**
     * ilGuidedTourPlugin constructor
     */
    public function __construct(
        ilDBInterface $db,
        ilComponentRepositoryWrite $component_repository,
        string $id)
    {
        global $DIC;

        // Initialize plugin
        $this->db = $db;
        $this->component_repository = $component_repository;
        $this->id = $id;
        parent::__construct($db, $component_repository, $id);

        if (!isset($DIC["global_screen"])) {
            return;
        }

        // Add GuidedTourMainBarProvider to provider collection
        $this->addPluginProviders();

        // Add scripts and styles to metadata
        $this->addMetadata();
    }

    /**
     * @return void
     */
    private function addPluginProviders(): void
    {
        global $DIC;

        $this->provider_collection->setMainBarProvider(new GuidedTourMainBarProvider($DIC, $this));
    }

    /**
     * @return void
     */
    private function addMetadata(): void {
        global $DIC;

        $directory = $this->getDirectory();
        $meta_content = $DIC->globalScreen()->layout()->meta();
        $meta_content->addJs($directory . '/vendor/bootstrap-tourist/bootstrap-tourist.js', false, 1);
        $meta_content->addCss($directory . '/vendor/bootstrap-tourist/bootstrap-tourist.css');
        $meta_content->addCss($directory . '/vendor/bootstrap-tourist/bootstrap-tour.css');
        $meta_content->addJs($directory . '/js/main.js', false, 1);
    }

    /**
     * Get plugin name
     * @return string
     */
    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }

    /**
     * Get plugin instance
     * @return self|null
     * @throws Exception
     */
    public static function getInstance(): ?self
    {
        global $DIC;

        if (self::$instance instanceof self) {
            return self::$instance;
        }

        $component_repository = $DIC['component.repository'];
        $component_factory = $DIC['component.factory'];

        if (isset($component_factory) && isset($component_repository)) {
            $plugin_info = $component_repository->getComponentByTypeAndName(
                self::CTYPE,
                self::CNAME
            )->getPluginSlotById(self::SLOT_ID)->getPluginByName(self::PLUGIN_NAME);

            self::$instance = $component_factory->getPlugin($plugin_info->getId());

            return self::$instance;
        } else {
            return null;
        }
    }

    /**
     * Define uninstall handling
     * @return bool
     */
    public function uninstall(): bool
    {
        // uninstall languages
        $this->getLanguageHandler()->uninstall();

        // deregister from component repository
        $this->component_repository->removeStateInformationOf($this->getId());

        // drop tables
        $this->db->dropTable('gtour_tours');

        return true;
    }

    /**
     * @return bool
     */
    public function isLoaded(): bool {
        return $this->isLoaded;
    }

    /**
     * @param bool $isLoaded
     * @return void
     */
    public function setIsLoaded(bool $isLoaded): void
    {
        $this->isLoaded = $isLoaded;
    }
}