<?php
/**
 * AI Alt Text Generator.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates alt text suggestions for images using the WordPress AI connector
 * (wp_ai_client_prompt) or the AI Services plugin.
 *
 * WordPress AI connector (WordPress/ai plugin or WP 7.0 built-in):
 *   wp_ai_client_prompt( $prompt )->with_file( $data_uri )->generate_text()
 *   with_file() expects a base64 data URI, not a URL.
 *   generate_text() returns string|WP_Error directly.
 *
 * AI Services plugin (Felix Arntz / felixarntz/ai-services):
 *   ai_services()->get_available_service()->get_model()->generate_text( $content )
 *   Returns a Candidates object; text extracted via ->get(0)->get_content()->...->get_text().
 */
class AltTextGenerator {

	/**
	 * Rule slugs that support AI-assisted alt text generation.
	 */
	const SUPPORTED_RULES = [ 'img_alt_missing', 'img_alt_empty', 'img_alt_invalid' ];

	/**
	 * Number of alt text suggestions to request by default.
	 */
	const DEFAULT_NUM_SUGGESTIONS = 3;

	/**
	 * Check whether any supported AI integration is available.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return function_exists( 'wp_ai_client_prompt' ) || function_exists( 'ai_services' );
	}

	/**
	 * Generate alt text suggestions for a media attachment.
	 *
	 * @param int $attachment_id   WordPress attachment post ID.
	 * @param int $num_suggestions Number of suggestions to generate (1–5).
	 * @return array|\WP_Error     Array of suggestion objects or WP_Error on failure.
	 */
	public static function generate( int $attachment_id, int $num_suggestions = self::DEFAULT_NUM_SUGGESTIONS ) {
		if ( ! self::is_available() ) {
			return new \WP_Error(
				'no_ai_service',
				__( 'No AI service is available. Please configure the WordPress AI connector under Settings > Connectors, or install and configure the AI Services plugin.', 'accessibility-checker' )
			);
		}

		if ( 'attachment' !== get_post_type( $attachment_id ) ) {
			return new \WP_Error( 'invalid_attachment', __( 'The provided ID is not a valid media attachment.', 'accessibility-checker' ) );
		}

		$num_suggestions = max( 1, min( 5, $num_suggestions ) );
		$prompt          = self::build_prompt( $num_suggestions );

		if ( function_exists( 'wp_ai_client_prompt' ) ) {
			$data_uri = self::get_image_data_uri( $attachment_id );
			if ( is_wp_error( $data_uri ) ) {
				return $data_uri;
			}
			return self::generate_with_wp_ai_client( $data_uri, $prompt );
		}

		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
		if ( ! $image_url ) {
			return new \WP_Error( 'no_image_url', __( 'Could not retrieve an image URL for this attachment.', 'accessibility-checker' ) );
		}

		return self::generate_with_ai_services( $image_url, $prompt );
	}

	/**
	 * Get the image as a base64 data URI, reading from the local filesystem
	 * first and falling back to downloading the URL.
	 *
	 * @param int $attachment_id Attachment post ID.
	 * @return string|\WP_Error Data URI string or WP_Error on failure.
	 */
	private static function get_image_data_uri( int $attachment_id ) {
		// Prefer reading from disk — avoids an HTTP round-trip.
		$file_path = get_attached_file( $attachment_id );
		if ( $file_path && file_exists( $file_path ) ) {
			$data_uri = self::file_to_data_uri( $file_path );
			if ( $data_uri ) {
				return $data_uri;
			}
		}

		// Fall back to downloading the image URL.
		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
		if ( ! $image_url ) {
			return new \WP_Error( 'no_image_url', __( 'Could not retrieve an image URL for this attachment.', 'accessibility-checker' ) );
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$temp_file = download_url( $image_url );
		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		$data_uri = self::file_to_data_uri( $temp_file );
		wp_delete_file( $temp_file );

		if ( ! $data_uri ) {
			return new \WP_Error( 'file_read_error', __( 'Could not read the image file.', 'accessibility-checker' ) );
		}

		return $data_uri;
	}

	/**
	 * Convert a local file path to a base64 data URI.
	 *
	 * @param string $file_path Absolute path to the file.
	 * @return string|null Data URI or null on failure.
	 */
	private static function file_to_data_uri( string $file_path ): ?string {
		$mime_type = wp_check_filetype( $file_path )['type'] ?? null;
		if ( ! $mime_type ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		$contents = file_get_contents( $file_path );
		if ( false === $contents ) {
			return null;
		}

		return 'data:' . $mime_type . ';base64,' . base64_encode( $contents );
	}

	/**
	 * Build the structured prompt for alt text generation.
	 *
	 * @param int $num Number of suggestions to request.
	 * @return string
	 */
	private static function build_prompt( int $num ): string {
		return sprintf(
			'Analyze this image and generate %1$d distinct, accessibility-focused alt text suggestions.

Each suggestion must:
- Be under 125 characters (optimal for screen readers)
- Describe exactly what is visually visible in the image
- Focus on a different visual element or aspect across the %1$d suggestions (e.g., main subject, setting, action, or composition)
- Avoid starting with "Image of", "Photo of", "Picture of", or similar redundant phrases
- Use plain language, present tense, and active voice

Return ONLY a valid JSON array with exactly %1$d objects. Each object must have these keys:
- "alt": the alt text string (required, under 125 characters)
- "focus": a short noun phrase identifying the focal element of this suggestion (required)
- "explanation": one sentence explaining what aspect of the image this alt text emphasizes (required)

Example format:
[{"alt": "Worker harvesting ripe coffee cherries by hand", "focus": "harvester", "explanation": "Emphasizes the human labor and manual process."}]

Return only the JSON array with no markdown fencing or other text.',
			$num
		);
	}

	/**
	 * Generate using the WordPress AI connector (wp_ai_client_prompt).
	 *
	 * The builder API is:
	 *   wp_ai_client_prompt( $prompt )->with_file( $data_uri )->generate_text()
	 *
	 * with_file() requires a base64 data URI, not a plain URL.
	 * generate_text() returns string|WP_Error directly.
	 *
	 * @param string $data_uri Base64 data URI of the image.
	 * @param string $prompt   The generation prompt.
	 * @return array|\WP_Error
	 */
	private static function generate_with_wp_ai_client( string $data_uri, string $prompt ) {
		try {
			$result = wp_ai_client_prompt( $prompt )
				->with_file( $data_uri )
				->generate_text();

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return self::parse_suggestions( (string) $result );

		} catch ( \Throwable $e ) {
			return new \WP_Error( 'wp_ai_client_error', $e->getMessage() );
		}
	}

	/**
	 * Generate using the AI Services plugin (Felix Arntz / felixarntz/ai-services).
	 *
	 * @param string $image_url Full URL of the image.
	 * @param string $prompt    The generation prompt.
	 * @return array|\WP_Error
	 */
	private static function generate_with_ai_services( string $image_url, string $prompt ) {
		try {
			$ai = ai_services();

			// Request a service that supports both image understanding and text generation.
			$service = $ai->get_available_service(
				[
					'capabilities' => [ 'MULTIMODAL_INPUT', 'TEXT_GENERATION' ],
				]
			);

			if ( is_wp_error( $service ) ) {
				// Fallback: any text-generation capable service.
				$service = $ai->get_available_service(
					[
						'capabilities' => [ 'TEXT_GENERATION' ],
					]
				);
			}

			if ( is_wp_error( $service ) ) {
				return new \WP_Error(
					'no_configured_service',
					__( 'No AI service is configured. Please add an API key under Settings > AI Services.', 'accessibility-checker' )
				);
			}

			$model = $service->get_model(
				[
					'feature'      => 'accessibility-checker-alt-text',
					'capabilities' => [ 'MULTIMODAL_INPUT', 'TEXT_GENERATION' ],
				]
			);

			if ( is_wp_error( $model ) ) {
				return $model;
			}

			// Build content: image URL part + text prompt part.
			$content_args = [
				[
					'role'  => 'user',
					'parts' => [
						[
							'type' => 'image_url',
							'url'  => $image_url,
						],
						[
							'type' => 'text',
							'text' => $prompt,
						],
					],
				],
			];

			$candidates = $model->generate_text( $content_args );

			if ( is_wp_error( $candidates ) ) {
				return $candidates;
			}

			$text = self::extract_text_from_candidates( $candidates );
			if ( is_wp_error( $text ) ) {
				return $text;
			}

			return self::parse_suggestions( $text );

		} catch ( \Throwable $e ) {
			return new \WP_Error( 'ai_services_error', $e->getMessage() );
		}
	}

	/**
	 * Extract a generated text string from an ai-services Candidates object.
	 *
	 * Candidates object shape:
	 *   ->get(0)->get_content()->get_parts()->get(0)->get_text()
	 *
	 * @param mixed $candidates Candidates object from ai-services generate_text().
	 * @return string|\WP_Error
	 */
	private static function extract_text_from_candidates( $candidates ) {
		if ( is_string( $candidates ) ) {
			return $candidates;
		}

		if ( ! is_object( $candidates ) ) {
			return new \WP_Error( 'unexpected_response', __( 'Unexpected AI response format. Please try again.', 'accessibility-checker' ) );
		}

		try {
			// ai-services Candidates: indexed access via ->get().
			if ( method_exists( $candidates, 'get' ) ) {
				$candidate = $candidates->get( 0 );
				if ( $candidate ) {
					$text = self::text_from_candidate( $candidate );
					if ( is_string( $text ) && '' !== $text ) {
						return $text;
					}
				}
			}

			// Convenience / shortcut methods.
			foreach ( [ 'get_first_candidate_text', 'get_text', 'getText' ] as $method ) {
				if ( method_exists( $candidates, $method ) ) {
					$text = $candidates->$method();
					if ( is_string( $text ) && '' !== $text ) {
						return $text;
					}
				}
			}
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'text_extraction_error', $e->getMessage() );
		}

		return new \WP_Error(
			'unexpected_response',
			sprintf(
				/* translators: %s: PHP class name of the unexpected response object */
				__( 'Unexpected AI response type (%s). Please report this to the plugin author.', 'accessibility-checker' ),
				get_class( $candidates )
			)
		);
	}

	/**
	 * Extract text from a single candidate object.
	 *
	 * @param mixed $candidate Candidate object.
	 * @return string|null Text string or null if not extractable.
	 */
	private static function text_from_candidate( $candidate ): ?string {
		if ( ! is_object( $candidate ) ) {
			return null;
		}

		if ( method_exists( $candidate, 'get_content' ) ) {
			$content = $candidate->get_content();
			if ( $content && method_exists( $content, 'get_parts' ) ) {
				$parts      = $content->get_parts();
				$first_part = null;

				if ( is_array( $parts ) ) {
					$first_part = reset( $parts ) ?: null;
				} elseif ( is_object( $parts ) && method_exists( $parts, 'get' ) ) {
					$first_part = $parts->get( 0 );
				}

				if ( $first_part && method_exists( $first_part, 'get_text' ) ) {
					$text = $first_part->get_text();
					if ( is_string( $text ) ) {
						return $text;
					}
				}
			}
		}

		foreach ( [ 'get_text', 'getText' ] as $method ) {
			if ( method_exists( $candidate, $method ) ) {
				$text = $candidate->$method();
				if ( is_string( $text ) ) {
					return $text;
				}
			}
		}

		return null;
	}

	/**
	 * Parse JSON suggestion objects from the AI response text.
	 *
	 * @param string $text Raw text from the AI response.
	 * @return array|\WP_Error
	 */
	private static function parse_suggestions( string $text ) {
		// Strip markdown code fences if the AI wrapped the JSON.
		$text = preg_replace( '/^```(?:json)?\s*/m', '', $text );
		$text = preg_replace( '/\s*```\s*$/m', '', $text );
		$text = trim( $text );

		$data = json_decode( $text, true, 3 );

		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $data ) ) {
			return new \WP_Error(
				'parse_error',
				__( 'Could not parse the AI response. Please try again.', 'accessibility-checker' )
			);
		}

		$suggestions = [];
		foreach ( $data as $item ) {
			if ( ! is_array( $item ) || empty( $item['alt'] ) ) {
				continue;
			}
			$suggestions[] = [
				'alt'         => sanitize_text_field( $item['alt'] ),
				'focus'       => sanitize_text_field( $item['focus'] ?? '' ),
				'explanation' => sanitize_text_field( $item['explanation'] ?? '' ),
			];
		}

		if ( empty( $suggestions ) ) {
			return new \WP_Error(
				'no_suggestions',
				__( 'No valid alt text suggestions were generated. Please try again.', 'accessibility-checker' )
			);
		}

		return $suggestions;
	}
}
