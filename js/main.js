let gtour = function () {
  let actions = {
    /** Get MainBar-Elements */
    getMainbarElements: function () {
      const selector = '.il-mainbar-entries > li > button, .il-mainbar-entries > li > a';
      return Array.from(
        document.querySelectorAll(selector)
      );
    },
    /** Get MainBar-Element by title */
    getMainbarElementByTitle: function (title) {
      const elements = actions.getMainbarElements();
      return elements?.find(e => e.textContent.includes(title));
    },
    /** Get MainBar-Element by index */
    getMainbarElementByIndex: function (index) {
      const elements = actions.getMainbarElements();
      if (elements != null) {
        return elements[index - 1];
      } else {
        return null;
      }
    },
    /** Get MainBar Slate-Elements */
    getSlateElements: function () {
      const selector = '.il-maincontrols-slate.engaged a, .il-maincontrols-slate.engaged button';
      return Array.from(document.querySelectorAll(selector));
    },
    /** Get MainBar Slate-Element by title */
    getSlateElementByTitle: function (title) {
      const elements = actions.getSlateElements();
      return elements?.find(a => a.textContent.includes(title));
    },
    /** Get MainBar Slate-Element by index */
    getSlateElementByIndex: function (index) {
      const elements = actions.getSlateElements();
      if (elements != null) {
        return elements[index - 1];
      } else {
        return null;
      }
    },
    /** Click MainBar-Element by title */
    clickMainbarElementByTitle: function (title) {
      const element = actions.getMainbarElementByTitle(title);
      if (element) {
        element.click();
      } else {
        console.error("Element not found for title: " + title);
      }
    },
    /** Click MainBar-Element by index */
    clickMainbarElementByIndex: function (index) {
      const element = actions.getMainbarElementByIndex(index);
      if (element) {
        element.click();
      } else {
        console.error("Element not found at index: " + index);
      }
    },
    /** Click MainBar Slate-Element by title */
    clickSlateElementByTitle: function (title) {
      const element = actions.getSlateElementByTitle(title);
      if (typeof (element) != 'undefined' && element != null) {
        element.click();
      } else {

      }
    },
    /** Click MainBar Slate-Element by index */
    clickSlateElementByIndex: function (index) {
      const element = actions.getSlateElementByIndex(index);
      if (typeof (element) != 'undefined' && element != null) {
        element.click();
      } else {

      }
    },
    /** Check if MainBar Slate is visible by MainBar-Element index */
    isMainBarElementCollapsed: function (index) {
      const element = actions.getMainbarElementByIndex(index);
      if (typeof (element) != 'undefined' && element != null) {
        return element.classList.contains('engaged');
      } else {
        return false;
      }
    },
    /** Check if 'MainBar-Slate of an Item by given title is visible */
    isSlateVisibleByTitle: function (title) {
      const element = actions.getSlateElementByTitle(title);
      return typeof (element) != 'undefined' && element != null;
    },
    /** Check if MainBar Slate-Element is visible by Slate-Element index */
    isSlateVisibleByIndex: function (index) {
      const element = actions.getSlateElementByIndex(index);
      return typeof (element) != 'undefined' && element != null;
    },
    /** Get Tab-Elements */
    getTabElements: function () {
      const selector = '#ilTab > li > a';
      return Array.from(
        document.querySelectorAll(selector)
      );
    },
    /** Get Tab-Element by index */
    getTabElementByIndex: function (index) {
      const elements = actions.getTabElements();
      if (elements != null) {
        return elements[index - 1];
      } else {
        return null;
      }
    },
    /** Click Tab-Element by index */
    clickTabElementByIndex: function (index) {
      const selector = '#ilTab > li > a';
      let element = Array.from(
        document.querySelectorAll(selector)
      );
      if (element != null && Array.isArray(element)) {
        element = element[index - 1];
      }
      if (typeof (element) != 'undefined' && element != null) {
        element.click();
      } else {
      }
    },
    /** Get Tab Sub-Elements */
    getSubTabElements: function () {
      const selector = '#ilSubTab > li > a';
      return Array.from(
        document.querySelectorAll(selector)
      );
    },
    /** Get Tab Sub-Element by index */
    getSubTabElementByIndex: function (index) {
      const elements = actions.getSubTabElements();
      if (elements != null) {
        return elements[index - 1];
      } else {
        return null;
      }
    },
    /** Click Tab Sub-Element by index */
    clickSubTabElementByIndex: function (index) {
      const selector = '#ilSubTab > li > a';
      let element = Array.from(
        document.querySelectorAll(selector)
      );
      if (element != null && Array.isArray(element)) {
        element = element[index - 1];
      }

      if (typeof (element) != 'undefined' && element != null) {
        element.click();
      } else {

      }
    },
    /** Remove url parameter from the current URL without page reload. */
    removeUrlParam: function(parameter) {
      const url = new URL(window.location);
      const params = url.searchParams;

      // Remove the parameter if it exists
      if (params.has(parameter)) {
        params.delete(parameter);
        // Construct the new URL without the parameter
        url.search = params.toString();

        // Push the new URL to the URL bar without reloading the page
        window.history.pushState({ path: url.href }, '', url.href);
      }

      return url.href;
    },
    /** Go to url */
    goTo: function (url) {
      document.location.href = url;
    },
    /** Go x steps forward or backward */
    addStepsToCurrentTour: function (driverObj, steps) {
      if (driverObj != null && steps > 0) {
        const currentIndex = driverObj.getActiveIndex();
        driverObj.drive(currentIndex + steps);
      } else if (driverObj != null && steps < 0) {
        const currentIndex = driverObj.getActiveIndex();
        driverObj.drive(currentIndex - steps);
      }
    },
    /** Hide GuidedTour MainMenu-Element on small screens */
    hideGuidedTourOnSmallScreen: function () {
      if (il?.UI?.page != null && il.UI.page.isSmallScreen()) {
        let gtourMainBarElement = actions.getMainbarElementByTitle('Guided Tour');
        if (gtourMainBarElement != null) {
          gtourMainBarElement.closest('li').style.display = "none";
        }
      }
    },
    /**
     * Resolve element selector using smart pattern recognition
     * Falls back to CSS selector if pattern recognition fails
     */
    resolveSmartSelector: function(step) {
      // If no smart type is set, use regular element selector
      if (!step.elementType || step.elementType === 'css_selector') {
        return step.element;
      }

      let element = null;

      // Resolve based on element type
      switch (step.elementType) {
        case 'mainbar':
          if (step.elementName) {
            // Try to find by text content (stable identifier)
            element = actions.getMainbarElementByTitle(step.elementName);
            if (element) {
              return element;
            }
            // Fallback: try by ID (for backwards compatibility with old recordings)
            element = document.getElementById(step.elementName);
            if (element) {
              // If it's a slate, find the button that controls it
              if (element.classList.contains('il-maincontrols-slate')) {
                const button = document.querySelector(`button[aria-controls="${step.elementName}"]`);
                if (button) return button;
              }
              return element;
            }
          }
          break;

        case 'metabar':
          if (step.elementName) {
            // Try to find by text content or aria-label
            const metabarButtons = document.querySelectorAll('.il-metabar button, .il-metabar a');
            for (const btn of metabarButtons) {
              const text = btn.textContent.trim();
              const ariaLabel = btn.getAttribute('aria-label');
              if (text === step.elementName || ariaLabel === step.elementName) {
                return btn;
              }
            }
            // Fallback: try by ID (for backwards compatibility)
            element = document.getElementById(step.elementName);
            if (element) {
              // If it's a slate, find the button that controls it
              if (element.classList.contains('il-metabar-slate')) {
                const metabar = element.closest('.il-metabar-slates');
                if (metabar) {
                  const button = metabar.parentNode.querySelector('button');
                  if (button) return button;
                }
              }
              return element;
            }
          }
          break;

        case 'tab':
          if (step.elementName) {
            // Try to find by text content
            const tabElements = document.querySelectorAll('[role="tab"]');
            for (const tab of tabElements) {
              if (tab.textContent.trim() === step.elementName) {
                return tab;
              }
            }
            // Fallback: try by ID (for backwards compatibility)
            element = document.getElementById(step.elementName);
            if (element) return element;
          }
          break;

        case 'form':
          // Generic form selector - first h2 in content container
          element = document.querySelector('#ilContentContainer h2');
          if (element) return element;
          break;

        case 'table':
          // Generic table selector - first thead in content container
          element = document.querySelector('#ilContentContainer thead');
          if (element) return element;
          break;

        case 'toolbar_dropdown_button':
          if (step.elementName) {
            // Find toolbar dropdown button by text content
            const toolbarButtons = document.querySelectorAll('.c-toolbar button.dropdown-toggle');
            for (const btn of toolbarButtons) {
              if (btn.textContent.trim() === step.elementName) {
                return btn;
              }
            }
          }
          break;

        case 'toolbar_dropdown_item':
          if (step.elementName) {
            // Find by ID
            element = document.getElementById(step.elementName);
            if (element) return element;
          }
          break;

        case 'toolbar_button':
          if (step.elementName) {
            // Find toolbar button by text content
            const toolbarButtons = document.querySelectorAll('.c-toolbar button');
            for (const btn of toolbarButtons) {
              if (btn.textContent.trim() === step.elementName) {
                return btn;
              }
            }
          }
          break;

        case 'toolbar':
          // Generic toolbar selector - first toolbar item
          element = document.querySelector('#mainspacekeeper .c-toolbar .c-toolbar__item');
          if (element) return element;
          break;

        case 'button':
          // Generic primary button selector
          element = document.querySelector('#mainspacekeeper .btn-primary');
          if (element) return element;
          break;
      }

      // Fallback to CSS selector if smart resolution failed
      console.log('Smart resolution failed for', step.elementType, step.elementName, '- falling back to CSS selector');
      return step.element;
    },

    /**
     * Convert Bootstrap Tourist step format to Driver.js format
     * Handles element selectors, callbacks, and other options
     */
    convertStepToDriverFormat: function(step, index, totalSteps, config) {
      const driverStep = {};

      // Handle element selector with smart resolution
      if (step.element) {
        // Try smart selector resolution first
        const resolvedSelector = actions.resolveSmartSelector(step);

        // Check if element is a function string
        if (typeof resolvedSelector === 'string' && resolvedSelector.startsWith('func:')) {
          const funcBody = resolvedSelector.slice(5);
          try {
            const elementFunc = new Function('return ' + funcBody)();
            driverStep.element = elementFunc;
          } catch (error) {
            console.error("Error parsing element function:", error);
            driverStep.element = 'body';
          }
        } else if (typeof resolvedSelector === 'object' && resolvedSelector !== null) {
          // Direct DOM element returned from smart resolution
          driverStep.element = resolvedSelector;
        } else {
          driverStep.element = resolvedSelector;
        }
      } else if (step.orphan) {
        // Orphan steps have no element
        driverStep.element = null;
      }

      // Build popover content
      driverStep.popover = {
        title: step.title || '',
        description: step.content || '',
        side: step.placement || 'right',
        align: 'start'
      };

      // Add progress text if enabled
      if (config.showProgressText !== false) {
        driverStep.popover.showProgress = true;
      }

      // Don't override showButtons unless specifically needed
      // Let Driver.js use the global config by default

      // Convert onNext callback and handle path redirect
      let onNextCallback = null;

      if (step.onNext) {
        onNextCallback = typeof step.onNext === 'string'
          ? new Function('tour', step.onNext)
          : step.onNext;
      }

      if (step.path || onNextCallback) {
        driverStep.popover.onNextClick = function() {
          // Execute onNext callback first (e.g., click action)
          if (onNextCallback) {
            onNextCallback({ currentStep: index });
          }

          // Then handle path redirect if present
          if (step.path) {
            // Save tour progress
            window.sessionStorage.setItem('GTOUR_current_step', index + 1);
            window.location.href = step.path;
          } else {
            // No redirect, just move to next step
            window.driverObj.moveNext();
          }
        };
      }

      // Convert onPrev callback
      if (step.onPrev) {
        const originalOnPrev = typeof step.onPrev === 'string'
          ? new Function('tour', step.onPrev)
          : step.onPrev;

        driverStep.popover.onPrevClick = function() {
          originalOnPrev({ currentStep: index });
          window.driverObj.movePrevious();
        };
      }

      // Convert onShow callback
      if (step.onShow) {
        const originalOnShow = typeof step.onShow === 'string'
          ? new Function('tour', step.onShow)
          : step.onShow;

        driverStep.onHighlightStarted = function() {
          originalOnShow({ currentStep: index });
        };
      }

      // Convert onShown callback and track progress
      // Always track progress when step is highlighted
      driverStep.onHighlighted = function() {
        console.log('GuidedTour: Step highlighted', index);

        // Save current step to sessionStorage so tour can resume after page reload
        window.sessionStorage.setItem('GTOUR_current_step', index);

        // Execute original onShown callback if present
        if (step.onShown) {
          const originalOnShown = typeof step.onShown === 'string'
            ? new Function('tour', step.onShown)
            : step.onShown;
          originalOnShown({ currentStep: index });
        }

        // Track progress to server if updateProgressUrl is provided
        if (config.updateProgressUrl && config.name) {
          console.log('GuidedTour: Tracking progress for step', index);
          // Extract tour ID from config.name (format: "gtour-123")
          const tourId = config.name.replace('gtour-', '');
          if (tourId) {
            // Replace placeholder with actual tour ID and add step_index parameter
            const updateUrl = config.updateProgressUrl.replace('__TOUR_ID__', tourId) + '&step_index=' + index;
            console.log('GuidedTour: Sending progress update to', updateUrl);

            // Send AJAX request to update progress (fire and forget, no need to wait)
            fetch(updateUrl, {
              method: 'GET',
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            })
            .then(response => {
              console.log('GuidedTour: Progress update response', response.status);
              return response.json();
            })
            .then(data => {
              console.log('GuidedTour: Progress updated successfully', data);
            })
            .catch(error => {
              console.error('GuidedTour: Progress tracking failed', error);
            });
          } else {
            console.warn('GuidedTour: Could not extract tour ID from', config.name);
          }
        } else {
          console.warn('GuidedTour: Progress tracking not available', {
            hasUpdateUrl: !!config.updateProgressUrl,
            hasName: !!config.name,
            config: config
          });
        }
      };

      // Convert onHide callback
      if (step.onHide) {
        const originalOnHide = typeof step.onHide === 'string'
          ? new Function('tour', step.onHide)
          : step.onHide;

        driverStep.onDeselected = function() {
          originalOnHide({ currentStep: index });
        };
      }

      return driverStep;
    },
    /** Initialize Driver.js tour */
    init: function (config) {
      let tourSteps;
      let tourName = config.name;
      let tourStart = config.forceStart === 'true' || config.forceStart === true;
      let tourStorage = window[config.storage] || window.sessionStorage;
      let tourTplVariables = config.tpl;

      // Parse steps from JSON
      if (config.steps && config.steps.trim() !== "") {
        try {
          tourSteps = JSON.parse(config.steps);
        } catch (e) {
          console.error("Error parsing steps:", e);
          tourSteps = [];
        }
      } else {
        console.info("Steps data is empty or not defined.");
        tourSteps = [];
      }

      // Store tour configuration
      if (tourSteps && tourSteps.length > 0) {
        tourStorage.setItem('GTOUR_current_steps', config.steps);
      } else {
        // Try to load stored steps
        let _tourSteps = tourStorage.getItem('GTOUR_current_steps');
        if (_tourSteps != null && _tourSteps.length > 0) {
          tourSteps = JSON.parse(_tourSteps);
        }
      }

      // Store tour name
      if (tourName && tourName.length > 0) {
        tourStorage.setItem('GTOUR_current_name', tourName);
      } else {
        tourName = tourStorage.getItem('GTOUR_current_name');
        // Update config.name if we loaded tourName from storage
        if (tourName) {
          config.name = tourName;
        }
      }

      if (!tourSteps || tourSteps.length === 0) {
        console.info("No tour steps available - Tour aborted");
        // Clear tour data from storage to prevent repeated attempts
        tourStorage.removeItem('GTOUR_current_steps');
        tourStorage.removeItem('GTOUR_current_name');
        return;
      }

      // Convert steps to Driver.js format
      const driverSteps = tourSteps.map((step, index) =>
        actions.convertStepToDriverFormat(step, index, tourSteps.length, config)
      );

      // Check if any valid steps exist after conversion
      if (!driverSteps || driverSteps.length === 0) {
        console.info("No valid tour steps after conversion - Tour aborted");
        tourStorage.removeItem('GTOUR_current_steps');
        tourStorage.removeItem('GTOUR_current_name');
        return;
      }

      // Create Driver.js instance
      if (!window.driverJs) {
        console.error('Driver.js not loaded correctly. Please ensure driver.js.iife.js is loaded before main.js');
        return;
      }

      // Debug: Log configuration
      console.log('GuidedTour: Initializing Driver.js with config:', {
        allowClose: true,
        allowKeyboardControl: true,
        showButtons: ['next', 'previous', 'close'],
        stepsCount: driverSteps.length
      });

      const driverObj = window.driverJs({
        showProgress: config.showProgressText !== false,
        allowClose: true,
        allowKeyboardControl: true,
        overlayClickBehavior: 'close',
        showButtons: ['next', 'previous', 'close'],
        steps: driverSteps,
        onDestroyed: () => {
          console.log('GuidedTour: Tour destroyed - cleaning up');
          // Clean up on tour end
          actions.removeUrlParam('triggerTour');
          tourStorage.removeItem('GTOUR_current_step');
          // Mark tour as ended in session storage
          tourStorage.setItem(tourName + '_end', 'yes');

          // Send terminate notification to server if terminateUrl is provided
          // This marks the tour session as terminated (completed OR aborted)
          // Actual completion is tracked server-side when last step is reached
          if (config.terminateUrl && tourName) {
            // Extract tour ID from tourName (format: "gtour-123")
            const tourId = tourName.replace('gtour-', '');
            if (tourId) {
              // Replace placeholder with actual tour ID
              const terminateUrl = config.terminateUrl.replace('__TOUR_ID__', tourId);

              // Send AJAX request to mark tour session as terminated
              fetch(terminateUrl, {
                method: 'GET',
                headers: {
                  'X-Requested-With': 'XMLHttpRequest'
                }
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  console.log('GuidedTour: Tour session terminated');
                } else {
                  console.warn('GuidedTour: Failed to terminate tour session', data);
                }
              })
              .catch(error => {
                console.error('GuidedTour: Error terminating tour session', error);
              });
            }
          }
        },
        // Custom button labels
        nextBtnText: tourTplVariables?.btn_next || 'Next »',
        prevBtnText: tourTplVariables?.btn_prev || '« Prev',
        doneBtnText: tourTplVariables?.btn_stop || 'Done'
      });

      // Store driver object globally for access in callbacks
      window.driverObj = driverObj;

      // Check if tour should resume from saved step
      const savedStep = tourStorage.getItem('GTOUR_current_step');
      const tourEnded = tourStorage.getItem(tourName + '_end');

      if (tourStart === true) {
        // Force restart tour
        tourStorage.removeItem('GTOUR_current_step');
        tourStorage.removeItem(tourName + '_end');
        actions.removeUrlParam('triggerTour');
        driverObj.drive(0);
      } else if (tourEnded === 'yes') {
        // Tour was already completed
        // However, if we got this tour from server autostart, the server has decided
        // the user should see it (e.g., after stats reset), so we should start it anyway
        if (config.steps && config.steps.trim() !== "") {
          // Server explicitly sent tour steps = server wants tour to start
          console.info('Tour was marked as ended in sessionStorage, but server sent tour for autostart. Clearing flag and starting tour.');
          tourStorage.removeItem(tourName + '_end');
          tourStorage.removeItem('GTOUR_current_step');
          driverObj.drive(0);
        } else {
          // No steps from server = user is just continuing session, respect the flag
          console.info('Tour previously ended. Use ?triggerTour=' + tourName + ' to restart.');
        }
      } else if (savedStep) {
        // Resume from saved step
        driverObj.drive(parseInt(savedStep));
      } else {
        // Start from beginning
        driverObj.drive(0);
      }
    }
  };

  actions.hideGuidedTourOnSmallScreen();

  return {
    init: actions.init,
    getMainbarElements: actions.getMainbarElements,
    getMainbarElementByTitle: actions.getMainbarElementByTitle,
    getMainbarElementByIndex: actions.getMainbarElementByIndex,
    getSlateElements: actions.getSlateElements,
    getSlateElementByTitle: actions.getSlateElementByTitle,
    getSlateElementByIndex: actions.getSlateElementByIndex,
    clickMainbarElementByTitle: actions.clickMainbarElementByTitle,
    clickMainbarElementByIndex: actions.clickMainbarElementByIndex,
    clickSlateElementByTitle: actions.clickSlateElementByTitle,
    clickSlateElementByIndex: actions.clickSlateElementByIndex,
    isMainBarElementCollapsed: actions.isMainBarElementCollapsed,
    isSlateVisibleByTitle: actions.isSlateVisibleByTitle,
    isSlateVisibleByIndex: actions.isSlateVisibleByIndex,
    getTabElements: actions.getTabElements,
    getTabElementByIndex: actions.getTabElementByIndex,
    clickTabElementByIndex: actions.clickTabElementByIndex,
    getSubTabElementByIndex: actions.getSubTabElementByIndex,
    clickSubTabElementByIndex: actions.clickSubTabElementByIndex,
    goTo: actions.goTo,
    addStepsToCurrentTour: actions.addStepsToCurrentTour
  }
}

// Create a convenient alias for Driver.js
// Driver.js IIFE exports as window.driver.js.driver, we create window.driverJs as alias
if (window.driver && window.driver.js && window.driver.js.driver) {
  window.driverJs = window.driver.js.driver;
}

var il = il || {}; // var important!
il.Plugins = il.Plugins || {};
il.Plugins.GuidedTour = il.Plugins.GuidedTour || {};
il.Plugins.GuidedTour = gtour();
