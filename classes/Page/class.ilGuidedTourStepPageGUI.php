<?php declare(strict_types=1);

/**
 * Class ilGuidedTourStepPageGUI
 * GUI for Guided Tour Step Page Editor
 *
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version 2.1.0
 *
 * @ilCtrl_Calls ilGuidedTourStepPageGUI: ilPageEditorGUI, ilEditClipboardGUI, ilMDEditorGUI
 * @ilCtrl_Calls ilGuidedTourStepPageGUI: ilPublicUserProfileGUI, ilNoteGUI
 * @ilCtrl_IsCalledBy ilGuidedTourStepPageGUI: ilGuidedTourConfigGUI
 */
class ilGuidedTourStepPageGUI extends ilPageObjectGUI
{
    private ?string $return_url = null;
    private ?\ilGuidedTourStepPage $loaded_page_object = null;

    /**
     * Constructor - manually load page object to avoid factory lookup
     * Uses 'lm' as parent type since it's widely supported by all Page Component Plugins
     */
    public function __construct(string $a_parent_type, int $a_id = 0, int $a_old_nr = 0, bool $a_prevent_get_id = false, string $a_lang = "")
    {
        // Manually create the page object
        require_once __DIR__ . '/class.ilGuidedTourStepPage.php';

        $page_object = new \ilGuidedTourStepPage($a_id);

        // IMPORTANT: Load page content from database if it exists
        if ($a_id > 0 && $page_object->_exists('gtst', $a_id)) {
            try {
                $page_object->read();
                // CRITICAL: Build DOM immediately after reading to prevent null DOM errors
                $page_object->buildDom();
            } catch (Exception $e) {
                // Failed to load page - continue with empty page
            }
        }

        // Store the loaded page object to prevent it from being overwritten by factory
        $this->loaded_page_object = $page_object;

        // Call parent constructor with prevent_get_id=true to skip factory lookup
        parent::__construct('gtst', $a_id, $a_old_nr, true, $a_lang);

        // Set our page object after parent construction
        $this->setPageObject($page_object);
    }

    /**
     * Override getPageObject to always return our loaded page object
     * This prevents the parent from creating a new empty page object via factory
     *
     * @return ilPageObject
     */
    public function getPageObject(): ilPageObject
    {
        // Always return our pre-loaded page object if available
        if ($this->loaded_page_object !== null) {
            return $this->loaded_page_object;
        }

        // Fallback to parent implementation
        return parent::getPageObject();
    }

    /**
     * Override to use custom page editor that filters conflicting parameters
     * @return string
     */
    public function executeCommand(): string
    {
        global $DIC;
        $ctrl = $DIC->ctrl();
        $tpl = $DIC['tpl'];

        // Set page header with plugin icon, title and description
        require_once __DIR__ . '/../class.ilGuidedTourPlugin.php';
        $plugin = ilGuidedTourPlugin::getInstance();
        $tpl->setTitle($plugin->txt('plugin_title'));
        $tpl->setDescription($plugin->txt('plugin_description'));
        $tpl->setTitleIcon(
            $plugin->getDirectory() . '/templates/images/signpost-split.svg',
            $plugin->txt('plugin_title')
        );

        $next_class = $ctrl->getNextClass($this);
        $cmd = $ctrl->getCmd();

        // Only intercept when explicitly forwarding to ilPageEditorGUI
        if ($next_class == "ilpageeditorgui") {
            // Use our custom wrapper that filters conflicting parameters
            require_once __DIR__ . '/class.ilGuidedTourPageEditorWrapper.php';

            $page_editor = new \ilGuidedTourPageEditorWrapper($this->getPageObject(), $this);
            $ret = $ctrl->forwardCommand($page_editor);

            return $ret;
        }

        // For all other cases (including cmd=edit), use parent handling
        return parent::executeCommand();
    }

    /**
     * Get tabs - override to prevent tabs from being shown
     * @param string $a_activate
     * @return void
     */
    public function getTabs(string $a_activate = ""): void
    {
    }

    /**
     * Set return URL for back/cancel navigation
     * @param string $url
     * @return void
     */
    public function setReturnUrl(string $url): void
    {
        $this->return_url = $url;
    }

    /**
     * Get return URL for back/cancel navigation
     * @return string|null
     */
    public function getReturnUrl(): ?string
    {
        return $this->return_url;
    }

    /**
     * Override to provide custom exit/return behavior
     * Called when page editor wants to return to parent context
     * @return void
     */
    protected function returnToParent(): void
    {
        if ($this->return_url) {
            ilUtil::redirect($this->return_url);
        } else {
            // Fallback: try to construct return URL from parameters
            global $DIC;
            $tour_id = (int)($DIC->http()->request()->getQueryParams()['tour_id'] ?? 0);

            if ($tour_id > 0) {
                $return_url = ILIAS_HTTP_PATH . '/ilias.php?baseClass=ilAdministrationGUI'
                    . '&cmdClass=ilGuidedTourConfigGUI'
                    . '&cmd=showTourSteps'
                    . '&ctype=Services'
                    . '&cname=UIComponent'
                    . '&slot_id=uihk'
                    . '&plugin_id=gtour'
                    . '&pname=GuidedTour'
                    . '&ref_id=' . SYSTEM_FOLDER_ID
                    . '&tour_id=' . $tour_id;
                ilUtil::redirect($return_url);
            }
        }
    }
}
