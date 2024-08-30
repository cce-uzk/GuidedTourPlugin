<?php declare(strict_types=1);

namespace uzk\gtour\Model;
require_once __DIR__ . "/../../vendor/autoload.php";

use ILIAS\FileUpload\Exception\IllegalStateException;
use uzk\gtour\Data\GuidedTourResourceStakeholder;
use Exception;
use InvalidArgumentException;

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

    public function __construct(
        ?int $id = null,
        string $title = "",
        ?string $icon = null,
        string $type = self::TYPE_DEFAULT,
        ?string $script = "",
        bool $active = false,
        bool $automatic_triggered = true,
        array $rolesIds = array()
    ) {
        $this->setId($id);
        $this->setTitle($title);
        $this->setIconId($icon);
        $this->setType($type);
        $this->setScript($script);
        $this->setActive($active);
        $this->setAutomaticTriggered($automatic_triggered);
        $this->setRolesIds($rolesIds);
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

    public function setActive($a_val) : void
    {
        // Manual type validation for php 7.4 compatibility instead of union types
        if (is_bool($a_val) || is_string($a_val) || is_int($a_val)) {
            $value = filter_var($a_val, FILTER_VALIDATE_BOOLEAN);
            $this->active = $value;
        } else {
            throw new InvalidArgumentException("Invalid type for active property");
        }
    }

    public function isActive() : bool
    {
        return $this->active;
    }

    public function setAutomaticTriggered($a_val) : void
    {
        // Manual type validation for php 7.4 compatibility instead of union types
        if (is_bool($a_val) || is_string($a_val) || is_int($a_val)) {
            $value = filter_var($a_val, FILTER_VALIDATE_BOOLEAN);
            $this->automatic_triggered = $value;
        } else {
            throw new InvalidArgumentException("Invalid type for automatic_triggered property");
        }
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

    private function getRolesIdsAsJSON() : string
    {
        return json_encode($this->rolesIds);
    }

    private function setRolesIdsFromJSON(string $a_val) : void
    {
        if (!($a_val === "")) {
            $this->rolesIds = (array) json_decode($a_val);
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
            "roles_ids" => array('text', $this->getRolesIdsAsJSON())
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
            "roles_ids" => $this->getRolesIds()
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
    }
}