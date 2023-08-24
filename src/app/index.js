import { computePosition, autoUpdate, shift, offset, inline } from '@floating-ui/dom';
import { createFocusTrap } from 'focus-trap';
import { isFocusable, isTabbable } from 'tabbable';
import { Notyf } from 'notyf';

import { scan } from './scanner';

let JS_SCAN_ENABLED = false;
let INFO_ENABLED = false;
let DEBUG_ENABLED = false;
let SCAN_INTERVAL_IN_SECONDS = 30;

if (edac_script_vars.mode === 'full-scan') {
	SCAN_INTERVAL_IN_SECONDS = 3;
}


class AccessibilityCheckerHighlight {

	/**
	 * Constructor
	 */
	constructor(settings = {}) {

		const defaultSettings = {
			showIgnored: false
		}

		this.settings = { ...defaultSettings, ...settings };

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
		this.panelControlsFocusTrap = createFocusTrap('#' + this.panelControls.id, {
			clickOutsideDeactivates: true,
			escapeDeactivates: () => {
				this.panelClose();
			}
		});
		this.panelDescriptionFocusTrap = createFocusTrap('#' + this.panelDescription.id, {
			clickOutsideDeactivates: true,
			escapeDeactivates: () => {
				this.descriptionClose();
			}

		});

		this.disableStylesButton = document.querySelector('#edac-highlight-disable-styles');
		this.stylesDisabled = false;
		this.originalCss = [];

		this.init();
	}

	/**
	 * This function initializes the component by setting up event listeners 
	 * and managing the initial state of the panel based on the URL parameter.
	 */
	init() {
		// Add event listeners for 'next' and 'previous' buttons
		this.nextButton.addEventListener('click', (event) => {
			this.highlightFocusNext();
			this.focusTrapDescription();
		});
		this.previousButton.addEventListener('click', (event) => {
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
			this.enableStyles();
		});

		// Close description when close button is clicked
		this.descriptionCloseButton.addEventListener('click', () => this.descriptionClose());

		// Handle disable/enable styles
		this.disableStylesButton.addEventListener('click', () => {
			if (this.stylesDisabled) {
				this.enableStyles();
			} else {
				this.disableStyles();
			}
		});



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
		let htmlToFind = value.object;
		const parser = new DOMParser();
		const parsedHtml = parser.parseFromString(htmlToFind, 'text/html');
		const firstParsedElement = parsedHtml.body.firstElementChild;
		
		if (firstParsedElement) {
			htmlToFind = firstParsedElement.outerHTML;
		}

		
		// Compare the outer HTML of the parsed element with all elements on the page
		const allElements = document.body.querySelectorAll('*');

		for (const element of allElements) {
		
			if (element.outerHTML.replace(/\W/g, '') === htmlToFind.replace(/\W/g, '')) {
		
				const tooltip = this.addTooltip(element, value, index);

				this.issues[index].tooltip = tooltip.tooltip;

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

		const self = this;
		return new Promise(function (resolve, reject) {
			const xhr = new XMLHttpRequest();
			const url = edac_script_vars.ajaxurl + '?action=edac_frontend_highlight_ajax&post_id=' + edac_script_vars.postID + '&nonce=' + edac_script_vars.nonce;

			self.showWait(true);

			xhr.open('GET', url);

			xhr.onload = function () {
				if (xhr.status === 200) {

					self.showWait(false);

					const response = JSON.parse(xhr.responseText);
					if (true === response.success) {
						const response_json = JSON.parse(response.data);

						if (self.settings.showIgnored) {
							resolve(response_json);
						} else {
							resolve(
								response_json.filter(item => (item.id == self.urlParameter || item.rule_type !== 'ignored'))
							);
						}

					} else {
						resolve([]);
						//console.log(response);
					}
				} else {

					self.showWait(false);

					info('Request failed.  Returned status of ' + xhr.status);

					reject({
						status: xhr.status,
						statusText: xhr.statusText
					});
				}
			};

			xhr.onerror = function () {

				self.showWait(false);

				reject({
					status: xhr.status,
					statusText: xhr.statusText
				});
			}

			xhr.send();
		});
	}

	/**
	 * This function toggles showing Wait
	 */
	showWait(status = true) {
		if (status) {
			document.querySelector('body').classList.add('edac-app-wait');
		} else {
			document.querySelector('body').classList.remove('edac-app-wait');
		}
	}


	/**
	 * This function removes the highlight/tooltip buttons and runs cleanups for each.
	 */
	removeHighlightButtons() {

		this.tooltips.forEach((item) => {

			//remove click listener
			item.tooltip.removeEventListener('click', item.listeners.onClick);

			//remove position/resize listener: https://floating-ui.com/docs/autoUpdate
			item.listeners.cleanup();

		});

		const buttons = document.querySelectorAll('.edac-highlight-btn');
		buttons.forEach((button) => {
			button.remove();
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

		const onClick = (e) => {
			const id = e.currentTarget.dataset.id;
			this.showIssue(id);
			this.focusTrapDescription();
		};

		tooltip.addEventListener('click', onClick);


		// Add the tooltip to the page.
		document.body.append(tooltip);

		const updatePosition = function () {

			computePosition(element, tooltip, {
				placement: 'top-start',
				middleware: [],
			}).then(({ x, y, middlewareData, placement }) => {

				const elRect = element.getBoundingClientRect();
				const elHeight = element.offsetHeight == undefined ? 0 : element.offsetHeight;
				const elWidth = element.offsetWidth == undefined ? 0 : element.offsetWidth;
				const tooltipHeight = tooltip.offsetHeight == undefined ? 0 : tooltip.offsetHeight;
				const tooltipWidth = tooltip.offsetWidth == undefined ? 0 : tooltip.offsetWidth;


				let top = 0;
				let left = 0;

				if (tooltipHeight <= (elHeight * .8)) {
					top = tooltipHeight;
				}

				if (tooltipWidth >= (elWidth * .8)) {
					top = 0;
				}

				if (elRect.left < tooltipWidth) {
					x = 0;
				}

				if (elRect.left > window.screen) {
					x = window.screen.width - tooltipWidth;
				}

				if (elRect.top < tooltipHeight) {
					y = 0;
				}

				Object.assign(tooltip.style, {
					left: `${x + left}px`,
					top: `${y + top}px`
				});

			});

		};


		// Place the tooltip at the element's position on the page.
		// See: https://floating-ui.com/docs/autoUpdate	
		const cleanup = autoUpdate(
			element,
			tooltip,
			updatePosition, {
			ancestorScroll: true,
			ancestorResize: true,
			elementResize: true,
			layoutShift: true,
			animationFrame: true 	// TODO: Disable styles sometimes causes the toolbar to disappear until a scroll or resize event. This may help - but is expensive.


		}
		);


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
			<button id="edac-highlight-panel-toggle" class="edac-highlight-panel-toggle" aria-haspopup="dialog">Accessibility Checker Tools</button>
			<div id="edac-highlight-panel-description" class="edac-highlight-panel-description" role="dialog" aria-labelledby="edac-highlight-panel-description-title" tabindex="0">
			<button id="edac-highlight-panel-controls-close" class="edac-highlight-panel-controls-close" aria-label="Close">×</button>
				<div class="edac-highlight-panel-description-title"></div>
				<div class="edac-highlight-panel-description-content"></div>
				<div id="edac-highlight-panel-description-code" class="edac-highlight-panel-description-code"><code></code></div>			
			</div>
			<div id="edac-highlight-panel-controls" class="edac-highlight-panel-controls" tabindex="0">
				<button id="edac-highlight-panel-controls-close" class="edac-highlight-panel-controls-close" aria-label="Close">×</button>
				<div class="edac-highlight-panel-controls-title">Accessibility Checker</div>
				<div class="edac-highlight-panel-controls-summary">Loading...</div>
				<div class="edac-highlight-panel-controls-buttons">
					<div>
						<button id="edac-highlight-previous"><span aria-hidden="true">« </span>Previous</button>
						<button id="edac-highlight-next">Next<span aria-hidden="true"> »</span></button><br />
					</div>
					<div>
						<button id="edac-highlight-disable-styles" class="edac-highlight-disable-styles" aria-live="polite">Disable Styles</button>
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
	}


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
	}

	/**
	 * This function sets a focus trap on the controls panel
	 */
	focusTrapControls = () => {
		this.panelDescriptionFocusTrap.deactivate();
		this.panelControlsFocusTrap.activate();

		setTimeout(() => {
			this.panelControls.focus();
		}, 100); //give render time to complete.	

	}

	/**
	 * This function sets a focus trap on the description panel
	 */
	focusTrapDescription = () => {
		this.panelControlsFocusTrap.deactivate();
		this.panelDescriptionFocusTrap.activate();

		setTimeout(() => {
			this.panelDescription.focus();
		}, 100); //give render time to complete.

	}

	/**
	 * This function shows an issue related to an element.
	 * @param {string} id - The ID of the element.
	 */

	showIssue = (id) => {

		this.removeSelectedClasses();

		if (id === undefined) {
			return;
		}

		const issue = this.issues.find(issue => issue.id == id);
		this.currentButtonIndex = this.issues.findIndex(issue => issue.id == id);

		const tooltip = issue.tooltip;
		const element = issue.element;

		if (tooltip && element) {

			tooltip.classList.add('edac-highlight-btn-selected');
			element.classList.add('edac-highlight-element-selected');

			if (element.offsetWidth < 20) {
				element.classList.add('edac-highlight-element-selected-min-width');
			}

			if (element.offsetHeight < 5) {
				element.classList.add('edac-highlight-element-selected-min-height');
			}

			element.scrollIntoView({ block: 'center' });

			if (isFocusable(tooltip)) {
				//issueElement.focus();

				if (!this.checkVisibility(tooltip) || !this.checkVisibility(element)) {
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
	}


	/**
	 * This function checks if a given element is visible on the page.
	 * 
	 * @param {HTMLElement} el The element to check for visibility
	 * @returns 
	 */
	checkVisibility = (el) => {
		//checkVisibility is still in draft but well supported on many browsers.
		//See: https://drafts.csswg.org/cssom-view-1/#dom-element-checkvisibility
		//See: https://caniuse.com/mdn-api_element_checkvisibility
		if (typeof (el.checkVisibility) !== 'function') {

			//See: https://github.com/jquery/jquery/blob/main/src/css/hiddenVisibleSelectors.js
			return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);

		} else {
			return el.checkVisibility({
				checkOpacity: true,      // Check CSS opacity property too
				checkVisibilityCSS: true // Check CSS visibility property too
			});
		}
	}


	/**
	 * This function opens the accessibility checker panel.
	 */
	panelOpen(id) {

		this.panelControls.style.display = 'block';
		this.panelToggle.style.display = 'none';

		// Get the issues for this page.
		this.highlightAjax().then(
			(json) => {


				this.issues = json;

				json.forEach(function (value, index) {

					const element = this.findElement(value, index);
					if (element !== null) {
						this.issues[index].element = element;
					}

				}.bind(this));


				this.showIssueCount();

				if (id !== undefined) {

					this.showIssue(id);
					this.focusTrapDescription();

				}
			}
		).catch((err) => {
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
		selectedButtons.forEach((selectedButton) => {
			selectedButton.classList.remove('edac-highlight-btn-selected');
		});
		//remove selected class from previously selected elements
		const selectedElements = document.querySelectorAll('.edac-highlight-element-selected');
		selectedElements.forEach((selectedElement) => {
			selectedElement.classList.remove(
				'edac-highlight-element-selected',
				'edac-highlight-element-selected-min-width',
				'edac-highlight-element-selected-min-height'
			);

			if (selectedElement.classList.length == 0) {
				selectedElement.removeAttribute('class');
			}
		});
	}

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
	};

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
	};


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
			if (errorCount >= 0) {
				textContent += errorCount + ' error' + (errorCount == 1 ? '' : 's') + ', ';
			}
			if (warningCount >= 0) {
				textContent += warningCount + ' warning' + (warningCount == 1 ? '' : 's') + ', ';
			}
			if (ignoredCount >= 0) {
				textContent += 'and ' + ignoredCount + ' ignored issue' + (ignoredCount == 1 ? '' : 's') + ' detected.';
			} else {
				// Remove the trailing comma and add "detected."
				textContent = textContent.slice(0, -2) + ' detected.';
			}
		}

		div.textContent = textContent;
	}

}


if (window.top._scheduledScanRunning == undefined) {
	window.top._scheduledScanRunning = false;
	window.top._scheduledScanCurrentPost = false;
}



async function checkApi() {
	
	if (edac_script_vars.edacHeaders.Authorization == 'None') {
		return 401;
	}

	const response = await fetch(edac_script_vars.edacApiUrl + '/test', {
		method: "POST",
		headers: edac_script_vars.edacHeaders
	});

	return response.status;

}


async function postData(url = "", data = {}) {


	if (edac_script_vars.edacHeaders.Authorization == 'None') {
		return;
	}

	return await fetch(url, {
		method: "POST",
		headers: edac_script_vars.edacHeaders,
		body: JSON.stringify(data),
	}).then((res) => {
		return res.json();
	}).catch(() => {
		return {};
	});

}

async function getData(url = "") {

	if (edac_script_vars.edacHeaders.Authorization == 'None') {
		return {};
	}

	return await fetch(url, {
		method: "GET",
		headers: edac_script_vars.edacHeaders
	}).then((res) => {
		return res.json();
	}).catch(() => {
		return {};
	});

}

function info(message) {
	if (INFO_ENABLED) {
		console.info(message);
	}
}


function debug(message) {

	if (DEBUG_ENABLED) {

		if (location.href !== window.top.location.href) {
			console.debug('DEBUG [ ' + location.href + ' ]');
		}
		if (typeof message !== 'object') {
			console.debug('DEBUG: ' + message);
		} else {
			console.debug(message);
		}
	}
}

function saveScanResults(postId, violations, scheduled = false) {

	// Confirm api service is working.
	checkApi().then((status) => {


		if (status >= 400) {
			if (status == 401 && edac_script_vars.edacpApiUrl == '') {

				showNotice({
					msg: ' Whoops! It looks like your website is currently password protected. The free version of Accessibility Checker can only scan live websites. To scan this website for accessibility problems either remove the password protection or {link}. Scan results may be stored from a previous scan.',
					type: 'warning',
					url: 'https://equalizedigital.com/accessibility-checker/pricing/',
					label: 'upgrade to Accessibility Checker Pro',
					closeOthers: true
				});

				debug('Error: Password protected scans are not supported in the free version.');
			} else if (status == 401 && edac_script_vars.edacpApiUrl != '') {
				showNotice({
					msg: 'Whoops! It looks like your website is currently password protected. To scan this website for accessibility problems {link}.',
					type: 'warning',
					url: '/wp-admin/admin.php?page=accessibility_checker_settings',
					label: 'add your username and password to your Accessibility Checker Pro settings',
					closeOthers: true
				});

				debug('Error: Password protected scan in Pro, but password is not correct.');
			} else {
				showNotice({
					msg: 'Whoops! It looks like there was a problem connecting to the {link} which is required by Accessibility Checker.',
					type: 'warning',
					url: 'https://developer.wordpress.org/rest-api/frequently-asked-questions',
					label: 'Rest API',
					closeOthers: true
				});

				debug('Error: Cannot connect to API. Status code is: ' + status);
			}

		} else {

			info('Saving ' + postId + ': started');

			// Api is fine so we can send the scan results.
			postData(edac_script_vars.edacApiUrl + '/post-scan-results/' + postId, {
				violations: violations
			}).then((data) => {

				debug(data);

				info('Saving ' + postId + ': done');



				if (!data.success) {

					info('Saving ' + postId + ': error');

					showNotice({
						msg: 'Whoops! It looks like there was a problem updating. Please try again.',
						type: 'warning'
					});

				}

				if (scheduled) {
					debug('_scheduledScanRunning: false');

					window.top._scheduledScanRunning = false;
				};


			});

		};

	}).catch((error) => {
		info('Saving ' + postId + ': error');

		debug(error);
		showNotice({
			msg: 'Whoops! It looks like there was a problem updating. Please try again.',
			type: 'warning'
		});

	});

}

//TODO: see also https://developer.mozilla.org/en-US/docs/Web/API/BroadcastChannel
window.addEventListener(
	"message",
	(e) => {


		if (e.origin !== edac_script_vars.edacUrl) return;

		if (window === window.top) {

			//There has been a request to start a scan. Pass the message to the scanner's window.
			if (e.data && e.data.sender === 'edac_start_scan') {
				var scanner = document.getElementById('edac_scanner');
				var scannerWindow = scanner.contentWindow;
				scannerWindow.postMessage({
					'sender': 'edac_start_scan',
					'message': e.data.message
				});

			}

			//There has been a request to start a scheduled scan. Pass the message to the scanner's window.
			if (e.data && e.data.sender === 'edac_start_scheduled_scan') {
				var scheduledScanner = document.getElementById('edacp_scheduled_scanner');
				var scheduledScannerWindow = scheduledScanner.contentWindow;
				scheduledScannerWindow.postMessage({
					'sender': 'edac_start_scheduled_scan',
					'message': e.data.message
				});

			}

			//There has been a request to save the scan.
			if (e.data && e.data.sender === 'edac_save_scan') {

				saveScanResults(e.data.message.postId, e.data.message.violations, e.data.message.violations);

			}

		} else {

			if (e.data && e.data.sender === 'edac_start_scan') {
				const postId = e.data.message.postId;

				// We are running a scan in the iframe. We need to send the results
				// back to the top window so we can use that cookie to authenticate the rest post.
				// See: https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/

				info('Scan ' + postId + ': started');


				scan().then((results) => {

					info('Scan ' + postId + ': done');

					let violations = JSON.parse(JSON.stringify(results.violations));

					window.top.postMessage({
						'sender': 'edac_save_scan',
						'message': {
							postId: postId,
							violations: violations,
							scheduled: false
						}
					});


				});

			}



			if (e.data && e.data.sender === 'edac_start_scheduled_scan') {

				// We are running a scheduled scan in the iframe. We need to send the results
				// back to the top window so we can use that cookie to authenticate the rest post.
				// See: https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/

				const postId = e.data.message.postId;

				window.top._scheduledScanRunning = true;

				info("Scheduled scan: started " + postId);
				debug('_scheduledScanRunning: true');

				scan().then((results) => {


					info("Scheduled scan: done " + postId);

					let violations = JSON.parse(JSON.stringify(results.violations));

					window.top.postMessage({
						'sender': 'edac_save_scan',
						'message': {
							postId: postId,
							violations: violations,
							scheduled: true
						}
					});


				});

			}

		}

	},
	false,
);


window.addEventListener('DOMContentLoaded', () => {

	debug('We are loading the app in ' + edac_script_vars.mode + ' mode.');

	if(JS_SCAN_ENABLED){
	if (edac_script_vars.mode === 'editor-scan') {

		debug('App is loading from within the editor.');

		// We are loading the app from within the editor (rather than the page preview).			
		// Create an iframe in the editor for loading the page preview.
		// The page preview's url has an ?edac-action=scan, which tells the app 
		// loaded in the iframe to: 1) run the js scan, 2) post the results.
		const iframe = document.createElement('iframe');
		iframe.setAttribute('id', 'edac_scanner');
		iframe.setAttribute('src', edac_script_vars.scanUrl);
		iframe.style.width = screen.width + 'px';
		iframe.style.height = screen.height + 'px';
		iframe.style.position = 'absolute';
		iframe.style.left = '-' + screen.width + 'px';
		document.body.append(iframe);

		iframe.addEventListener("load", function (e) {

			debug('Scan iframe loaded.');

			// The frame has loaded the preview page, so post the message that fires the iframe scan and save.			
			window.postMessage({
				'sender': 'edac_start_scan',
				'message': {
					postId: edac_script_vars.postID
				}
			});

		});


		//Listen for dispatches from the wp data store
		let saving = false;
		if (wp.data !== undefined && wp.data.subscribe !== undefined) {
			wp.data.subscribe(() => {

				// Rescan the page if user saves post
				if (wp.data.select('core/editor').isSavingPost()) {
					saving = true;
				} else {
					if (saving) {
						saving = false;

						checkApi().then((status) => {
							if (status == 401 && edac_script_vars.edacpApiUrl == '') {

								showNotice({
									msg: ' Whoops! It looks like your website is currently password protected. The free version of Accessibility Checker can only scan live websites. To scan this website for accessibility problems either remove the password protection or {link}. Scan results may be stored from a previous scan.',
									type: 'warning',
									url: 'https://equalizedigital.com/accessibility-checker/pricing/',
									label: 'Upgrade to Accessibility Checker Pro',
									closeOthers: true
								});

								debug('Password protected scans are not supported on the free version.')
							} else {
								debug('Loading scan iframe: ' + edac_script_vars.scanUrl);
								iframe.setAttribute('src', edac_script_vars.scanUrl);
							}
						});


					}
				}

			});

		} else {
			debug("Gutenberg is not enabled.");
		}

	}


	if (
		(edac_script_vars.mode === 'editor-scan' && edac_script_vars.edacpApiUrl != '') || //&& edac_script_vars.pendingFullScan) ||
		(edac_script_vars.mode === 'full-scan')
	) {


		debug('App is loading either from the editor page or from the scheduled full scan page.');

		// Create an iframe in the editor for loading the page preview for the scheduled scans.
		const iframeScheduledScanner = document.createElement('iframe');
		iframeScheduledScanner.setAttribute('id', 'edacp_scheduled_scanner');
		iframeScheduledScanner.style.width = screen.width + 'px';
		iframeScheduledScanner.style.height = screen.height + 'px';
		iframeScheduledScanner.style.position = 'absolute';
		iframeScheduledScanner.style.left = '-' + screen.width + 'px';

		const onLoadIframeScheduledScanner = function (e) {
			debug('Loading scheduled scan iframe: done');

			var data = e.currentTarget.data;

			// The frame has loaded the preview page, so post the message that fires the iframe scan and save.
			window.postMessage({
				'sender': 'edac_start_scheduled_scan',
				'message': data
			});

		};
		iframeScheduledScanner.addEventListener('load', onLoadIframeScheduledScanner, false);

		document.body.append(iframeScheduledScanner);

		let scanInterval = setInterval(() => {


			if (!window.top._scheduledScanRunning) {

				debug('Polling to see if there are any scans pending.');


				// Poll to see if there are any scans pending.
				getData(edac_script_vars.edacpApiUrl + '/scheduled-scan-url')
					.then((data) => {


						if (data.code !== 'rest_no_route') {

							if (data.data !== undefined) {

								if (data.data.scanUrl !== undefined) {

									info('A post needs scanning: ' + data.data.scanUrl);
									debug(data);

									//set the data so we can pass it to the onload handler
									iframeScheduledScanner.data = data.data;


									// We have the url of the next in line to be scanned so pass to the iframe.
									iframeScheduledScanner.setAttribute('src', data.data.scanUrl);


								}

							}

						} else {

							info('There was a problem connecting to the API.');

							window.top._scheduledScanRunning = false;

							debug('_scheduledScanRunning: false');

						}
					});

			} else {
				debug('Waiting for previous scan to complete.');
			}

		}, SCAN_INTERVAL_IN_SECONDS * 1000);



	}
	}
	if (edac_script_vars.mode === 'ui' && edac_script_vars.active) {

		// We are loading the app in a normal page preview so show the user the ui
		new AccessibilityCheckerHighlight();

	}






});


if (window.top === window && window._showNotice === undefined) {

	var link = document.createElement("link");
	link.href = 'https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css';
	link.type = "text/css";
	link.rel = "stylesheet";
	link.media = "screen,print";
	document.getElementsByTagName("head")[0].appendChild(link);

	window._showNotice = function (options) {

		const settings = Object.assign({}, {
			msg: '',
			type: 'warning',
			url: false,
			label: '',
			closeOthers: true
		}, options);


		if (window.wp !== undefined && window.wp.data !== undefined && window.wp.data.dispatch !== undefined) {

			var o = { isDismissible: true };

			var msg = settings.msg;

			if (settings.url) {
				o.actions = [{
					url: settings.url,
					label: settings.label
				}];

				msg = msg.replace('{link}', settings.label);
			} else {
				msg = msg.replace('{link}', '');
			}

			if (settings.closeOthers) {
				document.querySelectorAll('.components-notice').forEach((element) => {
					element.style.display = 'none';
				});
			}

			setTimeout(function () {
				wp.data.dispatch("core/notices").createNotice(settings.type, msg, o);
			}, 10);





		} else {

			//TODO: do we need to show notices on preview pages? If not we can remove this section and Notyf.

			var msg = settings.msg;

			if (settings.url) {
				msg = msg.replace('{link}', '<a href="' + settings.url + '" target="_blank" arial-label="' + settings.label + '">' + settings.label + '</a>');
			} else {
				msg = msg.replace('{link}', '');
			}

			const notyf = new Notyf({
				position: { x: 'left', y: 'top' },
				ripple: false,
				types: [
					{
						type: 'success',
						background: '#eff9f1',
						duration: 2000,
						dismissible: true,
						icon: false
					},

					{
						type: 'warning',
						background: '#fef8ee',
						duration: 0,
						dismissible: true,
						icon: false
					},
					{
						type: 'error',
						background: '#f4a2a2',
						duration: 0,
						dismissible: true,
						icon: false
					}
				]
			});

			if (settings.closeOthers) {
				notyf.dismissAll();
			}

			const notification = notyf.open({
				type: settings.type,
				message: msg
			});


		}



	}

}


function showNotice(options) {
	window.top._showNotice(options);
}
