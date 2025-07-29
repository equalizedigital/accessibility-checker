export default {
	id: 'table_has_headers',
	evaluate: ( node ) => {
		if ( node.nodeName.toLowerCase() !== 'table' ) {
			return true;
		}

		// Enhanced logic to account for colspan, rowspan, and ARIA header relationships
		const hasAriaHeaders = node.querySelector( '[headers]' );
		const hasAriaLabelledBy = node.querySelector( '[aria-labelledby]' );

		// If table uses ARIA headers/labelledby relationships, it's considered valid
		if ( hasAriaHeaders || hasAriaLabelledBy ) {
			return true;
		}

		const rows = Array.from( node.querySelectorAll( 'tr' ) );

		if ( rows.length === 0 ) {
			return true;
		}

		// Case 1: Valid row-header table (every row starts with <th scope="row">)
		const isRowHeaderTable = rows.every( ( row ) => {
			const firstCell = row.children[ 0 ];
			if ( ! firstCell || firstCell.tagName.toLowerCase() !== 'th' ) {
				return false;
			}

			const scope = firstCell.getAttribute( 'scope' );
			return scope === 'row' || ! scope;
		} );

		if ( isRowHeaderTable ) {
			return true;
		}

		// Case 2: Classic table with header row
		const headerRow =
			node.querySelector( 'thead tr' ) ||
			rows.find( ( row ) => row.querySelectorAll( 'th' ).length > 0 );

		if ( ! headerRow ) {
			return false;
		}

		const thCount = headerRow.querySelectorAll( 'th' ).length;
		if ( thCount === 0 ) {
			return false;
		}

		// Calculate expected column count considering colspan
		let expectedCols = 0;
		headerRow.querySelectorAll( 'th' ).forEach( ( th ) => {
			const colspan = parseInt( th.getAttribute( 'colspan' ) ) || 1;
			expectedCols += colspan;
		} );

		let headerRowEncountered = false;

		for ( const row of rows ) {
			if ( ! headerRowEncountered && row === headerRow ) {
				headerRowEncountered = true;
				continue;
			}

			// Calculate actual column count considering colspan
			let actualCols = 0;
			const cells = row.querySelectorAll( 'td, th' );
			cells.forEach( ( cell ) => {
				const colspan = parseInt( cell.getAttribute( 'colspan' ) ) || 1;
				actualCols += colspan;
			} );

			// Allow for some flexibility with colspan/rowspan tables
			if ( actualCols > expectedCols && cells.length > thCount ) {
				return false;
			}
		}

		return true;
	},
};
