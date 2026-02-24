import { __ } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { moreVertical, search, update, trash } from '@wordpress/icons';

const SidebarTitleMenu = ( { postId, refetchData } ) => {
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
							onClose();
						} }
					>
						{ __( 'Scan', 'accessibility-checker' ) }
					</MenuItem>
					<MenuItem
						icon={ update }
						onClick={ () => {
							handleRefresh();
							onClose();
						} }
					>
						{ __( 'Refresh', 'accessibility-checker' ) }
					</MenuItem>
					<MenuItem
						icon={ trash }
						onClick={ async () => {
							await handleClearIssues();
							onClose();
						} }
					>
						{ __( 'Clear Issues', 'accessibility-checker' ) }
					</MenuItem>
				</MenuGroup>
			) }
		</DropdownMenu>
	);
};

export default SidebarTitleMenu;

