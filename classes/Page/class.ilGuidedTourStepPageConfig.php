<?php declare(strict_types=1);

/**
 * Class ilGuidedTourStepPageConfig
 * Page Configuration for Guided Tour Step Content
 *
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version 2.1.0
 */
class ilGuidedTourStepPageConfig extends ilPageConfig
{
    /**
     * Initialize page config
     */
    public function init(): void
    {
        // Enable basic page content types
        $this->setEnablePCType("Paragraph", true);  // Most important - text content
        $this->setEnablePCType("MediaObject", true);
        $this->setEnablePCType("FileList", true);
        $this->setEnablePCType("FileItem", true);
        $this->setEnablePCType("List", true);
        $this->setEnablePCType("Table", true);

        // Disable Resources - requires Container context which we don't have in plugin config
        $this->setEnablePCType("Resources", false);

        $this->setPreventHTMLUnmasking(false);
        $this->setEnableInternalLinks(true);
        $this->setEnableKeywords(false);
        $this->setEnableAnchors(false);
    }
}
