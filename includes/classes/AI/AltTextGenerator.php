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
 * (WordPress 7.0+ wp_ai_client_prompt) or the AI Services plugin.
 *
 * Two wp_ai_client_prompt() implementations exist with different APIs:
 *
 * Standalone wp-ai-client plugin (deprecated):
 *   wp_ai_client_prompt( $prompt )->with_file( $url, $mime )->generate_text()
 *   generate_text() returns string|WP_Error directly.
 *
 * WordPress 7.0 built-in:
 *   wp_ai_client_prompt( $prompt )->with_image( ['url'=>$url] )->send()
 *   send() returns GenerativeAiResult|WP_Error.
 *   Text extracted via: get_candidates()[0]->get_content()->get_parts()[0]->get_text()
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

		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );
		if ( ! $image_url ) {
			return new \WP_Error( 'no_image_url', __( 'Could not retrieve an image URL for this attachment.', 'accessibility-checker' ) );
		}

		$num_suggestions = max( 1, min( 5, $num_suggestions ) );
		$prompt          = self::build_prompt( $num_suggestions );

		if ( function_exists( 'wp_ai_client_prompt' ) ) {
			return self::generate_with_wp_ai_client( $image_url, $prompt );
		}

		return self::generate_with_ai_services( $image_url, $prompt );
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
	 * Determine the MIME type of an image from its URL extension.
	 *
	 * @param string $image_url Image URL.
	 * @return string MIME type string, defaulting to image/jpeg.
	 */
	private static function get_mime_type( string $image_url ): string {
		$ext      = strtolower( (string) pathinfo( wp_parse_url( $image_url, PHP_URL_PATH ) ?? '', PATHINFO_EXTENSION ) );
		$mime_map = [
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
			'gif'  => 'image/gif',
			'webp' => 'image/webp',
			'avif' => 'image/avif',
		];

		return $mime_map[ $ext ] ?? 'image/jpeg';
	}

	/**
	 * Generate using the WordPress AI Client.
	 *
	 * Detects which builder variant is present and calls the correct
	 * terminate method:
	 *   - Standalone wp-ai-client: generate_text() → string
	 *   - WordPress 7.0 built-in:  send()          → GenerativeAiResult
	 *
	 * @param string $image_url Full URL of the image.
	 * @param string $prompt    The generation prompt.
	 * @return array|\WP_Error
	 */
	private static function generate_with_wp_ai_client( string $image_url, string $prompt ) {
		try {
			$builder = wp_ai_client_prompt( $prompt );

			if ( is_wp_error( $builder ) ) {
				return $builder;
			}

			$mime_type = self::get_mime_type( $image_url );

			// ── Standalone wp-ai-client plugin ────────────────────────────────
			// generate_text() is the terminate method, returns string|WP_Error.
			if ( method_exists( $builder, 'generate_text' ) ) {
				if ( method_exists( $builder, 'with_file' ) ) {
					$builder = $builder->with_file( $image_url, $mime_type );
				}
				$text = $builder->generate_text();
				if ( is_wp_error( $text ) ) {
					return $text;
				}
				return self::parse_suggestions( (string) $text );
			}

			// ── WordPress 7.0 built-in ────────────────────────────────────────
			// send() is the terminate method, returns GenerativeAiResult|WP_Error.
			if ( method_exists( $builder, 'send' ) ) {
				// Attach image — WP 7.0 uses with_image(), fall back to with_file().
				if ( method_exists( $builder, 'with_image' ) ) {
					$builder = $builder->with_image( [ 'url' => $image_url ] );
				} elseif ( method_exists( $builder, 'with_file' ) ) {
					$builder = $builder->with_file( $image_url, $mime_type );
				}

				$result = $builder->send();

				if ( is_wp_error( $result ) ) {
					return $result;
				}

				$text = self::extract_text_from_result( $result );
				if ( is_wp_error( $text ) ) {
					return $text;
				}

				return self::parse_suggestions( $text );
			}

			// Neither known terminate method found — report builder class for diagnosis.
			return new \WP_Error(
				'no_generation_method',
				sprintf(
					/* translators: %s: PHP class name of the AI builder */
					__( 'No supported generation method found on the AI builder (%s). Please report this to the plugin author.', 'accessibility-checker' ),
					get_class( $builder )
				)
			);

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

			$text = self::extract_text_from_result( $candidates );
			if ( is_wp_error( $text ) ) {
				return $text;
			}

			return self::parse_suggestions( $text );

		} catch ( \Throwable $e ) {
			return new \WP_Error( 'ai_services_error', $e->getMessage() );
		}
	}

	/**
	 * Extract a generated text string from any AI result object.
	 *
	 * Handles three known response shapes in priority order:
	 *
	 * 1. String — returned directly by standalone wp-ai-client generate_text().
	 * 2. ai-services Candidates (object with get()):
	 *    ->get(0)->get_content()->get_parts()->get(0)->get_text()
	 * 3. WP 7.0 GenerativeAiResult (get_candidates() returns array):
	 *    ->get_candidates()[0]->get_content()->get_parts()[0]->get_text()
	 * 4. Convenience shortcuts: get_first_candidate_text(), get_text(), __toString.
	 *
	 * @param mixed $result Response object from any supported AI SDK.
	 * @return string|\WP_Error
	 */
	private static function extract_text_from_result( $result ) {
		if ( is_string( $result ) ) {
			return $result;
		}

		if ( ! is_object( $result ) ) {
			return new \WP_Error( 'unexpected_response', __( 'Unexpected AI response format. Please try again.', 'accessibility-checker' ) );
		}

		try {
			// ── ai-services Candidates object (uses ->get() for indexed access) ──
			if ( method_exists( $result, 'get' ) && ! method_exists( $result, 'get_candidates' ) ) {
				$candidate = $result->get( 0 );
				if ( $candidate ) {
					$text = self::text_from_candidate( $candidate );
					if ( is_string( $text ) ) {
						return $text;
					}
				}
			}

			// ── WP 7.0 GenerativeAiResult (get_candidates() returns plain array) ──
			if ( method_exists( $result, 'get_candidates' ) ) {
				$candidates    = $result->get_candidates();
				$first         = is_array( $candidates ) ? reset( $candidates ) : null;
				if ( $first ) {
					$text = self::text_from_candidate( $first );
					if ( is_string( $text ) ) {
						return $text;
					}
				}
			}

			// ── Convenience / shortcut methods ───────────────────────────────────
			foreach ( [ 'get_first_candidate_text', 'get_text', 'getText', '__toString' ] as $method ) {
				if ( method_exists( $result, $method ) ) {
					$text = $result->$method();
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
				get_class( $result )
			)
		);
	}

	/**
	 * Extract text from a single candidate object (shared by both SDK shapes).
	 *
	 * Tries the full parts chain then falls back to direct text methods.
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
				$parts     = $content->get_parts();
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

		// Direct text shortcut on the candidate itself.
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
