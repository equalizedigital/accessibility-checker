/* global process, module, __dirname */
/**
 * Stops the Playground instance that global-setup started.
 *
 * Leaves an instance alone when one was already running before the suite, so a
 * locally-started Playground survives a test run.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const PID_FILE = path.resolve( __dirname, '.auth', 'playground.pid' );

module.exports = async () => {
	if ( ! fs.existsSync( PID_FILE ) ) {
		return;
	}
	const pid = Number( fs.readFileSync( PID_FILE, 'utf8' ).trim() );
	try {
		// Negative pid: kill the whole process group the CLI was spawned into.
		process.kill( -pid, 'SIGTERM' );
	} catch ( error ) {
		try {
			process.kill( pid, 'SIGTERM' );
		} catch ( inner ) {
			// Already gone.
		}
	}
	fs.rmSync( PID_FILE, { force: true } );
};
