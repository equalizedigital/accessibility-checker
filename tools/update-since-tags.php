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

const EDAC_SINCE_TOOL_EXIT_OK            = 0;
const EDAC_SINCE_TOOL_EXIT_BAD_ARGS      = 1;
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
	edac_write_line( 'Usage: php tools/update-since-tags.php --version=<x.y.z> [options]' );
	edac_write_line( '' );
	edac_write_line( 'Options:' );
	edac_write_line( '  --root=<path>                Root directory to scan (default: parent of the tools/ directory)' );
	edac_write_line( '  --placeholder=<value>        Placeholder token after @since (default: x.x.x)' );
	edac_write_line( '  --changed-since-tag=<tag>    Only scan tracked PHP files changed since this Git tag/ref' );
	edac_write_line( '  --changed-since-last-tag     Only scan tracked PHP files changed since latest Git tag' );
	edac_write_line( '  --dry-run                    Show what would be changed without writing files' );
	edac_write_line( '  --help                       Show this help' );
	exit( EDAC_SINCE_TOOL_EXIT_OK ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

// Backward compatibility for the earlier positional-argument usage.
$version = $opts['version'] ?? ( $argv[1] ?? '' );
if ( ! is_string( $version ) || ! preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
	edac_write_line( 'Error: --version is required and must be in x.y.z format.', STDERR );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

// getopt() returns false (not null) for optional params given without a value (e.g. --root).
$root_opt = $opts['root'] ?? dirname( __DIR__ );
if ( false === $root_opt ) {
	edac_write_line( 'Error: --root requires a value.', STDERR );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
if ( ! is_string( $root_opt ) || ! is_dir( $root_opt ) ) {
	edac_write_line( 'Error: root directory does not exist: ' . (string) $root_opt, STDERR );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
// Use realpath() so filesystem-root paths like / or C:\ are preserved correctly.
$root = realpath( $root_opt );
if ( false === $root ) {
	edac_write_line( 'Error: could not resolve root directory: ' . $root_opt, STDERR );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

$placeholder_opt = $opts['placeholder'] ?? 'x.x.x';
if ( false === $placeholder_opt ) {
	edac_write_line( 'Error: --placeholder requires a value.', STDERR );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
if ( ! is_string( $placeholder_opt ) || '' === trim( $placeholder_opt ) ) {
	edac_write_line( 'Error: --placeholder must be a non-empty string.', STDERR );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
$placeholder = $placeholder_opt;

// getopt() returns false for optional params given without a value (e.g. --changed-since-tag).
$changed_since_tag_opt = $opts['changed-since-tag'] ?? null;
if ( false === $changed_since_tag_opt ) {
	edac_write_line( 'Error: --changed-since-tag requires a value.', STDERR );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
$changed_since_tag      = $changed_since_tag_opt;
$changed_since_last_tag = isset( $opts['changed-since-last-tag'] );
$dry_run                = isset( $opts['dry-run'] );

if ( null !== $changed_since_tag && $changed_since_last_tag ) {
	edac_write_line( 'Error: use either --changed-since-tag or --changed-since-last-tag, not both.', STDERR );
	exit( EDAC_SINCE_TOOL_EXIT_BAD_ARGS ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

// Validate the supplied ref actually exists before proceeding.
if ( null !== $changed_since_tag ) {
	$ref_check = trim( edac_run_git( $root, 'rev-parse --verify ' . escapeshellarg( (string) $changed_since_tag ) ) );
	if ( '' === $ref_check ) {
		edac_write_line( 'Error: invalid Git reference or tag: ' . (string) $changed_since_tag, STDERR );
		exit( EDAC_SINCE_TOOL_EXIT_RUNTIME_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( $changed_since_last_tag ) {
	$changed_since_tag = trim( edac_run_git( $root, 'describe --tags --abbrev=0' ) );
	if ( '' === $changed_since_tag ) {
		edac_write_line( 'Error: no tags found to use with --changed-since-last-tag.', STDERR );
		exit( EDAC_SINCE_TOOL_EXIT_RUNTIME_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

$excluded_dirs = [ '.git', 'vendor', 'node_modules', 'build', 'dist' ];
$php_files     = ( null !== $changed_since_tag )
	? edac_get_changed_php_files_since_ref( $root, (string) $changed_since_tag )
	: edac_get_all_php_files( $root, $excluded_dirs );

$placeholder_regex = preg_quote( $placeholder, '/' );
$pattern           = '/@since(\s+)' . $placeholder_regex . '/i';
$replacement       = '@since${1}' . $version;

$updated_files = 0;
$updated_tags  = 0;
$scanned_files = 0;

foreach ( $php_files as $file_path ) {
	++$scanned_files;

	if ( ! is_file( $file_path ) || ! is_readable( $file_path ) ) {
		continue;
	}

	$contents = file_get_contents( $file_path ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	if ( false === $contents ) {
		edac_write_line( "Warning: unable to read file: {$file_path}", STDERR );
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
		edac_write_line( "Would update {$count} tag(s) in {$display_path}" );
	} else {
		$write_result = file_put_contents( $file_path, $new_contents ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents
		if ( false === $write_result ) {
			edac_write_line( "Warning: unable to write file: {$file_path}", STDERR );
			continue;
		}
		edac_write_line( "Updated {$count} tag(s) in {$display_path}" );
	}

	++$updated_files;
	$updated_tags += (int) $count;
}

$status_label = $dry_run ? 'DRY RUN' : 'DONE';
edac_write_line( "{$status_label}: replaced {$updated_tags} placeholder @since tag(s) across {$updated_files} file(s); scanned {$scanned_files} PHP file(s)." );

exit( EDAC_SINCE_TOOL_EXIT_OK ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

/**
 * Get all PHP files in the repository, excluding known dependency/build dirs.
 *
 * @param string   $root          Root directory.
 * @param string[] $excluded_dirs Directory names to skip.
 * @return string[]
 */
function edac_get_all_php_files( string $root, array $excluded_dirs ): array {
	$files              = [];
	$directory_iterator = new RecursiveDirectoryIterator( $root, RecursiveDirectoryIterator::SKIP_DOTS );

	// Prune excluded directories before recursing into them for efficiency.
	$filter = new RecursiveCallbackFilterIterator(
		$directory_iterator,
		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed,VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		static function ( SplFileInfo $item, $key, RecursiveCallbackFilterIterator $iterator ) use ( $root, $excluded_dirs ): bool {
			if ( $item->isDir() ) {
				$relative_path = ltrim( substr( $item->getPathname(), strlen( $root ) ), DIRECTORY_SEPARATOR );
				foreach ( $excluded_dirs as $excluded ) {
					if ( $relative_path === $excluded || 0 === strpos( $relative_path, $excluded . DIRECTORY_SEPARATOR ) ) {
						return false;
					}
				}
			}

			return true;
		}
	);

	$iterator = new RecursiveIteratorIterator( $filter );

	foreach ( $iterator as $item ) {
		if ( ! $item instanceof SplFileInfo || $item->isDir() ) {
			continue;
		}

		if ( 'php' === strtolower( (string) $item->getExtension() ) ) {
			$files[] = $item->getPathname();
		}
	}

	sort( $files );

	return $files;
}

/**
 * Get tracked PHP files changed between a given ref and HEAD.
 *
 * Exits with EDAC_SINCE_TOOL_EXIT_RUNTIME_ERROR if the git command fails,
 * so a bad ref or non-repo root does not silently return an empty list.
 *
 * @param string $root Root directory.
 * @param string $ref  Git ref/tag.
 * @return string[]
 */
function edac_get_changed_php_files_since_ref( string $root, string $ref ): array {
	$cmd    = 'diff --name-only ' . escapeshellarg( $ref . '..HEAD' ) . ' -- ' . escapeshellarg( '*.php' );
	$output = edac_run_git( $root, $cmd, $exit_code );

	if ( 0 !== $exit_code ) {
		edac_write_line( "Error: git diff failed (exit {$exit_code}) for ref: {$ref}", STDERR );
		exit( EDAC_SINCE_TOOL_EXIT_RUNTIME_ERROR ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

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
 * @param string   $root      Root directory.
 * @param string   $args      Git args (already shell-escaped as needed).
 * @param int|null $exit_code Reference populated with the git process exit code.
 * @return string
 */
function edac_run_git( string $root, string $args, ?int &$exit_code = null ): string {
	$cmd    = 'git -C ' . escapeshellarg( $root ) . ' ' . $args;
	$output = [];
	exec( $cmd, $output, $exit_code ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec

	return implode( "\n", $output );
}

/**
 * Write a line to STDOUT/STDERR for CLI usage.
 *
 * @param string   $message Message to output.
 * @param resource $stream  Output stream, STDOUT by default.
 * @return void
 */
function edac_write_line( string $message, $stream = STDOUT ): void {
	fwrite( $stream, $message . "\n" ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite
}
