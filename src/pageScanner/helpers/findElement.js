/**
 * Find the element a selector points at, without ever throwing.
 *
 * Selectors come back from the scan and are looked up again afterwards. By
 * then the element may be gone (a script on the page can remove or replace it
 * once it has been checked), and the selector may not be one the browser
 * accepts. Either way the caller gets null and carries on.
 *
 * @param {string|string[]} selector The selector, as the scan reported it.
 * @param {Document}        root     The document to look in.
 * @return {Element|null} The element, or null if it cannot be found.
 */
export const findElement = ( selector, root = document ) => {
	try {
		return root.querySelector( selector );
	} catch ( error ) {
		return null;
	}
};
