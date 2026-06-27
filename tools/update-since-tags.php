#!/usr/bin/env php
<?php
/**
 * Update placeholder @since tags in PHP docblocks.
 *
 * Usage examples:
 * - php tools/update-since-tags.php --version=1.43.0
 * - php tools/update-since-tags.php --version=1.43.0 --changed-since-last-tag
 * - php tools/update-since-tags.php --version=1.43.0 --changed-since-tag=v1.42.0
 * - php tools/update-since-tags.php --version=1.43.0 --root=/path/to/repo --dry-run
 *
 * @package AccessibilityChecker
 */

declare( strict_types=1 );

const EDAC_SINCE_TOOL_EXIT_OK = 0;
const EDAC_SINCE_TOOL_EXIT_BAD_ARGS = 1;
const EDAC_SINCE_TOOL_EXIT_RUNTIME_ERROR = 2;

$opts = getopt(
	'',
	[
		'version:',
		'root::',
		'placeholder::',
		'changed-since-tag::',
		'changed-since-last-tag',
		'dry-run',
		'help',
	]
);

if ( isset( $opts['help'] ) ) {
	echo "Usage: php tools/update-since-tags.php --version=<x.y.z> [options]\n\n";
	echo "Options:\n";
	echo "  --root=<path>                Root directory to scan (default: current working directory)\n";
	echo "  --placeholder=<value>        Placeholder token after @since (default: x.x.x)\n";
	echo "  --changed-since-tag=<tag>    Only scan tracked PHP files changed since this Git tag/ref\n";
	echo "  --changed-since-last-tag     Only scan tracked PHP files changed since latest Git tag\n";
	echo "  --dry-run                    Show what would be changed without writing files\n";
	echo "  --help                       Show this help\n";
	exit( EDAC_SINCE_TOOL_EXIT_OK );
}

// Backward compatibility for the earlier positional-argument usage.
$version = $opts['version'] ?? ( $argv[1] ?? '' );
if ( ! is_string( $version ) || ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
	fwrite( STDERR, "Error: --version is required and must be in x.y.z format.\n" );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS );
}

$root = $opts['root'] ?? dirname( __DIR__ );
if ( ! is_string( $root ) || ! is_dir( $root ) ) {
	fwrite( STDERR, 'Error: root directory does not exist: ' . (string) $root . "\n" );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS );
}
$root = rtrim( (string) $root, DIRECTORY_SEPARATOR );

$placeholder = $opts['placeholder'] ?? 'x.x.x';
if ( ! is_string( $placeholder ) || '' === trim( $placeholder ) ) {
	fwrite( STDERR, "Error: --placeholder must be a non-empty string.\n" );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS );
}

$changed_since_tag = $opts['changed-since-tag'] ?? null;
$changed_since_last_tag = isset( $opts['changed-since-last-tag'] );
$dry_run = isset( $opts['dry-run'] );

if ( null !== $changed_since_tag && $changed_since_last_tag ) {
	fwrite( STDERR, "Error: use either --changed-since-tag or --changed-since-last-tag, not both.\n" );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS );
}

if ( $changed_since_last_tag ) {
	$changed_since_tag = trim( edac_run_git( $root, 'describe --tags --abbrev=0' ) );
	if ( '' === $changed_since_tag ) {
		fwrite( STDERR, "Error: no tags found to use with --changed-since-last-tag.\n" );
		exit( EDAC_SINCE_TOOL_EXIT_RUNTIME_ERROR );
	}
}

$excluded_dirs = [ '.git', 'vendor', 'node_modules', 'build', 'dist' ];
$php_files = ( null !== $changed_since_tag )
	? edac_get_changed_php_files_since_ref( $root, (string) $changed_since_tag )
	: edac_get_all_php_files( $root, $excluded_dirs );

$placeholder_regex = preg_quote( $placeholder, '/' );
$pattern = '/@since(\s+)' . $placeholder_regex . '/i';
$replacement = '@since${1}' . $version;

$updated_files = 0;
$updated_tags = 0;
$scanned_files = 0;

foreach ( $php_files as $file_path ) {
	++$scanned_files;

	if ( ! is_file( $file_path ) || ! is_readable( $file_path ) ) {
		continue;
	}

	$contents = file_get_contents( $file_path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	if ( false === $contents ) {
		fwrite( STDERR, "Warning: unable to read file: {$file_path}\n" );
		continue;
	}

	$count = preg_match_all( $pattern, $contents );
	if ( 0 === $count ) {
		continue;
	}

	$new_contents = preg_replace( $pattern, $replacement, $contents );
	if ( ! is_string( $new_contents ) || $new_contents === $contents ) {
		continue;
	}

	$display_path = ltrim( str_replace( $root, '', $file_path ), DIRECTORY_SEPARATOR );

	if ( $dry_run ) {
		echo "Would update {$count} tag(s) in {$display_path}\n";
	} else {
		$write_result = file_put_contents( $file_path, $new_contents ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
		if ( false === $write_result ) {
			fwrite( STDERR, "Warning: unable to write file: {$file_path}\n" );
			continue;
		}
		echo "Updated {$count} tag(s) in {$display_path}\n";
	}

	++$updated_files;
	$updated_tags += (int) $count;
}

$mode = $dry_run ? 'DRY RUN' : 'DONE';
echo "{$mode}: replaced {$updated_tags} placeholder @since tag(s) across {$updated_files} file(s); scanned {$scanned_files} PHP file(s).\n";

exit( EDAC_SINCE_TOOL_EXIT_OK );

/**
 * Get all PHP files in the repository, excluding known dependency/build dirs.
 *
 * @param string   $root          Root directory.
 * @param string[] $excluded_dirs Directory names to skip.
 * @return string[]
 */
function edac_get_all_php_files( string $root, array $excluded_dirs ): array {
	$files = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $root, RecursiveDirectoryIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $item ) {
		if ( ! $item instanceof SplFileInfo || $item->isDir() ) {
			continue;
		}

		$path = $item->getPathname();
		$relative_path = ltrim( str_replace( $root, '', $path ), DIRECTORY_SEPARATOR );

		foreach ( $excluded_dirs as $excluded ) {
			$needle = $excluded . DIRECTORY_SEPARATOR;
			if ( 0 === strpos( $relative_path, $needle ) || false !== strpos( $relative_path, DIRECTORY_SEPARATOR . $needle ) ) {
				continue 2;
			}
		}

		if ( 'php' === strtolower( (string) $item->getExtension() ) ) {
			$files[] = $path;
		}
	}

	sort( $files );

	return $files;
}

/**
 * Get tracked PHP files changed between a given ref and HEAD.
 *
 * @param string $root Root directory.
 * @param string $ref  Git ref/tag.
 * @return string[]
 */
function edac_get_changed_php_files_since_ref( string $root, string $ref ): array {
	$cmd = 'diff --name-only ' . escapeshellarg( $ref . '..HEAD' ) . ' -- ' . escapeshellarg( '*.php' );
	$output = edac_run_git( $root, $cmd );

	$files = [];
	foreach ( preg_split( '/\r?\n/', trim( $output ) ) as $line ) {
		if ( '' === $line ) {
			continue;
		}

		$path = $root . DIRECTORY_SEPARATOR . $line;
		if ( is_file( $path ) ) {
			$files[] = $path;
		}
	}

	sort( $files );

	return $files;
}

/**
 * Run a Git command in the target root and return stdout.
 *
 * @param string $root Root directory.
 * @param string $args Git args.
 * @return string
 */
function edac_run_git( string $root, string $args ): string {
	$cmd = 'git -C ' . escapeshellarg( $root ) . ' ' . $args . ' 2>/dev/null';
	$output = shell_exec( $cmd ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_shell_exec

	return is_string( $output ) ? $output : '';
}


