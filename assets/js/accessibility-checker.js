
class AccessibilityCheckerDisableHTML {
	constructor() {
		this.disableStylesButton = document.querySelector('#edac-highlight-disable-styles');
		this.closePanel = document.querySelector('#edac-highlight-panel-close');
		this.stylesDisabled = false;
		this.originalCss = [];
		this.init();
	}

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

	disableStyles() {
		this.originalCss = Array.from(document.head.querySelectorAll('style[type="text/css"], style, link[rel="stylesheet"]'));

		var elementsWithStyle = document.querySelectorAll('*[style]:not([class^="edac"])');
		elementsWithStyle.forEach(function (element) {
			element.removeAttribute("style");
		});

		this.originalCss = this.originalCss.filter(function (element) {
			if (element.id === 'edac-css' || element.id === 'dashicons-css') {
				return false;
			}
			return true;
		});

		document.head.dataset.css = this.originalCss;
		this.originalCss.forEach(function (element) {
			element.remove();
		});
		this.stylesDisabled = true;
		this.disableStylesButton.textContent = "Enable Styles";
		//alert("Styles have been disabled. Click the button again to enable styles.");
	}

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
		this.stylesDisabled = false;
		this.disableStylesButton.textContent = "Disable Styles";
		//alert("Styles have been enabled.");
	}
}


class AccessibilityCheckerHighlight {

	constructor() {
		this.addHighlightPanel();
		this.nextButton = document.querySelector('#edac-highlight-next');
		this.previousButton = document.querySelector('#edac-highlight-previous');
		this.panelToggle = document.querySelector('#edac-highlight-panel-toggle');
		this.closePanel = document.querySelector('#edac-highlight-panel-close');
		this.panelDescription = document.querySelector('#edac-highlight-panel-description');
		this.panelControls = document.querySelector('#edac-highlight-panel-controls');
		this.issues = null;
		this.currentButtonIndex = 0;
		this.descriptionTimeout;
		this.init();
	}

	init() {
		this.highlightButtonFocus();
		this.highlightButtonFocusOut();
		this.nextButton.addEventListener('click', () => this.highlightFocusNext());
		this.previousButton.addEventListener('click', () => this.highlightFocusPrevious());
		this.panelToggle.addEventListener('click', () => this.panelOpen());
		this.closePanel.addEventListener('click', () => this.panelClose());
	}

	findElement(value, index) {
	
		// Parse the HTML snippet
		const htmlSnippet = value.object;
		const parser = new DOMParser();
		const parsedHtml = parser.parseFromString(htmlSnippet, 'text/html');
		console.log(parsedHtml);
		const firstParsedElement = parsedHtml.body.firstElementChild;
	
		// If there's no parsed element, return null
		if (!firstParsedElement) {
			return null;
		}
	
		// Compare the outer HTML of the parsed element with all elements on the page
		//const allElements = document.querySelectorAll('*');
		//const allElements = [document.documentElement].concat(Array.from(document.querySelectorAll('*')));
		const allElements = document.body.querySelectorAll('*');
	
		for (const element of allElements) {
	
			if (element.outerHTML === firstParsedElement.outerHTML) {
				
				// Add a solid red 5px border to the matched element
				//element.style.border = '5px solid red';
				this.wrapElement(element, value);
				this.addTooltip(element, value, index);
				//element.setAttribute('aria-hidden', 'false');
				return element;
			}
		}
	
		// If no matching element is found, return null
		return null;
	}

	highlightAjax() {
		const xhr = new XMLHttpRequest();
		const url = edac_script_vars.ajaxurl + '?action=edac_frontend_highlight_ajax&post_id=' + edac_script_vars.postID + '&nonce=' + edac_script_vars.nonce;
	
		xhr.open('GET', url);
	  
		xhr.onload = function() {
			if (xhr.status === 200) {
				const response = JSON.parse(xhr.responseText);
				if (true === response.success) {
					let response_json = JSON.parse(response.data);
					console.log(response_json);
					this.issues = response_json;
					response_json.forEach(function(value, index) {
						//console.log(value.object);
						const matchedElement = this.findElement(value, index);
						console.log(matchedElement);
					}.bind(this));
				} else {
					console.log(response);
				}
			} else {
				console.log('Request failed.  Returned status of ' + xhr.status);
			}
		}.bind(this);
		xhr.send();
	}

	wrapElement(element, value) {
		const parent = element.parentNode;
		const wrapper = document.createElement('div');
		wrapper.className = `edac-highlight edac-highlight-${value.rule_type}`;
		parent.insertBefore(wrapper, element);
		wrapper.appendChild(element);
	}

	unwrapElements() {
		const elements = document.querySelectorAll('.edac-highlight');
		
		for (let i = 0; i < elements.length; i++) {
			const element = elements[i];
			const parent = element.parentNode;
			const wrapper = parent.parentNode;
		
			if (wrapper.tagName === 'DIV' && wrapper.classList.contains('edac-highlight')) {
			parent.removeChild(element);
			wrapper.parentNode.insertBefore(element, wrapper);
			wrapper.parentNode.removeChild(wrapper);
			}
		}
	}

	removeHighlightButtons() {
		const elements = document.querySelectorAll('.edac-highlight-btn');
		
		for (let i = 0; i < elements.length; i++) {
			elements[i].remove();
		}
	}
	
	addTooltip(element, value, index) {
		// Create tooltip HTML markup.
		const tooltipHTML = `
			<button class="edac-highlight-btn edac-highlight-btn-${value.rule_type}"
					aria-label="${value.rule_title}"
					aria-expanded="false"
					data-issue-id="${index}"
					aria-controls="edac-highlight-tooltip-${value.id}"></button>
		`;
	
		// Add the tooltip markup before the element.
		element.insertAdjacentHTML('beforebegin', tooltipHTML);
	}

	addHighlightPanel() {
		const newElement = `
			<div class="edac-highlight-panel">
			<button id="edac-highlight-panel-toggle" class="edac-highlight-panel-toggle" title="Toggle accessibility tools"></button>
			<div id="edac-highlight-panel-description" class="edac-highlight-panel-description">
				<button class="edac-highlight-panel-description-close">Close</button>
				<div class="edac-highlight-panel-description-title"></div>
				<div class="edac-highlight-panel-description-content"></div>			
			</div>
			<div id="edac-highlight-panel-controls" class="edac-highlight-panel-controls">					
				<button id="edac-highlight-panel-close" class="edac-highlight-panel-close" aria-label="Close accessibility highlights panel">Close</button><br />
				<button id="edac-highlight-previous"><span aria-hidden="true">« </span>previous</button>
				<button id="edac-highlight-next">Next<span aria-hidden="true"> »</span></button><br />
				<button id="edac-highlight-disable-styles">Disable Styles</button>
			</div>
			</div>
		`;
		
		document.body.insertAdjacentHTML('afterbegin', newElement);
	}

	highlightFocusNext() {
		const highlightButtons = document.querySelectorAll('.edac-highlight-btn');
		this.currentButtonIndex = (this.currentButtonIndex + 1) % highlightButtons.length;
		highlightButtons[this.currentButtonIndex].focus();
		console.log( 'Visible: ' + this.isElementVisible(highlightButtons[this.currentButtonIndex]));
		console.log( 'Hidden: ' + this.isElementHidden(highlightButtons[this.currentButtonIndex]));
	}
	
	highlightFocusPrevious() {
		const highlightButtons = document.querySelectorAll('.edac-highlight-btn');
		this.currentButtonIndex = (this.currentButtonIndex - 1 + highlightButtons.length) % highlightButtons.length;
		highlightButtons[this.currentButtonIndex].focus();
	}

	isElementVisible(el) {
		const rect = el.getBoundingClientRect();
		const windowHeight =
		window.innerHeight || document.documentElement.clientHeight;
		const windowWidth =
		window.innerWidth || document.documentElement.clientWidth;
	
		return (
		rect.top >= 0 &&
		rect.left >= 0 &&
		rect.bottom <= windowHeight &&
		rect.right <= windowWidth
		);
	}

	isElementHidden(el) {
		const style = window.getComputedStyle(el);
		return style.display === 'none';
	}

	panelOpen() {
		this.panelControls.style.display = 'block';
		this.panelToggle.style.display = 'none';
		this.highlightAjax();
	}

	panelClose() {
		this.panelControls.style.display = 'none';
		this.panelDescription.style.display = 'none';
		this.panelToggle.style.display = 'block';
		this.unwrapElements();
		this.removeHighlightButtons();
	}

	highlightButtonFocus() {
		document.addEventListener('focusin', (event) => {
			const focusedElement = event.target;
			if (focusedElement.classList.contains('edac-highlight-btn')) {
			const highlightParent = focusedElement.closest('.edac-highlight');
			if (highlightParent) {
				highlightParent.classList.add('active');
				//focusedElement.scrollIntoView();
		
				const dataIssueId = focusedElement.getAttribute('data-issue-id');
				this.description( dataIssueId );
				
				this.cancelDescriptionTimeout();
			}
			}
		});
	}

	highlightButtonFocusOut() {
		document.addEventListener('focusout', (event) => {
			const unfocusedElement = event.target;
			if (unfocusedElement.classList.contains('edac-highlight-btn')) {
				const highlightParent = unfocusedElement.closest('.edac-highlight');
				if (highlightParent) {
					highlightParent.classList.remove('active');
					const description = document.querySelector('#edac-highlight-panel-description');
					this.descriptionTimeout = setTimeout(function() {
						description.style.display = 'none';
					}, 500); // 1000 milliseconds (1 second) delay
				}
			}
		});
	}

	cancelDescriptionTimeout() {
		clearTimeout(this.descriptionTimeout);
	}

	description( dataIssueId ) {

		
		const descriptionTitle = document.querySelector('.edac-highlight-panel-description-title');
		const descriptionContent = document.querySelector('.edac-highlight-panel-description-content');
		let content = this.issues[dataIssueId].summary;

		this.panelDescription.style.display = 'block';

		content += ` <br /><a class="edac-highlight-panel-description-reference" href="${this.issues[dataIssueId].link}">Full Documentation</a>`;

		descriptionTitle.innerHTML = this.issues[dataIssueId].rule_title;
		descriptionContent.innerHTML = content;
	}

}

window.addEventListener('DOMContentLoaded', () => {
	if( true == edac_script_vars.active ) {
		new AccessibilityCheckerHighlight();
		new AccessibilityCheckerDisableHTML();
	}
});