<?php declare(strict_types=1);

namespace uzk\gtour\Data;
require_once __DIR__ . "/../../vendor/autoload.php";

use uzk\gtour\Model\GuidedTour;

/**
 * Class GuidedTourIRepository
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
interface GuidedTourIRepository
{
    // Create operations
    /**
     * @param GuidedTour $a_tour
     * @return GuidedTour|null
     */
    public function createTour(GuidedTour $a_tour): ?GuidedTour;

    /**
     * @param GuidedTour[] $a_tours
     * @return void
     */
    public function createTours(array $a_tours): void;

    // Read operations
    /**
     * @param int $a_id
     * @return GuidedTour|null
     */
    public function getTourById(int $a_id): ?GuidedTour;

    /**
     * @return GuidedTour[]|null
     */
    public function getTours(): ?array;

    // Update operations
    /**
     * @param GuidedTour $a_tour
     * @return GuidedTour|null
     */
    public function updateTour(GuidedTour $a_tour): ?GuidedTour;

    /**
     * @param GuidedTour[] $a_tours
     * @return void
     */
    public function updateTours(array $a_tours): void;

    // Delete operations
    /**
     * @param int $a_id
     * @return bool
     */
    public function deleteTourById(int $a_id): bool;

    /**
     * @param int[] $a_ids
     * @return void
     */
    public function deleteToursByIds(array $a_ids): void;
}