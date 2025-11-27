<?php declare(strict_types=1);

namespace uzk\gtour\Table;
require_once __DIR__ . "/../../vendor/autoload.php";

use uzk\gtour\Data\GuidedTourRepository;
use uzk\gtour\Data\GuidedTourStepRepository;
use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\Data\Range;
use ILIAS\Data\Order;
use ILIAS\UI\Factory as UIFactory;
use ILIAS\UI\Component\Symbol\Icon\Icon;
use ilLanguage;
use ilUtil;

/**
 * Data retrieval for Guided Tours table
 *
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version 2.0.0
 */
class GuidedTourTableDataRetrieval implements DataRetrieval
{
    protected GuidedTourRepository $tourRepo;
    protected GuidedTourStepRepository $stepRepo;
    protected UIFactory $ui_factory;
    protected ilLanguage $lng;

    public function __construct()
    {
        global $DIC;

        $this->tourRepo = new GuidedTourRepository();
        $this->stepRepo = new GuidedTourStepRepository();
        $this->ui_factory = $DIC->ui()->factory();
        $this->lng = $DIC->language();
    }

    public function getRows(
        DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): \Generator {
        // Get all tours
        $tours = $this->tourRepo->getTours();

        // Apply sorting
        $tours = $this->sortTours($tours, $order);

        // Apply range (pagination)
        $offset = $range->getStart();
        $length = $range->getLength();
        $tours = array_slice($tours, $offset, $length);

        // Generate rows
        foreach ($tours as $tour) {
            $tour_id = (string)$tour->getId();

            // Get step count
            $steps = $this->stepRepo->getStepsByTourId($tour->getId());
            $steps_count = count($steps);

            // Create status icons
            $active_icon = $this->ui_factory->symbol()->icon()->custom(
                $tour->isActive() ?
                    ilUtil::getImagePath('standard/icon_ok.svg') :
                    ilUtil::getImagePath('standard/icon_not_ok.svg'),
                $tour->isActive() ? $this->lng->txt('active') : $this->lng->txt('inactive'),
                Icon::SMALL
            );

            $automatic_triggered_icon = $this->ui_factory->symbol()->icon()->custom(
                $tour->isAutomaticTriggered() ?
                    ilUtil::getImagePath('standard/icon_ok.svg') :
                    ilUtil::getImagePath('standard/icon_not_ok.svg'),
                $tour->isAutomaticTriggered() ? $this->lng->txt('active') : $this->lng->txt('inactive'),
                Icon::SMALL
            );

            // Build row data
            $row_data = [
                'title' => $tour->getTitle(),
                'type' => $this->formatType($tour->getType()),
                'active' => $active_icon,
                'automatic_triggered' => $automatic_triggered_icon,
                'steps_count' => $steps_count
            ];

            yield $row_builder->buildDataRow($tour_id, $row_data);
        }
    }

    public function getTotalRowCount(?array $filter_data, ?array $additional_parameters): ?int
    {
        $tours = $this->tourRepo->getTours();
        return count($tours);
    }

    /**
     * Sort tours based on order
     */
    protected function sortTours(array $tours, Order $order): array
    {
        $aspect = $order->join('', fn($i, $k, $v) => $k);
        $direction = $order->join('', fn($i, $k, $v) => $v);

        if (empty($aspect)) {
            return $tours;
        }

        usort($tours, function ($a, $b) use ($aspect, $direction) {
            $val_a = $this->getTourValue($a, $aspect);
            $val_b = $this->getTourValue($b, $aspect);

            if ($val_a === $val_b) {
                return 0;
            }

            $result = $val_a < $val_b ? -1 : 1;

            return $direction === 'DESC' ? -$result : $result;
        });

        return $tours;
    }

    /**
     * Get tour value for sorting
     */
    protected function getTourValue($tour, string $aspect)
    {
        switch ($aspect) {
            case 'title':
                return strtolower($tour->getTitle());
            case 'type':
                return strtolower($tour->getType());
            case 'active':
                return $tour->isActive() ? 1 : 0;
            case 'automatic_triggered':
                return $tour->isAutomaticTriggered() ? 1 : 0;
            case 'steps_count':
                $steps = $this->stepRepo->getStepsByTourId($tour->getId());
                return count($steps);
            default:
                return '';
        }
    }

    /**
     * Format type for display
     */
    protected function formatType(string $type): string
    {
        return ucfirst($type);
    }
}
