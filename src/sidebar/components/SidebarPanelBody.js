/**
 * Sidebar Panel Body
 *
 * Wraps the core PanelBody so its accordion heading is a level 3 heading.
 * PanelBody hard-codes an H2, which puts every accordion at the same level as
 * the sidebar title. PanelBody has no heading level prop, so the level is set
 * on the rendered heading with aria-level.
 */

import { PanelBody } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';

export const SIDEBAR_PANEL_HEADING_LEVEL = '3';

/**
 * Panel body whose heading sits below the sidebar title.
 *
 * @param {Object} props - Props passed through to PanelBody.
 */
const SidebarPanelBody = ( props ) => {
	const ref = useRef( null );

	// Run after every render in case PanelBody re-creates its heading.
	useEffect( () => {
		const heading = ref.current?.querySelector( '.components-panel__body-title' );
		if ( heading && heading.getAttribute( 'aria-level' ) !== SIDEBAR_PANEL_HEADING_LEVEL ) {
			heading.setAttribute( 'aria-level', SIDEBAR_PANEL_HEADING_LEVEL );
		}
	} );

	return <PanelBody ref={ ref } { ...props } />;
};

export default SidebarPanelBody;
