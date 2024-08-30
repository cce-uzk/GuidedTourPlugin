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
    addStepsToCurrentTour: function (tour, steps) {
      if (tour != null && steps > 0) {
        tour.setCurrentStep(tour.getCurrentStepIndex() + steps);
      } else if (tour != null && steps < 0) {
        tour.setCurrentStep(tour.getCurrentStepIndex() - steps);
      }
    },
    /** Hide GuidedTour MainMenu-Element on small screens */
    hideGuidedTourOnSmallScreen: function () {
      if (il?.UI?.page != null && il.UI.page.isSmallScreen()) {
        let gtourMainBarElement = actions.getMainbarElementByTitle('Guided Tour');
        if (gtourMainBarElement != null) {
          gtourMainBarElement().closest('li').css("display", "none");
        }
      }
    },
    /** */
    init: function (config) {
      let tour;
      let tourSteps;
      let tourName = config.name;
      let tourStart = config.forceStart === 'true' || config.forceStart === true;
      let tourStorage = window[config.storage]; // Assuming storage is a string referencing a global storage object
      let tourTplVariables = config.tpl;

      // Check if config.steps is not null and not an empty string
      if (config.steps && config.steps.trim() !== "") {
        try {
          tourSteps = JSON.parse(config.steps);
        } catch (e) {
          console.error("Error parsing steps:", e);
          tourSteps = []; // Set to default empty array if parsing fails
        }
      } else {
        console.info("Steps data is empty or not defined.");
        tourSteps = []; // Set to default empty array if no data
      }

      // Update the template with values from tourTplVariables
      const tourTemplate = `
        <div class='popover tour'>
            <div class='arrow'></div>
            <h4 class='popover-title'></h4>
            <div class='popover-content'></div>
            <div class='popover-navigation' style='padding: 9px 12px'>
                <button class='btn-default btn-sm' data-role='prev'>« ${tourTplVariables.btn_prev}</button><span data-role='separator'>|</span>
                <button class='btn-default btn-sm' data-role='next'>${tourTplVariables.btn_next} »</button><span data-role='separator'>|</span>
                <button class='btn-default btn-sm' data-role='end'>${tourTplVariables.btn_stop}</button>
            </div>
        </div>`;

      // Validate and default to sessionStorage if the specified storage type is not defined
      if (!tourStorage) {
        console.warn('Specified storage type is not defined. Defaulting to sessionStorage.');
        tourStorage = window.sessionStorage;
      }

      // Ensure tour steps storage is correctly handled
      if (tourSteps && tourSteps.length > 0) {
        tourStorage.setItem('GTOUR_current_steps', config.steps);
      } else {
        // Get stored tour steps if available
        let _tourSteps = tourStorage.getItem('GTOUR_current_steps');
        if (_tourSteps != null && _tourSteps.length > 0) {
          tourSteps = JSON.parse(_tourSteps);
        }
      }

      // Ensure tour name storage is correctly handled
      if (tourName && tourName.length > 0) {
        tourStorage.setItem('GTOUR_current_name', tourName);
      } else {
        // Get stored tour name if available
        tourName = tourStorage.getItem('GTOUR_current_name');
      }

      if (tourSteps && tourSteps.length > 0) {
        // Initialize the tour with provided or corrected configurations
        tour = new Tour({
          name: tourName,
          framework: 'bootstrap3',
          storage: tourStorage,
          smartPlacement: true,
          template: tourTemplate,
          onStart: () => actions.removeUrlParam('triggerTour'),
          steps: tourSteps
        });

        // Check tourStart and handle as boolean
        if (tourStart === true) {
          // Force a tour restart or first run
          tour.restart();
        } else {
          // Continue tour
          tour.start();
        }
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
    isSlateVisibleByTitle: actions.isSlateVisibleByIndex,
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

var il = il || {}; // var important!
il.Plugins = il.Plugins || {};
il.Plugins.GuidedTour = il.Plugins.GuidedTour || {};
il.Plugins.GuidedTour = gtour($);