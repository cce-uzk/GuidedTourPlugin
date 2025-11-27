<?php declare(strict_types=1);

require_once __DIR__ . '/class.ilGuidedTourStepPageConfig.php';

/**
 * Class ilGuidedTourStepPage
 * Page Object for Guided Tour Step Content
 *
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version 2.1.0
 */
class ilGuidedTourStepPage extends ilPageObject
{
    /**
     * Get parent type
     * Use 'lm' (Learning Module) as parent type since it's widely supported
     * by all Page Component Plugins. Our custom type 'gtst' would require
     * registering with each Page Component Plugin separately.
     * @return string
     */
    public function getParentType(): string
    {
        return 'gtst';
    }

    /**
     * Initialize page config
     * @return void
     */
    public function initPageConfig(): void
    {
        $this->setPageConfig(new ilGuidedTourStepPageConfig());
    }

    /**
     * Override getDomDoc to ensure DOM is built
     * @return DOMDocument
     */
    public function getDomDoc(): DOMDocument
    {
        try {
            $dom = parent::getDomDoc();
            if ($dom === null) {
                // DOM is null, try to build it
                $this->buildDom();
                $dom = parent::getDomDoc();
            }
            return $dom;
        } catch (\Throwable $e) {
            // If still null, create empty page structure
            $this->setXMLContent('<PageObject></PageObject>');
            $this->buildDom();
            return parent::getDomDoc();
        }
    }

    /**
     * Override getHierIdsForPCIds to ensure HierIDs are added to DOM first
     *
     * This is a workaround for ILIAS core bug where getHierIdsForPCIds() doesn't call addHierIDs()
     * before trying to read HierIds from the DOM, causing "Undefined array key" errors in
     * PageCommandActionHandler when deleting page content elements.
     *
     * @param array $a_pc_ids Array of PC IDs
     * @return array Associative array mapping PC IDs to Hier IDs
     */
    public function getHierIdsForPCIds(array $a_pc_ids): array
    {
        // Ensure DOM is built
        $this->buildDom();

        // Ensure HierIDs are added to the DOM before retrieving them
        // This fixes the "Undefined array key" error in PageCommandActionHandler:214
        $this->addHierIDs();

        // Call parent implementation which will now find the HierIDs
        return parent::getHierIdsForPCIds($a_pc_ids);
    }

    /**
     * Override getPCModel to ensure DOM is properly built before extracting models
     *
     * @return array
     */
    public function getPCModel(): array
    {
        // Ensure DOM is built
        $this->buildDom();

        // Call parent implementation
        return parent::getPCModel();
    }

    /**
     * Create a new page with the next available ID
     * Note: This is kept for backwards compatibility, but we now use step_id directly as page_id
     * @return int The new page ID
     * @deprecated Use step_id directly as page_id instead
     */
    public function createPageWithNextId(): int
    {
        $query = $this->db->query('SELECT max(page_id) as last_id FROM page_object WHERE parent_type='
            . $this->db->quote($this->getParentType(), 'text'));
        try {
            $assoc = $this->db->fetchAssoc($query);
            $this->setId(
                $assoc['last_id'] + 1
            );
            $this->createFromXML();
        } catch (ilDatabaseException $e) {
            $this->createPageWithNextId();
        }

        return $this->getId();
    }
}
