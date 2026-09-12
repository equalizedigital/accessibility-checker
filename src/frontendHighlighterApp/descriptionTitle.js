/**
 * Builds the description title markup.
 *
 * @param {Object} args                 The title parts.
 * @param {string} args.title           The issue's rule title.
 * @param {string} [args.notice]        Status notice text, if the element was not found.
 * @param {string} [args.typeBadgeHtml] Pre-built severity badge markup.
 * @return {{ hasNotice: boolean, html: string }} The markup, and whether a notice is present.
 */
export function buildDescriptionTitle( { title, notice = '', typeBadgeHtml = '' } ) {
	const hasNotice = Boolean( notice );

	const noticeHtml = hasNotice
		? `<div class="edac-highlight-panel-description-notice">${ notice }</div>`
		: '';

	const html = `${ noticeHtml }<span class="edac-highlight-panel-description-title-text" role="heading" aria-level="3">${ title }</span>${ typeBadgeHtml }`;

	return { hasNotice, html };
}
