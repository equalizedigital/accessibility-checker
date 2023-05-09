
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
		this.urlParameter = this.get_url_parameter('edac');
		this.init();
	}

	init() {
		this.highlightButtonFocus();
		this.highlightButtonFocusOut();
		this.nextButton.addEventListener('click', (event) => this.highlightFocusNext());
		this.previousButton.addEventListener('click', (event) => this.highlightFocusPrevious());
		this.panelToggle.addEventListener('click', () => this.panelOpen());
		this.closePanel.addEventListener('click', () => this.panelClose());

		if(this.urlParameter){
			this.panelOpen();
		}
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

				//highlightButtons[this.currentButtonIndex].style.display = 'block';
				//element.setAttribute('aria-hidden', 'false');
				//element.setAttribute('tabindex', '0');
				//wp-block-navigation__responsive-close
				/*
				var elements = document.getElementsByClassName('wp-block-navigation__responsive-close');
				for (var i = 0; i < elements.length; i++) {
					elements[i].setAttribute('tabindex', '0');
					elements[i].style.display = 'block';
				}
				*/


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
					data-id="${value.id}"
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
				<div id="edac-highlight-panel-description-code" class="edac-highlight-panel-description-code"><code></code></div>			
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
		event.preventDefault();
		const id = this.issues[this.currentButtonIndex]['id'];
		const issueElement = document.querySelector(`[data-id="${id}"]`);
		if( issueElement ) {
			issueElement.focus();
		}
		this.description( id );
		this.currentButtonIndex = (this.currentButtonIndex + 1) % this.issues.length;

		//console.log( 'Visible: ' + this.isElementVisible(highlightButtons[this.currentButtonIndex]));
		//console.log( 'Hidden: ' + this.isElementHidden(highlightButtons[this.currentButtonIndex]));
	}
	
	highlightFocusPrevious() {
		const id = this.issues[this.currentButtonIndex]['id'];
		const issueElement = document.querySelector(`[data-id="${id}"]`);
		if( issueElement ) {
			issueElement.focus();
		}
		this.currentButtonIndex = (this.currentButtonIndex - 1 + this.issues.length) % this.issues.length;
		this.description( id );
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
		
				const dataIssueId = focusedElement.getAttribute('data-id');
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

	description( dataId ) {
		// get the value of the property by key
		const searchTerm = dataId;
		const keyToSearch = "id";
		const matchingObj = this.issues.find(obj => obj[keyToSearch] === searchTerm);
		//const value = matchingObj ? matchingObj[keyToSearch] : undefined;

		if( matchingObj ) {
			const descriptionTitle = document.querySelector('.edac-highlight-panel-description-title');
			const descriptionContent = document.querySelector('.edac-highlight-panel-description-content');
			const descriptionCode = document.querySelector('.edac-highlight-panel-description-code code');
			let content = matchingObj.summary;

			this.panelDescription.style.display = 'block';

			content += ` <br /><a class="edac-highlight-panel-description-reference" href="${matchingObj.link}">Full Documentation</a>`;

			content += `<button class="edac-highlight-panel-description-code-button" aria-expanded="false" aria-controls="edac-highlight-panel-description-code">Affected Code</button>`;

			// title and content
			descriptionTitle.innerHTML = matchingObj.rule_title;
			descriptionContent.innerHTML = content;

			// code object
			let textNode = document.createTextNode(matchingObj.object);
			descriptionCode.innerText = textNode.nodeValue;
			
			// set code button listener
			this.codeContainer = document.querySelector('.edac-highlight-panel-description-code');
			this.codeButton = document.querySelector('.edac-highlight-panel-description-code-button');
			this.codeButton.addEventListener('click', () => this.codeToggle());

			// close the code container each time the description is opened
			this.codeContainer.style.display = 'none';
		}
	}

	get_url_parameter(sParam) {
		var sPageURL = window.location.search.substring(1);
		var sURLVariables = sPageURL.split('&');
		var sParameterName, i;
		
		for (i = 0; i < sURLVariables.length; i++) {
			sParameterName = sURLVariables[i].split('=');
		
			if (sParameterName[0] === sParam) {
			return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
			}
		}
		return false;
	};

	codeToggle() {
		if (this.codeContainer.style.display === 'none' || this.codeContainer.style.display === '') {
			this.codeContainer.style.display = 'block';
			this.codeButton.setAttribute('aria-expanded', 'true');
		} else {
			this.codeContainer.style.display = 'none';
			this.codeButton.setAttribute('aria-expanded', 'false');
		}
	};

}

window.addEventListener('DOMContentLoaded', () => {
	if( true == edac_script_vars.active ) {
		new AccessibilityCheckerHighlight();
		new AccessibilityCheckerDisableHTML();
	}
});