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

		// Case 1: Valid row-header table (every row starts with <th scope="row">)
		const isRowHeaderTable = rows.every( ( row ) => {
			const firstCell = row.children[ 0 ];
			if ( ! firstCell || firstCell.tagName.toLowerCase() !== 'th' ) {
				return false;
			}

			return scope === 'row' || !scope;
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

		let headerRowEncountered = false;

		for ( const row of rows ) {
			if ( ! headerRowEncountered && row === headerRow ) {
				headerRowEncountered = true;
				continue;
			}

			const tdCount = row.querySelectorAll( 'td' ).length;
			if ( tdCount > thCount ) {
				return false;
			}
		}

		return true;
	},
};
