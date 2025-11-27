<?php declare(strict_types=1);

namespace uzk\gtour\Data;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\DI\Container;
use ilDBInterface;
use uzk\gtour\Data\GuidedTourStepRepository;
use uzk\gtour\Data\GuidedTourRepository;
use uzk\gtour\Model\GuidedTour;

/**
 * Class GuidedTourUserFinishedRepository
 * Manages user tour usage and completion tracking with state + history
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class GuidedTourUserFinishedRepository
{
    protected ilDBInterface $db;
    protected ?int $currentHistoryId = null; // Track current tour run

    public function __construct()
    {
        /** @var Container $DIC */
        global $DIC;
        $this->db = $DIC->database();
    }

    /**
     * Mark a tour as started for a user (creates new history entry + updates state)
     * Only creates a new history entry if no active (unfinished) session exists
     * @param int $tour_id
     * @param int $user_id
     * @return void
     */
    public function setStarted(int $tour_id, int $user_id): void
    {
        $now = time();

        // Check if there's already an active (unterminated) history entry
        $activeHistorySet = $this->db->queryF(
            "SELECT id FROM gtour_usage_history " .
            "WHERE tour_id = %s AND user_id = %s AND terminated_ts IS NULL " .
            "ORDER BY started_ts DESC LIMIT 1",
            ["integer", "integer"],
            [$tour_id, $user_id]
        );

        $activeHistory = $this->db->fetchAssoc($activeHistorySet);
        $shouldCreateNewHistory = empty($activeHistory);

        // Check if state record exists
        $set = $this->db->queryF(
            "SELECT * FROM gtour_user_state WHERE tour_id = %s AND user_id = %s",
            ["integer", "integer"],
            [$tour_id, $user_id]
        );

        if ($rec = $this->db->fetchAssoc($set)) {
            // Update state only if creating new history (= new tour run)
            if ($shouldCreateNewHistory) {
                $this->db->update("gtour_user_state",
                    [
                        "last_started_ts" => ["integer", $now],
                        "times_started" => ["integer", (int)$rec['times_started'] + 1],
                        "last_step_reached" => ["integer", 0], // Reset for new run
                        "show_again" => ["integer", 0] // Clear show_again flag when user starts tour
                    ],
                    ["tour_id" => ["integer", $tour_id], "user_id" => ["integer", $user_id]]
                );
            }
        } else {
            // Insert new state record
            $this->db->insert("gtour_user_state", [
                "tour_id" => ["integer", $tour_id],
                "user_id" => ["integer", $user_id],
                "last_started_ts" => ["integer", $now],
                "times_started" => ["integer", 1],
                "times_completed" => ["integer", 0],
                "last_step_reached" => ["integer", 0],
                "show_again" => ["integer", 0]
            ]);
        }

        // Create new history entry ONLY if no active session exists
        if ($shouldCreateNewHistory) {
            $this->currentHistoryId = $this->db->nextId('gtour_usage_history');
            $this->db->insert("gtour_usage_history", [
                "id" => ["integer", $this->currentHistoryId],
                "tour_id" => ["integer", $tour_id],
                "user_id" => ["integer", $user_id],
                "started_ts" => ["integer", $now],
                "last_step_reached" => ["integer", 0]
            ]);
        } else {
            // Reuse existing active history entry
            $this->currentHistoryId = (int)$activeHistory['id'];
        }
    }

    /**
     * Mark a tour session as terminated for a user (updates state + current history entry)
     * This is called when the tour is closed (completed OR aborted)
     * Handles trigger_mode logic: 'normal', 'always', 'until_completed'
     * @param int $tour_id
     * @param int $user_id
     * @return void
     */
    public function setTerminated(int $tour_id, int $user_id): void
    {
        $now = time();

        // Load tour to get trigger_mode
        $tourRepo = new GuidedTourRepository();
        $tour = $tourRepo->getTourById($tour_id);
        $trigger_mode = $tour ? $tour->getTriggerMode() : GuidedTour::TRIGGER_MODE_NORMAL;

        // Check if state record exists
        $set = $this->db->queryF(
            "SELECT * FROM gtour_user_state WHERE tour_id = %s AND user_id = %s",
            ["integer", "integer"],
            [$tour_id, $user_id]
        );
        $state = $this->db->fetchAssoc($set);

        if ($state) {
            // Determine show_again flag based on trigger_mode
            $show_again = 0;
            switch ($trigger_mode) {
                case GuidedTour::TRIGGER_MODE_ALWAYS:
                    // Always show again after termination
                    $show_again = 1;
                    break;
                case GuidedTour::TRIGGER_MODE_UNTIL_COMPLETED:
                    // Only show again if not completed yet
                    $show_again = ((int)$state['times_completed'] === 0) ? 1 : 0;
                    break;
                case GuidedTour::TRIGGER_MODE_NORMAL:
                default:
                    // Don't show again (default behavior)
                    $show_again = 0;
                    break;
            }

            // Update existing state: update last_terminated_ts and show_again
            $this->db->update("gtour_user_state",
                [
                    "last_terminated_ts" => ["integer", $now],
                    "show_again" => ["integer", $show_again]
                ],
                ["tour_id" => ["integer", $tour_id], "user_id" => ["integer", $user_id]]
            );
        } else {
            // No state exists - create minimal state record just to track termination
            // Determine show_again flag based on trigger_mode
            $show_again = 0;
            switch ($trigger_mode) {
                case GuidedTour::TRIGGER_MODE_ALWAYS:
                    $show_again = 1;
                    break;
                case GuidedTour::TRIGGER_MODE_UNTIL_COMPLETED:
                    // Not completed yet (times_completed = 0), so show again
                    $show_again = 1;
                    break;
                case GuidedTour::TRIGGER_MODE_NORMAL:
                default:
                    $show_again = 0;
                    break;
            }

            $this->db->insert("gtour_user_state", [
                "tour_id" => ["integer", $tour_id],
                "user_id" => ["integer", $user_id],
                "last_started_ts" => ["integer", $now],
                "last_terminated_ts" => ["integer", $now],
                "times_started" => ["integer", 1],
                "times_completed" => ["integer", 0],
                "last_step_reached" => ["integer", 0],
                "show_again" => ["integer", $show_again]
            ]);

            // Also create history entry (terminated immediately)
            $history_id = $this->db->nextId('gtour_usage_history');
            $this->db->insert("gtour_usage_history", [
                "id" => ["integer", $history_id],
                "tour_id" => ["integer", $tour_id],
                "user_id" => ["integer", $user_id],
                "started_ts" => ["integer", $now],
                "terminated_ts" => ["integer", $now],
                "last_step_reached" => ["integer", 0]
            ]);
            return; // Early return, no need to update history
        }

        // Update current history entry (most recent unterminated entry for this user/tour)
        if ($this->currentHistoryId) {
            // We have the ID from setStarted()
            $this->db->update("gtour_usage_history",
                ["terminated_ts" => ["integer", $now]],
                ["id" => ["integer", $this->currentHistoryId]]
            );
        } else {
            // Fallback: Find most recent unterminated history entry
            $set = $this->db->queryF(
                "SELECT id FROM gtour_usage_history " .
                "WHERE tour_id = %s AND user_id = %s AND terminated_ts IS NULL " .
                "ORDER BY started_ts DESC LIMIT 1",
                ["integer", "integer"],
                [$tour_id, $user_id]
            );
            if ($rec = $this->db->fetchAssoc($set)) {
                $this->db->update("gtour_usage_history",
                    ["terminated_ts" => ["integer", $now]],
                    ["id" => ["integer", $rec['id']]]
                );
            }
        }

        // Reset currentHistoryId
        $this->currentHistoryId = null;
    }

    /**
     * Check if a user should NOT see an autostart tour again
     * Returns true if user has already seen (started/terminated) the tour AND show_again flag is not set
     * This prevents autostart tours from showing repeatedly
     * @param int $tour_id
     * @param int $user_id
     * @return bool
     */
    public function hasFinished(int $tour_id, int $user_id): bool
    {
        $set = $this->db->queryF(
            "SELECT times_started, last_terminated_ts, show_again FROM gtour_user_state " .
            " WHERE tour_id = %s AND user_id = %s",
            ["integer", "integer"],
            [$tour_id, $user_id]
        );
        if ($rec = $this->db->fetchAssoc($set)) {
            // User should not see tour again if:
            // 1. They have terminated (closed) the tour at least once, AND
            // 2. The show_again flag is not set (admin hasn't forced re-display)
            return $rec['last_terminated_ts'] !== null && (int)$rec['show_again'] === 0;
        }
        return false;
    }

    /**
     * Check if a user has actually completed a tour (reached the last step)
     * @param int $tour_id
     * @param int $user_id
     * @return bool
     */
    public function hasCompleted(int $tour_id, int $user_id): bool
    {
        $set = $this->db->queryF(
            "SELECT times_completed FROM gtour_user_state " .
            " WHERE tour_id = %s AND user_id = %s",
            ["integer", "integer"],
            [$tour_id, $user_id]
        );
        if ($rec = $this->db->fetchAssoc($set)) {
            return (int)$rec['times_completed'] > 0;
        }
        return false;
    }

    /**
     * Update last step reached (updates state + current history entry)
     * Also marks tour as completed if last step is reached
     * @param int $tour_id
     * @param int $user_id
     * @param int $step_index Current step index (0-based)
     * @param int $total_steps Total number of steps in the tour
     * @return void
     */
    public function updateLastStep(int $tour_id, int $user_id, int $step_index, int $total_steps): void
    {
        // Check if this is the last step (completion)
        $is_last_step = $step_index >= ($total_steps - 1);

        // Ensure state record exists (in case setStarted wasn't called yet or failed)
        $set = $this->db->queryF(
            "SELECT * FROM gtour_user_state WHERE tour_id = %s AND user_id = %s",
            ["integer", "integer"],
            [$tour_id, $user_id]
        );
        $state = $this->db->fetchAssoc($set);

        if (!$state) {
            // No state record exists - create it first (and also create history entry)
            $now = time();
            $this->db->insert("gtour_user_state", [
                "tour_id" => ["integer", $tour_id],
                "user_id" => ["integer", $user_id],
                "last_started_ts" => ["integer", $now],
                "times_started" => ["integer", 1],
                "times_completed" => ["integer", 0],
                "last_step_reached" => ["integer", $step_index],
                "show_again" => ["integer", 0]
            ]);
            $state = ['times_completed' => 0];

            // Also create history entry
            $this->currentHistoryId = $this->db->nextId('gtour_usage_history');
            $this->db->insert("gtour_usage_history", [
                "id" => ["integer", $this->currentHistoryId],
                "tour_id" => ["integer", $tour_id],
                "user_id" => ["integer", $user_id],
                "started_ts" => ["integer", $now],
                "last_step_reached" => ["integer", $step_index]
            ]);
        }

        // Prepare update data
        $updateData = ["last_step_reached" => ["integer", $step_index]];

        // If last step reached, increment times_completed
        if ($is_last_step && isset($state['times_completed'])) {
            $updateData["times_completed"] = ["integer", (int)$state['times_completed'] + 1];
        }

        // Update state (only if record exists now)
        if ($state) {
            $this->db->update("gtour_user_state",
                $updateData,
                ["tour_id" => ["integer", $tour_id], "user_id" => ["integer", $user_id]]
            );
        }

        // Update current history entry
        if ($this->currentHistoryId) {
            $this->db->update("gtour_usage_history",
                ["last_step_reached" => ["integer", $step_index]],
                ["id" => ["integer", $this->currentHistoryId]]
            );
        } else {
            // Fallback: Update most recent unterminated history entry
            // (currentHistoryId is an instance variable, so it's null on each new request)
            $set = $this->db->queryF(
                "SELECT id FROM gtour_usage_history " .
                "WHERE tour_id = %s AND user_id = %s AND terminated_ts IS NULL " .
                "ORDER BY started_ts DESC LIMIT 1",
                ["integer", "integer"],
                [$tour_id, $user_id]
            );
            if ($rec = $this->db->fetchAssoc($set)) {
                $this->db->update("gtour_usage_history",
                    ["last_step_reached" => ["integer", $step_index]],
                    ["id" => ["integer", $rec['id']]]
                );
            }
        }
    }

    /**
     * Reset/delete all usage records for a tour (state + history)
     * @param int $tour_id
     * @return void
     */
    public function resetTour(int $tour_id): void
    {
        $this->db->manipulateF(
            "DELETE FROM gtour_user_state WHERE tour_id = %s",
            ["integer"],
            [$tour_id]
        );
        $this->db->manipulateF(
            "DELETE FROM gtour_usage_history WHERE tour_id = %s",
            ["integer"],
            [$tour_id]
        );
    }

    /**
     * Reset completion status for a tour (allows tour to autostart again)
     * Keeps all statistics/history intact, only sets show_again flag
     * This allows users to see the tour again (e.g., after tour updates)
     * @param int $tour_id
     * @return void
     */
    public function resetCompletionStatus(int $tour_id): void
    {
        // Set show_again flag to 1 in state table
        // This makes hasFinished() return false, so autostart tours will show again
        // times_completed and history remain unchanged, so statistics stay accurate
        $this->db->manipulateF(
            "UPDATE gtour_user_state SET show_again = 1 WHERE tour_id = %s",
            ["integer"],
            [$tour_id]
        );
    }

    /**
     * Reset/delete usage record for a specific user and tour (state + history)
     * @param int $tour_id
     * @param int $user_id
     * @return void
     */
    public function resetForUser(int $tour_id, int $user_id): void
    {
        $this->db->manipulateF(
            "DELETE FROM gtour_user_state WHERE tour_id = %s AND user_id = %s",
            ["integer", "integer"],
            [$tour_id, $user_id]
        );
        $this->db->manipulateF(
            "DELETE FROM gtour_usage_history WHERE tour_id = %s AND user_id = %s",
            ["integer", "integer"],
            [$tour_id, $user_id]
        );
    }

    /**
     * Get usage statistics for a tour and user (from state table)
     * @param int $tour_id
     * @param int $user_id
     * @return array|null Array with last_started_ts, last_terminated_ts, last_step_reached, times_started, times_completed or null
     */
    public function getUsageStats(int $tour_id, int $user_id): ?array
    {
        $set = $this->db->queryF(
            "SELECT last_started_ts, last_terminated_ts, last_step_reached, times_started, times_completed FROM gtour_user_state " .
            " WHERE tour_id = %s AND user_id = %s",
            ["integer", "integer"],
            [$tour_id, $user_id]
        );
        if ($rec = $this->db->fetchAssoc($set)) {
            return [
                'last_started_ts' => $rec['last_started_ts'] ? (int)$rec['last_started_ts'] : null,
                'last_terminated_ts' => $rec['last_terminated_ts'] ? (int)$rec['last_terminated_ts'] : null,
                'last_step_reached' => (int)$rec['last_step_reached'],
                'times_started' => (int)$rec['times_started'],
                'times_completed' => (int)$rec['times_completed']
            ];
        }
        return null;
    }

    /**
     * Get timestamp when user last terminated the tour (closed it, completed or aborted)
     * @param int $tour_id
     * @param int $user_id
     * @return int|null Timestamp or null if never terminated
     */
    public function getTerminatedTimestamp(int $tour_id, int $user_id): ?int
    {
        $set = $this->db->queryF(
            "SELECT last_terminated_ts FROM gtour_user_state " .
            " WHERE tour_id = %s AND user_id = %s",
            ["integer", "integer"],
            [$tour_id, $user_id]
        );
        if ($rec = $this->db->fetchAssoc($set)) {
            return $rec['last_terminated_ts'] ? (int)$rec['last_terminated_ts'] : null;
        }
        return null;
    }

    /**
     * Get aggregate statistics for a tour (privacy-friendly, using history table)
     * @param int $tour_id
     * @return array Statistics including total starts, unique users, completion rates, etc.
     */
    public function getTourStatistics(int $tour_id): array
    {
        // Get total number of steps for this tour
        $stepRepo = new GuidedTourStepRepository();
        $steps = $stepRepo->getStepsByTourId($tour_id);
        $total_steps = count($steps);

        // Get basic counts and timestamps from HISTORY table (all runs)
        $set = $this->db->queryF(
            "SELECT " .
            "COUNT(*) as total_starts, " .
            "COUNT(DISTINCT user_id) as unique_users, " .
            "MIN(started_ts) as first_usage, " .
            "MAX(started_ts) as last_usage, " .
            "SUM(CASE WHEN terminated_ts IS NOT NULL THEN 1 ELSE 0 END) as terminated_count, " .
            "SUM(CASE WHEN terminated_ts IS NULL AND started_ts IS NOT NULL THEN 1 ELSE 0 END) as active_count " .
            "FROM gtour_usage_history " .
            "WHERE tour_id = %s",
            ["integer"],
            [$tour_id]
        );

        $stats = [
            'total_starts' => 0,
            'unique_users' => 0,
            'users_completed' => 0,
            'first_usage' => null,
            'last_usage' => null,
            'usage_last_7_days' => 0,
            'usage_last_month' => 0,
            'terminated_count' => 0,
            'active_count' => 0,
            'completed_count' => 0,
            'partial_count' => 0,
            'avg_step_reached' => 0.0
        ];

        if ($rec = $this->db->fetchAssoc($set)) {
            $stats['total_starts'] = (int)$rec['total_starts'];
            $stats['unique_users'] = (int)$rec['unique_users'];
            $stats['first_usage'] = $rec['first_usage'] ? (int)$rec['first_usage'] : null;
            $stats['last_usage'] = $rec['last_usage'] ? (int)$rec['last_usage'] : null;
            $stats['terminated_count'] = (int)$rec['terminated_count'];
            $stats['active_count'] = (int)$rec['active_count'];
        }

        // Get all history entries to calculate per-user statistics and completion counts
        $historyQuery = $this->db->queryF(
            "SELECT user_id, last_step_reached, terminated_ts " .
            "FROM gtour_usage_history " .
            "WHERE tour_id = %s",
            ["integer"],
            [$tour_id]
        );

        $userMaxSteps = []; // user_id => max_step_reached
        $completed_runs = 0;
        $partial_runs = 0;

        while ($historyRec = $this->db->fetchAssoc($historyQuery)) {
            $userId = (int)$historyRec['user_id'];
            $stepReached = (int)$historyRec['last_step_reached'];
            $terminatedTs = $historyRec['terminated_ts'];

            // Track maximum step reached by this user across all runs
            if (!isset($userMaxSteps[$userId]) || $stepReached > $userMaxSteps[$userId]) {
                $userMaxSteps[$userId] = $stepReached;
            }

            // Count completed vs partial runs (only for terminated sessions)
            if ($terminatedTs !== null) {
                if ($total_steps > 0 && $stepReached >= ($total_steps - 1)) {
                    $completed_runs++;
                } else {
                    $partial_runs++;
                }
            }
        }

        $stats['completed_count'] = $completed_runs;
        $stats['partial_count'] = $partial_runs;

        // Count users who completed at least once (using times_completed from state table)
        // times_completed is incremented when user reaches the last step
        $completionQuery = $this->db->queryF(
            "SELECT COUNT(DISTINCT user_id) as count FROM gtour_user_state " .
            "WHERE tour_id = %s AND times_completed > 0",
            ["integer"],
            [$tour_id]
        );
        if ($completionRec = $this->db->fetchAssoc($completionQuery)) {
            $stats['users_completed'] = (int)$completionRec['count'];
        }

        // Calculate average of maximum steps across all users
        // Convert from 0-based index to 1-based step number (index 0 = step 1, index 4 = step 5)
        if (count($userMaxSteps) > 0) {
            $totalStepNumbers = 0;
            foreach ($userMaxSteps as $maxIndex) {
                $totalStepNumbers += ($maxIndex + 1); // Convert index to step number
            }
            $stats['avg_step_reached'] = $totalStepNumbers / count($userMaxSteps);
        } else {
            $stats['avg_step_reached'] = 0.0;
        }

        // Get usage in last 7 days (from history)
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);
        $set = $this->db->queryF(
            "SELECT COUNT(*) as count FROM gtour_usage_history " .
            "WHERE tour_id = %s AND started_ts >= %s",
            ["integer", "integer"],
            [$tour_id, $sevenDaysAgo]
        );
        if ($rec = $this->db->fetchAssoc($set)) {
            $stats['usage_last_7_days'] = (int)$rec['count'];
        }

        // Get usage in last 30 days (month) (from history)
        $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);
        $set = $this->db->queryF(
            "SELECT COUNT(*) as count FROM gtour_usage_history " .
            "WHERE tour_id = %s AND started_ts >= %s",
            ["integer", "integer"],
            [$tour_id, $thirtyDaysAgo]
        );
        if ($rec = $this->db->fetchAssoc($set)) {
            $stats['usage_last_month'] = (int)$rec['count'];
        }

        return $stats;
    }

    /**
     * Get all tour statistics for all tours
     * @return array Array of statistics keyed by tour_id
     */
    public function getAllToursStatistics(): array
    {
        // Get all unique tour IDs from history table
        $set = $this->db->query("SELECT DISTINCT tour_id FROM gtour_usage_history ORDER BY tour_id");

        $allStats = [];
        while ($rec = $this->db->fetchAssoc($set)) {
            $tourId = (int)$rec['tour_id'];
            $allStats[$tourId] = $this->getTourStatistics($tourId);
        }

        return $allStats;
    }
}
