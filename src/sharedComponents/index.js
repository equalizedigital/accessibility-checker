/**
 * Shared UI components exposed as a runtime global for consumption
 * by other add-ons.
 *
 * Accessed via window.edacSharedComponents at runtime.
 * Import using the '@edac/shared-components' webpack external alias.
 */
export { default as Icon } from '../sidebar/components/Icon';
export { default as Badge } from '../sidebar/components/Badge';
