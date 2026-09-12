/**
 * Builds the front-end highlighter's issue description title markup.
 *
 * The description title row is a wrapping flex container. When an issue's
 * element cannot be located on the page a status notice ("The element was not
 * found on the page.") is rendered as the first item in that row, so the
 * notice has to claim a full line of its own - otherwise it sits alongside the
 * issue title (PRO-1332) instead of above it. The returned `hasNotice` flag
 * lets the caller mark the container for the stacked layout.
 */

/**
 * Builds the description title markup.
 *
 * @param {Object} args                 The title parts.
 * @param {string} args.title           The issue's rule title.
 * @param {string} [args.notice]        Status notice text, shown when the element was not found, visible or focusable.
 * @param {string} [args.typeBadgeHtml] Pre-built severity badge markup, if the issue has one.
 * @return {{ hasNotice: boolean, html: string }} Whether a status notice is present, and the markup to render.
 */
export function buildDescriptionTitle( { title, notice = '', typeBadgeHtml = '' } ) {
	const hasNotice = Boolean( notice );

	const noticeHtml = hasNotice
		? `<div class="edac-highlight-panel-description-notice">${ notice }</div>`
		: '';

	const html = `${ noticeHtml }<span class="edac-highlight-panel-description-title-text" role="heading" aria-level="3">${ title }</span>${ typeBadgeHtml }`;

	return { hasNotice, html };
}
