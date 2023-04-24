
class AccessibilityCheckerDisableHTML {

	constructor() {
		this.disableStylesButton = document.querySelector('#edac-highlight-disable-styles');
		this.init();
	}

	init() {
		this.disableStylesButton.addEventListener('click', () => this.disabledStyles());
	}

	disabledStyles() {
		var css = Array.from(document.head.querySelectorAll('style[type="text/css"], style, link[rel="stylesheet"]'));
	
		// remove inline styles
		// except for elements with class starting with edac
		var elementsWithStyle = document.querySelectorAll('*[style]:not([class^="edac"])');
		elementsWithStyle.forEach(function(element) {
			element.removeAttribute("style");
		});
	
		css = css.filter(function(element) {
			console.log(element.id);
			if (element.id === 'edac-css' || element.id === 'dashicons-css') {
				return false;
			}
			return true;
		});
	
		document.head.dataset.css = css;
		css.forEach(function(element) {
			element.remove();
		});
		//alert("Styles have been disabled. To enable styles please refresh the page.");
	}
}

class AccessibilityCheckerHighlight {

	constructor() {
		this.nextButton = document.querySelector('#edac-highlight-next');
		this.previousButton = document.querySelector('#edac-highlight-previous');
		this.panelToggle = document.querySelector('#edac-highlight-panel-toggle');
		this.issues = null;
		this.currentButtonIndex = 0;
		this.descriptionTimeout;
		this.init();
		this.highlightButtonFocus();
		this.highlightButtonFocusOut();
	}

	init() {
		this.nextButton.addEventListener('click', () => this.highlightFocusNext());
		this.previousButton.addEventListener('click', () => this.highlightFocusPrevious());
		this.panelToggle.addEventListener('click', () => this.panelOpen());
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

	descriptionAjax() {
		const xhr = new XMLHttpRequest();
		const url = edac_script_vars.ajaxurl + '?action=edac_frontend_highlight_description_ajax&nonce=' + edac_script_vars.nonce;
	
		xhr.open('GET', url);
	  
		xhr.onload = function() {
			if (xhr.status === 200) {
				const response = JSON.parse(xhr.responseText);
				if (true === response.success) {
					let response_json = JSON.parse(response.data);
					console.log(response_json);
					
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
	
	
	addTooltip(element, value, index) {
		// Create tooltip HTML markup.
		const tooltipHTML = `
			<button class="edac-highlight-btn edac-highlight-btn-${value.ruletype}"
					aria-label="${value.rule_title}"
					aria-expanded="false"
					data-issue-id="${index}"
					aria-controls="edac-highlight-tooltip-${value.id}"></button>
		`;
	
		// Add the tooltip markup before the element.
		element.insertAdjacentHTML('beforebegin', tooltipHTML);
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
		const panelControls = document.querySelector('#edac-highlight-panel-controls');
		panelControls.style.display = 'block';
		this.panelToggle.style.display = 'none';
		this.highlightAjax();
		this.descriptionAjax();
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

		const description = document.querySelector('#edac-highlight-panel-description');
		const descriptionTitle = document.querySelector('.edac-highlight-panel-description-title');
		const descriptionContent = document.querySelector('.edac-highlight-panel-description-content');
		let content = this.issues[dataIssueId].summary;

		description.style.display = 'block';

		content += `<a class="edac-highlight-panel-description-reference" href="${this.issues[dataIssueId].rule_title}">Full Documentation</a>`;

		descriptionTitle.innerHTML = this.issues[dataIssueId].rule_title;
		descriptionContent.innerHTML = content;
	}

}

window.addEventListener('DOMContentLoaded', () => {
	new AccessibilityCheckerHighlight();
	new AccessibilityCheckerDisableHTML();
});