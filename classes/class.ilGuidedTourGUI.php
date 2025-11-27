<?php declare(strict_types=1);

require_once __DIR__ . "/../vendor/autoload.php";

use uzk\gtour\Data\GuidedTourUserFinishedRepository;
use uzk\gtour\Data\GuidedTourStepRepository;
use ILIAS\DI\Container;

/**
 * Class ilGuidedTourGUI
 * Frontend GUI for AJAX calls (finish tour, etc.)
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 * @ilCtrl_IsCalledBy ilGuidedTourGUI: ilUIPluginRouterGUI
 */
class ilGuidedTourGUI implements ilCtrlBaseClassInterface
{
    protected ilObjUser $user;
    protected ilCtrl $ctrl;
    protected \ILIAS\HTTP\Services $http;
    protected GuidedTourUserFinishedRepository $finishedRepo;
    protected GuidedTourStepRepository $stepRepo;

    public function __construct()
    {
        /** @var Container $DIC */
        global $DIC;

        $this->user = $DIC->user();
        $this->ctrl = $DIC->ctrl();
        $this->http = $DIC->http();
        $this->finishedRepo = new GuidedTourUserFinishedRepository();
        $this->stepRepo = new GuidedTourStepRepository();
    }

    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd('terminateTour');

        switch ($cmd) {
            case 'terminateTour':
                $this->terminateTour();
                break;
            case 'finishTour':
                // Legacy support - redirect to terminateTour
                $this->terminateTour();
                break;
            case 'updateProgress':
                $this->updateProgress();
                break;
            case 'resetProgress':
                $this->resetProgress();
                break;
            default:
                // Invalid command
                $this->sendJsonError('Invalid command');
        }
    }

    /**
     * Mark tour session as terminated for current user (AJAX endpoint)
     * Called when tour is closed (completed OR aborted)
     */
    protected function terminateTour(): void
    {
        $query_params = $this->http->request()->getQueryParams();
        $post_params = $this->http->request()->getParsedBody();

        // Tour ID can come from query or post
        $tour_id = $query_params['tour_id'] ?? $post_params['tour_id'] ?? null;

        if ($tour_id === null) {
            $this->sendJsonError('Missing tour_id parameter');
            return;
        }

        $tour_id = (int)$tour_id;
        $user_id = $this->user->getId();

        // Mark tour session as terminated
        $this->finishedRepo->setTerminated($tour_id, $user_id);

        // Send success response
        $this->sendJsonSuccess([
            'terminated' => true,
            'tour_id' => $tour_id,
            'timestamp' => time()
        ]);
    }

    /**
     * Update tour progress (track current step) for current user (AJAX endpoint)
     */
    protected function updateProgress(): void
    {
        $query_params = $this->http->request()->getQueryParams();
        $post_params = $this->http->request()->getParsedBody();

        // Tour ID and step index can come from query or post
        $tour_id = $query_params['tour_id'] ?? $post_params['tour_id'] ?? null;
        $step_index = $query_params['step_index'] ?? $post_params['step_index'] ?? null;

        if ($tour_id === null) {
            $this->sendJsonError('Missing tour_id parameter');
            return;
        }

        if ($step_index === null) {
            $this->sendJsonError('Missing step_index parameter');
            return;
        }

        $tour_id = (int)$tour_id;
        $step_index = (int)$step_index;
        $user_id = $this->user->getId();

        // Get total number of steps for this tour
        $steps = $this->stepRepo->getStepsByTourId($tour_id);
        $total_steps = count($steps);

        // Mark tour as started ONLY when first step (step 0) is reached
        // This ensures we only count actual tour starts, not every step
        if ($step_index === 0) {
            $this->finishedRepo->setStarted($tour_id, $user_id);
        }

        // Update last step reached (and mark as completed if last step)
        $this->finishedRepo->updateLastStep($tour_id, $user_id, $step_index, $total_steps);

        // Send success response
        $this->sendJsonSuccess([
            'progress_updated' => true,
            'tour_id' => $tour_id,
            'step_index' => $step_index,
            'completed' => $step_index >= ($total_steps - 1)
        ]);
    }

    /**
     * Reset tour progress for current user (AJAX endpoint)
     */
    protected function resetProgress(): void
    {
        $query_params = $this->http->request()->getQueryParams();
        $tour_id = $query_params['tour_id'] ?? null;

        if ($tour_id === null) {
            $this->sendJsonError('Missing tour_id parameter');
            return;
        }

        $tour_id = (int)$tour_id;
        $user_id = $this->user->getId();

        // Reset progress
        $this->finishedRepo->resetForUser($tour_id, $user_id);

        // Send success response
        $this->sendJsonSuccess([
            'reset' => true,
            'tour_id' => $tour_id
        ]);
    }

    /**
     * Send JSON success response
     */
    protected function sendJsonSuccess(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge(['success' => true], $data));
        exit;
    }

    /**
     * Send JSON error response
     */
    protected function sendJsonError(string $message): void
    {
        header('Content-Type: application/json');
        header('HTTP/1.1 400 Bad Request');
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
        exit;
    }
}
