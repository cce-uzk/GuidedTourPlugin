<?php declare(strict_types=1);

namespace uzk\gtour\Model;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\FileUpload\Exception\IllegalStateException;
use uzk\gtour\Data\GuidedTourResourceStakeholder;
use Exception;

/**
 * Class GuidedTour
 * GuidedTour Object
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class GuidedTour
{
    const TYPES = array('any', 'crs', 'fold', 'grp', 'tst', 'exc', 'book', 'ildashboardgui', 'ilmembershipoverviewgui');
    const TYPE_DEFAULT = 'any';

    const TRIGGER_MODE_NORMAL = 'normal';
    const TRIGGER_MODE_ALWAYS = 'always';
    const TRIGGER_MODE_UNTIL_COMPLETED = 'until_completed';
    const TRIGGER_MODES = array(self::TRIGGER_MODE_NORMAL, self::TRIGGER_MODE_ALWAYS, self::TRIGGER_MODE_UNTIL_COMPLETED);

    /** @var ?int */
    protected ?int $id;
    /** @var string */
    protected string $title;
    /** @var ?string */
    protected ?string $icon;
    /** @var string */
    protected string $type;
    /** @var ?string */
    protected ?string $script;
    /** @var bool */
    protected bool $active;
    /** @var bool */
    protected bool $automatic_triggered;
    /** @var array */
    protected array $rolesIds;
    /** @var ?string */
    protected ?string $language_code;
    /** @var ?string */
    protected ?string $description;
    /** @var ?string */
    protected ?string $scenario;
    /** @var ?int */
    protected ?int $ref_id;
    /** @var string */
    protected string $trigger_mode;

    public function __construct(
        ?int $id = null,
        string $title = "",
        ?string $icon = null,
        string $type = self::TYPE_DEFAULT,
        ?string $script = "",
        bool|string|int $active = false,
        bool|string|int $automatic_triggered = true,
        array $rolesIds = array(),
        ?string $language_code = null,
        ?string $description = null,
        ?string $scenario = null,
        ?int $ref_id = null,
        string $trigger_mode = self::TRIGGER_MODE_NORMAL
    ) {
        $this->setId($id);
        $this->setTitle($title);
        $this->setIconId($icon);
        $this->setType($type);
        $this->setScript($script);
        $this->setActive($active);
        $this->setAutomaticTriggered($automatic_triggered);
        $this->setRolesIds($rolesIds);
        $this->setLanguageCode($language_code);
        $this->setDescription($description);
        $this->setScenario($scenario);
        $this->setRefId($ref_id);
        $this->setTriggerMode($trigger_mode);
    }

    public function setId(?int $a_val) : void
    {
        $this->id = $a_val;
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function setTitle(string $a_val) : void
    {
        $this->title = $a_val;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function setIconId(?string $a_val) : void
    {
        $this->icon = $a_val;
    }

    public function getIconId() : ?string
    {
        return $this->icon;
    }

    public function setType(string $a_val) : void
    {
        $this->type = $a_val;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setScript(?string $a_val) : void
    {
        $this->script = $a_val;
    }

    public function getScript() : ?string
    {
        return $this->script;
    }

    /**
     * Get the effective script for this tour
     * Returns JSON from steps if they exist, otherwise returns the manual script
     * @return string|null
     */
    public function getEffectiveScript() : ?string
    {
        // Check if tour has steps defined in database
        $stepRepo = new \uzk\gtour\Data\GuidedTourStepRepository();
        $steps = $stepRepo->getStepsByTourId($this->getId());

        if (!empty($steps)) {
            // Generate JSON from steps
            return $this->generateJsonFromSteps($steps);
        }

        // No steps defined, return manual script
        return $this->script;
    }

    /**
     * Generate JSON array from steps
     * @param array $steps Array of GuidedTourStep objects
     * @return string JSON string
     */
    protected function generateJsonFromSteps(array $steps) : string
    {
        // Sort steps by sort_order
        usort($steps, function($a, $b) {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });

        // Convert steps to JSON array format
        $stepsArray = [];
        foreach ($steps as $step) {
            $stepsArray[] = $step->toJsonArray();
        }

        return json_encode($stepsArray, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function setActive(bool|string|int $a_val = false) : void
    {
        $value = filter_var($a_val, FILTER_VALIDATE_BOOLEAN);
        $this->active = $value;
    }

    public function isActive() : bool
    {
        return $this->active;
    }

    public function setAutomaticTriggered(bool|string|int $a_val = false) : void
    {
        $value = filter_var($a_val, FILTER_VALIDATE_BOOLEAN);
        $this->automatic_triggered = $value;
    }

    public function isAutomaticTriggered() : bool
    {
        return $this->automatic_triggered;
    }

    public function getRolesIds() : array
    {
        return $this->rolesIds;
    }

    public function setRolesIds(array $a_val) : void
    {
        $this->rolesIds = $a_val;
    }

    public function getLanguageCode() : ?string
    {
        return $this->language_code;
    }

    public function setLanguageCode(?string $a_val) : void
    {
        $this->language_code = $a_val;
    }

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function setDescription(?string $a_val) : void
    {
        $this->description = $a_val;
    }

    public function getScenario() : ?string
    {
        return $this->scenario;
    }

    public function setScenario(?string $a_val) : void
    {
        $this->scenario = $a_val;
    }

    public function getRefId() : ?int
    {
        return $this->ref_id;
    }

    public function setRefId(?int $a_val) : void
    {
        $this->ref_id = $a_val;
    }

    public function getTriggerMode() : string
    {
        return $this->trigger_mode;
    }

    public function setTriggerMode(string $a_val) : void
    {
        // Validate trigger mode
        if (!in_array($a_val, self::TRIGGER_MODES)) {
            $a_val = self::TRIGGER_MODE_NORMAL;
        }
        $this->trigger_mode = $a_val;
    }

    private function getRolesIdsAsJSON() : string
    {
        return json_encode($this->rolesIds);
    }

    private function setRolesIdsFromJSON(string $a_val) : void
    {
        if (!($a_val === "")) {
            // Use json_decode with true parameter to get array instead of object
            $decoded = json_decode($a_val, true);
            // Ensure we have a numeric array, not an associative one
            $this->rolesIds = is_array($decoded) ? array_values($decoded) : [];
        } else {
            $this->rolesIds = array();
        }
    }

    public static function getTypes() : array
    {
        return self::TYPES;
    }

    /**
     * @param string|null $public_ressource_id
     * @param bool        $deletion_flag
     * @throws IllegalStateException
     * @throws Exception
     */
    public function updateIcon(string $public_ressource_id = null, bool $deletion_flag = false) : void
    {
        try {
            global $DIC;
            $rs = $DIC->resourceStorage();
            $stakeholder = new GuidedTourResourceStakeholder();

            if (($deletion_flag || $DIC->upload()->hasUploads()) && isset($public_ressource_id)) {
                // Remove existing icon
                $identification = $rs->manage()->find($this->getIconId());
                if ($identification !== null) {
                    $rs->manage()->remove($identification, $stakeholder);
                    $this->setIconId(null);
                }
            }

            if ($DIC->upload()->hasUploads() && !$DIC->upload()->hasBeenProcessed()) {
                // save new icon or keep
                $DIC->upload()->process();
                if ($DIC->upload()->hasBeenProcessed() && $DIC->upload()->hasUploads()) {
                    $results = $DIC->upload()->getResults();
                    if (!empty($results)) {
                        $result = end($results);
                        if ($result !== false && $result->isOK()) {
                            $identification = $rs->manage()->upload($result, $stakeholder);
                            $this->setIconId($identification->serialize());
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Handle exception
            throw new Exception("Failed to update icon: " . $e->getMessage());
        }
    }

    /**
     * @return string
     */
    public function getIconTitle() : string
    {
        global $DIC;
        $rs = $DIC->resourceStorage();

        if (!empty($this->getIconId())) {
            $identification = $rs->manage()->find($this->getIconId());
            if ($identification !== null) {
                $currentRevision = $rs->manage()->getCurrentRevision($identification);
                if (!empty($currentRevision->getIdentification())) {
                    return $currentRevision->getTitle();
                }
            }
        }
        return '';
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function getIconSrc(): ?string
    {
        global $DIC;
        $rs = $DIC->resourceStorage();

        if (!empty($this->getIconId())) {
            $identification = $rs->manage()->find($this->getIconId());
            if ($identification !== null) {
                if (!empty($rs->manage()->find($identification->serialize()))) {
                    $src = $rs->consume()->src($identification);
                    return $src->getSrc();
                }
            }
        }
        return null;
    }

    /**
     * Write the properties to an array
     * @return array
     */
    public function toArray() : array
    {
        return array(
            "tour_id" => array('integer', $this->getId()),
            "title" => array('text', $this->getTitle()),
            "icon_id" => array('text', $this->getIconId()),
            "type" => array('text', $this->getType()),
            "script" => array('text', $this->getScript()),
            "is_active" => array('integer', $this->isActive()),
            "is_automatic_triggered" => array('integer', $this->isAutomaticTriggered()),
            "roles_ids" => array('text', $this->getRolesIdsAsJSON()),
            "language_code" => array('text', $this->getLanguageCode()),
            "description" => array('text', $this->getDescription()),
            "scenario" => array('text', $this->getScenario()),
            "ref_id" => array('integer', $this->getRefId()),
            "trigger_mode" => array('text', $this->getTriggerMode())
        );
    }

    /**
     * @return array
     */
    public function toDataArray() : array
    {
        return array(
            'tour_id' => $this->getId(),
            'title' => $this->getTitle(),
            "icon_id" => $this->getIconId(),
            'type' => $this->getType(),
            'script' => $this->getScript(),
            'is_active' => $this->isActive(),
            "is_automatic_triggered" => $this->isAutomaticTriggered(),
            "roles_ids" => $this->getRolesIds(),
            "language_code" => $this->getLanguageCode(),
            "description" => $this->getDescription(),
            "scenario" => $this->getScenario(),
            "ref_id" => $this->getRefId(),
            "trigger_mode" => $this->getTriggerMode()
        );
    }

    /**
     * @return array[]
     */
    public function toPrimaryArray() : array
    {
        return array(
            "tour_id" => array('int', $this->getId())
        );
    }

    /**
     * Get the properties from an array
     * @param array $array
     * @return void
     */
    public function fromArray(array $array = array()): void
    {
        $this->setId($array['tour_id']);
        $this->setTitle($array['title']);
        $this->setIconId($array['icon_id']);
        $this->setType($array['type']);
        $this->setScript($array['script']);
        $this->setActive($array['is_active']);
        $this->setAutomaticTriggered($array['is_automatic_triggered']);
        $this->setRolesIdsFromJSON($array['roles_ids']);
        $this->setLanguageCode($array['language_code'] ?? null);
        $this->setDescription($array['description'] ?? null);
        $this->setScenario($array['scenario'] ?? null);
        $this->setRefId($array['ref_id'] ?? null);
        $this->setTriggerMode($array['trigger_mode'] ?? self::TRIGGER_MODE_NORMAL);
    }
}
