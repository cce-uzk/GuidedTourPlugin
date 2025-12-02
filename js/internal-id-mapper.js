/**
 * Internal ID Mapper for GuidedTour Plugin
 * Enables intelligent element recognition using ILIAS internal identifiers
 *
 * This module provides our own mapping registration system that works
 * independently of ILIAS version. Components call il.Plugins.GuidedTour.registerMapping()
 * during rendering to register their internal_id → frontend_id mappings.
 *
 * @author Nadimo Staszak <nadimo.staszak@uni-koeln.de>
 */

// Ensure namespace exists
var il = il || {};
il.Plugins = il.Plugins || {};
il.Plugins.GuidedTour = il.Plugins.GuidedTour || {};

/**
 * Public registration function
 * Called by ILIAS components during rendering via ComponentDecorator
 *
 * @param {string} internalId - The stable internal identifier (e.g., "mm_pd_crs_grp")
 * @param {string} frontendId - The dynamic frontend ID (e.g., "il_ui_fw_...")
 */
il.Plugins.GuidedTour.registerMapping = function(internalId, frontendId) {
    if (!il.Plugins.GuidedTour.mapper) {
        console.warn('[GTour] Mapper not initialized yet, queuing mapping:', internalId, '→', frontendId);
        // Queue for when mapper is ready
        if (!il.Plugins.GuidedTour._pendingMappings) {
            il.Plugins.GuidedTour._pendingMappings = [];
        }
        il.Plugins.GuidedTour._pendingMappings.push([internalId, frontendId]);
        return;
    }

    il.Plugins.GuidedTour.mapper._register(internalId, frontendId);
};

il.Plugins.GuidedTour.mapper = (function() {
    'use strict';

    // Storage: internalId <-> frontendId mappings
    const internalToFrontend = new Map();
    const frontendToInternal = new Map();

    /**
     * Initialize the mapper
     */
    function init() {
        //console.log('[GTour Mapper] Initializing internal ID mapper...');

        // Process any pending mappings that came in before initialization
        if (il.Plugins.GuidedTour._pendingMappings && il.Plugins.GuidedTour._pendingMappings.length > 0) {
            //console.log(`[GTour Mapper] Processing ${il.Plugins.GuidedTour._pendingMappings.length} pending mappings...`);
            il.Plugins.GuidedTour._pendingMappings.forEach(function([internalId, frontendId]) {
                _register(internalId, frontendId);
            });
            il.Plugins.GuidedTour._pendingMappings = [];
        }

        //console.log('[GTour Mapper] Initialization complete.');

        // Debug output after page load
        if (window.addEventListener) {
            window.addEventListener('load', function() {
                setTimeout(function() {
                    const count = internalToFrontend.size;
                    //console.log(`[GTour Mapper] Captured ${count} mappings after page load`);

                    if (count === 0) {
                        console.warn('[GTour Mapper] No mappings captured. Check if GlobalScreen components are rendering.');
                    }
                }, 500);
            });
        }
    }

    /**
     * Internal registration function
     * @private
     */
    function _register(internalId, frontendId) {
        //console.log(`[GTour Mapper] Registered: ${internalId} → ${frontendId}`);

        // Store bidirectional mapping
        internalToFrontend.set(internalId, frontendId);
        frontendToInternal.set(frontendId, internalId);
    }

    /**
     * Find DOM element by internal ID
     * @param {string} internalId - The ILIAS internal identifier (e.g., "mm_pd_crs_grp")
     * @returns {HTMLElement|null} The DOM element or null if not found
     */
    function findElementByInternalId(internalId) {
        const frontendId = internalToFrontend.get(internalId);

        if (!frontendId) {
            console.warn(`[GTour Mapper] No frontend ID found for internal ID: ${internalId}`);
            return null;
        }

        // Strategy 1: Try aria-controls FIRST (most common for MainBar buttons)
        // IMPORTANT: aria-controls gets overwritten by Driver.js to "driver-popover-content"!
        // So if we find via aria-controls, we MUST update the mapping to the element's ID
        let element = document.querySelector(`[aria-controls="${frontendId}"]`);
        if (element) {
            //console.log(`[GTour Mapper] ✓ Found element for internal ID "${internalId}" via aria-controls:`, element);
            // Update mapping to use element's ID (Driver.js will overwrite aria-controls!)
            if (element.id) {
                //console.log(`[GTour Mapper] → Updating mapping to use element ID: ${element.id} (aria-controls will be overwritten by Driver.js)`);
                internalToFrontend.set(internalId, element.id);
                frontendToInternal.delete(frontendId);
                frontendToInternal.set(element.id, internalId);
            }
            return element;
        }

        // Strategy 2: Try direct ID lookup (updated mapping or elements with direct ID)
        element = document.getElementById(frontendId);
        if (element) {
            //console.log(`[GTour Mapper] ✓ Found element for internal ID "${internalId}" via getElementById:`, element);

            // Special case for MetaBar: If we found a Slate, find the controlling button
            if (element.classList.contains('il-maincontrols-slate')) {
                //console.log(`[GTour Mapper] → Element is a slate, checking if it's MetaBar...`);
                const slatesContainer = element.parentElement;
                //console.log(`[GTour Mapper] → Parent element:`, slatesContainer);

                if (slatesContainer && slatesContainer.classList.contains('il-metabar-slates')) {
                    //console.log(`[GTour Mapper] → Confirmed: MetaBar slate! Looking for button...`);
                    // The button is the PARENT of .il-metabar-slates (not sibling!)
                    // Structure: <button> → <div.il-metabar-slates> → <div.il-maincontrols-slate>
                    const button = slatesContainer.previousElementSibling;
                    //console.log(`[GTour Mapper] → Parent of slates container:`, button);

                    if (button && button.tagName === 'BUTTON') {
                        //console.log(`[GTour Mapper] → Found controlling button:`, button);
                        // Update mapping to use button's ID
                        if (button.id) {
                            //console.log(`[GTour Mapper] → Updating mapping: ${internalId} from ${frontendId} to ${button.id}`);
                            internalToFrontend.set(internalId, button.id);
                            frontendToInternal.delete(frontendId);
                            frontendToInternal.set(button.id, internalId);
                        } else {
                            console.warn(`[GTour Mapper] → Button has no ID!`);
                        }
                        return button;
                    } else {
                        console.warn(`[GTour Mapper] → Previous sibling is not a button!`);
                    }
                } else {
                    //console.log(`[GTour Mapper] → Not a MetaBar slate (parent is not .il-metabar-slates)`);
                }
            }

            return element;
        }

        // Strategy 3: Try aria-labelledby (button points to label element)
        element = document.querySelector(`[aria-labelledby="${frontendId}"]`);
        if (element) {
            //console.log(`[GTour Mapper] ✓ Found element for internal ID "${internalId}" via aria-labelledby:`, element);
            // If element has an ID, update the mapping to use the element's ID
            // This makes future lookups more stable
            if (element.id) {
                //console.log(`[GTour Mapper] → Updating mapping to use element ID: ${element.id}`);
                internalToFrontend.set(internalId, element.id);
                frontendToInternal.delete(frontendId);
                frontendToInternal.set(element.id, internalId);
            }
            return element;
        }

        console.warn(`[GTour Mapper] No element found for frontend ID: ${frontendId} (tried aria-controls, id, aria-labelledby)`);
        return null;
    }

    /**
     * Get internal ID from a DOM element
     * @param {HTMLElement} element - The DOM element
     * @returns {string|null} The internal ID or null if not mapped
     */
    function getInternalIdFromElement(element) {
        if (!element) {
            return null;
        }

        // First: Check if element itself has an ID that's mapped
        if (element.id) {
            const internalId = frontendToInternal.get(element.id);
            if (internalId) {
                //console.log(`[GTour Mapper] ✓ Found internal ID "${internalId}" via element.id:`, element);
                return internalId;
            }
        }

        // Second: Check aria-labelledby attribute (common for MainBar/MetaBar buttons)
        const ariaLabelledBy = element.getAttribute('aria-labelledby');
        if (ariaLabelledBy) {
            const internalId = frontendToInternal.get(ariaLabelledBy);
            if (internalId) {
                //console.log(`[GTour Mapper] ✓ Found internal ID "${internalId}" via aria-labelledby:`, element);
                return internalId;
            }
        }

        // Third: Check aria-controls attribute (for buttons that control slates/dropdowns)
        const ariaControls = element.getAttribute('aria-controls');
        if (ariaControls) {
            const internalId = frontendToInternal.get(ariaControls);
            if (internalId) {
                //console.log(`[GTour Mapper] ✓ Found internal ID "${internalId}" via aria-controls:`, element);
                return internalId;
            }
        }

        return null;
    }

    /**
     * Check if element has an internal ID mapping
     * @param {HTMLElement} element - The DOM element to check
     * @returns {boolean} True if element has internal ID mapping
     */
    function hasInternalId(element) {
        if (!element || !element.id) {
            return false;
        }
        return frontendToInternal.has(element.id);
    }

    /**
     * Get element type (mainbar, metabar, etc.) for an internal ID
     * @param {string} internalId - The internal identifier
     * @returns {string|null} The element type or null if not found
     */
    function getTypeByInternalId(internalId) {
        if (!il.Plugins.GuidedTour.internalMappings) {
            return null;
        }

        const mapping = il.Plugins.GuidedTour.internalMappings.find(m => m.internal_id === internalId);
        return mapping ? mapping.type : null;
    }

    /**
     * Debug function: Display all available mappings
     */
    function debug() {
        console.log('\n=== GTour Internal ID Mappings ===\n');

        // Show captured runtime mappings
        console.log('Runtime Mappings (Internal → Frontend):');
        if (internalToFrontend.size === 0) {
            console.log('  (none captured yet)');
        } else {
            internalToFrontend.forEach((frontendId, internalId) => {
                const el = document.getElementById(frontendId);
                const exists = el ? '✓' : '✗';
                console.log(`  ${exists} ${internalId} → ${frontendId}`, el);
            });
        }

        // Show available mappings from backend
        console.log('\nAvailable Internal IDs (from backend):');
        if (il.Plugins.GuidedTour.internalMappings && il.Plugins.GuidedTour.internalMappings.length > 0) {
            il.Plugins.GuidedTour.internalMappings.forEach(mapping => {
                const hasFrontend = internalToFrontend.has(mapping.internal_id);
                const status = hasFrontend ? '✓ mapped' : '✗ not mapped yet';
                console.log(`  [${mapping.type}] ${mapping.internal_id} - ${status}`);
            });
        } else {
            console.log('  (no mappings provided by backend)');
        }

        console.log('\n=================================\n');
    }

    /**
     * Test function: Find and highlight element by internal ID
     * @param {string} internalId - The internal identifier to test
     * @returns {HTMLElement|null} The found element or null
     */
    function test(internalId) {
        console.log(`\n=== Testing Internal ID: "${internalId}" ===`);

        const frontendId = internalToFrontend.get(internalId);
        console.log('Frontend ID:', frontendId || '(not found)');

        if (frontendId) {
            const element = document.getElementById(frontendId);
            console.log('Element:', element || '(not found in DOM)');

            if (element) {
                console.log('✓ Success! Element found and will be highlighted.');

                // Highlight element for 2 seconds
                const originalOutline = element.style.outline;
                element.style.outline = '3px solid red';
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });

                setTimeout(() => {
                    element.style.outline = originalOutline;
                }, 2000);

                return element;
            }
        }

        console.log('✗ Test failed: Element not found');
        return null;
    }

    /**
     * List all internal IDs with their status
     * @returns {Array} Array of objects with internal_id, type, mapped, and element properties
     */
    function list() {
        const results = [];

        if (il.Plugins.GuidedTour.internalMappings) {
            il.Plugins.GuidedTour.internalMappings.forEach(mapping => {
                const frontendId = internalToFrontend.get(mapping.internal_id);
                const element = frontendId ? document.getElementById(frontendId) : null;

                results.push({
                    internal_id: mapping.internal_id,
                    type: mapping.type,
                    mapped: !!frontendId,
                    frontend_id: frontendId || null,
                    element_exists: !!element
                });
            });
        }

        return results;
    }

    /**
     * Get mapping statistics
     * @returns {Object} Statistics object
     */
    function stats() {
        const available = il.Plugins.GuidedTour.internalMappings ? il.Plugins.GuidedTour.internalMappings.length : 0;
        const mapped = internalToFrontend.size;

        return {
            available_from_backend: available,
            captured_mappings: mapped,
            mapping_rate: available > 0 ? ((mapped / available) * 100).toFixed(1) + '%' : 'N/A'
        };
    }

    // Public API
    return {
        init: init,
        _register: _register,  // Expose for il.gtour.registerMapping()
        findElementByInternalId: findElementByInternalId,
        getInternalIdFromElement: getInternalIdFromElement,
        hasInternalId: hasInternalId,
        getTypeByInternalId: getTypeByInternalId,
        debug: debug,
        test: test,
        list: list,
        stats: stats,

        // Direct access to Maps for advanced debugging
        get maps() {
            return {
                internalToFrontend: internalToFrontend,
                frontendToInternal: frontendToInternal
            };
        }
    };
})();

/**
 * Universal element finder for use in Action code
 * Tries internal ID first, then falls back to CSS selector
 *
 * Usage in onNext/onPrev actions:
 *   const el = il.Plugins.GuidedTour.findElement('mm_pd_crs_grp');
 *   if (el) el.click();
 *
 * @param {string} internalIdOrSelector - Internal ID or CSS selector
 * @returns {HTMLElement|null} The found element or null
 */
il.Plugins.GuidedTour.findElement = function(internalIdOrSelector) {
    if (!internalIdOrSelector) {
        return null;
    }

    // Try as internal ID first (if mapper is ready)
    if (il.Plugins.GuidedTour.mapper) {
        const element = il.Plugins.GuidedTour.mapper.findElementByInternalId(internalIdOrSelector);
        if (element) {
            console.log(`[GTour] findElement: Resolved internal ID "${internalIdOrSelector}"`, element);
            return element;
        }
    }

    // Fallback: Try as CSS selector
    try {
        const element = document.querySelector(internalIdOrSelector);
        if (element) {
            console.log(`[GTour] findElement: Found via querySelector("${internalIdOrSelector}")`, element);
        }
        return element;
    } catch (e) {
        console.warn(`[GTour] findElement: Neither internal ID nor valid selector: "${internalIdOrSelector}"`, e);
        return null;
    }
};

// Short alias for convenience in action code
il.Plugins.GuidedTour.$ = il.Plugins.GuidedTour.findElement;

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        il.Plugins.GuidedTour.mapper.init();
    });
} else {
    // DOM already loaded
    il.Plugins.GuidedTour.mapper.init();
}
