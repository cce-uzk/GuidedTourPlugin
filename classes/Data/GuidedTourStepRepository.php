<?php declare(strict_types=1);

namespace uzk\gtour\Data;
require_once __DIR__ . "/../../vendor/autoload.php";

use uzk\gtour\Model\GuidedTourStep;
use ILIAS\DI\Container;
use ilDBInterface;
use ILIAS\DI\Exceptions\Exception;
use InvalidArgumentException;

/**
 * Class GuidedTourStepRepository
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class GuidedTourStepRepository
{
    protected ilDBInterface $db;

    public function __construct()
    {
        /** @var Container $DIC */
        global $DIC;

        $this->db = $DIC->database();
    }

    /**
     * Create a new step
     */
    public function createStep(GuidedTourStep $step): ?GuidedTourStep
    {
        try {
            if (empty($step->getId())) {
                $nextId = $this->db->nextId("gtour_steps");
                $step->setId($nextId);

                $data = [
                    'step_id' => ['integer', $step->getId()],
                    'tour_id' => ['integer', $step->getTourId()],
                    'sort_order' => ['integer', $step->getSortOrder()],
                    'element' => ['text', $step->getElement()],
                    'title' => ['text', $step->getTitle()],
                    'content' => ['clob', $step->getContent()],
                    'content_page_id' => ['integer', $step->getContentPageId()],
                    'placement' => ['text', $step->getPlacement()],
                    'orphan' => ['integer', $step->isOrphan() ? 1 : 0],
                    'on_next' => ['clob', $step->getOnNext()],
                    'on_prev' => ['clob', $step->getOnPrev()],
                    'on_show' => ['clob', $step->getOnShow()],
                    'on_shown' => ['clob', $step->getOnShown()],
                    'on_hide' => ['clob', $step->getOnHide()],
                    'path' => ['text', $step->getPath()],
                    'element_type' => ['text', $step->getElementType()],
                    'element_name' => ['text', $step->getElementName()]
                ];

                $this->db->insert('gtour_steps', $data);
                return $this->getStepById($step->getId());
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
        return null;
    }

    /**
     * Get step by ID
     */
    public function getStepById(int|string $id): ?GuidedTourStep
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) {
            throw new InvalidArgumentException("Invalid ID provided.");
        }

        $query = "SELECT * FROM gtour_steps WHERE step_id = " . $this->db->quote($id, "integer");
        $result = $this->db->query($query);
        $record = $this->db->fetchAssoc($result);

        if ($record) {
            return $this->recordToStep($record);
        }

        return null;
    }

    /**
     * Get all steps for a tour
     * @return GuidedTourStep[]
     */
    public function getStepsByTourId(int $tourId): array
    {
        $query = "SELECT * FROM gtour_steps
                  WHERE tour_id = " . $this->db->quote($tourId, "integer") . "
                  ORDER BY sort_order ASC";

        $result = $this->db->query($query);
        $steps = [];

        while ($record = $this->db->fetchAssoc($result)) {
            $steps[] = $this->recordToStep($record);
        }

        return $steps;
    }

    /**
     * Update an existing step
     */
    public function updateStep(GuidedTourStep $step): bool
    {
        try {
            $data = [
                'tour_id' => ['integer', $step->getTourId()],
                'sort_order' => ['integer', $step->getSortOrder()],
                'element' => ['text', $step->getElement()],
                'title' => ['text', $step->getTitle()],
                'content' => ['clob', $step->getContent()],
                'content_page_id' => ['integer', $step->getContentPageId()],
                'placement' => ['text', $step->getPlacement()],
                'orphan' => ['integer', $step->isOrphan() ? 1 : 0],
                'on_next' => ['clob', $step->getOnNext()],
                'on_prev' => ['clob', $step->getOnPrev()],
                'on_show' => ['clob', $step->getOnShow()],
                'on_shown' => ['clob', $step->getOnShown()],
                'on_hide' => ['clob', $step->getOnHide()],
                'path' => ['text', $step->getPath()]
            ];

            $where = [
                'step_id' => ['integer', $step->getId()]
            ];

            $this->db->update('gtour_steps', $data, $where);
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Delete a step
     */
    public function deleteStep(int $stepId): bool
    {
        try {
            $query = "DELETE FROM gtour_steps WHERE step_id = " . $this->db->quote($stepId, "integer");
            $this->db->manipulate($query);
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Delete all steps for a tour
     */
    public function deleteStepsByTourId(int $tourId): bool
    {
        try {
            $query = "DELETE FROM gtour_steps WHERE tour_id = " . $this->db->quote($tourId, "integer");
            $this->db->manipulate($query);
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Update sort order for steps
     * @param array $stepIds Array of step IDs in the desired order
     */
    public function updateSortOrder(array $stepIds): bool
    {
        try {
            $sortOrder = 1;
            foreach ($stepIds as $stepId) {
                $data = ['sort_order' => ['integer', $sortOrder]];
                $where = ['step_id' => ['integer', $stepId]];
                $this->db->update('gtour_steps', $data, $where);
                $sortOrder++;
            }
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Get next sort order value for a tour
     */
    public function getNextSortOrder(int $tourId): int
    {
        $query = "SELECT MAX(sort_order) as max_order
                  FROM gtour_steps
                  WHERE tour_id = " . $this->db->quote($tourId, "integer");

        $result = $this->db->query($query);
        $record = $this->db->fetchAssoc($result);

        return ($record && $record['max_order']) ? (int)$record['max_order'] + 1 : 1;
    }

    /**
     * Convert database record to GuidedTourStep object
     */
    protected function recordToStep(array $record): GuidedTourStep
    {
        return new GuidedTourStep(
            id: (int)$record['step_id'],
            tourId: (int)$record['tour_id'],
            sortOrder: (int)$record['sort_order'],
            element: $record['element'],
            title: $record['title'],
            content: $record['content'],
            contentPageId: isset($record['content_page_id']) ? (int)$record['content_page_id'] : null,
            placement: $record['placement'] ?? GuidedTourStep::PLACEMENT_DEFAULT,
            orphan: (bool)$record['orphan'],
            onNext: $record['on_next'],
            onPrev: $record['on_prev'],
            onShow: $record['on_show'],
            onShown: $record['on_shown'],
            onHide: $record['on_hide'],
            path: $record['path'],
            elementType: $record['element_type'] ?? null,
            elementName: $record['element_name'] ?? null
        );
    }

    /**
     * Convert steps to JSON format for tour export
     * @param int $tourId
     * @return string JSON representation of steps
     */
    public function getStepsAsJson(int $tourId): string
    {
        $steps = $this->getStepsByTourId($tourId);
        $jsonArray = [];

        foreach ($steps as $step) {
            $jsonArray[] = $step->toJsonArray();
        }

        return json_encode($jsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
