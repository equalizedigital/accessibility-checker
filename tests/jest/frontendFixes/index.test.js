/**
 * Tests for the frontend fixes entry rerun handling.
 */

const flushImports = () => new Promise( ( resolve ) => setTimeout( resolve, 0 ) );

describe( 'Frontend fixes rerun events', () => {
	let listeners;
	let originalAddEventListener;

	beforeEach( () => {
		jest.resetModules();
		document.body.innerHTML = '';

		// Track listeners so each test starts with a clean document.
		listeners = [];
		originalAddEventListener = document.addEventListener;
		document.addEventListener = function( type, fn, options ) {
			listeners.push( [ type, fn, options ] );
			return originalAddEventListener.call( this, type, fn, options );
		};
	} );

	afterEach( () => {
		listeners.forEach( ( [ type, fn, options ] ) => document.removeEventListener( type, fn, options ) );
		document.addEventListener = originalAddEventListener;
		delete window.edac_frontend_fixes;
	} );

	const loadEntry = async ( data ) => {
		window.edac_frontend_fixes = data;
		await import( '../../../src/frontendFixes/index.js' );
		await flushImports();
	};

	test( 'fires edac-fixes-rerun when a configured event fires', async () => {
		await loadEntry( { rerunEvents: [ 'facetwp-loaded' ] } );

		const handler = jest.fn();
		document.addEventListener( 'edac-fixes-rerun', handler );

		document.dispatchEvent( new CustomEvent( 'facetwp-loaded' ) );

		expect( handler ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'ignores edac-fixes-rerun in the configured list to avoid a loop', async () => {
		await loadEntry( { rerunEvents: [ 'edac-fixes-rerun' ] } );

		const handler = jest.fn();
		document.addEventListener( 'edac-fixes-rerun', handler );

		document.dispatchEvent( new CustomEvent( 'edac-fixes-rerun' ) );

		expect( handler ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'does not fail when no rerun events are passed', async () => {
		await expect( loadEntry( {} ) ).resolves.toBeUndefined();
	} );

	test( 'reruns the prevent new window fix on links added after load', async () => {
		document.body.innerHTML = '<a id="first" href="#" target="_blank">First</a>';

		await loadEntry( {
			prevent_links_opening_new_windows: { enabled: true },
			rerunEvents: [ 'gform/post_render' ],
		} );

		expect( document.getElementById( 'first' ).hasAttribute( 'target' ) ).toBe( false );

		document.body.insertAdjacentHTML( 'beforeend', '<a id="second" href="#" target="_blank">Second</a>' );
		document.dispatchEvent( new CustomEvent( 'gform/post_render' ) );

		expect( document.getElementById( 'second' ).hasAttribute( 'target' ) ).toBe( false );
	} );

	test( 'reruns the tabindex fix on elements added after load', async () => {
		await loadEntry( {
			tabindex: { enabled: true },
			rerunEvents: [ 'facetwp-loaded' ],
		} );

		document.body.innerHTML = '<button id="btn" tabindex="3">Go</button>';
		document.dispatchEvent( new CustomEvent( 'facetwp-loaded' ) );

		expect( document.getElementById( 'btn' ).getAttribute( 'tabindex' ) ).toBe( '0' );
	} );

	test( 'reruns the remove title fix on elements added after load', async () => {
		await loadEntry( {
			remove_title_if_preferred_accessible_name: { enabled: true },
		} );

		document.body.innerHTML = '<img id="img" src="a.png" alt="Logo" title="Logo">';
		document.dispatchEvent( new CustomEvent( 'edac-fixes-rerun' ) );

		expect( document.getElementById( 'img' ).hasAttribute( 'title' ) ).toBe( false );
	} );
} );
