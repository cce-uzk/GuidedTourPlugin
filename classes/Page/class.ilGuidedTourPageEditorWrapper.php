<?php declare(strict_types=1);

require_once './Services/COPage/classes/class.ilPageEditorGUI.php';

/**
 * Wrapper around ilPageEditorGUI that filters conflicting parameters
 *
 * The parent constructor reads ctype/cname from the request and stores them
 * in $this->requested_ctype and $this->requested_cname. We need to clear
 * these AFTER construction to prevent misinterpretation of plugin path params.
 *
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCParagraphGUI, ilPCTableGUI, ilPCTableDataGUI
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCMediaObjectGUI, ilPCListGUI, ilPCListItemGUI
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCFileListGUI, ilPCFileItemGUI, ilObjMediaObjectGUI
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCSourceCodeGUI, ilInternalLinkGUI, ilPCQuestionGUI
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCSectionGUI, ilPCDataTableGUI, ilPCResourcesGUI
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCMapGUI, ilPCPluggedGUI, ilPCTabsGUI, ilPCTabGUI, IlPCPlaceHolderGUI
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCContentIncludeGUI, ilPCLoginPageElementGUI
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCInteractiveImageGUI, ilPCProfileGUI, ilPCVerificationGUI
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCBlogGUI, ilPCQuestionOverviewGUI, ilPCSkillsGUI
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCConsultationHoursGUI, ilPCMyCoursesGUI, ilPCAMDPageListGUI
 * @ilCtrl_Calls ilGuidedTourPageEditorWrapper: ilPCGridGUI, ilPCGridCellGUI, ilPageEditorServerAdapterGUI
 */
class ilGuidedTourPageEditorWrapper extends ilPageEditorGUI
{
    public function __construct(
        ilPageObject $a_page_obj,
        ilPageObjectGUI $a_page_gui
    ) {
        // Call parent constructor
        // This will set $this->requested_ctype and $this->requested_cname
        // from the HTTP request (which contain plugin path values)
        parent::__construct($a_page_obj, $a_page_gui);

        // Now override the properties that were set from the request
        // Use reflection to access protected properties
        try {
            $reflection = new \ReflectionClass(ilPageEditorGUI::class);

            // Clear requested_ctype property
            $prop_ctype = $reflection->getProperty('requested_ctype');
            $prop_ctype->setAccessible(true);
            $prop_ctype->setValue($this, '');

            // Clear requested_cname property
            $prop_cname = $reflection->getProperty('requested_cname');
            $prop_cname->setAccessible(true);
            $prop_cname->setValue($this, '');
        } catch (\ReflectionException $e) {
            // If reflection fails, log but continue
            // The page editor might still work for basic operations
        }
    }

    /**
     * Override executeCommand to handle forwarding and catch returnToParent calls
     */
    public function executeCommand(): string
    {
        global $DIC;
        $ctrl = $DIC->ctrl();

        $next_class = $ctrl->getNextClass($this);

        // Manually handle forwarding to ilPageEditorServerAdapterGUI
        // because ilCtrl doesn't automatically know about our wrapper class
        if ($next_class == "ilpageeditorserveradaptergui" ||
            strtolower($ctrl->getCmdClass()) == "ilpageeditorserveradaptergui") {

            require_once './Services/COPage/Editor/class.ilPageEditorServerAdapterGUI.php';
            $adapter = new \ilPageEditorServerAdapterGUI(
                $this->page_gui,
                $ctrl,
                $DIC->ui(),
                $DIC->http()->request()
            );
            return $ctrl->forwardCommand($adapter);
        }

        try {
            return parent::executeCommand();
        } catch (\TypeError $e) {
            // If parent's returnToParent fails, use our manual redirect
            if (strpos($e->getMessage(), 'returnToParent') !== false ||
                strpos($e->getMessage(), 'appendParameterString') !== false) {
                $this->returnToParent();
                exit; // returnToParent redirects, so we exit here
            }
            throw $e;
        }
    }

    /**
     * Override returnToParent to provide direct redirect
     * Uses the return URL set on the parent page GUI
     */
    protected function returnToParent(): void
    {
        global $DIC;
        $ctrl = $DIC->ctrl();

        // First, try to use the return URL set on the page GUI
        if ($this->page_gui instanceof \ilGuidedTourStepPageGUI) {
            $return_url = $this->page_gui->getReturnUrl();
            if ($return_url) {
                ilUtil::redirect($return_url);
                return;
            }
        }

        // Try to use ilCtrl's return mechanism
        $parent_url = $ctrl->getParentReturn($this);
        if ($parent_url !== null) {
            try {
                $ctrl->returnToParent($this);
                return;
            } catch (\Throwable $e) {
                // If that fails, fall through to manual redirect
            }
        }

        // Last resort: Manual redirect back to plugin config
        $query_params = $DIC->http()->request()->getQueryParams();
        $tour_id = (int)($query_params['tour_id'] ?? 0);

        if ($tour_id > 0) {
            // Use ilCtrl to generate the proper URL with full class path
            $ctrl->setParameterByClass('ilGuidedTourConfigGUI', 'tour_id', $tour_id);
            $ctrl->setParameterByClass('ilGuidedTourConfigGUI', 'ctype', 'Services');
            $ctrl->setParameterByClass('ilGuidedTourConfigGUI', 'cname', 'UIComponent');
            $ctrl->setParameterByClass('ilGuidedTourConfigGUI', 'slot_id', 'uihk');
            $ctrl->setParameterByClass('ilGuidedTourConfigGUI', 'plugin_id', 'gtour');
            $ctrl->setParameterByClass('ilGuidedTourConfigGUI', 'pname', 'GuidedTour');
            $ctrl->setParameterByClass('ilGuidedTourConfigGUI', 'ref_id', SYSTEM_FOLDER_ID);

            $back_url = $ctrl->getLinkTargetByClass(
                ['ilAdministrationGUI', 'ilObjComponentSettingsGUI', 'ilGuidedTourConfigGUI'],
                'showTourSteps'
            );

            ilUtil::redirect($back_url);
        }
    }
}
