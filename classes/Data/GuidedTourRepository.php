<?php declare(strict_types=1);

namespace uzk\gtour\Data;
require_once __DIR__ . "/../../vendor/autoload.php";

use uzk\gtour\Model\GuidedTour;
use ILIAS\DI\Container;
use ilDBInterface;
use ILIAS\DI\Exceptions\Exception;
use ilDatabaseException;
use InvalidArgumentException;

/**
 * Class GuidedTourRepository
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class GuidedTourRepository implements GuidedTourIRepository
{
    protected ilDBInterface $db;

    public function __construct()
    {
        /** @var Container $DIC */
        global $DIC;

        $this->db = $DIC->database();
    }

    /*
     * Create operations
     */
    /**
     * @param GuidedTour $a_tour
     * @return GuidedTour|null
     */
    public function createTour(GuidedTour $a_tour): ?GuidedTour
    {
        try {
            if (empty($a_tour->getId())) {
                $nextId = $this->db->nextId("gtour_tours");
                $a_tour->setId($nextId);
                $data = $a_tour->toArray();

                $rows_affected = $this->db->insert('gtour_tours', $data);

                if ($rows_affected > 0) {
                    return $this->getTourById($a_tour->getId());
                } else {
                    // Query did not execute successfully
                    throw new Exception("No changes were made for tour ID: " . $a_tour->getId());
                }
            } else {
                // Tour id is still specified
                throw new Exception("Failed to add still saved or invalid tour.");
            }
        } catch (Exception $e) {
            // Log the exception
            error_log($e->getMessage());
        }
        return null;
    }

    /**
     * @param GuidedTour[] $a_tours
     * @return void
     */
    public function createTours(array $a_tours): void
    {
        foreach ($a_tours as $a_tour) {
            if (!($a_tour instanceof GuidedTour)) {
                error_log("Invalid object encountered in the array");
                continue; // Skip if the item is not an instance of GuidedTour
            }

            // Call createTour for each GuidedTour object
            $createdTour = $this->createTour($a_tour);
            if (null === $createdTour) {
                error_log("Failed to create tour .");
            }
        }

    }

    /*
     * Read operations
     */
    /**
     * @param int|string $a_id
     * @return GuidedTour|null
     */
    public function getTourById(int|string $a_id): ?GuidedTour
    {
        // Convert and validate the input
        $a_id = filter_var($a_id, FILTER_VALIDATE_INT);
        if ($a_id === false) {
            throw new InvalidArgumentException("Invalid ID provided.");
        }

        // Prepare and execute the query to fetch the tour by ID
        $query = "SELECT * FROM gtour_tours WHERE tour_id = " . $this->db->quote($a_id, "integer");
        $result = $this->db->query($query);

        // Fetch the record
        $record = $this->db->fetchAssoc($result);

        if ($record) {
            // Create a new GuidedTour object and populate it from the array
            $tour = new GuidedTour();
            $tour->fromArray($record);
            return $tour;
        }

        // Return null if no tour is found
        return null;
    }

    /**
     * @return GuidedTour[]
     */
    public function getTours(): array
    {
        $tours = [];
        try {
            $statement = $this->db->query("SELECT * FROM gtour_tours");
            $records = $this->db->fetchAll($statement);
            if ($records) {
                foreach ($records as $record) {
                    $obj = new GuidedTour();
                    $obj->fromArray($record);
                    $tours[] = $obj;
                }
            } else {
                // Query did not execute successfully
                error_log("Failed to fetch tours.");
                return [];
            }
        } catch (Exception $e) {
            // Log the exception
            error_log($e->getMessage());
        }
        return $tours;
    }

    /*
     * Update operations
     */
    /**
     * @param GuidedTour $a_tour
     * @return GuidedTour|null
     */
    public function updateTour(GuidedTour $a_tour): ?GuidedTour
    {
        try {
            if (!empty($a_tour->getId())) {
                $data = $a_tour->toArray();
                $primary = $a_tour->toPrimaryArray();

                $rows_affected = $this->db->update('gtour_tours', $data, $primary);

                if ($rows_affected > 0) {
                    return $this->getTourById((int) $primary['tour_id']);
                } else {
                    // Query did not execute successfully
                    throw new Exception("No changes were made for tour ID: " . $a_tour->getId());
                }
            } else {
                // Tour id is not specified
                error_log("Failed to update tour .");
                return null;
            }
        } catch (Exception $e) {
            // Log the exception
            error_log($e->getMessage());
        }
        return null;
    }

    /**
     * @param GuidedTour[] $a_tours
     * @return void
     */
    public function updateTours(array $a_tours): void
    {
        foreach ($a_tours as $a_tour) {
            if (!($a_tour instanceof GuidedTour)) {
                error_log("Invalid object encountered in the array");
                continue; // Skip if the item is not an instance of GuidedTour
            }

            // Call updateTour for each GuidedTour object
            $updatedTour = $this->updateTour($a_tour);
            if (null === $updatedTour) {
                error_log("Failed to update tour with ID: " . $a_tour->getId());
            }
        }
    }

    /*
     * Delete operations
     */
    /**
     * @param int|string $a_id
     * @return bool
     */
    public function deleteTourById(int|string $a_id): bool
    {
        // Convert and validate the input
        $a_id = filter_var($a_id, FILTER_VALIDATE_INT);
        if ($a_id === false) {
            throw new InvalidArgumentException("Invalid ID provided.");
        }

        $this->deleteToursByIds([$a_id]);

        // Return true to indicate successful deletion
        return true;
    }

    /**
     * @param int[] $a_ids
     * @return void
     */
    public function deleteToursByIds(array $a_ids): void
    {
        // Prepare and execute the SQL statement to delete tours from the database
        $statement = $this->db->prepareManip(
            "DELETE FROM gtour_tours WHERE tour_id IN (?)",
            array("int")
        );

        try {
            $this->db->execute($statement, $a_ids);
        } catch (ilDatabaseException $e) {
            // Log the exception
            error_log($e->getMessage());
        } finally {
            $this->db->free($statement);
        }
    }
}