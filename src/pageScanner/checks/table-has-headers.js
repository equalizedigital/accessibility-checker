export default {
	id: 'table_has_headers',
	evaluate: ( node ) => {
		if ( node.nodeName.toLowerCase() !== 'table' ) {
			return true;
		}

		const rows = Array.from( node.querySelectorAll( 'tr' ) );

		if ( rows.length === 0 ) {
			return true;
		}

		// Helper function to calculate the actual column span of cells in a row
		const getEffectiveColumnCount = ( row ) => {
			let columnCount = 0;
			Array.from( row.children ).forEach( ( cell ) => {
				const colspan = parseInt( cell.getAttribute( 'colspan' ) || '1', 10 );
				columnCount += colspan;
			} );
			return columnCount;
		};

		// Helper function to count header cells accounting for colspan
		const getHeaderColumnCount = ( row ) => {
			let headerColumnCount = 0;
			Array.from( row.children ).forEach( ( cell ) => {
				if ( cell.tagName.toLowerCase() === 'th' ) {
					const colspan = parseInt( cell.getAttribute( 'colspan' ) || '1', 10 );
					headerColumnCount += colspan;
				}
			} );
			return headerColumnCount;
		};

		// Helper function to validate ARIA header relationships
		const validateAriaHeaders = () => {
			const dataCells = node.querySelectorAll( 'td[headers]' );
			if ( dataCells.length === 0 ) {
				return true; // No ARIA headers to validate
			}

			for ( const cell of dataCells ) {
				const headerIds = cell.getAttribute( 'headers' ).split( /\s+/ );
				for ( const headerId of headerIds ) {
					if ( headerId && ! node.querySelector( `#${ headerId }` ) ) {
						return false; // Referenced header doesn't exist
					}
				}
			}
			return true;
		};

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
			// Validate ARIA relationships even for row-header tables
			return validateAriaHeaders();
		}

		// Case 2: Check for tables with header rows (including complex structures)
		const headerRows = rows.filter( ( row ) =>
			Array.from( row.children ).some( ( cell ) =>
				cell.tagName.toLowerCase() === 'th'
			)
		);

		if ( headerRows.length === 0 ) {
			return false;
		}

		// Calculate the maximum column span from header rows
		let maxHeaderColumns = 0;
		headerRows.forEach( ( row ) => {
			const headerColumns = getHeaderColumnCount( row );
			if ( headerColumns > maxHeaderColumns ) {
				maxHeaderColumns = headerColumns;
			}
		} );

		if ( maxHeaderColumns === 0 ) {
			return false;
		}

		// Find data rows (rows without any th elements)
		const dataRows = rows.filter( ( row ) =>
			! Array.from( row.children ).some( ( cell ) =>
				cell.tagName.toLowerCase() === 'th'
			)
		);

		// Validate that data rows don't exceed the column structure
		for ( const row of dataRows ) {
			const columnCount = getEffectiveColumnCount( row );
			if ( columnCount > maxHeaderColumns ) {
				return false;
			}
		}

		// Validate ARIA header relationships
		return validateAriaHeaders();
	},
};
