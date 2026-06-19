<?php
/**
 * AI Simplified Summary Generator.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\AI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates a simplified summary of post content using the WordPress AI
 * connector (wp_ai_client_prompt) or the AI Services plugin.
 *
 * The summary must be written at or below an 8th-grade reading level to
 * satisfy WCAG 3.1.5 (Reading Level, AAA).
 *
 * WordPress AI connector:
 *   wp_ai_client_prompt( $prompt )->generate_text()  → string|WP_Error
 *
 * AI Services plugin:
 *   ai_services()->get_available_service()->get_model()->generate_text()
 */
class SimplifiedSummaryGenerator {

	/**
	 * Maximum number of characters of post content to send to the AI.
	 * Keeps token usage and latency reasonable for long posts.
	 */
	const MAX_CONTENT_LENGTH = 8000;

	/**
	 * Check whether any supported AI integration is available.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return AltTextGenerator::is_available();
	}

	/**
	 * Generate a simplified summary for a post.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return string|\WP_Error The generated summary text or WP_Error on failure.
	 */
	public static function generate( int $post_id ) {
		if ( ! self::is_available() ) {
			return new \WP_Error(
				'no_ai_service',
				__( 'No AI service is available. Please configure the WordPress AI connector under Settings > Connectors, or install and configure the AI Services plugin.', 'accessibility-checker' )
			);
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'invalid_post', __( 'The provided ID is not a valid post.', 'accessibility-checker' ) );
		}

		$content = self::get_post_text( $post );
		if ( '' === $content ) {
			return new \WP_Error( 'no_content', __( 'This post does not have enough content to summarize.', 'accessibility-checker' ) );
		}

		$prompt = self::build_prompt( $content );

		if ( function_exists( 'wp_ai_client_prompt' ) ) {
			return self::generate_with_wp_ai_client( $prompt );
		}

		return self::generate_with_ai_services( $prompt );
	}

	/**
	 * Extract plain text from post content, applying the same filters used
	 * by the readability REST handler so the AI sees what the reader sees.
	 *
	 * @param \WP_Post $post Post object.
	 * @return string Plain text content, truncated to MAX_CONTENT_LENGTH.
	 */
	private static function get_post_text( \WP_Post $post ): string {
		$content = $post->post_content;

		// Apply content filters (shortcodes, blocks, etc.).
		$content = apply_filters( 'the_content', $content );

		// Allow third-party plugins to modify what we send to the AI.
		$content = apply_filters( 'edac_filter_readability_content', $content, $post->ID );

		// Strip all HTML tags, matching the readability grade calculation.
		$content = wp_filter_nohtml_kses( $content );
		$content = str_replace( ']]>', ']]&gt;', $content );
		$content = trim( $content );

		if ( strlen( $content ) > self::MAX_CONTENT_LENGTH ) {
			$content = substr( $content, 0, self::MAX_CONTENT_LENGTH );
		}

		return $content;
	}

	/**
	 * Build the AI prompt.
	 *
	 * @param string $content Plain-text post content.
	 * @return string
	 */
	private static function build_prompt( string $content ): string {
		return sprintf(
			'Write a simplified summary of the following content. The summary must:
- Be written at or below an 8th-grade reading level (Flesch-Kincaid Grade Level 8 or lower)
- Use short sentences (15 words or fewer on average)
- Use common, everyday words — avoid jargon, technical terms, and complex vocabulary
- Cover the main idea and key points of the content
- Be between 2 and 5 sentences long
- Be written in plain prose, not as a list or with headings
- Be suitable for people with cognitive or reading disabilities

Return only the summary text with no preamble, explanation, or formatting.

Content to summarize:
%s',
			$content
		);
	}

	/**
	 * Generate using the WordPress AI connector (wp_ai_client_prompt).
	 *
	 * Text-only prompt — no image attachment needed.
	 * generate_text() returns string|WP_Error directly.
	 *
	 * @param string $prompt The generation prompt.
	 * @return string|\WP_Error
	 */
	private static function generate_with_wp_ai_client( string $prompt ) {
		try {
			$result = wp_ai_client_prompt( $prompt )->generate_text();

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return sanitize_textarea_field( trim( (string) $result ) );

		} catch ( \Throwable $e ) {
			return new \WP_Error( 'wp_ai_client_error', $e->getMessage() );
		}
	}

	/**
	 * Generate using the AI Services plugin (Felix Arntz / felixarntz/ai-services).
	 *
	 * @param string $prompt The generation prompt.
	 * @return string|\WP_Error
	 */
	private static function generate_with_ai_services( string $prompt ) {
		try {
			$ai      = ai_services();
			$service = $ai->get_available_service( [ 'capabilities' => [ 'TEXT_GENERATION' ] ] );

			if ( is_wp_error( $service ) ) {
				return new \WP_Error(
					'no_configured_service',
					__( 'No AI service is configured. Please add an API key under Settings > AI Services.', 'accessibility-checker' )
				);
			}

			$model = $service->get_model(
				[
					'feature'      => 'accessibility-checker-simplified-summary',
					'capabilities' => [ 'TEXT_GENERATION' ],
				]
			);

			if ( is_wp_error( $model ) ) {
				return $model;
			}

			$candidates = $model->generate_text(
				[
					[
						'role'  => 'user',
						'parts' => [ [ 'type' => 'text', 'text' => $prompt ] ],
					],
				]
			);

			if ( is_wp_error( $candidates ) ) {
				return $candidates;
			}

			$text = self::extract_text( $candidates );
			if ( is_wp_error( $text ) ) {
				return $text;
			}

			return sanitize_textarea_field( trim( $text ) );

		} catch ( \Throwable $e ) {
			return new \WP_Error( 'ai_services_error', $e->getMessage() );
		}
	}

	/**
	 * Extract text from an ai-services Candidates object.
	 *
	 * @param mixed $candidates Candidates object.
	 * @return string|\WP_Error
	 */
	private static function extract_text( $candidates ) {
		if ( is_string( $candidates ) ) {
			return $candidates;
		}

		if ( ! is_object( $candidates ) ) {
			return new \WP_Error( 'unexpected_response', __( 'Unexpected AI response format. Please try again.', 'accessibility-checker' ) );
		}

		try {
			if ( method_exists( $candidates, 'get' ) ) {
				$candidate = $candidates->get( 0 );
				if ( $candidate && method_exists( $candidate, 'get_content' ) ) {
					$content = $candidate->get_content();
					if ( $content && method_exists( $content, 'get_parts' ) ) {
						$parts      = $content->get_parts();
						$first_part = is_array( $parts ) ? reset( $parts ) : ( method_exists( $parts, 'get' ) ? $parts->get( 0 ) : null );
						if ( $first_part && method_exists( $first_part, 'get_text' ) ) {
							$text = $first_part->get_text();
							if ( is_string( $text ) && '' !== $text ) {
								return $text;
							}
						}
					}
				}
			}

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
				/* translators: %s: PHP class name */
				__( 'Unexpected AI response type (%s). Please report this to the plugin author.', 'accessibility-checker' ),
				get_class( $candidates )
			)
		);
	}
}
