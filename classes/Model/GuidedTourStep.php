<?php declare(strict_types=1);

namespace uzk\gtour\Model;
require_once __DIR__ . "/../../vendor/autoload.php";

/**
 * Class GuidedTourStep
 * GuidedTour Step Object
 * @author  Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 * @version $Id$
 */
class GuidedTourStep
{
    const PLACEMENTS = ['top', 'bottom', 'left', 'right', 'center'];
    const PLACEMENT_DEFAULT = 'right';

    // Element types for smart pattern recognition
    const ELEMENT_TYPE_MAINBAR = 'mainbar';
    const ELEMENT_TYPE_METABAR = 'metabar';
    const ELEMENT_TYPE_TAB = 'tab';
    const ELEMENT_TYPE_FORM = 'form';
    const ELEMENT_TYPE_TABLE = 'table';
    const ELEMENT_TYPE_TOOLBAR = 'toolbar';
    const ELEMENT_TYPE_BUTTON = 'button';
    const ELEMENT_TYPE_CSS_SELECTOR = 'css_selector';

    protected ?int $id;
    protected int $tourId;
    protected int $sortOrder;
    protected ?string $element;
    protected ?string $title;
    protected ?string $content;
    protected ?int $contentPageId;
    protected string $placement;
    protected bool $orphan;
    protected ?string $onNext;
    protected ?string $onPrev;
    protected ?string $onShow;
    protected ?string $onShown;
    protected ?string $onHide;
    protected ?string $path;
    protected ?string $elementType;
    protected ?string $elementName;

    public function __construct(
        ?int $id = null,
        int $tourId = 0,
        int $sortOrder = 0,
        ?string $element = null,
        ?string $title = null,
        ?string $content = null,
        ?int $contentPageId = null,
        string $placement = self::PLACEMENT_DEFAULT,
        bool|string|int $orphan = false,
        ?string $onNext = null,
        ?string $onPrev = null,
        ?string $onShow = null,
        ?string $onShown = null,
        ?string $onHide = null,
        ?string $path = null,
        ?string $elementType = null,
        ?string $elementName = null
    ) {
        $this->setId($id);
        $this->setTourId($tourId);
        $this->setSortOrder($sortOrder);
        $this->setElement($element);
        $this->setTitle($title);
        $this->setContent($content);
        $this->setContentPageId($contentPageId);
        $this->setPlacement($placement);
        $this->setOrphan($orphan);
        $this->setOnNext($onNext);
        $this->setOnPrev($onPrev);
        $this->setOnShow($onShow);
        $this->setOnShown($onShown);
        $this->setOnHide($onHide);
        $this->setPath($path);
        $this->setElementType($elementType);
        $this->setElementName($elementName);
    }

    public function setId(?int $a_val): void
    {
        $this->id = $a_val;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTourId(int $a_val): void
    {
        $this->tourId = $a_val;
    }

    public function getTourId(): int
    {
        return $this->tourId;
    }

    public function setSortOrder(int $a_val): void
    {
        $this->sortOrder = $a_val;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setElement(?string $a_val): void
    {
        $this->element = $a_val;
    }

    public function getElement(): ?string
    {
        return $this->element;
    }

    public function setTitle(?string $a_val): void
    {
        $this->title = $a_val;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setContent(?string $a_val): void
    {
        $this->content = $a_val;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContentPageId(?int $a_val): void
    {
        $this->contentPageId = $a_val;
    }

    public function getContentPageId(): ?int
    {
        return $this->contentPageId;
    }

    public function setPlacement(string $a_val): void
    {
        if (in_array($a_val, self::PLACEMENTS)) {
            $this->placement = $a_val;
        } else {
            $this->placement = self::PLACEMENT_DEFAULT;
        }
    }

    public function getPlacement(): string
    {
        return $this->placement;
    }

    public function setOrphan(bool|string|int $a_val): void
    {
        $this->orphan = filter_var($a_val, FILTER_VALIDATE_BOOLEAN);
    }

    public function isOrphan(): bool
    {
        return $this->orphan;
    }

    public function setOnNext(?string $a_val): void
    {
        $this->onNext = $a_val;
    }

    public function getOnNext(): ?string
    {
        return $this->onNext;
    }

    public function setOnPrev(?string $a_val): void
    {
        $this->onPrev = $a_val;
    }

    public function getOnPrev(): ?string
    {
        return $this->onPrev;
    }

    public function setOnShow(?string $a_val): void
    {
        $this->onShow = $a_val;
    }

    public function getOnShow(): ?string
    {
        return $this->onShow;
    }

    public function setOnShown(?string $a_val): void
    {
        $this->onShown = $a_val;
    }

    public function getOnShown(): ?string
    {
        return $this->onShown;
    }

    public function setOnHide(?string $a_val): void
    {
        $this->onHide = $a_val;
    }

    public function getOnHide(): ?string
    {
        return $this->onHide;
    }

    public function setPath(?string $a_val): void
    {
        $this->path = $a_val;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setElementType(?string $a_val): void
    {
        $this->elementType = $a_val;
    }

    public function getElementType(): ?string
    {
        return $this->elementType;
    }

    public function setElementName(?string $a_val): void
    {
        $this->elementName = $a_val;
    }

    public function getElementName(): ?string
    {
        return $this->elementName;
    }

    /**
     * Convert step to array for table display
     */
    public function toDataArray(): array
    {
        return [
            'step_id' => $this->getId(),
            'tour_id' => $this->getTourId(),
            'sort_order' => $this->getSortOrder(),
            'element' => $this->getElement(),
            'title' => $this->getTitle(),
            'content' => $this->getContent(),
            'content_page_id' => $this->getContentPageId(),
            'placement' => $this->getPlacement(),
            'orphan' => $this->isOrphan(),
            'on_next' => $this->getOnNext(),
            'on_prev' => $this->getOnPrev(),
            'on_show' => $this->getOnShow(),
            'on_shown' => $this->getOnShown(),
            'on_hide' => $this->getOnHide(),
            'path' => $this->getPath(),
            'element_type' => $this->getElementType(),
            'element_name' => $this->getElementName()
        ];
    }

    /**
     * Convert step to JSON format for Driver.js
     */
    public function toJsonArray(): array
    {
        $json = [];

        if ($this->getElement()) {
            $json['element'] = $this->getElement();
        }

        if ($this->getTitle()) {
            $json['title'] = $this->getTitle();
        }

        // Content: Use Page Object content if page_id exists, otherwise use plain content
        $content = $this->getEffectiveContent();
        if ($content) {
            $json['content'] = $content;
        }

        if ($this->getPlacement()) {
            $json['placement'] = $this->getPlacement();
        }

        if ($this->isOrphan()) {
            $json['orphan'] = true;
        }

        if ($this->getOnNext()) {
            $json['onNext'] = $this->getOnNext();
        }

        if ($this->getOnPrev()) {
            $json['onPrev'] = $this->getOnPrev();
        }

        if ($this->getOnShow()) {
            $json['onShow'] = $this->getOnShow();
        }

        if ($this->getOnShown()) {
            $json['onShown'] = $this->getOnShown();
        }

        if ($this->getOnHide()) {
            $json['onHide'] = $this->getOnHide();
        }

        if ($this->getPath()) {
            $json['path'] = $this->getPath();
        }

        if ($this->getElementType()) {
            $json['elementType'] = $this->getElementType();
        }

        if ($this->getElementName()) {
            $json['elementName'] = $this->getElementName();
        }

        return $json;
    }

    /**
     * Get effective content for this step
     * Returns content from Page Object if page_id exists, otherwise returns plain content
     * @return string|null
     */
    public function getEffectiveContent(): ?string
    {
        // Check if step has a page object
        if ($this->getContentPageId() !== null && $this->getContentPageId() > 0) {
            try {
                // Load content from Page Object (without GUI to avoid template dependency)
                require_once __DIR__ . '/../Page/class.ilGuidedTourStepPage.php';

                // Check if page exists
                if (!\ilGuidedTourStepPage::_exists('gtst', $this->getContentPageId())) {
                    return $this->content;
                }

                $page_object = new \ilGuidedTourStepPage($this->getContentPageId());
                $page_object->buildDom();

                global $DIC;
                $logger = $DIC->logger()->root();

                // Check if rendered content is cached
                $rendered = $page_object->getRenderedContent();

                if ($rendered && trim($rendered) !== '') {
                    $logger->debug('GuidedTourStep: Using cached rendered content');
                    return $rendered;
                }

                $logger->debug('GuidedTourStep: No cached content, extracting from DOM');

                // If not cached, extract from DOM (lightweight fallback without full XSLT)
                $dom = $page_object->getDomDoc();
                $xpath = new \DOMXPath($dom);

                // Get all Paragraph elements
                $paragraphs = $xpath->query('//Paragraph');
                $html_parts = [];

                foreach ($paragraphs as $para_node) {
                    $text = trim($para_node->nodeValue);
                    if ($text !== '') {
                        // Use div to preserve spacing like ILIAS does
                        $html_parts[] = '<div class="ilc_text_block_Standard">' . nl2br(htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</div>';
                    }
                }

                if (!empty($html_parts)) {
                    return implode("\n", $html_parts);
                }

                $logger->debug('GuidedTourStep: No paragraphs found, falling back to plain content');
            } catch (\Throwable $e) {
                // Fallback to plain content if page loading fails
                global $DIC;
                if (isset($DIC)) {
                    $DIC->logger()->root()->error('GuidedTourStep: Failed to load page content: ' . $e->getMessage());
                }
                return $this->content;
            }
        }

        // No page object or page not found, return plain content
        return $this->content;
    }
}
