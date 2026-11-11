import { act } from 'react';
import { createRoot } from 'react-dom/client';
export const renderReact = ( ui ) => {
	const container = document.createElement( 'div' );
	document.body.appendChild( container );
	const root = createRoot( container );
	act( () => {
		root.render( ui );
	} );
	return {
		container,
		render: ( nextUi ) => {
			act( () => {
				root.render( nextUi );
			} );
		},
		unmount: () => {
			act( () => {
				root.unmount();
			} );
			container.remove();
		},
	};
};
