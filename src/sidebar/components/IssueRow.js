/**
 * Issue Row Component
 *
 * Displays a single issue with action menu.
 */

import { __, sprintf } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { moreVertical, seen, code, check, tool } from '@wordpress/icons';
import Badge from './Badge';
import { getDismissReasonLabel } from '../utils/dismissHelpers';

/**
 * Single issue row with actions dropdown
 *
 * @param {Object}   props             - Component props.
 * @param {Object}   props.issue       - Issue object.
 * @param {Object}   props.rule        - Rule object (for accessing fixes, etc).
 * @param {Function} props.onAction    - Action handler function.
 * @param {boolean}  props.showIgnored - Whether showing dismissed issues.
 */
const IssueRow = ( { issue, rule, onAction, showIgnored = false } ) => {
	return (
		<li className="edac-analysis__issue-row">
			<button
				type="button"
				className="edac-analysis__issue-link"
				onClick={ () => onAction( 'details', issue ) }
				aria-haspopup="dialog"
			>
				{ __( 'Issue', 'accessibility-checker' ) } #{ issue.id }
			</button>
			{ showIgnored && issue?.ignre_reason && (
				<Badge
					label={ getDismissReasonLabel( issue.ignre_reason ) }
					type="info"
					size="small"
				/>
			) }
			<DropdownMenu
				icon={ moreVertical }
				label={ issue?.id ? sprintf( __( 'Issue actions for %s', 'accessibility-checker' ), issue.id ) : __( 'Issue actions', 'accessibility-checker' ) }
				className="edac-analysis__issue-menu"
			>
				{ ( { onClose } ) => (
					<MenuGroup className="edac-sidebar-menu-group">
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
							{ showIgnored
								? __( 'Reopen issue', 'accessibility-checker' )
								: __( 'Dismiss issue', 'accessibility-checker' ) }
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
