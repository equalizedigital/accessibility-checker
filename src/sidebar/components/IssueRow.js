/**
 * Issue Row Component
 *
 * Displays a single issue with action menu.
 */

import { __ } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { moreVertical, seen, code, check, tool } from '@wordpress/icons';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Get the "View on page" URL for an issue
 *
 * @param {Object} issue    - The issue object containing id.
 * @param {string} viewLink - The permalink or preview link.
 * @return {string|null} The URL to view the issue on the frontend.
 */
const getViewOnPageUrl = ( issue, viewLink ) => {
	const { highlightNonce } = window.edac_sidebar_app || {};

	if ( ! viewLink ) {
		return null;
	}

	const url = new URL( viewLink );
	url.searchParams.set( 'edac', issue.id );
	if ( highlightNonce ) {
		url.searchParams.set( 'edac_nonce', highlightNonce );
	}

	return url.toString();
};

/**
 * Single issue row with actions dropdown
 *
 * @param {Object} props       - Component props.
 * @param {Object} props.issue - Issue object.
 * @param {Object} props.rule  - Rule object (optional, for context).
 */
const IssueRow = ( { issue, rule } ) => {
	// Get the appropriate view link from the editor store
	const viewLink = useSelect( ( select ) => {
		const { getEditedPostPreviewLink, getPermalink, isCurrentPostPublished } = select( editorStore );
		return isCurrentPostPublished() ? getPermalink() : getEditedPostPreviewLink();
	}, [] );

	const handleAction = ( action ) => {
		// Handle the 'view' action to open the issue on the frontend
		if ( action === 'view' ) {
			const url = getViewOnPageUrl( issue, viewLink );
			if ( url ) {
				window.open( url, '_blank', 'noopener,noreferrer' );
			}
			return;
		}

		const openIssueModal = ( focusSection = null ) => {
			if ( window.edacIssueModal?.open ) {
				window.edacIssueModal.open( {
					issue,
					rule,
					focusSection,
				} );
			}
		};

		// Handle the 'details' action that will open a modal
		if ( action === 'details' ) {
			openIssueModal( null );
			return;
		}

		// Handle the 'code' action to open modal with code section focused
		if ( action === 'code' ) {
			openIssueModal( 'code' );
			return;
		}

		// Handle the 'ignore' action to open modal with dismiss section focused
		if ( action === 'ignore' ) {
			openIssueModal( 'dismiss' );
			return;
		}

		// eslint-disable-next-line no-console
		console.log( `Action: ${ action }`, issue );
		// TODO: Implement remaining actions (fix)
	};

	return (
		<li className="edac-analysis__issue-row">
			<button
				type="button"
				className="edac-analysis__issue-link"
				onClick={ () => handleAction( 'details' ) }
			>
				{ __( 'Issue', 'accessibility-checker' ) } #{ issue.id }
			</button>
			<DropdownMenu
				icon={ moreVertical }
				label={ __( 'Issue actions', 'accessibility-checker' ) }
				className="edac-analysis__issue-menu"
			>
				{ ( { onClose } ) => (
					<MenuGroup>
						<MenuItem
							icon={ seen }
							onClick={ () => {
								handleAction( 'view' );
								onClose();
							} }
						>
							{ __( 'View on page', 'accessibility-checker' ) }
						</MenuItem>
						<MenuItem
							icon={ code }
							onClick={ () => {
								handleAction( 'code' );
								onClose();
							} }
						>
							{ __( 'Show code', 'accessibility-checker' ) }
						</MenuItem>
						<MenuItem
							icon={ check }
							onClick={ () => {
								handleAction( 'ignore' );
								onClose();
							} }
						>
							{ __( 'Not an Issue', 'accessibility-checker' ) }
						</MenuItem>
						<MenuItem
							icon={ tool }
							onClick={ () => {
								handleAction( 'fix' );
								onClose();
							} }
						>
							{ __( 'Apply fix', 'accessibility-checker' ) }
						</MenuItem>
					</MenuGroup>
				) }
			</DropdownMenu>
		</li>
	);
};

export default IssueRow;
