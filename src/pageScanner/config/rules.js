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
import tableHasHeaders from '../checks/table-has-headers';
import missingTableHeader from '../rules/table-header-missing';
import headingTagEmpty from '../rules/empty-heading-tag';
import headingIsEmpty from '../checks/heading-is-empty';
import transcriptMissing from '../checks/has-transcript';
import missingTranscript from '../rules/missing-transcript';
import buttonEmpty from '../rules/empty-button';
import buttonIsEmpty from '../checks/button-is-empty';
import sliderDetected from '../checks/slider-detected';
import sliderPresent from '../rules/slider-present';
import isvideoDetected from '../checks/is-video-detected';
import videoPresent from '../rules/video-present';
import linkEmpty from '../rules/empty-link';
import linkIsEmpty from '../checks/link-is-empty';
import longdescValid from '../checks/longdesc-valid';
import longDescriptionInvalid from '../rules/long-description-invalid';
import emptyTableHeader from '../rules/empty-table-header';
import tableHeaderIsEmpty from '../checks/table-header-is-empty';
import imgAltMissing from '../rules/img-alt-missing';
import imgAltMissingCheck from '../checks/img-alt-missing-check';
import imgAltInvalid from '../rules/img-alt-invalid';
import imgAltInvalidCheck from '../checks/img-alt-invalid-check';
import imgAltRedundant from '../rules/img-alt-redundant';
import imgAltRedundantCheck from '../checks/img-alt-redundant-check';
import imgLinkedAltMissing from '../rules/img-linked-alt-missing';
import linkedImageAltPresent from '../checks/linked-image-alt-present';
import imgLinkedAltEmpty from '../rules/img-linked-alt-empty';
import linkedImageAltNotEmpty from '../checks/linked-image-alt-not-empty';
import imageAltLong from '../rules/img-alt-long';
import imgAltLongCheck from '../checks/img-alt-long-check';
import imgAltEmpty from '../rules/img-alt-empty';
import imgAltEmptyCheck from '../checks/img-alt-empty-check';
import linkNonHtmlFile from '../rules/link-non-html-file';
import linkPointsToHtml from '../checks/link-points-to-html';
import linkImproper from '../rules/link-improper';
import linkHasValidHrefOrRole from '../checks/link-has-valid-href-or-role';
import missingHeadings from '../rules/missing-headings';
import hasSubheadingsIfLongContent from '../checks/has-subheadings-if-long-content';
import imageAnimated from '../rules/img-animated';
import imageAnimatedCheck from '../checks/img-animated-check';
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
	missingTableHeader,
	headingTagEmpty,
	missingTranscript,
	buttonEmpty,
	sliderPresent,
	videoPresent,
	linkEmpty,
	longDescriptionInvalid,
	emptyTableHeader,
	imgAltMissing,
	imgAltInvalid,
	imgAltRedundant,
	imgLinkedAltMissing,
	imgLinkedAltEmpty,
	imageAltLong,
	imgAltEmpty,
	linkNonHtmlFile,
	linkImproper,
	missingHeadings,
	imageAnimated,
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
	tableHasHeaders,
	headingIsEmpty,
	transcriptMissing,
	buttonIsEmpty,
	sliderDetected,
	isvideoDetected,
	linkIsEmpty,
	longdescValid,
	tableHeaderIsEmpty,
	imgAltMissingCheck,
	imgAltInvalidCheck,
	imgAltRedundantCheck,
	linkedImageAltPresent,
	linkedImageAltNotEmpty,
	{
		...imgAltLongCheck,
		options: {
			maxAltLength: window?.scanOptions?.maxAltLength || imgAltLongCheck.options.maxAltLength,
		},
	}, // This check supports an override of it's maxAltLength option when one is set in scanOptions.
	imgAltEmptyCheck,
	linkPointsToHtml,
	linkHasValidHrefOrRole,
	hasSubheadingsIfLongContent,
	imageAnimatedCheck,
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
	'form-field-multiple-labels',
];

// Define custom rule IDs from the rules array.
export const customRuleIdsArray = rulesArray.map( ( rule ) => rule.id );
