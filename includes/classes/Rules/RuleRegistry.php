<?php
/**
 * Rule Registry for loading all accessibility rules.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules;

/**
 * Rule Registry class for loading all accessibility rules.
 */
class RuleRegistry {
	/**
	 * Load all rules from known rule classes.
	 *
	 * @return array Combined array of all rule definitions.
	 */
	public static function load_rules(): array {
		$rules = [];
		
		// List of rule class names - add new rules here.
		$rule_classes = [
			'AriaHiddenRule',
			'BrokenAriaReferenceRule',
			'BrokenSkipAnchorLinkRule',
			'ColorContrastFailureRule',
			'DuplicateFormLabelRule',
			'EmptyButtonRule',
			'EmptyHeadingTagRule',
			'EmptyLinkRule',
			'EmptyParagraphTagRule',
			'EmptyTableHeaderRule',
			'IframeMissingTitleRule',
			'ImageMapMissingAltTextRule',
			'ImgAltEmptyRule',
			'ImgAltInvalidRule',
			'ImgAltLongRule',
			'ImgAltMissingRule',
			'ImgAltRedundantRule',
			'ImgAnimatedGifRule',
			'ImgLinkedAltEmptyRule',
			'ImgLinkedAltMissingRule',
			'IncorrectHeadingOrderRule',
			'LinkAmbiguousTextRule',
			'LinkBlankRule',
			'LinkImproperRule',
			'LinkMsOfficeFileRule',
			'LinkNonHtmlFileRule',
			'LinkPdfRule',
			'LongDescriptionInvalidRule',
			'MetaViewportRule',
			'MissingFormLabelRule',
			'MissingHeadingsRule',
			'MissingLangAttrRule',
			'MissingTableHeaderRule',
			'MissingTitleRule',
			'MissingTranscriptRule',
			'PossibleHeadingRule',
			'SliderPresentRule',
			'TabOrderModifiedRule',
			'TextBlinkingScrollingRule',
			'TextJustifiedRule',
			'TextSmallRule',
			'UnderlinedTextRule',
			'VideoPresentRule',
		];
		
		foreach ( $rule_classes as $class_name ) {
			$full_class_name = __NAMESPACE__ . '\\Rule\\' . $class_name;
			
			// PSR-4 autoloader will handle loading the class.
			if ( is_subclass_of( $full_class_name, RuleInterface::class ) ) {
				$rules[] = $full_class_name::get_rule();
			}
		}
		
		return $rules;
	}
}
