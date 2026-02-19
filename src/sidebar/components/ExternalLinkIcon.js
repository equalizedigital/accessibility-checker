/**
 * External Link Icon Component
 *
 * Renders an icon indicating that a link opens in a new window/tab.
 * The icon is hidden from screen readers, but screen reader text is provided
 * to communicate that the link opens in a new window.
 *
 * @return {Element} The external link icon and screen reader text.
 */
const ExternalLinkIcon = () => {
	return (
		<>
			<span aria-hidden="true">{ ' ↗' }</span>
			<span className="screen-reader-text">, opens in a new window</span>
		</>
	);
};

export default ExternalLinkIcon;
