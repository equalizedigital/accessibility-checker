/**
 * External Link Icon Component
 *
 * Renders an icon indicating that a link opens in a new window/tab.
 * The icon is hidden from screen readers, but optionally provides screen reader text
 * to communicate that the link opens in a new window.
 *
 * @param {Object}  props                      - Component props.
 * @param {boolean} props.showScreenReaderText - Whether to include screen reader text. Defaults to true.
 * @return {Element} The external link icon and optional screen reader text.
 */
import { __ } from '@wordpress/i18n';

const ExternalLinkIcon = ( { showScreenReaderText = true } = {} ) => {
	return (
		<>
			<span aria-hidden="true">{ ' ↗' }</span>
			{ showScreenReaderText && (
				<span className="screen-reader-text">{ __( ', opens a new window', 'accessibility-checker' ) }</span>
			) }
		</>
	);
};

export default ExternalLinkIcon;
