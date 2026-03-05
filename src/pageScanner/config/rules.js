import {
	rulesArray as baseRulesArray,
	checksArray as baseChecksArray,
	standardRuleIdsArray,
	customRuleIdsArray,
	imgAltLongCheck,
} from '@equalizedigital/accessibility-checker-rules';

// Re-export unchanged arrays directly.
export { standardRuleIdsArray, customRuleIdsArray };
export const rulesArray = baseRulesArray;

// Override imgAltLongCheck's maxAltLength with the runtime scanOptions value if set.
export const checksArray = baseChecksArray.map( ( check ) => {
	if ( check.id === imgAltLongCheck.id ) {
		return {
			...check,
			options: {
				maxAltLength: window?.scanOptions?.maxAltLength || check.options.maxAltLength,
			},
		};
	}
	return check;
} );
