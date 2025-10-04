<?php
/**
 * Generate Hooks Documentation.
 *
 * Scans the repository for add_action/add_filter calls and generates
 * `docs/hooks.md` containing a simple table of discovered hooks.
 *
 * Only hooks that are plugin-specific (prefixed with `edac_` or `edacp_`)
 * are included.
 *
 * @package AccessibilityChecker
 */

$root = dirname( __DIR__ );

// Configuration: allow overriding via environment variables for CI or local runs.
$github_owner  = getenv( 'GH_OWNER' ) ? getenv( 'GH_OWNER' ) : 'equalizedigital';
$github_repo   = getenv( 'GH_REPO' ) ? getenv( 'GH_REPO' ) : 'accessibility-checker';
$github_branch = getenv( 'GH_BRANCH' ) ? getenv( 'GH_BRANCH' ) : 'develop';

$rii = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $root )
);

/**
 * Find the nearest PHPDoc block before $pos within $contents and return comprehensive information.
 *
 * @param string $contents Full file contents.
 * @param int    $pos      Byte offset where the hook call begins.
 * @param int    $max_lines Maximum number of lines allowed between docblock end and pos.
 * @return array Information extracted from the docblock including summary, since, params and return.
 */
function edac_find_nearest_docblock( $contents, $pos, $max_lines = 10 ) {
	$leading = substr( $contents, 0, $pos );

	// Find all docblocks before the position.
	if ( preg_match_all( '/\/\*\*(?:[^*]|\*(?!\/))*\*\//s', $leading, $matches, PREG_OFFSET_CAPTURE ) ) {
		$last     = end( $matches[0] );
		$doc_text = $last[0];
		$doc_pos  = $last[1];
		$doc_end  = $doc_pos + strlen( $doc_text );

		// Lines between doc end and the hook position.
		$between  = substr( $contents, $doc_end, max( 0, $pos - $doc_end ) );
		$line_gap = substr_count( $between, "\n" );
		if ( $line_gap <= $max_lines ) {
			// Clean comment markers and collect lines.
			$lines = preg_split( '/\r?\n/', $doc_text );
			$clean = [];
			foreach ( $lines as $ln ) {
				$ln = preg_replace( '/^\s*\/\*\*\s?/', '', $ln );
				$ln = preg_replace( '/^\s*\*\s?/', '', $ln );
				$ln = preg_replace( '/\s*\*\/$/', '', $ln );
				$ln = trim( $ln );
				if ( '' !== $ln ) {
					$clean[] = $ln;
				}
			}

			$summary     = '';
			$since       = '';
			$params      = [];
			$return_info = [
				'type'        => '',
				'description' => '',
			];
			
			if ( ! empty( $clean ) ) {
				// Find first line that is not an @tag (e.g., not starting with @).
				foreach ( $clean as $c ) {
					if ( 0 === strpos( $c, '@' ) ) {
						continue;
					}
					$summary = $c;
					break;
				}

				// Extract all docblock tags.
				foreach ( $clean as $c ) {
					// Extract @since tag.
					if ( 0 === strpos( $c, '@since' ) ) {
						$parts = preg_split( '/\s+/', $c, 2 );
						$since = isset( $parts[1] ) ? $parts[1] : '';
					}
					
					// Extract @param information.
					if ( 0 === strpos( $c, '@param' ) && preg_match( '/@param\s+(\S+)\s+\$(\S+)(?:\s+(.+))?/', $c, $param_matches ) ) {
						$param_type = $param_matches[1];
						$param_name = $param_matches[2];
						$param_desc = isset( $param_matches[3] ) ? $param_matches[3] : '';
						
						$params[ $param_name ] = [
							'type'        => $param_type,
							'description' => $param_desc,
						];
					}
					
					// Extract @return information.
					if ( 0 === strpos( $c, '@return' ) && preg_match( '/@return\s+(\S+)(?:\s+(.+))?/', $c, $return_matches ) ) {
						$return_info['type']        = $return_matches[1];
						$return_info['description'] = isset( $return_matches[2] ) ? $return_matches[2] : '';
					}
				}
			}

			return [
				'summary'     => $summary,
				'since'       => $since,
				'params'      => $params,
				'return_info' => $return_info,
			];
		}
	}

	// Fallback: try to find the enclosing function's docblock (if any).
	if ( preg_match_all( '/function\s+[A-Za-z0-9_]+\s*\([^\)]*\)\s*\{?/s', $leading, $fn_matches, PREG_OFFSET_CAPTURE ) ) {
		$last_fn = end( $fn_matches[0] );
		$fn_pos  = $last_fn[1];

		// Find the last docblock before the function.
		if ( preg_match_all( '/\/\*\*(?:[^*]|\*(?!\/))*\*\//s', substr( $contents, 0, $fn_pos ), $m2, PREG_OFFSET_CAPTURE ) ) {
			$last2    = end( $m2[0] );
			$doc_text = $last2[0];

			// Clean and extract as above.
			$lines = preg_split( '/\r?\n/', $doc_text );
			$clean = [];
			foreach ( $lines as $ln ) {
				$ln = preg_replace( '/^\s*\/\*\*\s?/', '', $ln );
				$ln = preg_replace( '/^\s*\*\s?/', '', $ln );
				$ln = preg_replace( '/\s*\*\/$/', '', $ln );
				$ln = trim( $ln );
				if ( '' !== $ln ) {
					$clean[] = $ln;
				}
			}

			$summary     = '';
			$since       = '';
			$params      = [];
			$return_info = [
				'type'        => '',
				'description' => '',
			];
			
			if ( ! empty( $clean ) ) {
				// Find first non-tag line for summary.
				foreach ( $clean as $c ) {
					if ( 0 === strpos( $c, '@' ) ) {
						continue;
					}
					$summary = $c;
					break;
				}
				
				// Extract all tags.
				foreach ( $clean as $c ) {
					// Extract @since tag.
					if ( 0 === strpos( $c, '@since' ) ) {
						$parts = preg_split( '/\s+/', $c, 2 );
						$since = isset( $parts[1] ) ? $parts[1] : '';
					}
					
					// Extract @param information.
					if ( 0 === strpos( $c, '@param' ) && preg_match( '/@param\s+(\S+)\s+\$(\S+)(?:\s+(.+))?/', $c, $param_matches ) ) {
						$param_type = $param_matches[1];
						$param_name = $param_matches[2];
						$param_desc = isset( $param_matches[3] ) ? $param_matches[3] : '';
						
						$params[ $param_name ] = [
							'type'        => $param_type,
							'description' => $param_desc,
						];
					}
					
					// Extract @return information.
					if ( 0 === strpos( $c, '@return' ) && preg_match( '/@return\s+(\S+)(?:\s+(.+))?/', $c, $return_matches ) ) {
						$return_info['type']        = $return_matches[1];
						$return_info['description'] = isset( $return_matches[2] ) ? $return_matches[2] : '';
					}
				}
			}

			return [
				'summary'     => $summary,
				'since'       => $since,
				'params'      => $params,
				'return_info' => $return_info,
			];
		}
	}

	return [
		'summary'     => '',
		'since'       => '',
		'params'      => [],
		'return_info' => [
			'type'        => '',
			'description' => '',
		],
	];
}

// Collect candidates per hook so we can pick the single defining location.
$candidates = [];

foreach ( $rii as $file ) {
	if ( $file->isDir() ) {
		continue;
	}

	$filepath = $file->getPathname();

	// Skip vendor directory and non-PHP files.
	if ( false !== strpos( $filepath, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}

	if ( ! preg_match( '/\.php$/', $filepath ) ) {
		continue;
	}

	$contents = file_get_contents( $filepath ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown

	// 1) Find hook definitions: do_action (action) and apply_filters (filter).
	if ( preg_match_all( '/\b(do_action|apply_filters)\s*\(\s*([\'\"])([^\2]+?)\2/s', $contents, $def_matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		foreach ( $def_matches as $row ) {
			$fn        = $row[1][0];
			$hook_name = $row[3][0];
			$pos       = $row[0][1];
			$line      = 1;
			if ( false !== $pos ) {
				$line = substr_count( substr( $contents, 0, $pos ), "\n" ) + 1;
			}

			if ( preg_match( '/^(edac_|edacp_)/', $hook_name ) ) {
				$relpath = substr( $filepath, strlen( $root ) + 1 );

				// Try to capture the nearest PHPDoc block (within 10 lines) for summary and @since.
				$doc_info                   = edac_find_nearest_docblock( $contents, $pos, 10 );
				$candidates[ $hook_name ][] = [
					'hook'        => $hook_name,
					'type'        => ( 'do_action' === $fn ) ? 'action' : 'filter',
					'file'        => $relpath,
					'line'        => $line,
					'source'      => 'definition',
					'summary'     => $doc_info['summary'],
					'since'       => $doc_info['since'],
					'params'      => $doc_info['params'],
					'return_info' => $doc_info['return_info'],
				];
			}
		}
	}

	// 2) Find listeners (add_action/add_filter) - less authoritative for a definition.
	if ( preg_match_all( '/add_(action|filter)\s*\(\s*([\'\"])([^\2]+?)\2/', $contents, $matches_rows, PREG_SET_ORDER ) ) {
		foreach ( $matches_rows as $row ) {
			$hook_type = $row[1];
			$hook_name = $row[3];
			$pos       = strpos( $contents, $row[0] );
			$line      = 1;
			if ( false !== $pos ) {
				$line = substr_count( substr( $contents, 0, $pos ), "\n" ) + 1;
			}

			if ( preg_match( '/^(edac_|edacp_)/', $hook_name ) ) {
				$relpath = substr( $filepath, strlen( $root ) + 1 );

				// For listeners, also try to find a nearby docblock (within 6 lines).
				$doc_info = edac_find_nearest_docblock( $contents, $pos, 6 );

				$candidates[ $hook_name ][] = [
					'hook'        => $hook_name,
					'type'        => $hook_type,
					'file'        => $relpath,
					'line'        => $line,
					'source'      => 'listener',
					'summary'     => $doc_info['summary'],
					'since'       => $doc_info['since'],
					'params'      => $doc_info['params'],
					'return_info' => $doc_info['return_info'],
				];
			}
		}
	}
}

$github_base = sprintf( 'https://github.com/%s/%s/blob/%s/', $github_owner, $github_repo, $github_branch );

// Choose the best candidate per hook. Strategy: prefer definitions when present, and prefer files
// outside of tests/, dist/, build/, vendor/ etc.
$hooks = [];
foreach ( $candidates as $hook_name => $list ) {
	// First, filter out entries from non-authoritative directories.
	$filtered_list = array_filter(
		$list,
		static function ( $entry ) {
			// Skip entries from test/build/vendor directories.
			$excluded_paths = [
				'/tests/',
				'/dist/',
				'/build/',
				'/docs/',
				'/.github/',
				'/node_modules/',
				'/vendor/',
			];
			
			foreach ( $excluded_paths as $excluded_path ) {
				if ( false !== strpos( $entry['file'], $excluded_path ) ) {
					return false;
				}
			}
			return true;
		}
	);
	
	// If we filtered out everything, fall back to the original list.
	if ( ! empty( $filtered_list ) ) {
		$list = array_values( $filtered_list );
	}
	
	// If any definition candidates exist, narrow to them. Otherwise keep listeners.
	$defs = array_filter(
		$list,
		static function ( $e ) {
			return isset( $e['source'] ) && 'definition' === $e['source'];
		} 
	);
	if ( ! empty( $defs ) ) {
		$list = array_values( $defs );
	}

	$best = null;
	foreach ( $list as $entry ) {
		$score = 0;
		// Heuristics to penalize less-authoritative locations.
		if ( false !== strpos( $entry['file'], '/tests/' ) ) {
			$score += 1000;
		}
		if ( false !== strpos( $entry['file'], '/dist/' ) ) {
			$score += 800;
		}
		if ( false !== strpos( $entry['file'], '/build/' ) ) {
			$score += 600;
		}
		if ( false !== strpos( $entry['file'], '/vendor/' ) ) {
			$score += 500;
		}
		if ( false !== strpos( $entry['file'], '/docs/' ) ) {
			$score += 400;
		}
		if ( false !== strpos( $entry['file'], '/.github/' ) ) {
			$score += 300;
		}
		if ( false !== strpos( $entry['file'], '/node_modules/' ) ) {
			$score += 200;
		}

		// Lower line numbers are slightly preferred (first definition in file).
		$score = $score * 10000 + $entry['line'];

		if ( null === $best || $score < $best['score'] ) {
			$best          = $entry;
			$best['score'] = $score;
		}
	}

	if ( $best ) {
		$hooks[] = [
			'hook'        => $best['hook'],
			'type'        => $best['type'],
			'file'        => $best['file'],
			'line'        => $best['line'],
			'summary'     => isset( $best['summary'] ) ? $best['summary'] : '',
			'since'       => isset( $best['since'] ) ? $best['since'] : '',
			'params'      => isset( $best['params'] ) ? $best['params'] : [],
			'return_info' => isset( $best['return_info'] ) ? $best['return_info'] : [
				'type'        => '',
				'description' => '',
			],
		];
	}
}

usort(
	$hooks,
	static function ( $a, $b ) {
		return strcmp( $a['hook'], $b['hook'] );
	}
);

$out  = "# EDAC Hooks Reference\n\n";
$out .= "This document is auto-generated by `tools/generate-hooks-docs.php`. It lists only plugin-specific hooks (prefixed with `edac_` or `edacp_`) and links files to the plugin's GitHub branch.\n\n";

// Group hooks by type (actions/filters).
$actions = array_filter(
	$hooks,
	static function ( $h ) {
		return 'action' === $h['type'];
	}
);
$filters = array_filter(
	$hooks,
	static function ( $h ) {
		return 'filter' === $h['type'];
	}
);

usort(
	$actions,
	static function ( $a, $b ) {
		return strcmp( $a['hook'], $b['hook'] );
	}
);
usort(
	$filters,
	static function ( $a, $b ) {
		return strcmp( $a['hook'], $b['hook'] );
	}
);

// Actions Table.
$out .= "## Actions\n\n";
$out .= "These are the actions provided by the Accessibility Checker plugin that you can hook into.\n\n";

foreach ( $actions as $entry ) {
	// Create a GitHub link for the file and line.
	$parts     = explode( DIRECTORY_SEPARATOR, $entry['file'] );
	$enc_parts = array_map( 'rawurlencode', $parts );
	$rel       = implode( '/', $enc_parts );
	$url       = $github_base . $rel . '#L' . $entry['line'];

	// Clean summary for table cell (escape pipes and newlines).
	$summary = str_replace( [ "\n", "\r", '|' ], [ ' ', ' ', '\|' ], $entry['summary'] );
	$since   = isset( $entry['since'] ) && ! empty( $entry['since'] ) ? $entry['since'] : 'N/A';

	// Action header.
	$out .= "### `{$entry['hook']}`\n\n";
	$out .= "*{$summary}*\n\n";
	
	// File location and version.
	$out .= "**Defined in:** [{$entry['file']}]({$url}) (line {$entry['line']})\n\n";
	$out .= "**Since:** {$since}\n\n";
	
	// Parameters section if available.
	if ( ! empty( $entry['params'] ) ) {
		$out .= "**Parameters:**\n\n";
		
		foreach ( $entry['params'] as $param_name => $param_data ) {
			$param_type = $param_data['type'];
			$param_desc = $param_data['description'];
			
			$out .= "- `\${$param_name}` ({$param_type}) - {$param_desc}\n";
		}
		$out .= "\n";
	}
	
	$out .= "---\n\n";
}

// Filters Table.
$out .= "## Filters\n\n";
$out .= "These are the filters provided by the Accessibility Checker plugin that you can modify.\n\n";

foreach ( $filters as $entry ) {
	// Create a GitHub link for the file and line.
	$parts     = explode( DIRECTORY_SEPARATOR, $entry['file'] );
	$enc_parts = array_map( 'rawurlencode', $parts );
	$rel       = implode( '/', $enc_parts );
	$url       = $github_base . $rel . '#L' . $entry['line'];

	// Clean summary for table cell (escape pipes and newlines).
	$summary = str_replace( [ "\n", "\r", '|' ], [ ' ', ' ', '\|' ], $entry['summary'] );
	$since   = isset( $entry['since'] ) && ! empty( $entry['since'] ) ? $entry['since'] : 'N/A';

	// Filter header.
	$out .= "### `{$entry['hook']}`\n\n";
	$out .= "*{$summary}*\n\n";
	
	// File location and version.
	$out .= "**Defined in:** [{$entry['file']}]({$url}) (line {$entry['line']})\n\n";
	$out .= "**Since:** {$since}\n\n";
	
	// Parameters section if available.
	if ( ! empty( $entry['params'] ) ) {
		$out .= "**Parameters:**\n\n";
		
		foreach ( $entry['params'] as $param_name => $param_data ) {
			$param_type = $param_data['type'];
			$param_desc = $param_data['description'];
			
			$out .= "- `\${$param_name}` ({$param_type}) - {$param_desc}\n";
		}
		$out .= "\n";
	}
	
	// Return value if available.
	if ( ! empty( $entry['return_info']['type'] ) ) {
		$return_type = $entry['return_info']['type'];
		$return_desc = $entry['return_info']['description'];
		
		$out .= "**Returns:** ({$return_type}) {$return_desc}\n\n";
	}
	
	$out .= "---\n\n";
}

$docs_path = realpath( __DIR__ . '/..' ) . '/docs/hooks.md';
file_put_contents( $docs_path, $out ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_file_put_contents

printf( "Generated docs/hooks.md with %d hooks.\n", count( $hooks ) );
