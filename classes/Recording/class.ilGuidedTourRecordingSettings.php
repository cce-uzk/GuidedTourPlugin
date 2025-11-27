<?php declare(strict_types=1);

/**
 * Settings for tour recording mode
 * Manages recording session state similar to ilMemberViewSettings
 *
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 */
class ilGuidedTourRecordingSettings
{
    public const SESSION_RECORDING_ACTIVE = 'gtour_recording_active';
    public const SESSION_RECORDING_TOUR_ID = 'gtour_recording_tour_id';
    public const SESSION_RECORDING_STEPS = 'gtour_recording_steps';

    private static ?ilGuidedTourRecordingSettings $instance = null;
    private bool $active = false;
    private ?int $tour_id = null;
    private array $recorded_steps = [];

    private function __construct()
    {
        $this->read();
    }

    public static function getInstance(): ilGuidedTourRecordingSettings
    {
        return self::$instance ?? (self::$instance = new ilGuidedTourRecordingSettings());
    }

    /**
     * Check if recording is currently active
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Get the tour ID being recorded
     */
    public function getTourId(): ?int
    {
        return $this->tour_id;
    }

    /**
     * Get recorded steps
     */
    public function getRecordedSteps(): array
    {
        return $this->recorded_steps;
    }

    /**
     * Start recording for a specific tour
     */
    public function startRecording(int $tour_id): void
    {
        $this->active = true;
        $this->tour_id = $tour_id;
        $this->recorded_steps = [];

        ilSession::set(self::SESSION_RECORDING_ACTIVE, true);
        ilSession::set(self::SESSION_RECORDING_TOUR_ID, $tour_id);
        ilSession::set(self::SESSION_RECORDING_STEPS, []);
    }

    /**
     * Pause recording (keep steps but stop capturing)
     */
    public function pauseRecording(): void
    {
        $this->active = false;
        ilSession::set(self::SESSION_RECORDING_ACTIVE, false);
        // Keep tour_id and steps in session
    }

    /**
     * Resume recording (continue from paused state)
     */
    public function resumeRecording(): void
    {
        if ($this->tour_id !== null) {
            $this->active = true;
            ilSession::set(self::SESSION_RECORDING_ACTIVE, true);
        }
    }

    /**
     * Discard all recorded steps but keep tour_id
     */
    public function discardSteps(): void
    {
        $this->recorded_steps = [];
        ilSession::set(self::SESSION_RECORDING_STEPS, []);
    }

    /**
     * Check if there are any recorded steps
     */
    public function hasRecordedSteps(): bool
    {
        return !empty($this->recorded_steps);
    }

    /**
     * Stop recording and clear session completely
     */
    public function stopRecording(): void
    {
        $this->active = false;
        $this->tour_id = null;
        $this->recorded_steps = [];

        ilSession::clear(self::SESSION_RECORDING_ACTIVE);
        ilSession::clear(self::SESSION_RECORDING_TOUR_ID);
        ilSession::clear(self::SESSION_RECORDING_STEPS);
    }

    /**
     * Add a recorded step
     */
    public function addRecordedStep(array $step_data): void
    {
        $this->recorded_steps[] = $step_data;
        ilSession::set(self::SESSION_RECORDING_STEPS, $this->recorded_steps);
    }

    /**
     * Read settings from session
     */
    protected function read(): void
    {
        $this->active = (bool) ilSession::get(self::SESSION_RECORDING_ACTIVE);
        $this->tour_id = ilSession::get(self::SESSION_RECORDING_TOUR_ID);
        $this->recorded_steps = ilSession::get(self::SESSION_RECORDING_STEPS) ?? [];
    }
}
