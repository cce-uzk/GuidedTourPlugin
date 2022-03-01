<?php
/**
 * Class ilGuidedTour
 * GuidedTour Object
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 *
 */
class ilGuidedTour
{
    protected static $tours;

    const TYPES = array('any', 'crs', 'fold', 'grp', 'tst', 'exc', 'book', 'ildashboardgui', 'ilmembershipoverviewgui');
    const TYPE_DEFAULT = 'any';

    protected $tour_id;
    protected $title;
    protected $icon;
    protected $active;
    protected $type;
    protected $script;
    protected $rolesIds;

    public function setTourId($a_val)
    {
        $this->tour_id = (int)$a_val;
    }

    public function getTourId()
    {
        return $this->tour_id;
    }

    public function setTitle($a_val)
    {
        $this->title = (string)$a_val;
    }

    public function getTitle(): string
    {
        return (string)$this->title;
    }

    public function setIconId($a_val)
    {
        $this->icon = $a_val;
    }

    public function getIconId()
    {
        return $this->icon;
    }

    public function setType($a_val)
    {
        $this->type = (string)$a_val;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setScript($a_val)
    {
        $a_val = str_replace("< ", "<", $a_val);
        $this->script = (string)$a_val;
    }

    public function getScript()
    {
        return $this->script;
    }

    public function setActive($a_val)
    {
        $this->active = (bool)$a_val;
    }

    public function isActive(): bool
    {
        return (bool)$this->active;
    }

    public function getRolesIds()
    {
        return $this->rolesIds;
    }

    public function setRolesIds($a_val)
    {
        $this->rolesIds = (array)$a_val;
    }

    private function getRolesIdsAsJSON() : string
    {
        if(isset($this->rolesIds)) {
            return json_encode($this->rolesIds);
        }
        else {
            return '';
        }
    }

    private function setRolesIdsFromJSON($a_val)
    {
        if(isset($a_val)) {
            $this->rolesIds = (array)json_decode((string)$a_val);
        } else {
            $this->rolesIds = array();
        }
    }

    public function __construct()
    {

    }

    /**
     * @throws \ILIAS\FileUpload\Exception\IllegalStateException
     * @throws Exception
     */
    public function updateIcon($deletionFlag = false)
    {
        global $DIC;
        $stakeholder = new ilGuidedTourResourceStakeholder();

        if ($DIC->upload()->hasUploads() && !$DIC->upload()->hasBeenProcessed()) {

            if ($deletionFlag) {
                // remove existing icon
                if ($this->getIconId() != null) {
                    $identification = new \ILIAS\ResourceStorage\Identification\ResourceIdentification($this->getIconId());
                    if (!empty($identification)) {
                        if (!empty($DIC->resourceStorage()->manage()->find($identification))) {
                            $DIC->resourceStorage()->manage()->remove($identification, $stakeholder);
                            $this->setIconId(null);
                        }
                    }
                }
            }

            if(empty($this->getIconId()) || $deletionFlag) {
                // save new icon or keep
                $DIC->upload()->process();
                if ($DIC->upload()->hasBeenProcessed() && $DIC->upload()->hasUploads()) {
                    $results = $DIC->upload()->getResults();
                    if (!empty($results)) {
                        $result = end($results);
                        if ($result !== false && $result->isOK()) {
                            $identification = $DIC->resourceStorage()->manage()->upload($result, $stakeholder);
                            if (!empty($identification)) {
                                $this->setIconId($identification->serialize());
                            }
                        }
                    }
                }
            }
        }
    }

    public function getIconTitle(): string
    {
        global $DIC;
        if (!empty($this->getIconId())) {
            $identification = new \ILIAS\ResourceStorage\Identification\ResourceIdentification($this->getIconId());
            if (!empty($identification)) {
                if (!empty($DIC->resourceStorage()->manage()->find($identification))) {
                    $currentRevision = $DIC->resourceStorage()->manage()->getCurrentRevision($identification);
                    if (!empty($currentRevision)) {
                        return $currentRevision->getTitle();
                    }
                }
            }
        }
        return '';
    }

    public function getIconSrc()
    {
        global $DIC;
        if (!empty($this->getIconId())) {
            $identification = new \ILIAS\ResourceStorage\Identification\ResourceIdentification($this->getIconId());
            if (!empty($identification)) {
                if (!empty($DIC->resourceStorage()->manage()->find($identification))) {
                    $src = $DIC->resourceStorage()->consume()->src($identification);
                    if (!empty($src)) {
                        return $src->getSrc();
                    }
                }
            }
        }
        return null;
    }

    /**
     * Wrote the properties to an array
     * @return array
     */
    public function toArray(): array
    {
        return array(
            "tour_id" => array('integer', $this->getTourId()),
            "is_active" => array('integer', $this->isActive()),
            "title" => array('text', $this->getTitle()),
            "type" => array('text', $this->getType()),
            "script" => array('text', $this->getScript()),
            "icon_id" => array('text', $this->getIconId()),
            "roles_ids" => array('text', $this->getRolesIdsAsJSON())
        );
    }

    public function toDataArray(): array
    {
        //return array_map(function($row){ return $row['value']; }, $this->toArray());
        return array(
            'tour_id' => $this->getTourId(),
            'is_active' => $this->isActive(),
            'title' => $this->getTitle(),
            'type' => $this->getType(),
            'script' => $this->getScript(),
            "icon_id" => $this->getIconId(),
            "roles_ids" => $this->getRolesIds()
        );
    }

    public function toPrimaryArray(): array
    {
        return array(
            "tour_id" => array('int', $this->getTourId())
        );
    }

    public function toPrimaryDataArray(): array
    {
        //return array_map(function($row){ return $row['value']; }, $this->toPrimaryArray());
        return array(
            'tour_id' => $this->getTourId()
        );
    }

    /**
     * Get the properties from an array
     * @param array
     */
    public function fromArray($array = array())
    {
        $this->setTourId($array['tour_id']);
        $this->setActive($array['is_active']);
        $this->setTitle($array['title']);
        $this->setType($array['type']);
        $this->setScript($array['script']);
        $this->setIconId($array['icon_id']);
        $this->setRolesIdsFromJSON($array['roles_ids']);
    }

    public static function getDefaultTour(): ilGuidedTour
    {
        $tour = new self;
        $tour->setType(self::TYPE_DEFAULT);
        $tour->setActive(false);

        return $tour;
    }

    public static function getTypes(): array
    {
        return self::TYPES;
    }

    /**
     * Get the list of all tours, indexed by tour_id
     * @return self[]
     */
    public static function getTours(): array
    {
        self::loadTours();
        return self::$tours;
    }

    /**
     *  Get a tour by id
     * @param int $tour_id
     * @return self
     */
    public static function getTourById(int $tour_id)
    {
        self::loadTours();

        foreach (self::$tours as $tour) {
            if ($tour->getTourId() == $tour_id) {
                return $tour;
            }
        }
        return null;
    }

    /**
     * Save all tours
     */
    public function saveAll()
    {
        self::loadTours();

        if (empty($this->tour_id)) {
            // add current tour to tours
            self::$tours = array();
            self::$tours[] = $this;
        } else {
            $i = 0;
            foreach (self::$tours as $tour) {
                if ($tour->getTourId == $this->tour_id) {
                    self::$tours[i] = $this;
                    break;
                }
                $i++;
            }
        }
        self::insertOrUpdateTours();
    }

    /**
     * Save current tour
     */
    public  function save()
    {
        self::insertTour($this);
        self::updateTour($this);
    }

    /**
     * Load tours from database
     */
    public static function loadTours()
    {
        if (!isset(self::$tours)) {
            self::$tours = array();
            global $DIC;
            $db = $DIC->database();
            $result = $db->query("SELECT * FROM gtour_tours");

            while ($record = $db->fetchAssoc($result)) {
                $obj = new self();
                $obj->fromArray((array)$record);
                array_push(self::$tours, $obj);
            }
        }
    }

    /**
     * Insert or update tours to database
     */
    public static function insertOrUpdateTours()
    {
        self::insertTours();
        self::updateTours();
    }

    /**
     * Update tours to database
     */
    private static function updateTours()
    {
        self::loadTours();
        foreach (self::$tours as $tour) {
            self::updateTour($tour);
        }
    }

    /**
     * Insert tour to database
     * @param ilGuidedTour $tour
     */
    public static function updateTour(self $tour)
    {
        global $DIC;
        $db = $DIC->database();
        if (!empty($tour->getTourId())) {
            $data = $tour->toArray();
            $primary = $tour->toPrimaryArray();

            $db->update('gtour_tours', $data, $primary);
        }
    }

    /**
     * Insert tours to database
     */
    private static function insertTours()
    {
        self::loadTours();
        foreach (self::$tours as $tour) {
            self::insertTour($tour);
        }
    }

    /**
     * Insert tour to database
     * @param ilGuidedTour $tour
     */
    public static function insertTour(self $tour)
    {
        global $DIC;
        $db = $DIC->database();
        if (empty($tour->getTourId())) {
            $nextId = $db->nextId("gtour_tours");
            $tour->setTourId($nextId);
            $data = $tour->toArray();
            $db->insert('gtour_tours', $data);
        }
    }

    /**
     * Delete tours by given ids
     * @param int[]
     * @throws ilDatabaseException
     */
    public static function deleteTours($tour_ids = [])
    {
        global $DIC;
        self::loadTours();
        foreach ($tour_ids as $tour_id) {
            $i = 0;
            foreach (self::$tours as $tour) {
                if ($tour->getTourId() == $tour_id) {
                    unset(self::$tours[$i]);
                }
                $i++;
            }
        }
        $statement = $DIC->database()->prepareManip(
            "DELETE FROM gtour_tours WHERE tour_id IN (?)",
            array("int")
        );
        $DIC->database()->execute($statement, $tour_ids);
        $DIC->database()->free($statement);
    }
}