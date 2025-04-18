export const fontSizeInPx = ( node ) => {
	if ( ! node || node.nodeType !== Node.ELEMENT_NODE ) {
		return 0;
	}

	const fontSize = parseFloat( window.getComputedStyle( node ).fontSize );
	return typeof fontSize === 'number' ? fontSize : 0;
};

/**
 * A Map that normalizes all keys to lowercase
 */
export class NormalizedMap extends Map {
	set( key, value ) {
		return super.set( typeof key === 'string' ? key.toLowerCase() : key, value );
	}

	get( key ) {
		return super.get( typeof key === 'string' ? key.toLowerCase() : key );
	}

	has( key ) {
		return super.has( typeof key === 'string' ? key.toLowerCase() : key );
	}

	delete( key ) {
		return super.delete( typeof key === 'string' ? key.toLowerCase() : key );
	}
}
