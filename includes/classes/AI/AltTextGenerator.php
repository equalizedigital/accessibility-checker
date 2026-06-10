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
	 * Generate using the WordPress AI Client (WordPress 7.0+).
	 *
	 * @param string $image_url Full URL of the image.
	 * @param string $prompt    The generation prompt.
	 * @return array|\WP_Error
	 */
	private static function generate_with_wp_ai_client( string $image_url, string $prompt ) {
		try {
			$builder = wp_ai_client_prompt( 'accessibility-checker-alt-text' );

			if ( method_exists( $builder, 'with_image' ) ) {
				$builder = $builder->with_image( [ 'url' => $image_url ] );
			}

			if ( method_exists( $builder, 'with_message' ) ) {
				$builder = $builder->with_message( $prompt );
			}

			$response = $builder->send();
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'wp_ai_client_error', $e->getMessage() );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$text = '';
		if ( method_exists( $response, 'get_text' ) ) {
			$text = $response->get_text();
		} elseif ( is_string( $response ) ) {
			$text = $response;
		} else {
			return new \WP_Error( 'unexpected_response', __( 'Unexpected response format from AI service.', 'accessibility-checker' ) );
		}

		return self::parse_suggestions( $text );
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

			$text = '';
			if ( method_exists( $candidates, 'get_first_candidate_text' ) ) {
				$text = $candidates->get_first_candidate_text();
			} elseif ( is_string( $candidates ) ) {
				$text = $candidates;
			}

			return self::parse_suggestions( $text );

		} catch ( \Throwable $e ) {
			return new \WP_Error( 'ai_services_error', $e->getMessage() );
		}
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

		$data = json_decode( $text, true );

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
