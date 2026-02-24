import { __ } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useRef, useCallback } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import { moreVertical, search, update, trash } from '@wordpress/icons';
import { STORE_NAME } from '../store/accessibility-checker-store';

const SidebarTitleMenu = ( { postId, refetchData } ) => {
	const menuRef = useRef( null );
	const { setLastFocusedIssue } = useDispatch( STORE_NAME );

	/**
	 * Close the dropdown and return focus to the toggle button.
	 *
	 * @param {Function} onClose - The dropdown's onClose callback.
	 */
	const closeAndRestoreFocus = useCallback( ( onClose ) => {
		// Clear the last focused issue so the background-refresh focus
		// restoration doesn't steal focus from the menu button.
		setLastFocusedIssue( null );

		onClose();
		// Allow the dropdown to fully close before restoring focus.
		requestAnimationFrame( () => {
			const toggleButton = menuRef.current?.querySelector( 'button' );
			if ( toggleButton ) {
				toggleButton.focus();
			}
		} );
	}, [ setLastFocusedIssue ] );

	const handleScan = () => {
		document.dispatchEvent( new CustomEvent( 'edac-scan-requested', { detail: { success: true } } ) );
	};

	const handleRefresh = () => {
		if ( postId ) {
			refetchData( postId );
		}
	};

	const handleClearIssues = async () => {
		if ( ! postId ) {
			return;
		}

		// eslint-disable-next-line no-alert -- Use a confirm dialog to match classic metabox behavior.
		if ( ! confirm( __( 'This will clear all issues for this post. A save will be required to trigger a fresh scan of the post content. Do you want to continue?', 'accessibility-checker' ) ) ) {
			return;
		}

		try {
			const response = await apiFetch( {
				path: `/accessibility-checker/v1/clear-issues/${ postId }`,
				method: 'POST',
				data: {
					id: postId,
					flush: true,
				},
			} );

			if ( response?.success ) {
				document.dispatchEvent( new Event( 'edac-cleared-issues' ) );
			}
		} catch ( error ) {
			// eslint-disable-next-line no-console
			console.warn( 'Failed to clear issues:', error?.message || error );
		}
	};

	return (
		<div ref={ menuRef } className="edac-sidebar__title-menu-wrapper">
			<DropdownMenu
				icon={ moreVertical }
				label={ __( 'Sidebar actions', 'accessibility-checker' ) }
				className="edac-sidebar__title-menu"
			>
				{ ( { onClose } ) => (
					<MenuGroup>
						<MenuItem
							icon={ search }
							onClick={ () => {
								handleScan();
								closeAndRestoreFocus( onClose );
							} }
						>
							{ __( 'Scan', 'accessibility-checker' ) }
						</MenuItem>
						<MenuItem
							icon={ update }
							onClick={ () => {
								handleRefresh();
								closeAndRestoreFocus( onClose );
							} }
						>
							{ __( 'Refresh', 'accessibility-checker' ) }
						</MenuItem>
						<MenuItem
							icon={ trash }
							onClick={ async () => {
								await handleClearIssues();
								closeAndRestoreFocus( onClose );
							} }
						>
							{ __( 'Clear Issues', 'accessibility-checker' ) }
						</MenuItem>
					</MenuGroup>
				) }
			</DropdownMenu>
		</div>
	);
};

export default SidebarTitleMenu;

