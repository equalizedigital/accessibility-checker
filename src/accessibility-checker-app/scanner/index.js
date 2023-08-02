import 'axe-core';
import colorContrastFailure from './rules/color-contrast-failure';
//TODO: examples:
//import customRule1 from './rules/custom-rule-1';
//import alwaysFail from './checks/always-fail';


//TODO: 			
//see: https://www.youtube.com/watch?v=AtsX0dPCG_4
//see: https://github.com/dequelabs/axe-core/blob/develop/doc/developer-guide.md#api-reference
//see: https://www.deque.com/axe/core-documentation/api-documentation/

export async function scan(
	options = { configOptions: {}, runOptions: {} }
) {

	
	const context = { exclude: ['#wpadminbar', '.edac-panel-container'] };

	const defaults = {
		configOptions: {
			reporter: "raw",
	
			rules : [
				//customRule1,
				colorContrastFailure
			],
			checks: [
				//alwaysFail,
			  ],
		},
		runOptions: {
			runOnly: ['color_contrast_failure']
			/*	
			//TODO:
			runOnly: {
				type: 'tag',
				values: [
					'wcag2a', 'wcag2aa', 'wcag2aaa',
					'wcag21a', 'wcag21aa',
					'wcag22aa',
					'best-practice',
					'ACT',
					'section508',
					'TTv5',
					'experimental'
				]
			}
			*/
		}
	};

	const configOptions = Object.assign(defaults.configOptions, options.configOptions);
	axe.configure(configOptions);

	const runOptions = Object.assign(defaults.runOptions, options.runOptions);

	return await axe.run(context, runOptions)
		.then((rules) => {

			axe.reset();

			
			let violations = [];

			rules.forEach(item => {

				//Build an array of the dom selectors and ruleIDs for violations/failed tests
				item.violations.forEach( violation => {
					if(violation.result === 'failed'){
				
			
						violations.push({
							selector:violation.node.selector,
							html: document.querySelector(violation.node.selector).outerHTML,
							ruleId: item.id,
							impact: item.impact,
							tags: item.tags
						});
					}
				});

			});

			let rules_min = rules.map((r) => {
				return {
					id: r.id,
					description: r.description,
					help: r.help,
					impact: r.impact,
					tags: r.tags
				}
			});
			
			//Sort the violations by order they appear in the document
			violations.sort(function(a,b) {
				a = document.querySelector(a.selector);
				b = document.querySelector(b.selector);
				
				if( a === b) return 0;
				if( a.compareDocumentPosition(b) & 2) {
					// b comes before a
					return 1;
				}
				return -1;
			});
			
			return { rules, rules_min, violations };
			
	
		}).catch((err) => {
			axe.reset();

			//TODO:
			return err;
		});


	
};

