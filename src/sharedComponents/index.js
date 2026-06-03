/**
 * Shared UI components exposed as a runtime global for consumption
 * by other add-ons.
 *
 * Accessed via window.edacSharedComponents at runtime.
 * Import using the '@edac/shared-components' webpack external alias.
 */
export { default as Icon } from '../sidebar/components/Icon';
export { default as Badge } from '../sidebar/components/Badge';
export { default as ExternalLinkIcon } from '../sidebar/components/ExternalLinkIcon';
export {
	DISMISS_REASONS,
	getDismissReasonOptions,
	getDismissReasonLabel,
	getDismissReasonDescription,
} from '../sidebar/utils/dismissHelpers';
export { default as DismissPanel } from '../issueModal/components/DismissPanel';
export { default as FixCard } from '../issueModal/components/FixCard';
export { default as RichTextarea } from '../issueModal/components/RichTextarea';
export { default as IssueImage, extractImageUrls } from '../issueModal/components/IssueImage';
export { getSeverityLabel } from '../sidebar/utils/severityHelpers';
export { getRuleTypeBadgeProps, getSeverityBadgeProps } from '../sidebar/utils/badgeHelpers';
