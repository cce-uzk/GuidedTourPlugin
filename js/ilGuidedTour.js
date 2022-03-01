const ilGuidedTour = {

    /// Check if 'MainBar-Slate of an Item by given title is visible
    isSlateVisibleByTitle: (function (title) {
        const selector = '.il-maincontrols-slate.engaged > .il-maincontrols-slate-content > a, .il-maincontrols-slate.engaged > .il-maincontrols-slate-content > button';
        let element = Array.from(
            document.querySelectorAll(selector)
        ).find(e => e.textContent.includes(`${title}`));

        if (typeof (element) != 'undefined' && element != null) {
            return true;
        } else {
            return false;
        }
    }),

    /// Get MainBar-Element Selector-String by given title
    getMainbarElementByTitle: (function (title) {
        return function () {
            const selector = '.il-mainbar-entries > li > button, .il-mainbar-entries > li > a';
            const element = Array.from(
                document.querySelectorAll(selector)
            ).find(e => e.textContent.includes(`${title}`));

            return $(element);
        };
    }),

    /// Click MainBar-Element by given title
    clickMainbarElementByTitle: (function (title) {
        const selector = '.il-mainbar-entries > li > button, .il-mainbar-entries > li > a';
        let element = Array.from(
            document.querySelectorAll(selector)
        ).find(e => e.textContent.includes(`${title}`));

        if (typeof (element) != 'undefined' && element != null) {
            element.click();
        } else {

        }
    }),

    /// Get MainBar-Slide-Element Selector-String by given title
    getSlateElementByTitle: (function (title) {
        return function () {
            const selector = '.il-maincontrols-slate.engaged > .il-maincontrols-slate-content > a, .il-maincontrols-slate.engaged > .il-maincontrols-slate-content > button';
            let result = null;
            for (const a of document.querySelectorAll(selector)) {
                if (a.textContent.includes(title)) {
                    result = $(a);
                }
                break;
            }
            return result;
        };
    }),

    /// Click MainBar-Slide-Element by given title
    clickSlateElementByTitle: (function (title) {
        const selector = '.il-maincontrols-slate.engaged > .il-maincontrols-slate-content > a, .il-maincontrols-slate.engaged > .il-maincontrols-slate-content > button';
        let element = Array.from(
            document.querySelectorAll(selector)
        ).find(e => e.textContent.includes(`${title}`));

        if (typeof (element) != 'undefined' && element != null) {
            element.click();
        } else {

        }
    }),

    /// Check if 'MainBar-Slate of an Item by given element index => zero based
    isSlateVisibleByIndex: (function (index) {
        const selector = '.il-maincontrols-slate.engaged > .il-maincontrols-slate-content > a, .il-maincontrols-slate.engaged > .il-maincontrols-slate-content > button';
        let element = Array.from(
            document.querySelectorAll(selector)
        );

        if (typeof (element) != 'undefined' && element != null) {
            return true;
        } else {
            return false;
        }
    }),

    /// Get MainBar-Elements
    getMainbarElements: (function () {
        const selector = '.il-mainbar-entries > li > button, .il-mainbar-entries > li > a';
        const elements = Array.from(
            document.querySelectorAll(selector)
        );
        return elements;
    }),

    ///
    getMainbarElementByIndex: (function (index) {
        return function () {
            const elements = ilGuidedTour.getMainbarElements();
            if(elements != null) {
                return elements[index - 1];
            } else {
                return null;
            }
        };
    }),

    /// Click MainBar-Element by given element index => zero based
    clickMainbarElementByIndex: (function (index) {
        const selector = '.il-mainbar-entries > li > button, .il-mainbar-entries > li > a';
        let element = Array.from(
            document.querySelectorAll(selector)
        );
        if(element != null && Array.isArray(element)){
            element = element[index - 1];
        }

        if (typeof (element) != 'undefined' && element != null) {
            element.click();
        } else {

        }
    }),

    /// Get MainBar-Slate-Elements
    getSlateElements: (function () {
        const selector = '.il-maincontrols-slate.engaged > .il-maincontrols-slate-content > a, .il-maincontrols-slate.engaged > .il-maincontrols-slate-content > button';
        const elements = Array.from(
            document.querySelectorAll(selector)
        );
        return elements;
    }),

    /// Check if MainBar-Slate of an Item by given id is visible
    isMainBarElementCollapsed: (function (index) {
        const selector = '.il-mainbar-entries > li > button, .il-mainbar-entries > li > a';

        let element = Array.from(
            document.querySelectorAll(selector)
        );
        if(element != null && Array.isArray(element)){
            element = element[index - 1];
        }

        if (typeof (element) != 'undefined' && element != null) {
            if(element.classList.contains('engaged')) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }),

    /// Get jQuery-SlateElement by Slate Index
    getSlateElementByIndex: (function (index) {
        return function () {
            const elements = ilGuidedTour.getSlateElements();
            if(elements != null){
                return function () {
                    return $(elements[index - 1]);
                };
            } else {
                return function () { return null; };
            }
        };
    }),

    /// Click MainBar-Slate-Element by Index
    clickSlateElementByIndex: (function (index) {
        const selector = '.il-maincontrols-slate.engaged > .il-maincontrols-slate-content > a, .il-maincontrols-slate.engaged > .il-maincontrols-slate-content > button';
        let element = Array.from(
            document.querySelectorAll(selector)
        );
        if(element != null && Array.isArray(element)){
            element = element[index - 1];
        }

        if (typeof (element) != 'undefined' && element != null) {
            element.click();
        } else {

        }
    }),

    /// Get ilTab-Elements
    getTabElements: (function () {
        const selector = '#ilTab > li > a';
        const elements = Array.from(
            document.querySelectorAll(selector)
        );

        return elements;
    }),

    /// Get ilTab-Element by Index
    getTabElementByIndex: (function (index) {
        return function () {
            const elements = ilGuidedTour.getTabElements();
            if(elements != null) {
                return $(elements[index - 1]);
            } else {
                return null;
            }
        };
    }),

    /// Click ilTab-Element by Index
    clickTabElementByIndex: (function (index) {
        const selector = '#ilTab > li > a';
        let element = Array.from(
            document.querySelectorAll(selector)
        );
        if(element != null && Array.isArray(element)){
            element = element[index - 1];
        }

        if (typeof (element) != 'undefined' && element != null) {
            element.click();
        } else {

        }
    }),

    /// Get ilTab-Sub-Elements
    getSubTabElements: (function () {
        const selector = '#ilSubTab > li > a';
        const elements = Array.from(
            document.querySelectorAll(selector)
        );

        return elements;
    }),

    /// Get ilTab-Sub-Element by Index
    getSubTabElementByIndex: (function (index) {
        return function () {
            const elements = ilGuidedTour.getSubTabElements();
            if(elements != null) {
                return $(elements[index - 1]);
            } else {
                return null;
            }
        };
    }),

    /// Click ilTab-Sub-Element by Index
    clickSubTabElementByIndex: (function (index) {
        const selector = '#ilSubTab > li > a';
        let element = Array.from(
            document.querySelectorAll(selector)
        );
        if(element != null && Array.isArray(element)){
            element = element[index - 1];
        }

        if (typeof (element) != 'undefined' && element != null) {
            element.click();
        } else {

        }
    }),

    goTo: (function (url) {
        document.location.href = url;
        return (new jQuery.Deferred()).promise();
    }),

    addStepsToCurrentTour: (function (tour, steps) {
        if (tour != null && steps > 0) {
            tour.setCurrentStep(tour.getCurrentStepIndex() + steps);
        } else if (tour != null && steps < 0) {
            tour.setCurrentStep(tour.getCurrentStepIndex() - steps);
        }
    })
}