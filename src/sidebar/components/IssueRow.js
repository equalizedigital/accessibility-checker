/**
 * Issue Row Component
 *
 * Displays a single issue with action menu.
 */

import { __ } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { moreVertical, seen, code, check, tool } from '@wordpress/icons';

/**
 * Single issue row with actions dropdown
 *
 * @param {Object}   props          - Component props.
 * @param {Object}   props.issue    - Issue object.
 * @param {Object}   props.rule     - Rule object (for accessing fixes, etc).
 * @param {Function} props.onAction - Action handler function.
 */
const IssueRow = ( { issue, rule, onAction } ) => {
	return (
		<li className="edac-analysis__issue-row">
			<button
				type="button"
				className="edac-analysis__issue-link"
				onClick={ () => onAction( 'details', issue ) }
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
								onAction( 'view', issue );
								onClose();
							} }
						>
							{ __( 'View on page', 'accessibility-checker' ) }
						</MenuItem>
						<MenuItem
							icon={ code }
							onClick={ () => {
								onAction( 'code', issue );
								onClose();
							} }
						>
							{ __( 'Show code', 'accessibility-checker' ) }
						</MenuItem>
						<MenuItem
							icon={ check }
							onClick={ () => {
								onAction( 'ignore', issue );
								onClose();
							} }
						>
							{ __( 'Not an Issue', 'accessibility-checker' ) }
						</MenuItem>
						{ rule?.fixes?.length > 0 && (
							<MenuItem
								icon={ tool }
								onClick={ () => {
									onAction( 'fix', issue );
									onClose();
								} }
							>
								{ __( 'Apply fix', 'accessibility-checker' ) }
							</MenuItem>
						) }
					</MenuGroup>
				) }
			</DropdownMenu>
		</li>
	);
};

export default IssueRow;
