/**
 * CodeMirror HTML Viewer Component
 *
 * Read-only CodeMirror editor for displaying HTML code snippets.
 * Requires wp.codeEditor (wp_enqueue_code_editor) to be loaded on the page.
 */

import { useRef, useEffect } from '@wordpress/element';

/**
 * CodeMirror HTML viewer component
 *
 * @param {Object} props       - Component props.
 * @param {string} props.value - HTML code to display.
 */
const CodeMirrorViewer = ( { value } ) => {
	const textareaRef = useRef( null );
	const editorRef = useRef( null );

	useEffect( () => {
		if ( ! textareaRef.current || ! window.wp?.codeEditor ) {
			return;
		}

		// Initialize CodeMirror
		const settings = window.wp.codeEditor.defaultSettings || {};
		const editorSettings = {
			...settings,
			codemirror: {
				...settings.codemirror,
				mode: 'htmlmixed',
				readOnly: true,
				lineNumbers: true,
				lineWrapping: true,
				// Disable tab handling so it doesn't capture tab key
				extraKeys: {
					Tab: false,
					'Shift-Tab': false,
				},
			},
		};

		editorRef.current = window.wp.codeEditor.initialize( textareaRef.current, editorSettings );

		// Cleanup on unmount
		return () => {
			if ( editorRef.current?.codemirror ) {
				editorRef.current.codemirror.toTextArea();
			}
		};
	}, [] );

	// Update content when value changes
	useEffect( () => {
		if ( editorRef.current?.codemirror ) {
			editorRef.current.codemirror.setValue( value || '' );
		}
	}, [ value ] );

	return (
		<textarea
			ref={ textareaRef }
			defaultValue={ value }
			className="edac-analysis__code-textarea"
		/>
	);
};

export default CodeMirrorViewer;
