/**
 * Guided Tour Recording Module
 * Captures user interactions and generates XPath selectors for tour steps
 */
(function() {
    'use strict';

    let recordingActive = false;
    let recordedSteps = [];
    let hoveredElement = null;
    let stepCounter = 0;
    let lastCapturedElement = null;
    let captureDebounceTimer = null;
    let waitingForClick = false;
    let waitingForClickElement = null;
    let waitingForClickTimer = null;
    let lastCapturedStepIndex = -1;
    let f1KeyBlocked = false;
    let currentPageUrl = '';
    let editingStepIndex = -1;
    let editingActionType = null; // 'onNext' or 'onPrev'

    // Initialize recording interface
    document.addEventListener('DOMContentLoaded', function() {
        initRecordingInterface();
    });

    function initRecordingInterface() {
        const startBtn = document.getElementById('gtour-recording-start-btn');
        const pauseBtn = document.getElementById('gtour-recording-pause-btn');
        const discardBtn = document.getElementById('gtour-recording-discard-btn');
        const saveBtn = document.getElementById('gtour-recording-save-btn');
        const backBtn = document.getElementById('gtour-recording-back-btn');
        const toggleStepsBtn = document.getElementById('gtour-toggle-steps-btn');
        const closeStepsBtn = document.getElementById('gtour-close-steps-btn');
        const frame = document.getElementById('gtour-content-frame');

        if (!startBtn || !pauseBtn || !frame) {
            console.error('Recording UI elements not found');
            return;
        }

        // Initialize button state based on recording state
        initButtonState();

        // Start/Resume recording button
        startBtn.addEventListener('click', function() {
            startRecording();
        });

        // Pause recording button
        pauseBtn.addEventListener('click', function() {
            pauseRecording();
        });

        // Discard recording button
        discardBtn.addEventListener('click', function() {
            discardRecording();
        });

        // Save & Exit button
        saveBtn.addEventListener('click', function() {
            saveAndExit();
        });

        // Back to Edit button
        backBtn.addEventListener('click', function() {
            backToEdit();
        });

        // Toggle steps list button
        toggleStepsBtn.addEventListener('click', function() {
            toggleStepsList();
        });

        // Close steps list button
        closeStepsBtn.addEventListener('click', function() {
            hideStepsList();
        });

        // Initialize iframe content listeners after load
        frame.addEventListener('load', function() {
            console.log('Iframe loaded');
            initializeIframeListeners();
        });

        // Also add F1/F2/F3/ESC listener to parent window as fallback
        window.addEventListener('keydown', function(e) {
            // F1 - Capture step or onNext action
            if (e.key === 'F1' || e.keyCode === 112) {
                e.preventDefault();

                // Edit mode: Capture onNext element
                if (editingStepIndex >= 0 && hoveredElement) {
                    captureActionElement(hoveredElement, 'onNext');
                    return;
                }

                // Ignore repeated key events
                if (e.repeat || f1KeyBlocked) {
                    return;
                }

                console.log('F1 in parent - Recording active:', recordingActive, 'Hovered:', hoveredElement);

                if (recordingActive && hoveredElement) {
                    captureElement(hoveredElement);

                    // Block F1 for 1 second
                    f1KeyBlocked = true;
                    setTimeout(function() {
                        f1KeyBlocked = false;
                    }, 1000);
                }
            }

            // F2 - Capture onPrev action
            if (e.key === 'F2' || e.keyCode === 113) {
                e.preventDefault();

                if (editingStepIndex >= 0 && hoveredElement) {
                    captureActionElement(hoveredElement, 'onPrev');
                }
            }

            // F3 - Clear actions
            if (e.key === 'F3' || e.keyCode === 114) {
                e.preventDefault();

                if (editingStepIndex >= 0) {
                    clearActions();
                }
            }

            // ESC - Cancel edit mode
            if (e.key === 'Escape' || e.keyCode === 27) {
                if (editingStepIndex >= 0) {
                    e.preventDefault();
                    cancelEditingStep();
                }
            }
        });
    }

    function initButtonState() {
        // Read state from hidden inputs
        const isActive = document.getElementById('gtour-recording-active').value === '1';
        const hasSteps = document.getElementById('gtour-has-steps').value === '1';

        // Set recording state
        recordingActive = isActive;

        // Load recorded steps from session
        const stepsJson = document.getElementById('gtour-recorded-steps').value;
        if (stepsJson) {
            try {
                recordedSteps = JSON.parse(stepsJson);
                stepCounter = recordedSteps.length;
            } catch (e) {
                console.error('Failed to parse recorded steps:', e);
                recordedSteps = [];
                stepCounter = 0;
            }
        } else {
            recordedSteps = [];
            stepCounter = 0;
        }

        // Get buttons
        const startBtn = document.getElementById('gtour-recording-start-btn');
        const pauseBtn = document.getElementById('gtour-recording-pause-btn');
        const discardBtn = document.getElementById('gtour-recording-discard-btn');
        const saveBtn = document.getElementById('gtour-recording-save-btn');
        const backBtn = document.getElementById('gtour-recording-back-btn');
        const toggleStepsBtn = document.getElementById('gtour-toggle-steps-btn');

        // Hide all buttons initially
        startBtn.style.display = 'none';
        pauseBtn.style.display = 'none';
        discardBtn.style.display = 'none';
        saveBtn.style.display = 'none';
        backBtn.style.display = 'none';
        toggleStepsBtn.style.display = 'none';

        // Update button text and visibility based on state
        const startText = document.getElementById('gtour-record-start-text').value;
        const activeText = document.getElementById('gtour-record-active-text').value;
        const pausedText = document.getElementById('gtour-record-paused-text').value;
        const statusElement = document.getElementById('gtour-recording-status');
        const iconElement = document.getElementById('gtour-recording-icon');

        if (isActive) {
            // Recording active: Show pause and back always, discard and toggle only if steps exist
            pauseBtn.style.display = 'inline-flex';
            backBtn.style.display = 'inline-flex';

            // Only show discard and toggle steps if we have steps
            if (stepCounter > 0) {
                discardBtn.style.display = 'inline-flex';
                toggleStepsBtn.style.display = 'inline-flex';
            }

            statusElement.textContent = activeText;
            iconElement.style.color = '#ff0000';
            iconElement.style.animation = 'pulse 1s infinite';
        } else if (hasSteps) {
            // Paused with steps: Show start (as Resume), save, discard, back, toggle steps
            startBtn.style.display = 'inline-flex';
            startBtn.innerHTML = '<span class="gtour-icon-wrapper"><span class="glyphicon glyphicon-play"></span></span>' +
                                '<span class="gtour-btn-text">' + startText + '</span>';
            saveBtn.style.display = 'inline-flex';
            discardBtn.style.display = 'inline-flex';
            backBtn.style.display = 'inline-flex';
            toggleStepsBtn.style.display = 'inline-flex';
            statusElement.textContent = pausedText;
            iconElement.style.color = '#ff9800';
            iconElement.style.animation = 'none';
        } else {
            // Not started or paused without steps: Show start and back button
            startBtn.style.display = 'inline-flex';
            startBtn.innerHTML = '<span class="gtour-icon-wrapper"><span class="glyphicon glyphicon-record"></span></span>' +
                                '<span class="gtour-btn-text">' + startText + '</span>';
            backBtn.style.display = 'inline-flex';
            statusElement.textContent = document.getElementById('gtour-record-start-text').value;
            iconElement.style.color = '#999';
            iconElement.style.animation = 'none';
        }

        // Update step counter display
        updateStepsCounter();

        // If recording is active, ensure iframe listeners are initialized
        if (isActive) {
            const frame = document.getElementById('gtour-content-frame');
            if (frame.contentDocument) {
                // Set initial page URL
                currentPageUrl = frame.contentWindow.location.href;
                initializeIframeListeners();
            }
        }
    }

    function updateButtonVisibility() {
        // Update button visibility based on current step count
        const discardBtn = document.getElementById('gtour-recording-discard-btn');
        const toggleStepsBtn = document.getElementById('gtour-toggle-steps-btn');

        if (recordingActive) {
            // During active recording, show/hide discard and toggle based on step count
            if (stepCounter > 0) {
                discardBtn.style.display = 'inline-flex';
                toggleStepsBtn.style.display = 'inline-flex';
            } else {
                discardBtn.style.display = 'none';
                toggleStepsBtn.style.display = 'none';
            }
        }
    }

    function startRecording() {
        // Navigate to start recording URL (which will set session and reload page)
        const pauseUrl = document.getElementById('gtour-pause-url').value;
        // Extract base URL and redirect to start command
        const baseUrl = pauseUrl.replace('cmd=pauseRecording', 'cmd=startRecording');
        window.location.href = baseUrl;
    }

    function pauseRecording() {
        // Simply pause recording - stay in frame, keep steps in JS
        recordingActive = false;

        // Update UI
        document.getElementById('gtour-recording-icon').style.color = '#ff9800';
        document.getElementById('gtour-recording-icon').style.animation = 'none';

        const pausedText = document.getElementById('gtour-record-paused-text').value;
        document.getElementById('gtour-recording-status').textContent = pausedText;

        // Update buttons
        updateButtonsForPausedState();

        const stepsInMemoryText = document.getElementById('gtour-record-steps-in-memory-text').value;
        showNotification(stepsInMemoryText, 'info');
    }

    function resumeRecording() {
        // Resume recording
        recordingActive = true;

        // Update UI
        document.getElementById('gtour-recording-icon').style.color = '#ff0000';
        document.getElementById('gtour-recording-icon').style.animation = 'pulse 1s infinite';

        const activeText = document.getElementById('gtour-record-active-text').value;
        document.getElementById('gtour-recording-status').textContent = activeText;

        // Update buttons
        updateButtonsForActiveState();

        const resumedText = document.getElementById('gtour-record-resumed-text').value;
        showNotification(resumedText, 'success');

        // Re-initialize iframe listeners
        initializeIframeListeners();
    }

    function updateButtonsForActiveState() {
        const startBtn = document.getElementById('gtour-recording-start-btn');
        const pauseBtn = document.getElementById('gtour-recording-pause-btn');
        const discardBtn = document.getElementById('gtour-recording-discard-btn');
        const backBtn = document.getElementById('gtour-recording-back-btn');
        const toggleStepsBtn = document.getElementById('gtour-toggle-steps-btn');

        startBtn.style.display = 'none';
        pauseBtn.style.display = 'inline-flex';
        backBtn.style.display = 'inline-flex';

        if (stepCounter > 0) {
            discardBtn.style.display = 'inline-flex';
            toggleStepsBtn.style.display = 'inline-flex';
        }
    }

    function updateButtonsForPausedState() {
        const startBtn = document.getElementById('gtour-recording-start-btn');
        const pauseBtn = document.getElementById('gtour-recording-pause-btn');
        const discardBtn = document.getElementById('gtour-recording-discard-btn');
        const saveBtn = document.getElementById('gtour-recording-save-btn');
        const backBtn = document.getElementById('gtour-recording-back-btn');
        const toggleStepsBtn = document.getElementById('gtour-toggle-steps-btn');

        // Show "Resume" button instead of start
        const resumeText = document.getElementById('gtour-record-resume-text').value;
        startBtn.innerHTML = '<span class="gtour-icon-wrapper"><span class="glyphicon glyphicon-play"></span></span>' +
                            '<span class="gtour-btn-text">' + resumeText + '</span>';
        startBtn.style.display = 'inline-flex';
        startBtn.onclick = resumeRecording;

        pauseBtn.style.display = 'none';
        backBtn.style.display = 'inline-flex';
        discardBtn.style.display = 'inline-flex';
        toggleStepsBtn.style.display = 'inline-flex';
    }

    function saveAndExit() {
        // Not used
    }

    function backToEdit() {
        // Save steps to database and go back to edit page
        if (recordedSteps.length > 0) {
            saveRecordedSteps();
        } else {
            const backUrl = document.getElementById('gtour-back-url').value;
            window.location.href = backUrl;
        }
    }

    function initializeIframeListeners() {
        const frame = document.getElementById('gtour-content-frame');
        if (!frame) {
            console.error('Content frame not found');
            return;
        }

        const iframeDoc = frame.contentDocument || frame.contentWindow.document;
        if (!iframeDoc) {
            console.error('Cannot access iframe document');
            return;
        }

        console.log('Initializing iframe listeners');

        // Store current page URL
        currentPageUrl = frame.contentWindow.location.href;

        // Detect URL changes (navigation to new page)
        // When URL changes, update the previous step with the path to the new page
        const checkUrlChange = function() {
            const newUrl = frame.contentWindow.location.href;
            if (newUrl !== currentPageUrl && recordedSteps.length > 0) {
                // URL changed - set path on the last step to navigate to this new page
                const lastStepIndex = recordedSteps.length - 1;
                const path = newUrl.replace(window.location.origin, '');
                recordedSteps[lastStepIndex].path = path;
                console.log('URL changed - set path on step', lastStepIndex + 1, 'to:', path);
                currentPageUrl = newUrl;

                // Re-render steps list if visible
                if (document.getElementById('gtour-steps-panel').style.display !== 'none') {
                    renderStepsList();
                }
            }
        };

        // Check URL on every load event
        frame.addEventListener('load', checkUrlChange);

        // Prevent default F1/F2/F3/ESC behavior and add our handlers
        iframeDoc.addEventListener('keydown', function(e) {
            // Handle F1 - Capture step or onNext action
            if (e.key === 'F1' || e.keyCode === 112) {
                // Ignore repeated key events (key held down)
                if (e.repeat) {
                    e.preventDefault();
                    e.stopPropagation();
                    return;
                }

                e.preventDefault();
                e.stopPropagation();

                // Edit mode: Capture onNext element
                if (editingStepIndex >= 0 && hoveredElement) {
                    console.log('F1 in edit mode - capturing onNext element:', hoveredElement);
                    captureActionElement(hoveredElement, 'onNext');
                    return;
                }

                // Ignore if F1 is currently blocked (normal mode only)
                if (f1KeyBlocked) {
                    console.log('F1 blocked - too soon after last capture');
                    return;
                }

                console.log('F1 pressed!');

                if (recordingActive && hoveredElement) {
                    console.log('Capturing element:', hoveredElement);
                    captureElement(hoveredElement);

                    // Block F1 for 1 second to prevent accidental duplicates
                    f1KeyBlocked = true;
                    setTimeout(function() {
                        f1KeyBlocked = false;
                        console.log('F1 unblocked');
                    }, 1000);
                } else {
                    console.log('Cannot capture - recordingActive:', recordingActive, 'hoveredElement:', hoveredElement);
                }
            }

            // Handle F2 - Capture onPrev action (edit mode only)
            if (e.key === 'F2' || e.keyCode === 113) {
                e.preventDefault();
                e.stopPropagation();

                if (editingStepIndex >= 0 && hoveredElement) {
                    console.log('F2 in edit mode - capturing onPrev element:', hoveredElement);
                    captureActionElement(hoveredElement, 'onPrev');
                }
            }

            // Handle F3 - Clear actions (edit mode only)
            if (e.key === 'F3' || e.keyCode === 114) {
                e.preventDefault();
                e.stopPropagation();

                if (editingStepIndex >= 0) {
                    console.log('F3 in edit mode - clearing actions');
                    clearActions();
                }
            }

            // Handle ESC - Cancel edit mode
            if (e.key === 'Escape' || e.keyCode === 27) {
                if (editingStepIndex >= 0) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('ESC - cancelling edit mode');
                    cancelEditingStep();
                }
            }
        }, true); // Use capture phase to catch event early

        // Also listen on iframe window for keydown as fallback
        frame.contentWindow.addEventListener('keydown', function(e) {
            // F1 - Capture step or onNext action
            if (e.key === 'F1' || e.keyCode === 112) {
                e.preventDefault();
                e.stopPropagation();

                // Edit mode: Capture onNext element
                if (editingStepIndex >= 0 && hoveredElement) {
                    captureActionElement(hoveredElement, 'onNext');
                    return;
                }

                // Ignore repeated key events
                if (e.repeat || f1KeyBlocked) {
                    return;
                }

                if (recordingActive && hoveredElement) {
                    captureElement(hoveredElement);

                    // Block F1 for 1 second
                    f1KeyBlocked = true;
                    setTimeout(function() {
                        f1KeyBlocked = false;
                    }, 1000);
                }
            }

            // F2 - Capture onPrev action
            if (e.key === 'F2' || e.keyCode === 113) {
                e.preventDefault();
                e.stopPropagation();

                if (editingStepIndex >= 0 && hoveredElement) {
                    captureActionElement(hoveredElement, 'onPrev');
                }
            }

            // F3 - Clear actions
            if (e.key === 'F3' || e.keyCode === 114) {
                e.preventDefault();
                e.stopPropagation();

                if (editingStepIndex >= 0) {
                    clearActions();
                }
            }

            // ESC - Cancel edit mode
            if (e.key === 'Escape' || e.keyCode === 27) {
                if (editingStepIndex >= 0) {
                    e.preventDefault();
                    e.stopPropagation();
                    cancelEditingStep();
                }
            }
        }, true); // Use capture phase

        // Track hovered element
        iframeDoc.addEventListener('mouseover', function(e) {
            if (recordingActive) {
                hoveredElement = e.target;
                highlightElement(hoveredElement);
            }
        });

        iframeDoc.addEventListener('mouseout', function() {
            if (recordingActive) {
                removeHighlight();
            }
        });

        // Capture clicks for "click on next" functionality
        iframeDoc.addEventListener('click', function(e) {
            if (waitingForClick && waitingForClickElement === e.target) {
                e.preventDefault();
                e.stopPropagation();
                registerClickAction();
            }
        }, true); // Use capture phase to prevent navigation

        console.log('Iframe listeners initialized');
    }

    function detectElementType(element) {
        // Try to detect ILIAS UI patterns for stable element recognition
        // Returns: { element: actualElement, type: 'mainbar|metabar|tab|form|table|toolbar|button|css_selector', name: 'element_name' }

        let type = 'css_selector';
        let name = '';
        let actualElement = element;

        // If element is a .caret or other small child, try to find parent button/link
        if (element.classList.contains('caret') || element.tagName === 'SPAN' || element.tagName === 'I') {
            const parentButton = element.closest('button, a');
            if (parentButton) {
                actualElement = parentButton;
                element = parentButton;
            }
        }

        // FIRST: Try to get Internal ID from mapper (most stable!)
        // Check if element or any parent has an Internal ID
        // NOTE: Elements come from the IFRAME, so we need to access the iframe's mapper!
        const frame = document.getElementById('gtour-content-frame');
        const iframeWindow = frame ? frame.contentWindow : null;

        let checkElement = element;
        while (checkElement && checkElement !== document.body) {
            // Check if mapper is available in the IFRAME window and element has internal ID
            if (iframeWindow && iframeWindow.il && iframeWindow.il.Plugins && iframeWindow.il.Plugins.GuidedTour && iframeWindow.il.Plugins.GuidedTour.mapper) {
                let internalId = iframeWindow.il.Plugins.GuidedTour.mapper.getInternalIdFromElement(checkElement);
                
				// Determine type based on context
                const inMainbar = checkElement.closest('.il-maincontrols-mainbar');
                const inMetabar = checkElement.closest('.il-maincontrols-metabar');
                
				if (internalId) {
                    if (inMainbar) {
                        console.log('[Recording] ✓ Using Internal ID for MainBar:', internalId);
                        return {
                            element: actualElement,
                            type: 'mainbar',
                            name: internalId
                        };
                    } else if (inMetabar) {
                        console.log('[Recording] ✓ Using Internal ID for MetaBar:', internalId);
                        return {
                            element: actualElement,
                            type: 'metabar',
                            name: internalId
                        };
                    }
                } else {					
					if (inMetabar) {
						const slatesContainer = element.nextElementSibling;
						if (slatesContainer && slatesContainer.classList.contains('il-metabar-slates')) {
							checkElement = slatesContainer.querySelector('.il-maincontrols-slate');
							if (checkElement) {
								internalId = iframeWindow.il.Plugins.GuidedTour.mapper.getInternalIdFromElement(checkElement);
							}
						}
                        console.log('[Recording] ✓ Using Internal ID for MetaBar:', internalId);
                        return {
                            element: actualElement,
                            type: 'metabar',
                            name: internalId
                        };
					}
				}
            }
            checkElement = checkElement.parentElement;
        }

        // FALLBACK: Use text-based detection
        // Check if element is in mainbar
        const mainbar = element.closest('.il-maincontrols-mainbar');
        if (mainbar) {
            // Check if it's a slate trigger button or a direct mainbar button/link
            const mainbarButton = element.closest('.il-mainbar-entries > li > button, .il-mainbar-entries > li > a');
            if (mainbarButton) {
                // Use text content as fallback identifier
                const textContent = mainbarButton.textContent.trim();
                if (textContent) {
                    type = 'mainbar';
                    name = textContent;
                    console.log('[Recording] Using text fallback for MainBar:', textContent);
                    return { element: actualElement, type, name };
                }
            }
            // Fallback: try aria-label
            const buttonWithLabel = element.closest('button[aria-label], a[aria-label]');
            if (buttonWithLabel && mainbar.contains(buttonWithLabel)) {
                const ariaLabel = buttonWithLabel.getAttribute('aria-label');
                if (ariaLabel) {
                    type = 'mainbar';
                    name = ariaLabel;
                    console.log('[Recording] Using aria-label fallback for MainBar:', ariaLabel);
                    return { element: actualElement, type, name };
                }
            }
        }

        // Check if element is in metabar
        const metabar = element.closest('.il-maincontrols-metabar');
        if (metabar) {
            // Check for metabar button with text or aria-label
            const metabarButton = element.closest('.il-maincontrols-metabar button, .il-maincontrols-metabar a');
            if (metabarButton) {
                // Try text content first
                const textContent = metabarButton.textContent.trim();
                if (textContent) {
                    type = 'metabar';
                    name = textContent;
                    console.log('[Recording] Using text fallback for MetaBar:', textContent);
                    return { element: actualElement, type, name };
                }
                // Try aria-label as fallback
                const ariaLabel = metabarButton.getAttribute('aria-label');
                if (ariaLabel) {
                    type = 'metabar';
                    name = ariaLabel;
                    console.log('[Recording] Using aria-label fallback for MetaBar:', ariaLabel);
                    return { element: actualElement, type, name };
                }
            }
        }

        // Check if element is a tab
        const tabList = element.closest('[role="tablist"]');
        if (tabList) {
            const tab = element.closest('[role="tab"]');
            if (tab) {
                // Use text content as stable identifier
                const textContent = tab.textContent.trim();
                if (textContent) {
                    type = 'tab';
                    name = textContent;
                    return { element: actualElement, type, name };
                }
                // Fallback to ID if no text
                if (tab.id) {
                    type = 'tab';
                    name = tab.id;
                    return { element: actualElement, type, name };
                }
            }
        }

        // Check for form (h2 in content container)
        if (element.tagName === 'H2' || element.closest('h2')) {
            const contentContainer = element.closest('#ilContentContainer');
            if (contentContainer) {
                type = 'form';
                name = '';  // Generic form selector
                return { element: actualElement, type, name };
            }
        }

        // Check for table (thead in content container)
        if (element.tagName === 'THEAD' || element.closest('thead')) {
            const contentContainer = element.closest('#ilContentContainer');
            if (contentContainer) {
                type = 'table';
                name = '';  // Generic table selector
                return { element: actualElement, type, name };
            }
        }

        // Check for toolbar
        const toolbar = element.closest('.c-toolbar');
        if (toolbar) {
            // Check if it's a dropdown button in toolbar
            if (element.tagName === 'BUTTON' && element.classList.contains('dropdown-toggle')) {
                type = 'toolbar_dropdown_button';
                // Use text content as identifier
                const textContent = element.textContent.trim();
                if (textContent) {
                    name = textContent;
                    return { element: actualElement, type, name };
                }
            }
            // Check if it's a dropdown item with ID
            const dropdownItem = element.closest('.dropdown-menu a, .dropdown-menu button');
            if (dropdownItem && dropdownItem.id) {
                type = 'toolbar_dropdown_item';
                name = dropdownItem.id;
                return { element: actualElement, type, name };
            }
            // Check if it's a toolbar button with text
            if (element.tagName === 'BUTTON') {
                const textContent = element.textContent.trim();
                if (textContent) {
                    type = 'toolbar_button';
                    name = textContent;
                    return { element: actualElement, type, name };
                }
            }
            // Generic toolbar selector
            type = 'toolbar';
            name = '';
            return { element: actualElement, type, name };
        }

        // Check for primary button
        if (element.classList.contains('btn-primary') || element.closest('.btn-primary')) {
            const mainspacekeeper = element.closest('#mainspacekeeper');
            if (mainspacekeeper) {
                type = 'button';
                name = '';  // Generic button selector
                return { element: actualElement, type, name };
            }
        }

        // Fallback to CSS selector
        return { element: actualElement, type: 'css_selector', name: '' };
    }

    function captureElement(element) {
        if (!element) return;

        // Prevent capturing the same element multiple times in quick succession
        if (lastCapturedElement === element) {
            console.log('Skipping duplicate capture of same element');
            return;
        }

        // Clear any existing debounce timer
        if (captureDebounceTimer) {
            clearTimeout(captureDebounceTimer);
        }

        // Detect element type for smart recognition
        const detection = detectElementType(element);

        // Use the actual element returned from detection (may be parent if child was hovered)
        const actualElement = detection.element;

        // Determine what goes into 'element' field based on type
        // Option A: element_type + element (element_name not used)
        let elementSelector;

        if (detection.type === 'mainbar' || detection.type === 'metabar') {
            // For mainbar/metabar: Use Internal ID if available, otherwise generate CSS selector
            if (detection.name && detection.name !== '') {
                // Internal ID available - use it directly in element field
                elementSelector = detection.name;
                console.log(`[Recording] Storing Internal ID in element field: "${detection.name}"`);
            } else {
                // No Internal ID - fallback to CSS selector (not sustainable but best effort)
                elementSelector = getCssSelector(actualElement);
                console.log(`[Recording] No Internal ID found, using CSS selector (not sustainable): "${elementSelector}"`);
            }
        } else {
            // For other types: Always use CSS selector
            elementSelector = getCssSelector(actualElement);
        }

        // Create step data
        // Note: path is NOT set here - it will be set automatically when navigating to a new page
        const stepData = {
            element: elementSelector,        // Internal ID (for mainbar/metabar) OR CSS Selector
            title: generateStepTitle(actualElement),
            content: 'Click to interact with ' + getElementDescription(actualElement),
            placement: 'right',
            sort_order: stepCounter + 1,
            element_type: detection.type,    // 'mainbar', 'metabar', 'tab', 'css_selector', etc.
            element_name: null                // Not used anymore with Option A
        };

        // Add to local array
        recordedSteps.push(stepData);
        lastCapturedStepIndex = recordedSteps.length - 1;
        stepCounter++;

        // Note: We don't send to server immediately anymore
        // Steps will be synced when user pauses or saves

        // Update UI
        updateStepsCounter();
        updateButtonVisibility();

        const capturedText = document.getElementById('gtour-step-captured-text').value;
        const typeLabel = detection.type !== 'css_selector' ? ' [' + detection.type + ']' : '';
        showNotification(capturedText + ' (' + stepCounter + '): ' + stepData.title + typeLabel, 'success');

        // Flash the actual element
        flashElement(actualElement);

        // Update steps list if visible
        if (document.getElementById('gtour-steps-panel').style.display !== 'none') {
            renderStepsList();
        }

        // Start waiting for click on the actual element
        startWaitingForClick(actualElement);

        // Set last captured element and clear after 500ms
        lastCapturedElement = actualElement;
        captureDebounceTimer = setTimeout(function() {
            lastCapturedElement = null;
        }, 500);
    }


    function startWaitingForClick(element) {
        waitingForClick = true;
        waitingForClickElement = element;

        const hintText = document.getElementById('gtour-click-hint-text').value;
        showNotification(hintText, 'info');

        // Clear any existing timer
        if (waitingForClickTimer) {
            clearTimeout(waitingForClickTimer);
        }

        // Wait for 2 seconds
        waitingForClickTimer = setTimeout(function() {
            waitingForClick = false;
            waitingForClickElement = null;
        }, 2000);
    }

    function registerClickAction() {
        if (lastCapturedStepIndex >= 0 && lastCapturedStepIndex < recordedSteps.length) {
            // Add click action to the last captured step
            recordedSteps[lastCapturedStepIndex].popover_on_next_click = true;

            const clickText = document.getElementById('gtour-click-registered-text').value;
            showNotification(clickText, 'success');

            // Update steps list if visible
            if (document.getElementById('gtour-steps-panel').style.display !== 'none') {
                renderStepsList();
            }
        }

        // Clear waiting state
        waitingForClick = false;
        waitingForClickElement = null;
        if (waitingForClickTimer) {
            clearTimeout(waitingForClickTimer);
        }
    }


    function getCssSelector(element) {
        // Generate CSS selector for an element (Driver.js compatible)

        // If element has an ID, that's the best selector
        if (element.id) {
            return '#' + CSS.escape(element.id);
        }

        // Try to build a selector using class names and tag name
        const path = [];
        let currentElement = element;

        while (currentElement && currentElement.nodeType === Node.ELEMENT_NODE) {
            let selector = currentElement.tagName.toLowerCase();

            // Add ID if available
            if (currentElement.id) {
                selector = '#' + CSS.escape(currentElement.id);
                path.unshift(selector);
                break; // ID is unique, we can stop here
            }

            // Add classes if available
            if (currentElement.className && typeof currentElement.className === 'string') {
                const classes = currentElement.className.trim().split(/\s+/)
                    .filter(cls => cls.length > 0)
                    .map(cls => '.' + CSS.escape(cls))
                    .join('');
                if (classes) {
                    selector += classes;
                }
            }

            // Add nth-child if needed for uniqueness
            if (currentElement.parentNode) {
                const siblings = Array.from(currentElement.parentNode.children);
                if (siblings.length > 1) {
                    const index = siblings.indexOf(currentElement) + 1;
                    selector += ':nth-child(' + index + ')';
                }
            }

            path.unshift(selector);
            currentElement = currentElement.parentNode;

            // Stop at body or if we have enough specificity
            if (currentElement.tagName === 'BODY' || path.length > 4) {
                break;
            }
        }

        return path.join(' > ');
    }

    function getXPath(element) {
        // Generate XPath for an element (kept for reference, but not used)
        if (element.id) {
            return '//*[@id="' + element.id + '"]';
        }

        const parts = [];
        while (element && element.nodeType === Node.ELEMENT_NODE) {
            let index = 0;
            let sibling = element.previousSibling;

            while (sibling) {
                if (sibling.nodeType === Node.ELEMENT_NODE && sibling.nodeName === element.nodeName) {
                    index++;
                }
                sibling = sibling.previousSibling;
            }

            const tagName = element.nodeName.toLowerCase();
            const pathIndex = index > 0 ? '[' + (index + 1) + ']' : '';
            parts.unshift(tagName + pathIndex);

            element = element.parentNode;
        }

        return '/' + parts.join('/');
    }

    function generateStepTitle(element) {
        // Try to generate a meaningful title from the element
        if (element.textContent && element.textContent.trim().length > 0) {
            const text = element.textContent.trim().substring(0, 50);
            return text + (element.textContent.length > 50 ? '...' : '');
        }

        if (element.getAttribute('aria-label')) {
            return element.getAttribute('aria-label');
        }

        if (element.getAttribute('title')) {
            return element.getAttribute('title');
        }

        return element.tagName + ' element';
    }

    function getElementDescription(element) {
        const tag = element.tagName.toLowerCase();
        const id = element.id ? '#' + element.id : '';
        const classes = element.className ? '.' + element.className.split(' ').join('.') : '';
        return tag + id + classes;
    }

    function highlightElement(element) {
        const highlight = document.getElementById('gtour-element-highlight');
        const frame = document.getElementById('gtour-content-frame');
        const rect = element.getBoundingClientRect();
        const frameRect = frame.getBoundingClientRect();

        highlight.style.display = 'block';
        highlight.style.left = (frameRect.left + rect.left) + 'px';
        highlight.style.top = (frameRect.top + rect.top) + 'px';
        highlight.style.width = rect.width + 'px';
        highlight.style.height = rect.height + 'px';
    }

    function removeHighlight() {
        const highlight = document.getElementById('gtour-element-highlight');
        highlight.style.display = 'none';
    }

    function flashElement(element) {
        const originalBg = element.style.backgroundColor;
        const originalTransition = element.style.transition;

        element.style.transition = 'background-color 0.3s';
        element.style.backgroundColor = '#90EE90';

        setTimeout(function() {
            element.style.backgroundColor = originalBg;
            setTimeout(function() {
                element.style.transition = originalTransition;
            }, 300);
        }, 300);
    }

    function showNotification(message, type) {
        const notification = document.getElementById('gtour-notification');
        const text = document.getElementById('gtour-notification-text');

        text.textContent = message;
        notification.className = 'gtour-notification gtour-notification-' + type;
        notification.style.display = 'block';

        setTimeout(function() {
            notification.style.display = 'none';
        }, 3000);
    }

    function saveRecordedSteps() {
        const saveUrl = document.getElementById('gtour-save-url').value;

        showNotification('Saving ' + recordedSteps.length + ' steps...', 'info');

        fetch(saveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                steps: recordedSteps
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                setTimeout(function() {
                    window.location.href = data.redirect_url;
                }, 1500);
            } else {
                showNotification('Error: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error saving steps:', error);
            showNotification('Error saving steps', 'error');
        });
    }

    function discardRecording() {
        const confirmText = document.getElementById('gtour-confirm-discard-text').value;
        if (confirm(confirmText)) {
            // Simply go back without saving
            const backUrl = document.getElementById('gtour-back-url').value;
            window.location.href = backUrl;
        }
    }

    function toggleStepsList() {
        const panel = document.getElementById('gtour-steps-panel');
        const btn = document.getElementById('gtour-toggle-steps-btn');
        const showText = document.getElementById('gtour-show-steps-text').value;
        const hideText = document.getElementById('gtour-hide-steps-text').value;

        if (panel.style.display === 'none') {
            panel.style.display = 'block';
            btn.innerHTML = '<span class="gtour-icon-wrapper"><span class="glyphicon glyphicon-list"></span></span>' +
                          '<span class="gtour-btn-text"><span id="gtour-toggle-steps-text">' + hideText + '</span> (<span id="gtour-steps-count">' + recordedSteps.length + '</span>)</span>';
            renderStepsList();
        } else {
            panel.style.display = 'none';
            btn.innerHTML = '<span class="gtour-icon-wrapper"><span class="glyphicon glyphicon-list"></span></span>' +
                          '<span class="gtour-btn-text"><span id="gtour-toggle-steps-text">' + showText + '</span> (<span id="gtour-steps-count">' + recordedSteps.length + '</span>)</span>';
        }
    }

    function hideStepsList() {
        const panel = document.getElementById('gtour-steps-panel');
        const btn = document.getElementById('gtour-toggle-steps-btn');
        const showText = document.getElementById('gtour-show-steps-text').value;

        panel.style.display = 'none';
        btn.innerHTML = '<span class="gtour-icon-wrapper"><span class="glyphicon glyphicon-list"></span></span>' +
                      '<span class="gtour-btn-text"><span id="gtour-toggle-steps-text">' + showText + '</span> (<span id="gtour-steps-count">' + recordedSteps.length + '</span>)</span>';
    }

    function renderStepsList() {
        const listContainer = document.getElementById('gtour-steps-list');
        const deleteText = document.getElementById('gtour-delete-step-text').value;

        if (recordedSteps.length === 0) {
            listContainer.innerHTML = '<p class="gtour-no-steps">No steps recorded yet</p>';
            return;
        }

        let html = '<ul class="gtour-steps-ul">';
        recordedSteps.forEach(function(step, index) {
            const hasPath = step.path && step.path.length > 0;
            html += '<li class="gtour-step-item' + (hasPath ? ' has-path' : '') + '" data-index="' + index + '">';
            html += '<div class="gtour-step-content">';
            html += '<strong>' + (index + 1) + '. ' + escapeHtml(step.title);
            if (hasPath) {
                html += ' <span class="gtour-path-badge" title="Navigiert zu neuer Seite: ' + escapeHtml(step.path) + '">&#x1F517;</span>';
            }
            html += '</strong><br>';
            html += '<span class="gtour-step-element">' + escapeHtml(step.element) + '</span>';
            if (step.element_type && step.element_type !== 'css_selector') {
                html += '<br><span class="gtour-step-type">Pattern: ' + escapeHtml(step.element_type) + '</span>';
            }
            if (hasPath) {
                html += '<br><span class="gtour-step-type">Path: ' + escapeHtml(step.path) + '</span>';
            }
            // Show onNext/onPrev actions if defined
            // Check both new system (step.onNext) and old system (step.popover_on_next_click)
            if (step.onNext) {
                html += '<br><span class="gtour-step-action">onNext: ' + escapeHtml(step.onNext) + '</span>';
            } else if (step.popover_on_next_click && step.element) {
                // Legacy system: Show element as onNext target
                html += '<br><span class="gtour-step-action">onNext: ' + escapeHtml(step.element) + '</span>';
            }
            if (step.onPrev) {
                html += '<br><span class="gtour-step-action">onPrev: ' + escapeHtml(step.onPrev) + '</span>';
            }

            // Show editing UI if this step is being edited
            if (editingStepIndex === index) {
                html += '<div class="gtour-edit-container">';

                // Inline editing for title and content
                html += '<div class="gtour-edit-fields">';
                html += '<label class="gtour-edit-label">Titel:</label>';
                html += '<input type="text" class="gtour-edit-title" data-index="' + index + '" value="' + escapeHtml(step.title) + '" placeholder="Step Titel">';
                html += '<label class="gtour-edit-label">Inhalt:</label>';
                html += '<textarea class="gtour-edit-content" data-index="' + index + '" placeholder="Step Beschreibung" rows="3">' + escapeHtml(step.content) + '</textarea>';
                html += '</div>';

                // Instructions
                html += '<div class="gtour-edit-instructions">';
                html += '<strong>📝 Edit Mode:</strong>';
                html += '<span class="gtour-edit-instruction">F1: Element für onNext auswählen</span>';
                html += '<span class="gtour-edit-instruction">F2: Element für onPrev auswählen</span>';
                html += '<span class="gtour-edit-instruction">F3: Actions löschen</span>';
                html += '<span class="gtour-edit-instruction">ESC oder ✎: Beenden</span>';
                html += '</div>';
                html += '</div>';
            }

            html += '</div>';
            html += '<div class="gtour-step-buttons">';
            html += '<button class="gtour-edit-step-btn' + (editingStepIndex === index ? ' gtour-editing-active' : '') + '" data-index="' + index + '" title="Edit actions (onNext/onPrev)">&#x270E;</button>';
            html += '<button class="gtour-delete-step-btn" data-index="' + index + '" title="' + deleteText + '">&times;</button>';
            html += '</div>';
            html += '</li>';
        });
        html += '</ul>';

        listContainer.innerHTML = html;

        // Add edit button listeners
        const editButtons = listContainer.querySelectorAll('.gtour-edit-step-btn');
        editButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                startEditingStep(index);
            });
        });

        // Add delete button listeners
        const deleteButtons = listContainer.querySelectorAll('.gtour-delete-step-btn');
        deleteButtons.forEach(function(btn) {
            btn.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                deleteStep(index);
            });
        });

        // Add title input listeners
        const titleInputs = listContainer.querySelectorAll('.gtour-edit-title');
        titleInputs.forEach(function(input) {
            input.addEventListener('input', function() {
                const index = parseInt(this.getAttribute('data-index'));
                if (index >= 0 && index < recordedSteps.length) {
                    recordedSteps[index].title = this.value;
                }
            });
        });

        // Add content textarea listeners
        const contentInputs = listContainer.querySelectorAll('.gtour-edit-content');
        contentInputs.forEach(function(textarea) {
            textarea.addEventListener('input', function() {
                const index = parseInt(this.getAttribute('data-index'));
                if (index >= 0 && index < recordedSteps.length) {
                    recordedSteps[index].content = this.value;
                }
            });
        });
    }

    function deleteStep(index) {
        if (index >= 0 && index < recordedSteps.length) {
            // If deleting the step that's currently being edited, cancel edit mode
            if (editingStepIndex === index) {
                editingStepIndex = -1;
                editingActionType = null;
            } else if (editingStepIndex > index) {
                // If editing a later step, adjust the index after deletion
                editingStepIndex--;
            }

            recordedSteps.splice(index, 1);
            stepCounter = recordedSteps.length;

            // Steps are only synced to session when pausing or saving
            // No immediate sync needed

            updateStepsCounter();
            updateButtonVisibility();
            renderStepsList();
            showNotification('Step deleted', 'info');
        }
    }

    function startEditingStep(index) {
        // If already editing this step, cancel edit mode (toggle)
        if (editingStepIndex === index) {
            cancelEditingStep();
            return;
        }

        if (index >= 0 && index < recordedSteps.length) {
            editingStepIndex = index;
            const step = recordedSteps[index];

            // Re-render steps list to show editing UI
            renderStepsList();

            console.log('[Recording] Started editing step ' + index, step);
        }
    }

    function cancelEditingStep() {
        if (editingStepIndex >= 0) {
            editingStepIndex = -1;
            editingActionType = null;

            // Re-render to remove editing UI
            renderStepsList();

            showNotification('Edit cancelled', 'info');
            console.log('[Recording] Cancelled editing');
        }
    }

    function captureActionElement(element, actionType) {
        if (editingStepIndex < 0 || editingStepIndex >= recordedSteps.length) {
            console.warn('[Recording] No step is being edited');
            return;
        }

        const step = recordedSteps[editingStepIndex];

        // Detect element type for smart recognition (same as regular capture)
        const detection = detectElementType(element);
        const actualElement = detection.element;

        // Determine what selector to store
        let elementSelector;
        if (detection.type === 'mainbar' || detection.type === 'metabar') {
            // Use Internal ID if available
            if (detection.name && detection.name !== '') {
                elementSelector = detection.name;
                console.log(`[Recording] Storing Internal ID for ${actionType}: "${detection.name}"`);
            } else {
                elementSelector = generateCssSelector(actualElement);
                console.log(`[Recording] No Internal ID, using CSS selector for ${actionType}: "${elementSelector}"`);
            }
        } else {
            elementSelector = generateCssSelector(actualElement);
        }

        // Store the action
        if (actionType === 'onNext') {
            step.onNext = elementSelector;
            showNotification('onNext set to: ' + elementSelector, 'success');
        } else if (actionType === 'onPrev') {
            step.onPrev = elementSelector;
            showNotification('onPrev set to: ' + elementSelector, 'success');
        }

        // Flash the element
        flashElement(actualElement);

        // Update steps list
        renderStepsList();

        console.log(`[Recording] Set ${actionType} for step ${editingStepIndex}:`, elementSelector);
    }

    function clearActions() {
        if (editingStepIndex < 0 || editingStepIndex >= recordedSteps.length) {
            return;
        }

        const step = recordedSteps[editingStepIndex];
        delete step.onNext;
        delete step.onPrev;

        renderStepsList();
        showNotification('Actions cleared for step ' + (editingStepIndex + 1), 'success');
        cancelEditingStep();
    }

    function updateStepsCounter() {
        document.getElementById('gtour-steps-count').textContent = stepCounter;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Add pulse animation for recording indicator
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    `;
    document.head.appendChild(style);

})();
