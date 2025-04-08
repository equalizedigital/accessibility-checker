import colorContrastFailure from '../rules/color-contrast-failure';
import underlinedText from '../rules/underlined-text';
import elementWithUnderline from '../checks/element-with-underline';
import elementIsAUTag from '../checks/element-is-u-tag';
import emptyParagraph from '../rules/empty-paragraph';
import paragraphNotEmpty from '../checks/paragraph-not-empty';
import possibleHeading from '../rules/possible-heading';
import paragraphStyledAsHeader from '../checks/paragraph-styled-as-header';
import textSmall from '../rules/text-small';
import textSizeTooSmall from '../checks/text-size-too-small';
import textJustified from '../rules/text-justified';
import textIsJustified from '../checks/text-is-justified';
import linkTargetBlank from '../rules/link_target_blank';
import linkTargetBlankWithoutInforming from '../checks/link-target-blank-without-informing';
import linkAmbiguousText from '../rules/link-ambiguous-text';
import hasAmbiguousText from '../checks/has-ambiguous-text';
import brokenAnchorLink from '../rules/broken-anchor-link';
import anchorExists from '../checks/anchor-exists';
import labelExtended from '../rules/extended/label';
import imageInputHasAlt from '../checks/image-input-has-alt';
import linkPDF from '../rules/link-pdf';
import linkMsOfficeFile from '../rules/link-ms-office-file';
import ariaHiddenValidUsage from '../checks/aria-hidden-valid-usage';
import ariaHiddenValidation from '../rules/aria-hidden-validation';
import ariaBrokenReference from '../rules/aria-broken-reference';
import ariaLabelNotFoundCheck from '../checks/aria-label-not-found';
import ariaDescribedByNotFoundCheck from '../checks/aria-describedby-not-found';
import ariaOwnsNotFoundCheck from '../checks/aria-owns-not-found';
import alwaysFail from '../checks/always-fail';

// Define all the custom rules to be used.
export const rulesArray = [
	colorContrastFailure,
	underlinedText,
	possibleHeading,
	emptyParagraph,
	textSmall,
	textJustified,
	linkTargetBlank,
	linkAmbiguousText,
	linkPDF,
	linkMsOfficeFile,
	brokenAnchorLink,
	labelExtended,
	ariaHiddenValidation,
	ariaBrokenReference,
];

// Define all the custom checks to be used.
export const checksArray = [
	alwaysFail,
	elementIsAUTag,
	elementWithUnderline,
	paragraphStyledAsHeader,
	paragraphNotEmpty,
	textSizeTooSmall,
	textIsJustified,
	linkTargetBlankWithoutInforming,
	hasAmbiguousText,
	anchorExists,
	imageInputHasAlt,
	ariaHiddenValidUsage,
	ariaLabelNotFoundCheck,
	ariaDescribedByNotFoundCheck,
	ariaOwnsNotFoundCheck,
];

// Define the standard axe core rules to be used.
export const standardRuleIdsArray = [
	'meta-viewport',
	'blink',
	'marquee',
	'document-title',
	'tabindex',
	'html-lang-valid',
	'html-has-lang',
];

// Define custom rule IDs from the rules array.
export const customRuleIdsArray = rulesArray.map( ( rule ) => rule.id );
