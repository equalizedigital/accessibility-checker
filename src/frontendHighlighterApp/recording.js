/**
 * Recording functionality for the Accessibility Checker Highlighter
 */
export class RecordingManager {
	constructor( highlighter ) {
		this.highlighter = highlighter;
		this.isRecording = false;
		this.selectedElement = null;
		this.setupEventHandlers();
	}

	setupEventHandlers() {
		const recordButton = document.getElementById( 'edac-highlight-record' );
		recordButton.addEventListener( 'click', () => {
			// If already recording, stop recording mode
			if ( this.isRecording ) {
				this.toggleRecording( false );
			} else {
				this.toggleRecording( true );
			}
		} );
	}

	toggleRecording( start ) {
		this.isRecording = start;
		const recordButton = document.getElementById( 'edac-highlight-record' );

		if ( start ) {
			recordButton.classList.add( 'recording' );
			recordButton.textContent = 'Stop Recording';
			recordButton.setAttribute( 'aria-label', 'Stop Recording Custom Issue' );
			this.startElementHighlighting();
		} else {
			recordButton.classList.remove( 'recording' );
			recordButton.textContent = 'Record';
			recordButton.setAttribute( 'aria-label', 'Record Custom Issue' );
			this.stopElementHighlighting();
		}
	}

	startElementHighlighting() {
		document.body.addEventListener( 'mouseover', this.handleElementHover );
		document.body.addEventListener( 'mouseout', this.handleElementUnhover );
		document.body.addEventListener( 'click', this.handleElementClick );
	}

	stopElementHighlighting() {
		document.body.removeEventListener( 'mouseover', this.handleElementHover );
		document.body.removeEventListener( 'mouseout', this.handleElementUnhover );
		document.body.removeEventListener( 'click', this.handleElementClick );

		// Clean up any remaining highlighted elements
		document.querySelectorAll( '.edac-highlight-element-recording' ).forEach( ( element ) => {
			element.classList.remove( 'edac-highlight-element-recording' );
		} );
	}

	handleElementHover = ( event ) => {
		if ( ! this.isRecording ) {
			return;
		}

		const element = event.target;
		if ( element === document.body || element.closest( '.edac-highlight-panel' ) || element.closest( '.edac-highlight-custom-issue-form' ) ) {
			return;
		}

		// Remove highlight from any other elements first
		document.querySelectorAll( '.edac-highlight-element-recording' ).forEach( ( el ) => {
			if ( el !== element ) {
				el.classList.remove( 'edac-highlight-element-recording' );
			}
		} );

		element.classList.add( 'edac-highlight-element-recording' );
		event.stopPropagation();
	};

	handleElementUnhover = ( event ) => {
		if ( ! this.isRecording ) {
			return;
		}

		const element = event.target;
		element.classList.remove( 'edac-highlight-element-recording' );
		event.stopPropagation();
	};

	handleElementClick = ( event ) => {
		if ( ! this.isRecording ) {
			return;
		}

		const element = event.target;
		if ( element.closest( '.edac-highlight-panel' ) || element.closest( '.edac-highlight-custom-issue-form' ) ) {
			return;
		}

		event.preventDefault();
		event.stopPropagation();

		// Store the selected element before removing the highlight class
		this.selectedElement = element;
		element.classList.remove( 'edac-highlight-element-recording' );

		// Open the form and stop recording mode
		this.openCustomIssueForm();
	};

	openCustomIssueForm() {
		const formEl = document.getElementById( 'edac-highlight-custom-issue-form' );
		formEl.style.display = 'block';
		this.toggleRecording( false );

		const form = formEl.querySelector( 'form' );
		const submitHandler = ( e ) => {
			e.preventDefault();
			this.handleCustomIssueSubmit( e );
		};

		const closeHandler = () => {
			formEl.style.display = 'none';
			this.selectedElement = null;
			form.removeEventListener( 'submit', submitHandler );
		};

		form.addEventListener( 'submit', submitHandler );
		formEl.querySelector( '.cancel' ).addEventListener( 'click', closeHandler );
		formEl.querySelector( '.edac-highlight-panel-description-close' ).addEventListener( 'click', closeHandler );
	}

	handleCustomIssueSubmit( event ) {
		event.preventDefault();

		const formEl = event.target;
		const customIssue = {
			id: 'custom-' + Date.now(),
			rule_title: formEl.querySelector( '#issue-title' ).value,
			rule_type: formEl.querySelector( '#issue-severity' ).value,
			description: formEl.querySelector( '#issue-description' ).value,
			success_criterion: formEl.querySelector( '#issue-success-criterion' ).value,
			recommended_fix: formEl.querySelector( '#issue-recommended-fix' ).value,
			html: this.selectedElement.outerHTML,
			element: this.selectedElement,
		};

		this.highlighter.issues.push( customIssue );

		const tooltip = this.highlighter.addTooltip(
			this.selectedElement,
			customIssue,
			this.highlighter.issues.length - 1,
			this.highlighter.issues.length
		);
		customIssue.tooltip = tooltip.tooltip;

		const formContainer = document.getElementById( 'edac-highlight-custom-issue-form' );
		formContainer.style.display = 'none';
		this.selectedElement = null;

		this.highlighter.showIssue( customIssue.id );
	}
}
