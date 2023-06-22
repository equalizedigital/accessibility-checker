/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ([
/* 0 */,
/* 1 */
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createFocusTrap: () => (/* binding */ createFocusTrap)
/* harmony export */ });
/* harmony import */ var tabbable__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(2);
/*!
* focus-trap 7.4.3
* @license MIT, https://github.com/focus-trap/focus-trap/blob/master/LICENSE
*/


function ownKeys(object, enumerableOnly) {
  var keys = Object.keys(object);
  if (Object.getOwnPropertySymbols) {
    var symbols = Object.getOwnPropertySymbols(object);
    enumerableOnly && (symbols = symbols.filter(function (sym) {
      return Object.getOwnPropertyDescriptor(object, sym).enumerable;
    })), keys.push.apply(keys, symbols);
  }
  return keys;
}
function _objectSpread2(target) {
  for (var i = 1; i < arguments.length; i++) {
    var source = null != arguments[i] ? arguments[i] : {};
    i % 2 ? ownKeys(Object(source), !0).forEach(function (key) {
      _defineProperty(target, key, source[key]);
    }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) {
      Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key));
    });
  }
  return target;
}
function _defineProperty(obj, key, value) {
  key = _toPropertyKey(key);
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value: value,
      enumerable: true,
      configurable: true,
      writable: true
    });
  } else {
    obj[key] = value;
  }
  return obj;
}
function _toPrimitive(input, hint) {
  if (typeof input !== "object" || input === null) return input;
  var prim = input[Symbol.toPrimitive];
  if (prim !== undefined) {
    var res = prim.call(input, hint || "default");
    if (typeof res !== "object") return res;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return (hint === "string" ? String : Number)(input);
}
function _toPropertyKey(arg) {
  var key = _toPrimitive(arg, "string");
  return typeof key === "symbol" ? key : String(key);
}

var activeFocusTraps = {
  activateTrap: function activateTrap(trapStack, trap) {
    if (trapStack.length > 0) {
      var activeTrap = trapStack[trapStack.length - 1];
      if (activeTrap !== trap) {
        activeTrap.pause();
      }
    }
    var trapIndex = trapStack.indexOf(trap);
    if (trapIndex === -1) {
      trapStack.push(trap);
    } else {
      // move this existing trap to the front of the queue
      trapStack.splice(trapIndex, 1);
      trapStack.push(trap);
    }
  },
  deactivateTrap: function deactivateTrap(trapStack, trap) {
    var trapIndex = trapStack.indexOf(trap);
    if (trapIndex !== -1) {
      trapStack.splice(trapIndex, 1);
    }
    if (trapStack.length > 0) {
      trapStack[trapStack.length - 1].unpause();
    }
  }
};
var isSelectableInput = function isSelectableInput(node) {
  return node.tagName && node.tagName.toLowerCase() === 'input' && typeof node.select === 'function';
};
var isEscapeEvent = function isEscapeEvent(e) {
  return e.key === 'Escape' || e.key === 'Esc' || e.keyCode === 27;
};
var isTabEvent = function isTabEvent(e) {
  return e.key === 'Tab' || e.keyCode === 9;
};

// checks for TAB by default
var isKeyForward = function isKeyForward(e) {
  return isTabEvent(e) && !e.shiftKey;
};

// checks for SHIFT+TAB by default
var isKeyBackward = function isKeyBackward(e) {
  return isTabEvent(e) && e.shiftKey;
};
var delay = function delay(fn) {
  return setTimeout(fn, 0);
};

// Array.find/findIndex() are not supported on IE; this replicates enough
//  of Array.findIndex() for our needs
var findIndex = function findIndex(arr, fn) {
  var idx = -1;
  arr.every(function (value, i) {
    if (fn(value)) {
      idx = i;
      return false; // break
    }

    return true; // next
  });

  return idx;
};

/**
 * Get an option's value when it could be a plain value, or a handler that provides
 *  the value.
 * @param {*} value Option's value to check.
 * @param {...*} [params] Any parameters to pass to the handler, if `value` is a function.
 * @returns {*} The `value`, or the handler's returned value.
 */
var valueOrHandler = function valueOrHandler(value) {
  for (var _len = arguments.length, params = new Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
    params[_key - 1] = arguments[_key];
  }
  return typeof value === 'function' ? value.apply(void 0, params) : value;
};
var getActualTarget = function getActualTarget(event) {
  // NOTE: If the trap is _inside_ a shadow DOM, event.target will always be the
  //  shadow host. However, event.target.composedPath() will be an array of
  //  nodes "clicked" from inner-most (the actual element inside the shadow) to
  //  outer-most (the host HTML document). If we have access to composedPath(),
  //  then use its first element; otherwise, fall back to event.target (and
  //  this only works for an _open_ shadow DOM; otherwise,
  //  composedPath()[0] === event.target always).
  return event.target.shadowRoot && typeof event.composedPath === 'function' ? event.composedPath()[0] : event.target;
};

// NOTE: this must be _outside_ `createFocusTrap()` to make sure all traps in this
//  current instance use the same stack if `userOptions.trapStack` isn't specified
var internalTrapStack = [];
var createFocusTrap = function createFocusTrap(elements, userOptions) {
  // SSR: a live trap shouldn't be created in this type of environment so this
  //  should be safe code to execute if the `document` option isn't specified
  var doc = (userOptions === null || userOptions === void 0 ? void 0 : userOptions.document) || document;
  var trapStack = (userOptions === null || userOptions === void 0 ? void 0 : userOptions.trapStack) || internalTrapStack;
  var config = _objectSpread2({
    returnFocusOnDeactivate: true,
    escapeDeactivates: true,
    delayInitialFocus: true,
    isKeyForward: isKeyForward,
    isKeyBackward: isKeyBackward
  }, userOptions);
  var state = {
    // containers given to createFocusTrap()
    // @type {Array<HTMLElement>}
    containers: [],
    // list of objects identifying tabbable nodes in `containers` in the trap
    // NOTE: it's possible that a group has no tabbable nodes if nodes get removed while the trap
    //  is active, but the trap should never get to a state where there isn't at least one group
    //  with at least one tabbable node in it (that would lead to an error condition that would
    //  result in an error being thrown)
    // @type {Array<{
    //   container: HTMLElement,
    //   tabbableNodes: Array<HTMLElement>, // empty if none
    //   focusableNodes: Array<HTMLElement>, // empty if none
    //   firstTabbableNode: HTMLElement|null,
    //   lastTabbableNode: HTMLElement|null,
    //   nextTabbableNode: (node: HTMLElement, forward: boolean) => HTMLElement|undefined
    // }>}
    containerGroups: [],
    // same order/length as `containers` list

    // references to objects in `containerGroups`, but only those that actually have
    //  tabbable nodes in them
    // NOTE: same order as `containers` and `containerGroups`, but __not necessarily__
    //  the same length
    tabbableGroups: [],
    nodeFocusedBeforeActivation: null,
    mostRecentlyFocusedNode: null,
    active: false,
    paused: false,
    // timer ID for when delayInitialFocus is true and initial focus in this trap
    //  has been delayed during activation
    delayInitialFocusTimer: undefined
  };
  var trap; // eslint-disable-line prefer-const -- some private functions reference it, and its methods reference private functions, so we must declare here and define later

  /**
   * Gets a configuration option value.
   * @param {Object|undefined} configOverrideOptions If true, and option is defined in this set,
   *  value will be taken from this object. Otherwise, value will be taken from base configuration.
   * @param {string} optionName Name of the option whose value is sought.
   * @param {string|undefined} [configOptionName] Name of option to use __instead of__ `optionName`
   *  IIF `configOverrideOptions` is not defined. Otherwise, `optionName` is used.
   */
  var getOption = function getOption(configOverrideOptions, optionName, configOptionName) {
    return configOverrideOptions && configOverrideOptions[optionName] !== undefined ? configOverrideOptions[optionName] : config[configOptionName || optionName];
  };

  /**
   * Finds the index of the container that contains the element.
   * @param {HTMLElement} element
   * @param {Event} [event]
   * @returns {number} Index of the container in either `state.containers` or
   *  `state.containerGroups` (the order/length of these lists are the same); -1
   *  if the element isn't found.
   */
  var findContainerIndex = function findContainerIndex(element, event) {
    var composedPath = typeof (event === null || event === void 0 ? void 0 : event.composedPath) === 'function' ? event.composedPath() : undefined;
    // NOTE: search `containerGroups` because it's possible a group contains no tabbable
    //  nodes, but still contains focusable nodes (e.g. if they all have `tabindex=-1`)
    //  and we still need to find the element in there
    return state.containerGroups.findIndex(function (_ref) {
      var container = _ref.container,
        tabbableNodes = _ref.tabbableNodes;
      return container.contains(element) || ( // fall back to explicit tabbable search which will take into consideration any
      //  web components if the `tabbableOptions.getShadowRoot` option was used for
      //  the trap, enabling shadow DOM support in tabbable (`Node.contains()` doesn't
      //  look inside web components even if open)
      composedPath === null || composedPath === void 0 ? void 0 : composedPath.includes(container)) || tabbableNodes.find(function (node) {
        return node === element;
      });
    });
  };

  /**
   * Gets the node for the given option, which is expected to be an option that
   *  can be either a DOM node, a string that is a selector to get a node, `false`
   *  (if a node is explicitly NOT given), or a function that returns any of these
   *  values.
   * @param {string} optionName
   * @returns {undefined | false | HTMLElement | SVGElement} Returns
   *  `undefined` if the option is not specified; `false` if the option
   *  resolved to `false` (node explicitly not given); otherwise, the resolved
   *  DOM node.
   * @throws {Error} If the option is set, not `false`, and is not, or does not
   *  resolve to a node.
   */
  var getNodeForOption = function getNodeForOption(optionName) {
    var optionValue = config[optionName];
    if (typeof optionValue === 'function') {
      for (var _len2 = arguments.length, params = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
        params[_key2 - 1] = arguments[_key2];
      }
      optionValue = optionValue.apply(void 0, params);
    }
    if (optionValue === true) {
      optionValue = undefined; // use default value
    }

    if (!optionValue) {
      if (optionValue === undefined || optionValue === false) {
        return optionValue;
      }
      // else, empty string (invalid), null (invalid), 0 (invalid)

      throw new Error("`".concat(optionName, "` was specified but was not a node, or did not return a node"));
    }
    var node = optionValue; // could be HTMLElement, SVGElement, or non-empty string at this point

    if (typeof optionValue === 'string') {
      node = doc.querySelector(optionValue); // resolve to node, or null if fails
      if (!node) {
        throw new Error("`".concat(optionName, "` as selector refers to no known node"));
      }
    }
    return node;
  };
  var getInitialFocusNode = function getInitialFocusNode() {
    var node = getNodeForOption('initialFocus');

    // false explicitly indicates we want no initialFocus at all
    if (node === false) {
      return false;
    }
    if (node === undefined || !(0,tabbable__WEBPACK_IMPORTED_MODULE_0__.isFocusable)(node, config.tabbableOptions)) {
      // option not specified nor focusable: use fallback options
      if (findContainerIndex(doc.activeElement) >= 0) {
        node = doc.activeElement;
      } else {
        var firstTabbableGroup = state.tabbableGroups[0];
        var firstTabbableNode = firstTabbableGroup && firstTabbableGroup.firstTabbableNode;

        // NOTE: `fallbackFocus` option function cannot return `false` (not supported)
        node = firstTabbableNode || getNodeForOption('fallbackFocus');
      }
    }
    if (!node) {
      throw new Error('Your focus-trap needs to have at least one focusable element');
    }
    return node;
  };
  var updateTabbableNodes = function updateTabbableNodes() {
    state.containerGroups = state.containers.map(function (container) {
      var tabbableNodes = (0,tabbable__WEBPACK_IMPORTED_MODULE_0__.tabbable)(container, config.tabbableOptions);

      // NOTE: if we have tabbable nodes, we must have focusable nodes; focusable nodes
      //  are a superset of tabbable nodes
      var focusableNodes = (0,tabbable__WEBPACK_IMPORTED_MODULE_0__.focusable)(container, config.tabbableOptions);
      return {
        container: container,
        tabbableNodes: tabbableNodes,
        focusableNodes: focusableNodes,
        firstTabbableNode: tabbableNodes.length > 0 ? tabbableNodes[0] : null,
        lastTabbableNode: tabbableNodes.length > 0 ? tabbableNodes[tabbableNodes.length - 1] : null,
        /**
         * Finds the __tabbable__ node that follows the given node in the specified direction,
         *  in this container, if any.
         * @param {HTMLElement} node
         * @param {boolean} [forward] True if going in forward tab order; false if going
         *  in reverse.
         * @returns {HTMLElement|undefined} The next tabbable node, if any.
         */
        nextTabbableNode: function nextTabbableNode(node) {
          var forward = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
          // NOTE: If tabindex is positive (in order to manipulate the tab order separate
          //  from the DOM order), this __will not work__ because the list of focusableNodes,
          //  while it contains tabbable nodes, does not sort its nodes in any order other
          //  than DOM order, because it can't: Where would you place focusable (but not
          //  tabbable) nodes in that order? They have no order, because they aren't tabbale...
          // Support for positive tabindex is already broken and hard to manage (possibly
          //  not supportable, TBD), so this isn't going to make things worse than they
          //  already are, and at least makes things better for the majority of cases where
          //  tabindex is either 0/unset or negative.
          // FYI, positive tabindex issue: https://github.com/focus-trap/focus-trap/issues/375
          var nodeIdx = focusableNodes.findIndex(function (n) {
            return n === node;
          });
          if (nodeIdx < 0) {
            return undefined;
          }
          if (forward) {
            return focusableNodes.slice(nodeIdx + 1).find(function (n) {
              return (0,tabbable__WEBPACK_IMPORTED_MODULE_0__.isTabbable)(n, config.tabbableOptions);
            });
          }
          return focusableNodes.slice(0, nodeIdx).reverse().find(function (n) {
            return (0,tabbable__WEBPACK_IMPORTED_MODULE_0__.isTabbable)(n, config.tabbableOptions);
          });
        }
      };
    });
    state.tabbableGroups = state.containerGroups.filter(function (group) {
      return group.tabbableNodes.length > 0;
    });

    // throw if no groups have tabbable nodes and we don't have a fallback focus node either
    if (state.tabbableGroups.length <= 0 && !getNodeForOption('fallbackFocus') // returning false not supported for this option
    ) {
      throw new Error('Your focus-trap must have at least one container with at least one tabbable node in it at all times');
    }
  };
  var tryFocus = function tryFocus(node) {
    if (node === false) {
      return;
    }
    if (node === doc.activeElement) {
      return;
    }
    if (!node || !node.focus) {
      tryFocus(getInitialFocusNode());
      return;
    }
    node.focus({
      preventScroll: !!config.preventScroll
    });
    state.mostRecentlyFocusedNode = node;
    if (isSelectableInput(node)) {
      node.select();
    }
  };
  var getReturnFocusNode = function getReturnFocusNode(previousActiveElement) {
    var node = getNodeForOption('setReturnFocus', previousActiveElement);
    return node ? node : node === false ? false : previousActiveElement;
  };

  // This needs to be done on mousedown and touchstart instead of click
  // so that it precedes the focus event.
  var checkPointerDown = function checkPointerDown(e) {
    var target = getActualTarget(e);
    if (findContainerIndex(target, e) >= 0) {
      // allow the click since it ocurred inside the trap
      return;
    }
    if (valueOrHandler(config.clickOutsideDeactivates, e)) {
      // immediately deactivate the trap
      trap.deactivate({
        // NOTE: by setting `returnFocus: false`, deactivate() will do nothing,
        //  which will result in the outside click setting focus to the node
        //  that was clicked (and if not focusable, to "nothing"); by setting
        //  `returnFocus: true`, we'll attempt to re-focus the node originally-focused
        //  on activation (or the configured `setReturnFocus` node), whether the
        //  outside click was on a focusable node or not
        returnFocus: config.returnFocusOnDeactivate
      });
      return;
    }

    // This is needed for mobile devices.
    // (If we'll only let `click` events through,
    // then on mobile they will be blocked anyways if `touchstart` is blocked.)
    if (valueOrHandler(config.allowOutsideClick, e)) {
      // allow the click outside the trap to take place
      return;
    }

    // otherwise, prevent the click
    e.preventDefault();
  };

  // In case focus escapes the trap for some strange reason, pull it back in.
  var checkFocusIn = function checkFocusIn(e) {
    var target = getActualTarget(e);
    var targetContained = findContainerIndex(target, e) >= 0;

    // In Firefox when you Tab out of an iframe the Document is briefly focused.
    if (targetContained || target instanceof Document) {
      if (targetContained) {
        state.mostRecentlyFocusedNode = target;
      }
    } else {
      // escaped! pull it back in to where it just left
      e.stopImmediatePropagation();
      tryFocus(state.mostRecentlyFocusedNode || getInitialFocusNode());
    }
  };

  // Hijack key nav events on the first and last focusable nodes of the trap,
  // in order to prevent focus from escaping. If it escapes for even a
  // moment it can end up scrolling the page and causing confusion so we
  // kind of need to capture the action at the keydown phase.
  var checkKeyNav = function checkKeyNav(event) {
    var isBackward = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    var target = getActualTarget(event);
    updateTabbableNodes();
    var destinationNode = null;
    if (state.tabbableGroups.length > 0) {
      // make sure the target is actually contained in a group
      // NOTE: the target may also be the container itself if it's focusable
      //  with tabIndex='-1' and was given initial focus
      var containerIndex = findContainerIndex(target, event);
      var containerGroup = containerIndex >= 0 ? state.containerGroups[containerIndex] : undefined;
      if (containerIndex < 0) {
        // target not found in any group: quite possible focus has escaped the trap,
        //  so bring it back into...
        if (isBackward) {
          // ...the last node in the last group
          destinationNode = state.tabbableGroups[state.tabbableGroups.length - 1].lastTabbableNode;
        } else {
          // ...the first node in the first group
          destinationNode = state.tabbableGroups[0].firstTabbableNode;
        }
      } else if (isBackward) {
        // REVERSE

        // is the target the first tabbable node in a group?
        var startOfGroupIndex = findIndex(state.tabbableGroups, function (_ref2) {
          var firstTabbableNode = _ref2.firstTabbableNode;
          return target === firstTabbableNode;
        });
        if (startOfGroupIndex < 0 && (containerGroup.container === target || (0,tabbable__WEBPACK_IMPORTED_MODULE_0__.isFocusable)(target, config.tabbableOptions) && !(0,tabbable__WEBPACK_IMPORTED_MODULE_0__.isTabbable)(target, config.tabbableOptions) && !containerGroup.nextTabbableNode(target, false))) {
          // an exception case where the target is either the container itself, or
          //  a non-tabbable node that was given focus (i.e. tabindex is negative
          //  and user clicked on it or node was programmatically given focus)
          //  and is not followed by any other tabbable node, in which
          //  case, we should handle shift+tab as if focus were on the container's
          //  first tabbable node, and go to the last tabbable node of the LAST group
          startOfGroupIndex = containerIndex;
        }
        if (startOfGroupIndex >= 0) {
          // YES: then shift+tab should go to the last tabbable node in the
          //  previous group (and wrap around to the last tabbable node of
          //  the LAST group if it's the first tabbable node of the FIRST group)
          var destinationGroupIndex = startOfGroupIndex === 0 ? state.tabbableGroups.length - 1 : startOfGroupIndex - 1;
          var destinationGroup = state.tabbableGroups[destinationGroupIndex];
          destinationNode = destinationGroup.lastTabbableNode;
        } else if (!isTabEvent(event)) {
          // user must have customized the nav keys so we have to move focus manually _within_
          //  the active group: do this based on the order determined by tabbable()
          destinationNode = containerGroup.nextTabbableNode(target, false);
        }
      } else {
        // FORWARD

        // is the target the last tabbable node in a group?
        var lastOfGroupIndex = findIndex(state.tabbableGroups, function (_ref3) {
          var lastTabbableNode = _ref3.lastTabbableNode;
          return target === lastTabbableNode;
        });
        if (lastOfGroupIndex < 0 && (containerGroup.container === target || (0,tabbable__WEBPACK_IMPORTED_MODULE_0__.isFocusable)(target, config.tabbableOptions) && !(0,tabbable__WEBPACK_IMPORTED_MODULE_0__.isTabbable)(target, config.tabbableOptions) && !containerGroup.nextTabbableNode(target))) {
          // an exception case where the target is the container itself, or
          //  a non-tabbable node that was given focus (i.e. tabindex is negative
          //  and user clicked on it or node was programmatically given focus)
          //  and is not followed by any other tabbable node, in which
          //  case, we should handle tab as if focus were on the container's
          //  last tabbable node, and go to the first tabbable node of the FIRST group
          lastOfGroupIndex = containerIndex;
        }
        if (lastOfGroupIndex >= 0) {
          // YES: then tab should go to the first tabbable node in the next
          //  group (and wrap around to the first tabbable node of the FIRST
          //  group if it's the last tabbable node of the LAST group)
          var _destinationGroupIndex = lastOfGroupIndex === state.tabbableGroups.length - 1 ? 0 : lastOfGroupIndex + 1;
          var _destinationGroup = state.tabbableGroups[_destinationGroupIndex];
          destinationNode = _destinationGroup.firstTabbableNode;
        } else if (!isTabEvent(event)) {
          // user must have customized the nav keys so we have to move focus manually _within_
          //  the active group: do this based on the order determined by tabbable()
          destinationNode = containerGroup.nextTabbableNode(target);
        }
      }
    } else {
      // no groups available
      // NOTE: the fallbackFocus option does not support returning false to opt-out
      destinationNode = getNodeForOption('fallbackFocus');
    }
    if (destinationNode) {
      if (isTabEvent(event)) {
        // since tab natively moves focus, we wouldn't have a destination node unless we
        //  were on the edge of a container and had to move to the next/previous edge, in
        //  which case we want to prevent default to keep the browser from moving focus
        //  to where it normally would
        event.preventDefault();
      }
      tryFocus(destinationNode);
    }
    // else, let the browser take care of [shift+]tab and move the focus
  };

  var checkKey = function checkKey(event) {
    if (isEscapeEvent(event) && valueOrHandler(config.escapeDeactivates, event) !== false) {
      event.preventDefault();
      trap.deactivate();
      return;
    }
    if (config.isKeyForward(event) || config.isKeyBackward(event)) {
      checkKeyNav(event, config.isKeyBackward(event));
    }
  };
  var checkClick = function checkClick(e) {
    var target = getActualTarget(e);
    if (findContainerIndex(target, e) >= 0) {
      return;
    }
    if (valueOrHandler(config.clickOutsideDeactivates, e)) {
      return;
    }
    if (valueOrHandler(config.allowOutsideClick, e)) {
      return;
    }
    e.preventDefault();
    e.stopImmediatePropagation();
  };

  //
  // EVENT LISTENERS
  //

  var addListeners = function addListeners() {
    if (!state.active) {
      return;
    }

    // There can be only one listening focus trap at a time
    activeFocusTraps.activateTrap(trapStack, trap);

    // Delay ensures that the focused element doesn't capture the event
    // that caused the focus trap activation.
    state.delayInitialFocusTimer = config.delayInitialFocus ? delay(function () {
      tryFocus(getInitialFocusNode());
    }) : tryFocus(getInitialFocusNode());
    doc.addEventListener('focusin', checkFocusIn, true);
    doc.addEventListener('mousedown', checkPointerDown, {
      capture: true,
      passive: false
    });
    doc.addEventListener('touchstart', checkPointerDown, {
      capture: true,
      passive: false
    });
    doc.addEventListener('click', checkClick, {
      capture: true,
      passive: false
    });
    doc.addEventListener('keydown', checkKey, {
      capture: true,
      passive: false
    });
    return trap;
  };
  var removeListeners = function removeListeners() {
    if (!state.active) {
      return;
    }
    doc.removeEventListener('focusin', checkFocusIn, true);
    doc.removeEventListener('mousedown', checkPointerDown, true);
    doc.removeEventListener('touchstart', checkPointerDown, true);
    doc.removeEventListener('click', checkClick, true);
    doc.removeEventListener('keydown', checkKey, true);
    return trap;
  };

  //
  // MUTATION OBSERVER
  //

  var checkDomRemoval = function checkDomRemoval(mutations) {
    var isFocusedNodeRemoved = mutations.some(function (mutation) {
      var removedNodes = Array.from(mutation.removedNodes);
      return removedNodes.some(function (node) {
        return node === state.mostRecentlyFocusedNode;
      });
    });

    // If the currently focused is removed then browsers will move focus to the
    // <body> element. If this happens, try to move focus back into the trap.
    if (isFocusedNodeRemoved) {
      tryFocus(getInitialFocusNode());
    }
  };

  // Use MutationObserver - if supported - to detect if focused node is removed
  // from the DOM.
  var mutationObserver = typeof window !== 'undefined' && 'MutationObserver' in window ? new MutationObserver(checkDomRemoval) : undefined;
  var updateObservedNodes = function updateObservedNodes() {
    if (!mutationObserver) {
      return;
    }
    mutationObserver.disconnect();
    if (state.active && !state.paused) {
      state.containers.map(function (container) {
        mutationObserver.observe(container, {
          subtree: true,
          childList: true
        });
      });
    }
  };

  //
  // TRAP DEFINITION
  //

  trap = {
    get active() {
      return state.active;
    },
    get paused() {
      return state.paused;
    },
    activate: function activate(activateOptions) {
      if (state.active) {
        return this;
      }
      var onActivate = getOption(activateOptions, 'onActivate');
      var onPostActivate = getOption(activateOptions, 'onPostActivate');
      var checkCanFocusTrap = getOption(activateOptions, 'checkCanFocusTrap');
      if (!checkCanFocusTrap) {
        updateTabbableNodes();
      }
      state.active = true;
      state.paused = false;
      state.nodeFocusedBeforeActivation = doc.activeElement;
      onActivate === null || onActivate === void 0 ? void 0 : onActivate();
      var finishActivation = function finishActivation() {
        if (checkCanFocusTrap) {
          updateTabbableNodes();
        }
        addListeners();
        updateObservedNodes();
        onPostActivate === null || onPostActivate === void 0 ? void 0 : onPostActivate();
      };
      if (checkCanFocusTrap) {
        checkCanFocusTrap(state.containers.concat()).then(finishActivation, finishActivation);
        return this;
      }
      finishActivation();
      return this;
    },
    deactivate: function deactivate(deactivateOptions) {
      if (!state.active) {
        return this;
      }
      var options = _objectSpread2({
        onDeactivate: config.onDeactivate,
        onPostDeactivate: config.onPostDeactivate,
        checkCanReturnFocus: config.checkCanReturnFocus
      }, deactivateOptions);
      clearTimeout(state.delayInitialFocusTimer); // noop if undefined
      state.delayInitialFocusTimer = undefined;
      removeListeners();
      state.active = false;
      state.paused = false;
      updateObservedNodes();
      activeFocusTraps.deactivateTrap(trapStack, trap);
      var onDeactivate = getOption(options, 'onDeactivate');
      var onPostDeactivate = getOption(options, 'onPostDeactivate');
      var checkCanReturnFocus = getOption(options, 'checkCanReturnFocus');
      var returnFocus = getOption(options, 'returnFocus', 'returnFocusOnDeactivate');
      onDeactivate === null || onDeactivate === void 0 ? void 0 : onDeactivate();
      var finishDeactivation = function finishDeactivation() {
        delay(function () {
          if (returnFocus) {
            tryFocus(getReturnFocusNode(state.nodeFocusedBeforeActivation));
          }
          onPostDeactivate === null || onPostDeactivate === void 0 ? void 0 : onPostDeactivate();
        });
      };
      if (returnFocus && checkCanReturnFocus) {
        checkCanReturnFocus(getReturnFocusNode(state.nodeFocusedBeforeActivation)).then(finishDeactivation, finishDeactivation);
        return this;
      }
      finishDeactivation();
      return this;
    },
    pause: function pause(pauseOptions) {
      if (state.paused || !state.active) {
        return this;
      }
      var onPause = getOption(pauseOptions, 'onPause');
      var onPostPause = getOption(pauseOptions, 'onPostPause');
      state.paused = true;
      onPause === null || onPause === void 0 ? void 0 : onPause();
      removeListeners();
      updateObservedNodes();
      onPostPause === null || onPostPause === void 0 ? void 0 : onPostPause();
      return this;
    },
    unpause: function unpause(unpauseOptions) {
      if (!state.paused || !state.active) {
        return this;
      }
      var onUnpause = getOption(unpauseOptions, 'onUnpause');
      var onPostUnpause = getOption(unpauseOptions, 'onPostUnpause');
      state.paused = false;
      onUnpause === null || onUnpause === void 0 ? void 0 : onUnpause();
      updateTabbableNodes();
      addListeners();
      updateObservedNodes();
      onPostUnpause === null || onPostUnpause === void 0 ? void 0 : onPostUnpause();
      return this;
    },
    updateContainerElements: function updateContainerElements(containerElements) {
      var elementsAsArray = [].concat(containerElements).filter(Boolean);
      state.containers = elementsAsArray.map(function (element) {
        return typeof element === 'string' ? doc.querySelector(element) : element;
      });
      if (state.active) {
        updateTabbableNodes();
      }
      updateObservedNodes();
      return this;
    }
  };

  // initialize container elements
  trap.updateContainerElements(elements);
  return trap;
};


//# sourceMappingURL=focus-trap.esm.js.map


/***/ }),
/* 2 */
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   focusable: () => (/* binding */ focusable),
/* harmony export */   isFocusable: () => (/* binding */ isFocusable),
/* harmony export */   isTabbable: () => (/* binding */ isTabbable),
/* harmony export */   tabbable: () => (/* binding */ tabbable)
/* harmony export */ });
/*!
* tabbable 6.1.2
* @license MIT, https://github.com/focus-trap/tabbable/blob/master/LICENSE
*/
// NOTE: separate `:not()` selectors has broader browser support than the newer
//  `:not([inert], [inert] *)` (Feb 2023)
// CAREFUL: JSDom does not support `:not([inert] *)` as a selector; using it causes
//  the entire query to fail, resulting in no nodes found, which will break a lot
//  of things... so we have to rely on JS to identify nodes inside an inert container
var candidateSelectors = ['input:not([inert])', 'select:not([inert])', 'textarea:not([inert])', 'a[href]:not([inert])', 'button:not([inert])', '[tabindex]:not(slot):not([inert])', 'audio[controls]:not([inert])', 'video[controls]:not([inert])', '[contenteditable]:not([contenteditable="false"]):not([inert])', 'details>summary:first-of-type:not([inert])', 'details:not([inert])'];
var candidateSelector = /* #__PURE__ */candidateSelectors.join(',');
var NoElement = typeof Element === 'undefined';
var matches = NoElement ? function () {} : Element.prototype.matches || Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
var getRootNode = !NoElement && Element.prototype.getRootNode ? function (element) {
  var _element$getRootNode;
  return element === null || element === void 0 ? void 0 : (_element$getRootNode = element.getRootNode) === null || _element$getRootNode === void 0 ? void 0 : _element$getRootNode.call(element);
} : function (element) {
  return element === null || element === void 0 ? void 0 : element.ownerDocument;
};

/**
 * Determines if a node is inert or in an inert ancestor.
 * @param {Element} [node]
 * @param {boolean} [lookUp] If true and `node` is not inert, looks up at ancestors to
 *  see if any of them are inert. If false, only `node` itself is considered.
 * @returns {boolean} True if inert itself or by way of being in an inert ancestor.
 *  False if `node` is falsy.
 */
var isInert = function isInert(node, lookUp) {
  var _node$getAttribute;
  if (lookUp === void 0) {
    lookUp = true;
  }
  // CAREFUL: JSDom does not support inert at all, so we can't use the `HTMLElement.inert`
  //  JS API property; we have to check the attribute, which can either be empty or 'true';
  //  if it's `null` (not specified) or 'false', it's an active element
  var inertAtt = node === null || node === void 0 ? void 0 : (_node$getAttribute = node.getAttribute) === null || _node$getAttribute === void 0 ? void 0 : _node$getAttribute.call(node, 'inert');
  var inert = inertAtt === '' || inertAtt === 'true';

  // NOTE: this could also be handled with `node.matches('[inert], :is([inert] *)')`
  //  if it weren't for `matches()` not being a function on shadow roots; the following
  //  code works for any kind of node
  // CAREFUL: JSDom does not appear to support certain selectors like `:not([inert] *)`
  //  so it likely would not support `:is([inert] *)` either...
  var result = inert || lookUp && node && isInert(node.parentNode); // recursive

  return result;
};

/**
 * Determines if a node's content is editable.
 * @param {Element} [node]
 * @returns True if it's content-editable; false if it's not or `node` is falsy.
 */
var isContentEditable = function isContentEditable(node) {
  var _node$getAttribute2;
  // CAREFUL: JSDom does not support the `HTMLElement.isContentEditable` API so we have
  //  to use the attribute directly to check for this, which can either be empty or 'true';
  //  if it's `null` (not specified) or 'false', it's a non-editable element
  var attValue = node === null || node === void 0 ? void 0 : (_node$getAttribute2 = node.getAttribute) === null || _node$getAttribute2 === void 0 ? void 0 : _node$getAttribute2.call(node, 'contenteditable');
  return attValue === '' || attValue === 'true';
};

/**
 * @param {Element} el container to check in
 * @param {boolean} includeContainer add container to check
 * @param {(node: Element) => boolean} filter filter candidates
 * @returns {Element[]}
 */
var getCandidates = function getCandidates(el, includeContainer, filter) {
  // even if `includeContainer=false`, we still have to check it for inertness because
  //  if it's inert, all its children are inert
  if (isInert(el)) {
    return [];
  }
  var candidates = Array.prototype.slice.apply(el.querySelectorAll(candidateSelector));
  if (includeContainer && matches.call(el, candidateSelector)) {
    candidates.unshift(el);
  }
  candidates = candidates.filter(filter);
  return candidates;
};

/**
 * @callback GetShadowRoot
 * @param {Element} element to check for shadow root
 * @returns {ShadowRoot|boolean} ShadowRoot if available or boolean indicating if a shadowRoot is attached but not available.
 */

/**
 * @callback ShadowRootFilter
 * @param {Element} shadowHostNode the element which contains shadow content
 * @returns {boolean} true if a shadow root could potentially contain valid candidates.
 */

/**
 * @typedef {Object} CandidateScope
 * @property {Element} scopeParent contains inner candidates
 * @property {Element[]} candidates list of candidates found in the scope parent
 */

/**
 * @typedef {Object} IterativeOptions
 * @property {GetShadowRoot|boolean} getShadowRoot true if shadow support is enabled; falsy if not;
 *  if a function, implies shadow support is enabled and either returns the shadow root of an element
 *  or a boolean stating if it has an undisclosed shadow root
 * @property {(node: Element) => boolean} filter filter candidates
 * @property {boolean} flatten if true then result will flatten any CandidateScope into the returned list
 * @property {ShadowRootFilter} shadowRootFilter filter shadow roots;
 */

/**
 * @param {Element[]} elements list of element containers to match candidates from
 * @param {boolean} includeContainer add container list to check
 * @param {IterativeOptions} options
 * @returns {Array.<Element|CandidateScope>}
 */
var getCandidatesIteratively = function getCandidatesIteratively(elements, includeContainer, options) {
  var candidates = [];
  var elementsToCheck = Array.from(elements);
  while (elementsToCheck.length) {
    var element = elementsToCheck.shift();
    if (isInert(element, false)) {
      // no need to look up since we're drilling down
      // anything inside this container will also be inert
      continue;
    }
    if (element.tagName === 'SLOT') {
      // add shadow dom slot scope (slot itself cannot be focusable)
      var assigned = element.assignedElements();
      var content = assigned.length ? assigned : element.children;
      var nestedCandidates = getCandidatesIteratively(content, true, options);
      if (options.flatten) {
        candidates.push.apply(candidates, nestedCandidates);
      } else {
        candidates.push({
          scopeParent: element,
          candidates: nestedCandidates
        });
      }
    } else {
      // check candidate element
      var validCandidate = matches.call(element, candidateSelector);
      if (validCandidate && options.filter(element) && (includeContainer || !elements.includes(element))) {
        candidates.push(element);
      }

      // iterate over shadow content if possible
      var shadowRoot = element.shadowRoot ||
      // check for an undisclosed shadow
      typeof options.getShadowRoot === 'function' && options.getShadowRoot(element);

      // no inert look up because we're already drilling down and checking for inertness
      //  on the way down, so all containers to this root node should have already been
      //  vetted as non-inert
      var validShadowRoot = !isInert(shadowRoot, false) && (!options.shadowRootFilter || options.shadowRootFilter(element));
      if (shadowRoot && validShadowRoot) {
        // add shadow dom scope IIF a shadow root node was given; otherwise, an undisclosed
        //  shadow exists, so look at light dom children as fallback BUT create a scope for any
        //  child candidates found because they're likely slotted elements (elements that are
        //  children of the web component element (which has the shadow), in the light dom, but
        //  slotted somewhere _inside_ the undisclosed shadow) -- the scope is created below,
        //  _after_ we return from this recursive call
        var _nestedCandidates = getCandidatesIteratively(shadowRoot === true ? element.children : shadowRoot.children, true, options);
        if (options.flatten) {
          candidates.push.apply(candidates, _nestedCandidates);
        } else {
          candidates.push({
            scopeParent: element,
            candidates: _nestedCandidates
          });
        }
      } else {
        // there's not shadow so just dig into the element's (light dom) children
        //  __without__ giving the element special scope treatment
        elementsToCheck.unshift.apply(elementsToCheck, element.children);
      }
    }
  }
  return candidates;
};
var getTabindex = function getTabindex(node, isScope) {
  if (node.tabIndex < 0) {
    // in Chrome, <details/>, <audio controls/> and <video controls/> elements get a default
    // `tabIndex` of -1 when the 'tabindex' attribute isn't specified in the DOM,
    // yet they are still part of the regular tab order; in FF, they get a default
    // `tabIndex` of 0; since Chrome still puts those elements in the regular tab
    // order, consider their tab index to be 0.
    // Also browsers do not return `tabIndex` correctly for contentEditable nodes;
    // so if they don't have a tabindex attribute specifically set, assume it's 0.
    //
    // isScope is positive for custom element with shadow root or slot that by default
    // have tabIndex -1, but need to be sorted by document order in order for their
    // content to be inserted in the correct position
    if ((isScope || /^(AUDIO|VIDEO|DETAILS)$/.test(node.tagName) || isContentEditable(node)) && isNaN(parseInt(node.getAttribute('tabindex'), 10))) {
      return 0;
    }
  }
  return node.tabIndex;
};
var sortOrderedTabbables = function sortOrderedTabbables(a, b) {
  return a.tabIndex === b.tabIndex ? a.documentOrder - b.documentOrder : a.tabIndex - b.tabIndex;
};
var isInput = function isInput(node) {
  return node.tagName === 'INPUT';
};
var isHiddenInput = function isHiddenInput(node) {
  return isInput(node) && node.type === 'hidden';
};
var isDetailsWithSummary = function isDetailsWithSummary(node) {
  var r = node.tagName === 'DETAILS' && Array.prototype.slice.apply(node.children).some(function (child) {
    return child.tagName === 'SUMMARY';
  });
  return r;
};
var getCheckedRadio = function getCheckedRadio(nodes, form) {
  for (var i = 0; i < nodes.length; i++) {
    if (nodes[i].checked && nodes[i].form === form) {
      return nodes[i];
    }
  }
};
var isTabbableRadio = function isTabbableRadio(node) {
  if (!node.name) {
    return true;
  }
  var radioScope = node.form || getRootNode(node);
  var queryRadios = function queryRadios(name) {
    return radioScope.querySelectorAll('input[type="radio"][name="' + name + '"]');
  };
  var radioSet;
  if (typeof window !== 'undefined' && typeof window.CSS !== 'undefined' && typeof window.CSS.escape === 'function') {
    radioSet = queryRadios(window.CSS.escape(node.name));
  } else {
    try {
      radioSet = queryRadios(node.name);
    } catch (err) {
      // eslint-disable-next-line no-console
      console.error('Looks like you have a radio button with a name attribute containing invalid CSS selector characters and need the CSS.escape polyfill: %s', err.message);
      return false;
    }
  }
  var checked = getCheckedRadio(radioSet, node.form);
  return !checked || checked === node;
};
var isRadio = function isRadio(node) {
  return isInput(node) && node.type === 'radio';
};
var isNonTabbableRadio = function isNonTabbableRadio(node) {
  return isRadio(node) && !isTabbableRadio(node);
};

// determines if a node is ultimately attached to the window's document
var isNodeAttached = function isNodeAttached(node) {
  var _nodeRoot;
  // The root node is the shadow root if the node is in a shadow DOM; some document otherwise
  //  (but NOT _the_ document; see second 'If' comment below for more).
  // If rootNode is shadow root, it'll have a host, which is the element to which the shadow
  //  is attached, and the one we need to check if it's in the document or not (because the
  //  shadow, and all nodes it contains, is never considered in the document since shadows
  //  behave like self-contained DOMs; but if the shadow's HOST, which is part of the document,
  //  is hidden, or is not in the document itself but is detached, it will affect the shadow's
  //  visibility, including all the nodes it contains). The host could be any normal node,
  //  or a custom element (i.e. web component). Either way, that's the one that is considered
  //  part of the document, not the shadow root, nor any of its children (i.e. the node being
  //  tested).
  // To further complicate things, we have to look all the way up until we find a shadow HOST
  //  that is attached (or find none) because the node might be in nested shadows...
  // If rootNode is not a shadow root, it won't have a host, and so rootNode should be the
  //  document (per the docs) and while it's a Document-type object, that document does not
  //  appear to be the same as the node's `ownerDocument` for some reason, so it's safer
  //  to ignore the rootNode at this point, and use `node.ownerDocument`. Otherwise,
  //  using `rootNode.contains(node)` will _always_ be true we'll get false-positives when
  //  node is actually detached.
  // NOTE: If `nodeRootHost` or `node` happens to be the `document` itself (which is possible
  //  if a tabbable/focusable node was quickly added to the DOM, focused, and then removed
  //  from the DOM as in https://github.com/focus-trap/focus-trap-react/issues/905), then
  //  `ownerDocument` will be `null`, hence the optional chaining on it.
  var nodeRoot = node && getRootNode(node);
  var nodeRootHost = (_nodeRoot = nodeRoot) === null || _nodeRoot === void 0 ? void 0 : _nodeRoot.host;

  // in some cases, a detached node will return itself as the root instead of a document or
  //  shadow root object, in which case, we shouldn't try to look further up the host chain
  var attached = false;
  if (nodeRoot && nodeRoot !== node) {
    var _nodeRootHost, _nodeRootHost$ownerDo, _node$ownerDocument;
    attached = !!((_nodeRootHost = nodeRootHost) !== null && _nodeRootHost !== void 0 && (_nodeRootHost$ownerDo = _nodeRootHost.ownerDocument) !== null && _nodeRootHost$ownerDo !== void 0 && _nodeRootHost$ownerDo.contains(nodeRootHost) || node !== null && node !== void 0 && (_node$ownerDocument = node.ownerDocument) !== null && _node$ownerDocument !== void 0 && _node$ownerDocument.contains(node));
    while (!attached && nodeRootHost) {
      var _nodeRoot2, _nodeRootHost2, _nodeRootHost2$ownerD;
      // since it's not attached and we have a root host, the node MUST be in a nested shadow DOM,
      //  which means we need to get the host's host and check if that parent host is contained
      //  in (i.e. attached to) the document
      nodeRoot = getRootNode(nodeRootHost);
      nodeRootHost = (_nodeRoot2 = nodeRoot) === null || _nodeRoot2 === void 0 ? void 0 : _nodeRoot2.host;
      attached = !!((_nodeRootHost2 = nodeRootHost) !== null && _nodeRootHost2 !== void 0 && (_nodeRootHost2$ownerD = _nodeRootHost2.ownerDocument) !== null && _nodeRootHost2$ownerD !== void 0 && _nodeRootHost2$ownerD.contains(nodeRootHost));
    }
  }
  return attached;
};
var isZeroArea = function isZeroArea(node) {
  var _node$getBoundingClie = node.getBoundingClientRect(),
    width = _node$getBoundingClie.width,
    height = _node$getBoundingClie.height;
  return width === 0 && height === 0;
};
var isHidden = function isHidden(node, _ref) {
  var displayCheck = _ref.displayCheck,
    getShadowRoot = _ref.getShadowRoot;
  // NOTE: visibility will be `undefined` if node is detached from the document
  //  (see notes about this further down), which means we will consider it visible
  //  (this is legacy behavior from a very long way back)
  // NOTE: we check this regardless of `displayCheck="none"` because this is a
  //  _visibility_ check, not a _display_ check
  if (getComputedStyle(node).visibility === 'hidden') {
    return true;
  }
  var isDirectSummary = matches.call(node, 'details>summary:first-of-type');
  var nodeUnderDetails = isDirectSummary ? node.parentElement : node;
  if (matches.call(nodeUnderDetails, 'details:not([open]) *')) {
    return true;
  }
  if (!displayCheck || displayCheck === 'full' || displayCheck === 'legacy-full') {
    if (typeof getShadowRoot === 'function') {
      // figure out if we should consider the node to be in an undisclosed shadow and use the
      //  'non-zero-area' fallback
      var originalNode = node;
      while (node) {
        var parentElement = node.parentElement;
        var rootNode = getRootNode(node);
        if (parentElement && !parentElement.shadowRoot && getShadowRoot(parentElement) === true // check if there's an undisclosed shadow
        ) {
          // node has an undisclosed shadow which means we can only treat it as a black box, so we
          //  fall back to a non-zero-area test
          return isZeroArea(node);
        } else if (node.assignedSlot) {
          // iterate up slot
          node = node.assignedSlot;
        } else if (!parentElement && rootNode !== node.ownerDocument) {
          // cross shadow boundary
          node = rootNode.host;
        } else {
          // iterate up normal dom
          node = parentElement;
        }
      }
      node = originalNode;
    }
    // else, `getShadowRoot` might be true, but all that does is enable shadow DOM support
    //  (i.e. it does not also presume that all nodes might have undisclosed shadows); or
    //  it might be a falsy value, which means shadow DOM support is disabled

    // Since we didn't find it sitting in an undisclosed shadow (or shadows are disabled)
    //  now we can just test to see if it would normally be visible or not, provided it's
    //  attached to the main document.
    // NOTE: We must consider case where node is inside a shadow DOM and given directly to
    //  `isTabbable()` or `isFocusable()` -- regardless of `getShadowRoot` option setting.

    if (isNodeAttached(node)) {
      // this works wherever the node is: if there's at least one client rect, it's
      //  somehow displayed; it also covers the CSS 'display: contents' case where the
      //  node itself is hidden in place of its contents; and there's no need to search
      //  up the hierarchy either
      return !node.getClientRects().length;
    }

    // Else, the node isn't attached to the document, which means the `getClientRects()`
    //  API will __always__ return zero rects (this can happen, for example, if React
    //  is used to render nodes onto a detached tree, as confirmed in this thread:
    //  https://github.com/facebook/react/issues/9117#issuecomment-284228870)
    //
    // It also means that even window.getComputedStyle(node).display will return `undefined`
    //  because styles are only computed for nodes that are in the document.
    //
    // NOTE: THIS HAS BEEN THE CASE FOR YEARS. It is not new, nor is it caused by tabbable
    //  somehow. Though it was never stated officially, anyone who has ever used tabbable
    //  APIs on nodes in detached containers has actually implicitly used tabbable in what
    //  was later (as of v5.2.0 on Apr 9, 2021) called `displayCheck="none"` mode -- essentially
    //  considering __everything__ to be visible because of the innability to determine styles.
    //
    // v6.0.0: As of this major release, the default 'full' option __no longer treats detached
    //  nodes as visible with the 'none' fallback.__
    if (displayCheck !== 'legacy-full') {
      return true; // hidden
    }
    // else, fallback to 'none' mode and consider the node visible
  } else if (displayCheck === 'non-zero-area') {
    // NOTE: Even though this tests that the node's client rect is non-zero to determine
    //  whether it's displayed, and that a detached node will __always__ have a zero-area
    //  client rect, we don't special-case for whether the node is attached or not. In
    //  this mode, we do want to consider nodes that have a zero area to be hidden at all
    //  times, and that includes attached or not.
    return isZeroArea(node);
  }

  // visible, as far as we can tell, or per current `displayCheck=none` mode, we assume
  //  it's visible
  return false;
};

// form fields (nested) inside a disabled fieldset are not focusable/tabbable
//  unless they are in the _first_ <legend> element of the top-most disabled
//  fieldset
var isDisabledFromFieldset = function isDisabledFromFieldset(node) {
  if (/^(INPUT|BUTTON|SELECT|TEXTAREA)$/.test(node.tagName)) {
    var parentNode = node.parentElement;
    // check if `node` is contained in a disabled <fieldset>
    while (parentNode) {
      if (parentNode.tagName === 'FIELDSET' && parentNode.disabled) {
        // look for the first <legend> among the children of the disabled <fieldset>
        for (var i = 0; i < parentNode.children.length; i++) {
          var child = parentNode.children.item(i);
          // when the first <legend> (in document order) is found
          if (child.tagName === 'LEGEND') {
            // if its parent <fieldset> is not nested in another disabled <fieldset>,
            // return whether `node` is a descendant of its first <legend>
            return matches.call(parentNode, 'fieldset[disabled] *') ? true : !child.contains(node);
          }
        }
        // the disabled <fieldset> containing `node` has no <legend>
        return true;
      }
      parentNode = parentNode.parentElement;
    }
  }

  // else, node's tabbable/focusable state should not be affected by a fieldset's
  //  enabled/disabled state
  return false;
};
var isNodeMatchingSelectorFocusable = function isNodeMatchingSelectorFocusable(options, node) {
  if (node.disabled ||
  // we must do an inert look up to filter out any elements inside an inert ancestor
  //  because we're limited in the type of selectors we can use in JSDom (see related
  //  note related to `candidateSelectors`)
  isInert(node) || isHiddenInput(node) || isHidden(node, options) ||
  // For a details element with a summary, the summary element gets the focus
  isDetailsWithSummary(node) || isDisabledFromFieldset(node)) {
    return false;
  }
  return true;
};
var isNodeMatchingSelectorTabbable = function isNodeMatchingSelectorTabbable(options, node) {
  if (isNonTabbableRadio(node) || getTabindex(node) < 0 || !isNodeMatchingSelectorFocusable(options, node)) {
    return false;
  }
  return true;
};
var isValidShadowRootTabbable = function isValidShadowRootTabbable(shadowHostNode) {
  var tabIndex = parseInt(shadowHostNode.getAttribute('tabindex'), 10);
  if (isNaN(tabIndex) || tabIndex >= 0) {
    return true;
  }
  // If a custom element has an explicit negative tabindex,
  // browsers will not allow tab targeting said element's children.
  return false;
};

/**
 * @param {Array.<Element|CandidateScope>} candidates
 * @returns Element[]
 */
var sortByOrder = function sortByOrder(candidates) {
  var regularTabbables = [];
  var orderedTabbables = [];
  candidates.forEach(function (item, i) {
    var isScope = !!item.scopeParent;
    var element = isScope ? item.scopeParent : item;
    var candidateTabindex = getTabindex(element, isScope);
    var elements = isScope ? sortByOrder(item.candidates) : element;
    if (candidateTabindex === 0) {
      isScope ? regularTabbables.push.apply(regularTabbables, elements) : regularTabbables.push(element);
    } else {
      orderedTabbables.push({
        documentOrder: i,
        tabIndex: candidateTabindex,
        item: item,
        isScope: isScope,
        content: elements
      });
    }
  });
  return orderedTabbables.sort(sortOrderedTabbables).reduce(function (acc, sortable) {
    sortable.isScope ? acc.push.apply(acc, sortable.content) : acc.push(sortable.content);
    return acc;
  }, []).concat(regularTabbables);
};
var tabbable = function tabbable(el, options) {
  options = options || {};
  var candidates;
  if (options.getShadowRoot) {
    candidates = getCandidatesIteratively([el], options.includeContainer, {
      filter: isNodeMatchingSelectorTabbable.bind(null, options),
      flatten: false,
      getShadowRoot: options.getShadowRoot,
      shadowRootFilter: isValidShadowRootTabbable
    });
  } else {
    candidates = getCandidates(el, options.includeContainer, isNodeMatchingSelectorTabbable.bind(null, options));
  }
  return sortByOrder(candidates);
};
var focusable = function focusable(el, options) {
  options = options || {};
  var candidates;
  if (options.getShadowRoot) {
    candidates = getCandidatesIteratively([el], options.includeContainer, {
      filter: isNodeMatchingSelectorFocusable.bind(null, options),
      flatten: true,
      getShadowRoot: options.getShadowRoot
    });
  } else {
    candidates = getCandidates(el, options.includeContainer, isNodeMatchingSelectorFocusable.bind(null, options));
  }
  return candidates;
};
var isTabbable = function isTabbable(node, options) {
  options = options || {};
  if (!node) {
    throw new Error('No node provided');
  }
  if (matches.call(node, candidateSelector) === false) {
    return false;
  }
  return isNodeMatchingSelectorTabbable(options, node);
};
var focusableCandidateSelector = /* #__PURE__ */candidateSelectors.concat('iframe').join(',');
var isFocusable = function isFocusable(node, options) {
  options = options || {};
  if (!node) {
    throw new Error('No node provided');
  }
  if (matches.call(node, focusableCandidateSelector) === false) {
    return false;
  }
  return isNodeMatchingSelectorFocusable(options, node);
};


//# sourceMappingURL=index.esm.js.map


/***/ }),
/* 3 */
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   arrow: () => (/* reexport safe */ _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.arrow),
/* harmony export */   autoPlacement: () => (/* reexport safe */ _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.autoPlacement),
/* harmony export */   autoUpdate: () => (/* binding */ z),
/* harmony export */   computePosition: () => (/* binding */ M),
/* harmony export */   detectOverflow: () => (/* reexport safe */ _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.detectOverflow),
/* harmony export */   flip: () => (/* reexport safe */ _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.flip),
/* harmony export */   getOverflowAncestors: () => (/* binding */ W),
/* harmony export */   hide: () => (/* reexport safe */ _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.hide),
/* harmony export */   inline: () => (/* reexport safe */ _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.inline),
/* harmony export */   limitShift: () => (/* reexport safe */ _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.limitShift),
/* harmony export */   offset: () => (/* reexport safe */ _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.offset),
/* harmony export */   platform: () => (/* binding */ k),
/* harmony export */   shift: () => (/* reexport safe */ _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.shift),
/* harmony export */   size: () => (/* reexport safe */ _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.size)
/* harmony export */ });
/* harmony import */ var _floating_ui_core__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(4);
function n(t){var e;return(null==(e=t.ownerDocument)?void 0:e.defaultView)||window}function o(t){return n(t).getComputedStyle(t)}function i(t){return t instanceof n(t).Node}function r(t){return i(t)?(t.nodeName||"").toLowerCase():""}function l(t){return t instanceof n(t).HTMLElement}function c(t){return t instanceof n(t).Element}function s(t){if("undefined"==typeof ShadowRoot)return!1;return t instanceof n(t).ShadowRoot||t instanceof ShadowRoot}function f(t){const{overflow:e,overflowX:n,overflowY:i,display:r}=o(t);return/auto|scroll|overlay|hidden|clip/.test(e+i+n)&&!["inline","contents"].includes(r)}function u(t){return["table","td","th"].includes(r(t))}function a(t){const e=d(),n=o(t);return"none"!==n.transform||"none"!==n.perspective||!e&&!!n.backdropFilter&&"none"!==n.backdropFilter||!e&&!!n.filter&&"none"!==n.filter||["transform","perspective","filter"].some((t=>(n.willChange||"").includes(t)))||["paint","layout","strict","content"].some((t=>(n.contain||"").includes(t)))}function d(){return!("undefined"==typeof CSS||!CSS.supports)&&CSS.supports("-webkit-backdrop-filter","none")}function h(t){return["html","body","#document"].includes(r(t))}const p=Math.min,m=Math.max,g=Math.round;function y(t){const e=o(t);let n=parseFloat(e.width)||0,i=parseFloat(e.height)||0;const r=l(t),c=r?t.offsetWidth:n,s=r?t.offsetHeight:i,f=g(n)!==c||g(i)!==s;return f&&(n=c,i=s),{width:n,height:i,fallback:f}}function x(t){return c(t)?t:t.contextElement}const w={x:1,y:1};function v(t){const e=x(t);if(!l(e))return w;const n=e.getBoundingClientRect(),{width:o,height:i,fallback:r}=y(e);let c=(r?g(n.width):n.width)/o,s=(r?g(n.height):n.height)/i;return c&&Number.isFinite(c)||(c=1),s&&Number.isFinite(s)||(s=1),{x:c,y:s}}const b={x:0,y:0};function L(t,e,o){var i,r;if(void 0===e&&(e=!0),!d())return b;const l=t?n(t):window;return!o||e&&o!==l?b:{x:(null==(i=l.visualViewport)?void 0:i.offsetLeft)||0,y:(null==(r=l.visualViewport)?void 0:r.offsetTop)||0}}function E(e,o,i,r){void 0===o&&(o=!1),void 0===i&&(i=!1);const l=e.getBoundingClientRect(),s=x(e);let f=w;o&&(r?c(r)&&(f=v(r)):f=v(e));const u=L(s,i,r);let a=(l.left+u.x)/f.x,d=(l.top+u.y)/f.y,h=l.width/f.x,p=l.height/f.y;if(s){const t=n(s),e=r&&c(r)?n(r):r;let o=t.frameElement;for(;o&&r&&e!==t;){const t=v(o),e=o.getBoundingClientRect(),i=getComputedStyle(o);e.x+=(o.clientLeft+parseFloat(i.paddingLeft))*t.x,e.y+=(o.clientTop+parseFloat(i.paddingTop))*t.y,a*=t.x,d*=t.y,h*=t.x,p*=t.y,a+=e.x,d+=e.y,o=n(o).frameElement}}return (0,_floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.rectToClientRect)({width:h,height:p,x:a,y:d})}function R(t){return((i(t)?t.ownerDocument:t.document)||window.document).documentElement}function T(t){return c(t)?{scrollLeft:t.scrollLeft,scrollTop:t.scrollTop}:{scrollLeft:t.pageXOffset,scrollTop:t.pageYOffset}}function S(t){return E(R(t)).left+T(t).scrollLeft}function C(t){if("html"===r(t))return t;const e=t.assignedSlot||t.parentNode||s(t)&&t.host||R(t);return s(e)?e.host:e}function F(t){const e=C(t);return h(e)?e.ownerDocument.body:l(e)&&f(e)?e:F(e)}function W(t,e){var o;void 0===e&&(e=[]);const i=F(t),r=i===(null==(o=t.ownerDocument)?void 0:o.body),l=n(i);return r?e.concat(l,l.visualViewport||[],f(i)?i:[]):e.concat(i,W(i))}function D(e,i,r){let s;if("viewport"===i)s=function(t,e){const o=n(t),i=R(t),r=o.visualViewport;let l=i.clientWidth,c=i.clientHeight,s=0,f=0;if(r){l=r.width,c=r.height;const t=d();(!t||t&&"fixed"===e)&&(s=r.offsetLeft,f=r.offsetTop)}return{width:l,height:c,x:s,y:f}}(e,r);else if("document"===i)s=function(t){const e=R(t),n=T(t),i=t.ownerDocument.body,r=m(e.scrollWidth,e.clientWidth,i.scrollWidth,i.clientWidth),l=m(e.scrollHeight,e.clientHeight,i.scrollHeight,i.clientHeight);let c=-n.scrollLeft+S(t);const s=-n.scrollTop;return"rtl"===o(i).direction&&(c+=m(e.clientWidth,i.clientWidth)-r),{width:r,height:l,x:c,y:s}}(R(e));else if(c(i))s=function(t,e){const n=E(t,!0,"fixed"===e),o=n.top+t.clientTop,i=n.left+t.clientLeft,r=l(t)?v(t):{x:1,y:1};return{width:t.clientWidth*r.x,height:t.clientHeight*r.y,x:i*r.x,y:o*r.y}}(i,r);else{const t=L(e);s={...i,x:i.x-t.x,y:i.y-t.y}}return (0,_floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.rectToClientRect)(s)}function H(t,e){const n=C(t);return!(n===e||!c(n)||h(n))&&("fixed"===o(n).position||H(n,e))}function O(t,e){return l(t)&&"fixed"!==o(t).position?e?e(t):t.offsetParent:null}function P(t,e){const i=n(t);if(!l(t))return i;let c=O(t,e);for(;c&&u(c)&&"static"===o(c).position;)c=O(c,e);return c&&("html"===r(c)||"body"===r(c)&&"static"===o(c).position&&!a(c))?i:c||function(t){let e=C(t);for(;l(e)&&!h(e);){if(a(e))return e;e=C(e)}return null}(t)||i}function V(t,e,n){const o=l(e),i=R(e),c="fixed"===n,s=E(t,!0,c,e);let u={scrollLeft:0,scrollTop:0};const a={x:0,y:0};if(o||!o&&!c)if(("body"!==r(e)||f(i))&&(u=T(e)),l(e)){const t=E(e,!0,c,e);a.x=t.x+e.clientLeft,a.y=t.y+e.clientTop}else i&&(a.x=S(i));return{x:s.left+u.scrollLeft-a.x,y:s.top+u.scrollTop-a.y,width:s.width,height:s.height}}const k={getClippingRect:function(t){let{element:e,boundary:n,rootBoundary:i,strategy:l}=t;const s="clippingAncestors"===n?function(t,e){const n=e.get(t);if(n)return n;let i=W(t).filter((t=>c(t)&&"body"!==r(t))),l=null;const s="fixed"===o(t).position;let u=s?C(t):t;for(;c(u)&&!h(u);){const e=o(u),n=a(u);n||"fixed"!==e.position||(l=null),(s?!n&&!l:!n&&"static"===e.position&&l&&["absolute","fixed"].includes(l.position)||f(u)&&!n&&H(t,u))?i=i.filter((t=>t!==u)):l=e,u=C(u)}return e.set(t,i),i}(e,this._c):[].concat(n),u=[...s,i],d=u[0],g=u.reduce(((t,n)=>{const o=D(e,n,l);return t.top=m(o.top,t.top),t.right=p(o.right,t.right),t.bottom=p(o.bottom,t.bottom),t.left=m(o.left,t.left),t}),D(e,d,l));return{width:g.right-g.left,height:g.bottom-g.top,x:g.left,y:g.top}},convertOffsetParentRelativeRectToViewportRelativeRect:function(t){let{rect:e,offsetParent:n,strategy:o}=t;const i=l(n),c=R(n);if(n===c)return e;let s={scrollLeft:0,scrollTop:0},u={x:1,y:1};const a={x:0,y:0};if((i||!i&&"fixed"!==o)&&(("body"!==r(n)||f(c))&&(s=T(n)),l(n))){const t=E(n);u=v(n),a.x=t.x+n.clientLeft,a.y=t.y+n.clientTop}return{width:e.width*u.x,height:e.height*u.y,x:e.x*u.x-s.scrollLeft*u.x+a.x,y:e.y*u.y-s.scrollTop*u.y+a.y}},isElement:c,getDimensions:function(t){return y(t)},getOffsetParent:P,getDocumentElement:R,getScale:v,async getElementRects(t){let{reference:e,floating:n,strategy:o}=t;const i=this.getOffsetParent||P,r=this.getDimensions;return{reference:V(e,await i(n),o),floating:{x:0,y:0,...await r(n)}}},getClientRects:t=>Array.from(t.getClientRects()),isRTL:t=>"rtl"===o(t).direction};function z(t,e,n,o){void 0===o&&(o={});const{ancestorScroll:i=!0,ancestorResize:r=!0,elementResize:l=!0,animationFrame:s=!1}=o,f=i||r?[...c(t)?W(t):t.contextElement?W(t.contextElement):[],...W(e)]:[];f.forEach((t=>{const e=!c(t)&&t.toString().includes("V");!i||s&&!e||t.addEventListener("scroll",n,{passive:!0}),r&&t.addEventListener("resize",n)}));let u,a=null;l&&(a=new ResizeObserver((()=>{n()})),c(t)&&!s&&a.observe(t),c(t)||!t.contextElement||s||a.observe(t.contextElement),a.observe(e));let d=s?E(t):null;return s&&function e(){const o=E(t);!d||o.x===d.x&&o.y===d.y&&o.width===d.width&&o.height===d.height||n();d=o,u=requestAnimationFrame(e)}(),n(),()=>{var t;f.forEach((t=>{i&&t.removeEventListener("scroll",n),r&&t.removeEventListener("resize",n)})),null==(t=a)||t.disconnect(),a=null,s&&cancelAnimationFrame(u)}}const M=(t,n,o)=>{const i=new Map,r={platform:k,...o},l={...r.platform,_c:i};return (0,_floating_ui_core__WEBPACK_IMPORTED_MODULE_0__.computePosition)(t,n,{...r,platform:l})};


/***/ }),
/* 4 */
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   arrow: () => (/* binding */ u),
/* harmony export */   autoPlacement: () => (/* binding */ v),
/* harmony export */   computePosition: () => (/* binding */ r),
/* harmony export */   detectOverflow: () => (/* binding */ s),
/* harmony export */   flip: () => (/* binding */ b),
/* harmony export */   hide: () => (/* binding */ P),
/* harmony export */   inline: () => (/* binding */ T),
/* harmony export */   limitShift: () => (/* binding */ O),
/* harmony export */   offset: () => (/* binding */ D),
/* harmony export */   rectToClientRect: () => (/* binding */ l),
/* harmony export */   shift: () => (/* binding */ k),
/* harmony export */   size: () => (/* binding */ B)
/* harmony export */ });
function t(t){return t.split("-")[1]}function e(t){return"y"===t?"height":"width"}function n(t){return t.split("-")[0]}function o(t){return["top","bottom"].includes(n(t))?"x":"y"}function i(i,r,a){let{reference:l,floating:s}=i;const c=l.x+l.width/2-s.width/2,f=l.y+l.height/2-s.height/2,m=o(r),u=e(m),g=l[u]/2-s[u]/2,d="x"===m;let p;switch(n(r)){case"top":p={x:c,y:l.y-s.height};break;case"bottom":p={x:c,y:l.y+l.height};break;case"right":p={x:l.x+l.width,y:f};break;case"left":p={x:l.x-s.width,y:f};break;default:p={x:l.x,y:l.y}}switch(t(r)){case"start":p[m]-=g*(a&&d?-1:1);break;case"end":p[m]+=g*(a&&d?-1:1)}return p}const r=async(t,e,n)=>{const{placement:o="bottom",strategy:r="absolute",middleware:a=[],platform:l}=n,s=a.filter(Boolean),c=await(null==l.isRTL?void 0:l.isRTL(e));let f=await l.getElementRects({reference:t,floating:e,strategy:r}),{x:m,y:u}=i(f,o,c),g=o,d={},p=0;for(let n=0;n<s.length;n++){const{name:a,fn:h}=s[n],{x:y,y:x,data:w,reset:v}=await h({x:m,y:u,initialPlacement:o,placement:g,strategy:r,middlewareData:d,rects:f,platform:l,elements:{reference:t,floating:e}});m=null!=y?y:m,u=null!=x?x:u,d={...d,[a]:{...d[a],...w}},v&&p<=50&&(p++,"object"==typeof v&&(v.placement&&(g=v.placement),v.rects&&(f=!0===v.rects?await l.getElementRects({reference:t,floating:e,strategy:r}):v.rects),({x:m,y:u}=i(f,g,c))),n=-1)}return{x:m,y:u,placement:g,strategy:r,middlewareData:d}};function a(t){return"number"!=typeof t?function(t){return{top:0,right:0,bottom:0,left:0,...t}}(t):{top:t,right:t,bottom:t,left:t}}function l(t){return{...t,top:t.y,left:t.x,right:t.x+t.width,bottom:t.y+t.height}}async function s(t,e){var n;void 0===e&&(e={});const{x:o,y:i,platform:r,rects:s,elements:c,strategy:f}=t,{boundary:m="clippingAncestors",rootBoundary:u="viewport",elementContext:g="floating",altBoundary:d=!1,padding:p=0}=e,h=a(p),y=c[d?"floating"===g?"reference":"floating":g],x=l(await r.getClippingRect({element:null==(n=await(null==r.isElement?void 0:r.isElement(y)))||n?y:y.contextElement||await(null==r.getDocumentElement?void 0:r.getDocumentElement(c.floating)),boundary:m,rootBoundary:u,strategy:f})),w="floating"===g?{...s.floating,x:o,y:i}:s.reference,v=await(null==r.getOffsetParent?void 0:r.getOffsetParent(c.floating)),b=await(null==r.isElement?void 0:r.isElement(v))&&await(null==r.getScale?void 0:r.getScale(v))||{x:1,y:1},A=l(r.convertOffsetParentRelativeRectToViewportRelativeRect?await r.convertOffsetParentRelativeRectToViewportRelativeRect({rect:w,offsetParent:v,strategy:f}):w);return{top:(x.top-A.top+h.top)/b.y,bottom:(A.bottom-x.bottom+h.bottom)/b.y,left:(x.left-A.left+h.left)/b.x,right:(A.right-x.right+h.right)/b.x}}const c=Math.min,f=Math.max;function m(t,e,n){return f(t,c(e,n))}const u=n=>({name:"arrow",options:n,async fn(i){const{element:r,padding:l=0}=n||{},{x:s,y:c,placement:f,rects:u,platform:g,elements:d}=i;if(null==r)return{};const p=a(l),h={x:s,y:c},y=o(f),x=e(y),w=await g.getDimensions(r),v="y"===y,b=v?"top":"left",A=v?"bottom":"right",R=v?"clientHeight":"clientWidth",P=u.reference[x]+u.reference[y]-h[y]-u.floating[x],E=h[y]-u.reference[y],T=await(null==g.getOffsetParent?void 0:g.getOffsetParent(r));let D=T?T[R]:0;D&&await(null==g.isElement?void 0:g.isElement(T))||(D=d.floating[R]||u.floating[x]);const L=P/2-E/2,k=p[b],O=D-w[x]-p[A],B=D/2-w[x]/2+L,C=m(k,B,O),H=null!=t(f)&&B!=C&&u.reference[x]/2-(B<k?p[b]:p[A])-w[x]/2<0;return{[y]:h[y]-(H?B<k?k-B:O-B:0),data:{[y]:C,centerOffset:B-C}}}}),g=["top","right","bottom","left"],d=g.reduce(((t,e)=>t.concat(e,e+"-start",e+"-end")),[]),p={left:"right",right:"left",bottom:"top",top:"bottom"};function h(t){return t.replace(/left|right|bottom|top/g,(t=>p[t]))}function y(n,i,r){void 0===r&&(r=!1);const a=t(n),l=o(n),s=e(l);let c="x"===l?a===(r?"end":"start")?"right":"left":"start"===a?"bottom":"top";return i.reference[s]>i.floating[s]&&(c=h(c)),{main:c,cross:h(c)}}const x={start:"end",end:"start"};function w(t){return t.replace(/start|end/g,(t=>x[t]))}const v=function(e){return void 0===e&&(e={}),{name:"autoPlacement",options:e,async fn(o){var i,r,a;const{rects:l,middlewareData:c,placement:f,platform:m,elements:u}=o,{crossAxis:g=!1,alignment:p,allowedPlacements:h=d,autoAlignment:x=!0,...v}=e,b=void 0!==p||h===d?function(e,o,i){return(e?[...i.filter((n=>t(n)===e)),...i.filter((n=>t(n)!==e))]:i.filter((t=>n(t)===t))).filter((n=>!e||t(n)===e||!!o&&w(n)!==n))}(p||null,x,h):h,A=await s(o,v),R=(null==(i=c.autoPlacement)?void 0:i.index)||0,P=b[R];if(null==P)return{};const{main:E,cross:T}=y(P,l,await(null==m.isRTL?void 0:m.isRTL(u.floating)));if(f!==P)return{reset:{placement:b[0]}};const D=[A[n(P)],A[E],A[T]],L=[...(null==(r=c.autoPlacement)?void 0:r.overflows)||[],{placement:P,overflows:D}],k=b[R+1];if(k)return{data:{index:R+1,overflows:L},reset:{placement:k}};const O=L.map((e=>{const n=t(e.placement);return[e.placement,n&&g?e.overflows.slice(0,2).reduce(((t,e)=>t+e),0):e.overflows[0],e.overflows]})).sort(((t,e)=>t[1]-e[1])),B=(null==(a=O.filter((e=>e[2].slice(0,t(e[0])?2:3).every((t=>t<=0))))[0])?void 0:a[0])||O[0][0];return B!==f?{data:{index:R+1,overflows:L},reset:{placement:B}}:{}}}};const b=function(e){return void 0===e&&(e={}),{name:"flip",options:e,async fn(o){var i;const{placement:r,middlewareData:a,rects:l,initialPlacement:c,platform:f,elements:m}=o,{mainAxis:u=!0,crossAxis:g=!0,fallbackPlacements:d,fallbackStrategy:p="bestFit",fallbackAxisSideDirection:x="none",flipAlignment:v=!0,...b}=e,A=n(r),R=n(c)===c,P=await(null==f.isRTL?void 0:f.isRTL(m.floating)),E=d||(R||!v?[h(c)]:function(t){const e=h(t);return[w(t),e,w(e)]}(c));d||"none"===x||E.push(...function(e,o,i,r){const a=t(e);let l=function(t,e,n){const o=["left","right"],i=["right","left"],r=["top","bottom"],a=["bottom","top"];switch(t){case"top":case"bottom":return n?e?i:o:e?o:i;case"left":case"right":return e?r:a;default:return[]}}(n(e),"start"===i,r);return a&&(l=l.map((t=>t+"-"+a)),o&&(l=l.concat(l.map(w)))),l}(c,v,x,P));const T=[c,...E],D=await s(o,b),L=[];let k=(null==(i=a.flip)?void 0:i.overflows)||[];if(u&&L.push(D[A]),g){const{main:t,cross:e}=y(r,l,P);L.push(D[t],D[e])}if(k=[...k,{placement:r,overflows:L}],!L.every((t=>t<=0))){var O,B;const t=((null==(O=a.flip)?void 0:O.index)||0)+1,e=T[t];if(e)return{data:{index:t,overflows:k},reset:{placement:e}};let n=null==(B=k.filter((t=>t.overflows[0]<=0)).sort(((t,e)=>t.overflows[1]-e.overflows[1]))[0])?void 0:B.placement;if(!n)switch(p){case"bestFit":{var C;const t=null==(C=k.map((t=>[t.placement,t.overflows.filter((t=>t>0)).reduce(((t,e)=>t+e),0)])).sort(((t,e)=>t[1]-e[1]))[0])?void 0:C[0];t&&(n=t);break}case"initialPlacement":n=c}if(r!==n)return{reset:{placement:n}}}return{}}}};function A(t,e){return{top:t.top-e.height,right:t.right-e.width,bottom:t.bottom-e.height,left:t.left-e.width}}function R(t){return g.some((e=>t[e]>=0))}const P=function(t){return void 0===t&&(t={}),{name:"hide",options:t,async fn(e){const{strategy:n="referenceHidden",...o}=t,{rects:i}=e;switch(n){case"referenceHidden":{const t=A(await s(e,{...o,elementContext:"reference"}),i.reference);return{data:{referenceHiddenOffsets:t,referenceHidden:R(t)}}}case"escaped":{const t=A(await s(e,{...o,altBoundary:!0}),i.floating);return{data:{escapedOffsets:t,escaped:R(t)}}}default:return{}}}}};function E(t){const e=c(...t.map((t=>t.left))),n=c(...t.map((t=>t.top)));return{x:e,y:n,width:f(...t.map((t=>t.right)))-e,height:f(...t.map((t=>t.bottom)))-n}}const T=function(t){return void 0===t&&(t={}),{name:"inline",options:t,async fn(e){const{placement:i,elements:r,rects:s,platform:m,strategy:u}=e,{padding:g=2,x:d,y:p}=t,h=Array.from(await(null==m.getClientRects?void 0:m.getClientRects(r.reference))||[]),y=function(t){const e=t.slice().sort(((t,e)=>t.y-e.y)),n=[];let o=null;for(let t=0;t<e.length;t++){const i=e[t];!o||i.y-o.y>o.height/2?n.push([i]):n[n.length-1].push(i),o=i}return n.map((t=>l(E(t))))}(h),x=l(E(h)),w=a(g);const v=await m.getElementRects({reference:{getBoundingClientRect:function(){if(2===y.length&&y[0].left>y[1].right&&null!=d&&null!=p)return y.find((t=>d>t.left-w.left&&d<t.right+w.right&&p>t.top-w.top&&p<t.bottom+w.bottom))||x;if(y.length>=2){if("x"===o(i)){const t=y[0],e=y[y.length-1],o="top"===n(i),r=t.top,a=e.bottom,l=o?t.left:e.left,s=o?t.right:e.right;return{top:r,bottom:a,left:l,right:s,width:s-l,height:a-r,x:l,y:r}}const t="left"===n(i),e=f(...y.map((t=>t.right))),r=c(...y.map((t=>t.left))),a=y.filter((n=>t?n.left===r:n.right===e)),l=a[0].top,s=a[a.length-1].bottom;return{top:l,bottom:s,left:r,right:e,width:e-r,height:s-l,x:r,y:l}}return x}},floating:r.floating,strategy:u});return s.reference.x!==v.reference.x||s.reference.y!==v.reference.y||s.reference.width!==v.reference.width||s.reference.height!==v.reference.height?{reset:{rects:v}}:{}}}};const D=function(e){return void 0===e&&(e=0),{name:"offset",options:e,async fn(i){const{x:r,y:a}=i,l=await async function(e,i){const{placement:r,platform:a,elements:l}=e,s=await(null==a.isRTL?void 0:a.isRTL(l.floating)),c=n(r),f=t(r),m="x"===o(r),u=["left","top"].includes(c)?-1:1,g=s&&m?-1:1,d="function"==typeof i?i(e):i;let{mainAxis:p,crossAxis:h,alignmentAxis:y}="number"==typeof d?{mainAxis:d,crossAxis:0,alignmentAxis:null}:{mainAxis:0,crossAxis:0,alignmentAxis:null,...d};return f&&"number"==typeof y&&(h="end"===f?-1*y:y),m?{x:h*g,y:p*u}:{x:p*u,y:h*g}}(i,e);return{x:r+l.x,y:a+l.y,data:l}}}};function L(t){return"x"===t?"y":"x"}const k=function(t){return void 0===t&&(t={}),{name:"shift",options:t,async fn(e){const{x:i,y:r,placement:a}=e,{mainAxis:l=!0,crossAxis:c=!1,limiter:f={fn:t=>{let{x:e,y:n}=t;return{x:e,y:n}}},...u}=t,g={x:i,y:r},d=await s(e,u),p=o(n(a)),h=L(p);let y=g[p],x=g[h];if(l){const t="y"===p?"bottom":"right";y=m(y+d["y"===p?"top":"left"],y,y-d[t])}if(c){const t="y"===h?"bottom":"right";x=m(x+d["y"===h?"top":"left"],x,x-d[t])}const w=f.fn({...e,[p]:y,[h]:x});return{...w,data:{x:w.x-i,y:w.y-r}}}}},O=function(t){return void 0===t&&(t={}),{options:t,fn(e){const{x:i,y:r,placement:a,rects:l,middlewareData:s}=e,{offset:c=0,mainAxis:f=!0,crossAxis:m=!0}=t,u={x:i,y:r},g=o(a),d=L(g);let p=u[g],h=u[d];const y="function"==typeof c?c(e):c,x="number"==typeof y?{mainAxis:y,crossAxis:0}:{mainAxis:0,crossAxis:0,...y};if(f){const t="y"===g?"height":"width",e=l.reference[g]-l.floating[t]+x.mainAxis,n=l.reference[g]+l.reference[t]-x.mainAxis;p<e?p=e:p>n&&(p=n)}if(m){var w,v;const t="y"===g?"width":"height",e=["top","left"].includes(n(a)),o=l.reference[d]-l.floating[t]+(e&&(null==(w=s.offset)?void 0:w[d])||0)+(e?0:x.crossAxis),i=l.reference[d]+l.reference[t]+(e?0:(null==(v=s.offset)?void 0:v[d])||0)-(e?x.crossAxis:0);h<o?h=o:h>i&&(h=i)}return{[g]:p,[d]:h}}}},B=function(e){return void 0===e&&(e={}),{name:"size",options:e,async fn(i){const{placement:r,rects:a,platform:l,elements:m}=i,{apply:u=(()=>{}),...g}=e,d=await s(i,g),p=n(r),h=t(r),y="x"===o(r),{width:x,height:w}=a.floating;let v,b;"top"===p||"bottom"===p?(v=p,b=h===(await(null==l.isRTL?void 0:l.isRTL(m.floating))?"start":"end")?"left":"right"):(b=p,v="end"===h?"top":"bottom");const A=w-d[v],R=x-d[b],P=!i.middlewareData.shift;let E=A,T=R;if(y){const t=x-d.left-d.right;T=h||P?c(R,t):t}else{const t=w-d.top-d.bottom;E=h||P?c(A,t):t}if(P&&!h){const t=f(d.left,0),e=f(d.right,0),n=f(d.top,0),o=f(d.bottom,0);y?T=x-2*(0!==t||0!==e?t+e:f(d.left,d.right)):E=w-2*(0!==n||0!==o?n+o:f(d.top,d.bottom))}await u({...i,availableWidth:T,availableHeight:E});const D=await l.getDimensions(m.floating);return x!==D.width||w!==D.height?{reset:{rects:!0}}:{}}}};


/***/ })
/******/ 	]);
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
(() => {
var __webpack_exports__ = {};
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _floating_ui_dom__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(3);
/* harmony import */ var focus_trap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(1);
/* harmony import */ var tabbable__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(2);



class AccessibilityCheckerDisableHTML {
  /**
   * Constructor
   */
  constructor() {
    this.disableStylesButton = document.querySelector('#edac-highlight-disable-styles');
    this.closePanel = document.querySelector('#edac-highlight-panel-controls-close');
    this.stylesDisabled = false;
    this.originalCss = [];
    this.init();
  }

  /**
   * This function initializes the component by setting up event listeners.
   */
  init() {
    this.disableStylesButton.addEventListener('click', () => {
      if (this.stylesDisabled) {
        this.enableStyles();
      } else {
        this.disableStyles();
      }
    });
    this.closePanel.addEventListener('click', () => this.enableStyles());
  }

  /**
   * This function disables all styles on the page.
   */
  disableStyles() {
    this.originalCss = Array.from(document.head.querySelectorAll('style[type="text/css"], style, link[rel="stylesheet"]'));
    var elementsWithStyle = document.querySelectorAll('*[style]:not([class^="edac"])');
    elementsWithStyle.forEach(function (element) {
      element.removeAttribute("style");
    });
    this.originalCss = this.originalCss.filter(function (element) {
      if (element.id === 'edac-app-css' || element.id === 'dashicons-css') {
        return false;
      }
      return true;
    });
    document.head.dataset.css = this.originalCss;
    this.originalCss.forEach(function (element) {
      element.remove();
    });
    document.querySelector('body').classList.add('edac-app-disable-styles');
    this.stylesDisabled = true;
    this.disableStylesButton.textContent = "Enable Styles";
  }

  /**
   * This function enables all styles on the page.
   */
  enableStyles() {
    this.originalCss.forEach(function (element) {
      if (element.tagName === 'STYLE') {
        document.head.appendChild(element.cloneNode(true));
      } else {
        const newElement = document.createElement('link');
        newElement.rel = 'stylesheet';
        newElement.href = element.href;
        document.head.appendChild(newElement);
      }
    });
    document.querySelector('body').classList.remove('edac-app-disable-styles');
    this.stylesDisabled = false;
    this.disableStylesButton.textContent = "Disable Styles";
  }
}
class AccessibilityCheckerHighlight {
  /**
   * Constructor
   */
  constructor() {
    this.addHighlightPanel();
    this.nextButton = document.querySelector('#edac-highlight-next');
    this.previousButton = document.querySelector('#edac-highlight-previous');
    this.panelToggle = document.querySelector('#edac-highlight-panel-toggle');
    this.closePanel = document.querySelector('#edac-highlight-panel-controls-close');
    this.panelDescription = document.querySelector('#edac-highlight-panel-description');
    this.panelControls = document.querySelector('#edac-highlight-panel-controls');
    this.descriptionCloseButton = document.querySelector('.edac-highlight-panel-description-close');
    this.issues = null;
    this.currentButtonIndex = null;
    this.urlParameter = this.get_url_parameter('edac');
    this.currentIssueStatus = null;
    this.tooltips = [];
    this.panelControlsFocusTrap = (0,focus_trap__WEBPACK_IMPORTED_MODULE_0__.createFocusTrap)('#' + this.panelControls.id, {
      clickOutsideDeactivates: true,
      escapeDeactivates: () => {
        this.panelClose();
      }
    });
    this.panelDescriptionFocusTrap = (0,focus_trap__WEBPACK_IMPORTED_MODULE_0__.createFocusTrap)('#' + this.panelDescription.id, {
      clickOutsideDeactivates: true,
      escapeDeactivates: () => {
        this.descriptionClose();
      }
    });
    this.init();
  }

  /**
   * This function initializes the component by setting up event listeners 
   * and managing the initial state of the panel based on the URL parameter.
   */
  init() {
    // Add event listeners for 'next' and 'previous' buttons
    this.nextButton.addEventListener('click', event => {
      this.highlightFocusNext();
      this.focusTrapDescription();
    });
    this.previousButton.addEventListener('click', event => {
      this.highlightFocusPrevious();
      this.focusTrapDescription();
    });

    // Manage panel open/close operations
    this.panelToggle.addEventListener('click', () => {
      this.panelOpen();
      this.focusTrapControls();
    });
    this.closePanel.addEventListener('click', () => {
      this.panelClose();
      this.panelControlsFocusTrap.deactivate();
      this.panelDescriptionFocusTrap.deactivate();
    });

    // Close description when close button is clicked
    this.descriptionCloseButton.addEventListener('click', () => this.descriptionClose());

    // Open panel if a URL parameter exists
    if (this.urlParameter) {
      this.panelOpen(this.urlParameter);
    }
  }

  /**
   * This function tries to find an element on the page that matches a given HTML snippet.
   * It parses the HTML snippet, and compares the outer HTML of the parsed element 
   * with all elements present on the page. If a match is found, it 
   * adds a tooltip, checks if the element is focusable, and then returns the element.
   * If no matching element is found, or if the parsed HTML snippet does not contain an element,
   * it returns null.
   *
   * @param {Object} value - Object containing the HTML snippet to be matched.
   * @param {number} index - Index of the element being searched.
   * @returns {HTMLElement|null} - Returns the matching HTML element, or null if no match is found.
   */
  findElement(value, index) {
    // Parse the HTML snippet
    const htmlSnippet = value.object;
    const parser = new DOMParser();
    const parsedHtml = parser.parseFromString(htmlSnippet, 'text/html');
    const firstParsedElement = parsedHtml.body.firstElementChild;

    // If there's no parsed element, return null
    if (!firstParsedElement) {
      return null;
    }

    // Compare the outer HTML of the parsed element with all elements on the page
    const allElements = document.body.querySelectorAll('*');
    for (const element of allElements) {
      if (element.outerHTML === firstParsedElement.outerHTML) {
        const tooltip = this.addTooltip(element, value, index);
        this.tooltips.push(tooltip);
        return element;
      }
    }

    // If no matching element is found, return null
    return null;
  }

  /**
   * This function makes an AJAX call to the server to retrieve the list of issues.
   *
   * Note: This function assumes that `edac_script_vars` is a global variable containing necessary data.
   */
  highlightAjax() {
    return new Promise(function (resolve, reject) {
      const xhr = new XMLHttpRequest();
      const url = edac_script_vars.ajaxurl + '?action=edac_frontend_highlight_ajax&post_id=' + edac_script_vars.postID + '&nonce=' + edac_script_vars.nonce;
      xhr.open('GET', url);
      xhr.onload = function () {
        if (xhr.status === 200) {
          const response = JSON.parse(xhr.responseText);
          //console.log(response);
          if (true === response.success) {
            const response_json = JSON.parse(response.data);
            resolve(response_json);
          } else {
            //console.log(response);
          }
        } else {
          console.log('Request failed.  Returned status of ' + xhr.status);
          reject({
            status: xhr.status,
            statusText: xhr.statusText
          });
        }
      };
      xhr.onerror = function () {
        reject({
          status: xhr.status,
          statusText: xhr.statusText
        });
      };
      xhr.send();
    });
  }

  /**
   * This function removes the highlight/tooltip buttons and runs cleanups for each.
   */
  removeHighlightButtons() {
    this.tooltips.forEach(item => {
      //find the associated element 
      const id = item.tooltip.dataset.id;

      //remove the data-element-id added we created the tooltip/button
      const element = document.querySelector(`[data-element-id="${id}"]`);
      if (element) {
        element.removeAttribute('data-element-id');
      }

      //remove click listener
      item.tooltip.removeEventListener('click', item.listeners.onClick);

      //remove position/resize listener: https://floating-ui.com/docs/autoUpdate
      item.listeners.cleanup();

      //remove tooltip
      item.tooltip.remove();
    });
  }

  /**
   * This function adds a new button element to the DOM, which acts as a tooltip for the highlighted element.
   * 
   * @param {HTMLElement} element - The DOM element before which the tooltip button will be inserted.
   * @param {Object} value - An object containing properties used to customize the tooltip button.
   * @param {Number} index - The index of the element being processed.
   * @return {Object} - information about the tooltip
   */
  addTooltip(element, value, index) {
    // Create the tooltip.
    let tooltip = document.createElement('button');
    tooltip.classList = 'edac-highlight-btn edac-highlight-btn-' + value.rule_type;
    tooltip.ariaLabel = value.rule_title;
    tooltip.ariaExpanded = 'false';
    //tooltip.ariaControls = 'edac-highlight-tooltip-' + value.id;

    //add data-id to the tooltip/button so we can find it later.
    tooltip.dataset.id = value.id;

    //add data-element-id to the element so we can find it later.
    element.dataset.elementId = value.id;
    const onClick = e => {
      const id = e.currentTarget.dataset.id;
      this.showIssue(id);
      this.focusTrapDescription();
    };
    tooltip.addEventListener('click', onClick);

    // Add the tooltip to the page.
    document.body.append(tooltip);

    // Place the tooltip at the element's position on the page.
    // See: https://floating-ui.com/docs/autoUpdate

    function updatePosition() {
      (0,_floating_ui_dom__WEBPACK_IMPORTED_MODULE_1__.computePosition)(element, tooltip, {
        placement: 'left'
      }).then(({
        x,
        y,
        middlewareData,
        placement
      }) => {
        Object.assign(tooltip.style, {
          left: `${x - 32}px`,
          top: `${y}px`
        });
      });
    }
    ;
    const cleanup = (0,_floating_ui_dom__WEBPACK_IMPORTED_MODULE_1__.autoUpdate)(element, tooltip, updatePosition);
    return {
      element,
      tooltip,
      listeners: {
        onClick,
        cleanup
      }
    };
  }

  /**
   * This function adds a new div element to the DOM, which contains the accessibility checker panel.
   */
  addHighlightPanel() {
    const newElement = `
			<div class="edac-highlight-panel">
			<button id="edac-highlight-panel-toggle" class="edac-highlight-panel-toggle" title="Toggle accessibility tools"></button>
			<div id="edac-highlight-panel-description" class="edac-highlight-panel-description" tabindex="0">
				<button class="edac-highlight-panel-description-close" aria-label="Close">×</button>
				<div class="edac-highlight-panel-description-title"></div>
				<div class="edac-highlight-panel-description-content"></div>
				<div id="edac-highlight-panel-description-code" class="edac-highlight-panel-description-code"><code></code></div>			
			</div>
			<div id="edac-highlight-panel-controls" class="edac-highlight-panel-controls" tabindex="0">
				<button id="edac-highlight-panel-controls-close" class="edac-highlight-panel-controls-close" aria-label="Close accessibility highlights panel" aria-label="Close">×</button>
				<div class="edac-highlight-panel-controls-title">Accessibility Checker</div>
				<div class="edac-highlight-panel-controls-summary"></div>
				<div class="edac-highlight-panel-controls-buttons">
					<div>
						<button id="edac-highlight-previous"><span aria-hidden="true">« </span>Previous</button>
						<button id="edac-highlight-next">Next<span aria-hidden="true"> »</span></button><br />
					</div>
					<div>
						<button id="edac-highlight-disable-styles" class="edac-highlight-disable-styles">Disable Styles</button>
					</div>
				</div>
			
			</div>
			</div>
		`;
    document.body.insertAdjacentHTML('afterbegin', newElement);
  }

  /**
   * This function highlights the next element on the page. It uses the 'currentButtonIndex' property to keep track of the current element.
   */
  highlightFocusNext = () => {
    if (this.currentButtonIndex == null) {
      this.currentButtonIndex = 0;
    } else {
      this.currentButtonIndex = (this.currentButtonIndex + 1) % this.issues.length;
    }
    const id = this.issues[this.currentButtonIndex]['id'];
    this.showIssue(id);
  };

  /**
   * This function highlights the previous element on the page. It uses the 'currentButtonIndex' property to keep track of the current element.
   */
  highlightFocusPrevious = () => {
    if (this.currentButtonIndex == null) {
      this.currentButtonIndex = this.issues.length - 1;
    } else {
      this.currentButtonIndex = (this.currentButtonIndex - 1 + this.issues.length) % this.issues.length;
    }
    const id = this.issues[this.currentButtonIndex]['id'];
    this.showIssue(id);
  };

  /**
   * This function sets a focus trap on the controls panel
   */
  focusTrapControls = () => {
    this.panelDescriptionFocusTrap.deactivate();
    this.panelControlsFocusTrap.activate();
    setTimeout(() => {
      this.panelControls.focus();
    }, 100); //give render time to complete.	
  };

  /**
   * This function sets a focus trap on the description panel
   */
  focusTrapDescription = () => {
    this.panelControlsFocusTrap.deactivate();
    this.panelDescriptionFocusTrap.activate();
    setTimeout(() => {
      this.panelDescription.focus();
    }, 100); //give render time to complete.
  };

  /**
   * This function shows an issue related to an element.
   * @param {string} id - The ID of the element.
   */

  showIssue = id => {
    this.removeSelectedClasses();
    if (id === undefined) {
      return;
    }
    this.currentButtonIndex = this.issues.findIndex(issue => issue.id == id);
    const issueElement = document.querySelector(`[data-id="${id}"]`);
    const element = document.querySelector(`[data-element-id="${id}"]`);
    if (issueElement && element) {
      issueElement.classList.add('edac-highlight-btn-selected');
      element.classList.add('edac-highlight-element-selected');
      element.scrollIntoView({
        block: 'center'
      });
      if ((0,tabbable__WEBPACK_IMPORTED_MODULE_2__.isFocusable)(issueElement)) {
        //issueElement.focus();

        if (!this.checkVisibility(issueElement) || !this.checkVisibility(element)) {
          this.currentIssueStatus = 'The element is not visible. Try disabling styles.';
          //TODO: console.log(`Element with id ${id} is not visible!`);
        } else {
          this.currentIssueStatus = null;
        }
      } else {
        this.currentIssueStatus = 'The element is not focusable. Try disabling styles.';
        //TODO: console.log(`Element with id ${id} is not focusable!`);
      }
    } else {
      this.currentIssueStatus = 'The element was not found on the page.';
      //TODO: console.log(`Element with id ${id} not found in the document!`);
    }

    this.descriptionOpen(id);
  };

  /**
   * This function checks if a given element is visible on the page.
   * 
   * @param {HTMLElement} el The element to check for visibility
   * @returns 
   */
  checkVisibility = el => {
    //checkVisibility is still in draft but well supported on many browsers.
    //See: https://drafts.csswg.org/cssom-view-1/#dom-element-checkvisibility
    //See: https://caniuse.com/mdn-api_element_checkvisibility
    if (typeof el.checkVisibility !== 'function') {
      //See: https://github.com/jquery/jquery/blob/main/src/css/hiddenVisibleSelectors.js
      return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    } else {
      return el.checkVisibility({
        checkOpacity: true,
        // Check CSS opacity property too
        checkVisibilityCSS: true // Check CSS visibility property too
      });
    }
  };

  /**
   * This function opens the accessibility checker panel.
   */
  panelOpen(id) {
    this.panelControls.style.display = 'block';
    this.panelToggle.style.display = 'none';

    // Get the issues for this page.
    this.highlightAjax().then(json => {
      //console.log(json);

      this.issues = json;
      json.forEach(function (value, index) {
        const matchedElement = this.findElement(value, index);
      }.bind(this));
      this.showIssueCount();
      if (id !== undefined) {
        this.showIssue(id);
        this.focusTrapDescription();
      }
    }).catch(err => {
      //TODO:
    });
  }

  /**
   * This function closes the accessibility checker panel.
   */
  panelClose() {
    this.panelControls.style.display = 'none';
    this.panelDescription.style.display = 'none';
    this.panelToggle.style.display = 'block';
    this.removeSelectedClasses();
    this.removeHighlightButtons();
    this.closePanel.removeEventListener('click', this.panelControlsFocusTrap.deactivate);
    this.panelToggle.focus();
  }

  /**
   * This function removes the classes that indicates a button or element are selected 
   */
  removeSelectedClasses = () => {
    //remove selected class from previously selected buttons
    const selectedButtons = document.querySelectorAll('.edac-highlight-btn-selected');
    selectedButtons.forEach(selectedButton => {
      selectedButton.classList.remove('edac-highlight-btn-selected');
    });
    //remove selected class from previously selected elements
    const selectedElements = document.querySelectorAll('.edac-highlight-element-selected');
    selectedElements.forEach(selectedElement => {
      selectedElement.classList.remove('edac-highlight-element-selected');
    });
  };

  /**
   * This function displays the description of the issue.
   * 
   * @param {string} dataId 
   */
  descriptionOpen(dataId) {
    // get the value of the property by key
    const searchTerm = dataId;
    const keyToSearch = "id";
    const matchingObj = this.issues.find(obj => obj[keyToSearch] === searchTerm);
    if (matchingObj) {
      const descriptionTitle = document.querySelector('.edac-highlight-panel-description-title');
      const descriptionContent = document.querySelector('.edac-highlight-panel-description-content');
      const descriptionCode = document.querySelector('.edac-highlight-panel-description-code code');
      let content = '';

      // Get the index and total
      content += ` <div class="edac-highlight-panel-description-index">${this.currentButtonIndex + 1} of ${this.issues.length}</div>`;

      // Get the status of the issue
      if (this.currentIssueStatus) {
        content += ` <div class="edac-highlight-panel-description-status">${this.currentIssueStatus}</div>`;
      }

      // Get the summary of the issue
      content += matchingObj.summary;

      // Get the link to the documentation
      content += ` <br /><a class="edac-highlight-panel-description-reference" href="${matchingObj.link}">Full Documentation</a>`;

      // Get the code button
      content += `<button class="edac-highlight-panel-description-code-button" aria-expanded="false" aria-controls="edac-highlight-panel-description-code">Show Code</button>`;

      // title and content
      descriptionTitle.innerHTML = matchingObj.rule_title + ' <span class="edac-highlight-panel-description-type edac-highlight-panel-description-type-' + matchingObj.rule_type + '" aria-label=" Issue type: ' + matchingObj.rule_type + '"> ' + matchingObj.rule_type + '</span>';

      // content
      descriptionContent.innerHTML = content;

      // code object
      // remove any non-html from the object
      const htmlSnippet = matchingObj.object;
      const parser = new DOMParser();
      const parsedHtml = parser.parseFromString(htmlSnippet, 'text/html');
      const firstParsedElement = parsedHtml.body.firstElementChild;
      if (firstParsedElement) {
        descriptionCode.innerText = firstParsedElement.outerHTML;
      } else {
        let textNode = document.createTextNode(matchingObj.object);
        descriptionCode.innerText = textNode.nodeValue;
      }

      // set code button listener
      this.codeContainer = document.querySelector('.edac-highlight-panel-description-code');
      this.codeButton = document.querySelector('.edac-highlight-panel-description-code-button');
      this.codeButton.addEventListener('click', () => this.codeToggle());

      // close the code container each time the description is opened
      this.codeContainer.style.display = 'none';

      // show the description
      this.panelDescription.style.display = 'block';
    }
  }

  /**
   * This function closes the description.
   */
  descriptionClose() {
    this.panelDescription.style.display = 'none';
    this.focusTrapControls();
  }

  /**
   * 	* This function retrieves the value of a given URL parameter.
   * 
   * @param {String} sParam The name of the URL parameter to be retrieved.
   * @returns {String|Boolean} Returns the value of the URL parameter, or false if the parameter is not found.
   */
  get_url_parameter(sParam) {
    let sPageURL = window.location.search.substring(1);
    let sURLVariables = sPageURL.split('&');
    let sParameterName, i;
    for (i = 0; i < sURLVariables.length; i++) {
      sParameterName = sURLVariables[i].split('=');
      if (sParameterName[0] === sParam) {
        return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
      }
    }
    return false;
  }
  /**
   * This function toggles the code container.
   */
  codeToggle() {
    if (this.codeContainer.style.display === 'none' || this.codeContainer.style.display === '') {
      this.codeContainer.style.display = 'block';
      this.codeButton.setAttribute('aria-expanded', 'true');
    } else {
      this.codeContainer.style.display = 'none';
      this.codeButton.setAttribute('aria-expanded', 'false');
    }
  }
  /**
   * This function counts the number of issues of a given type.
   * 
   * @param {String} rule_type The type of issue to be counted.
   * @returns {Number} The number of issues of a given type.
   */
  countIssues(rule_type) {
    let count = 0;
    for (let issue of this.issues) {
      if (issue.rule_type === rule_type) {
        count++;
      }
    }
    return count;
  }

  /**
   * This function counts the number of ignored issues.
   * 
   * @returns {Number} The number of ignored issues.
   */
  countIgnored() {
    let count = 0;
    for (let issue of this.issues) {
      if (issue.ignored == 1) {
        count++;
      }
    }
    return count;
  }

  /**
   * This function shows the count of issues in the panel.
   */
  showIssueCount() {
    let errorCount = this.countIssues('error');
    let warningCount = this.countIssues('warning');
    let ignoredCount = this.countIgnored();
    let div = document.querySelector('.edac-highlight-panel-controls-summary');
    let textContent = 'No issues detected.';
    if (errorCount > 0 || warningCount > 0 || ignoredCount > 0) {
      textContent = '';
      if (errorCount > 0) {
        textContent += errorCount + ' error' + (errorCount > 1 ? 's' : '') + ', ';
      }
      if (warningCount > 0) {
        textContent += warningCount + ' warning' + (warningCount > 1 ? 's' : '') + ', ';
      }
      if (ignoredCount > 0) {
        textContent += 'and ' + ignoredCount + ' Ignored Issue' + (ignoredCount > 1 ? 's' : '') + ' detected.';
      } else {
        // Remove the trailing comma and add "detected."
        textContent = textContent.slice(0, -2) + ' detected.';
      }
    }
    div.textContent = textContent;
  }
}
window.addEventListener('DOMContentLoaded', () => {
  if (true == edac_script_vars.active) {
    new AccessibilityCheckerHighlight();
    new AccessibilityCheckerDisableHTML();
  }
});
})();

// This entry need to be wrapped in an IIFE because it need to be isolated against other entry modules.
(() => {
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin

})();

/******/ })()
;