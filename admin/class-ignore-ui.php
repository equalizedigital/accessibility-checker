<?php
/**
 * Class file for ignore/dismiss UI rendering.
 *
 * Provides the canonical list of dismiss reasons and shared HTML
 * rendering helpers for the ignore panel used across the sidebar,
 * issue modal, classic metabox, and pro log/issues pages.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that provides dismiss reason data and ignore panel UI rendering.
 */
class IgnoreUI {

	/**
	 * Get the dismiss reasons with translatable labels and descriptions.
	 *
	 * @return array<string, array{label: string, description: string}>
	 */
	public static function get_reasons(): array {
		return [
			'false_positive' => [
				'label'       => __( 'False positive', 'accessibility-checker' ),
				'description' => __( 'The scanner flagged this, but it does not apply to this content.', 'accessibility-checker' ),
			],
			'remediated'     => [
				'label'       => __( 'Remediated', 'accessibility-checker' ),
				'description' => __( 'The issue has been fixed, but the page has not been rescanned yet.', 'accessibility-checker' ),
			],
			'accessible'     => [
				'label'       => __( 'Confirmed accessible', 'accessibility-checker' ),
				'description' => __( 'Reviewed and verified to meet accessibility requirements.', 'accessibility-checker' ),
			],
		];
	}

	/**
	 * Render the full ignore/dismiss panel for an issue.
	 *
	 * Produces the entire ignore panel: user info, dismiss reason radios,
	 * comment textarea, and the submit/manage button. Used by both the
	 * classic metabox (AJAX details) and the pro log/issues pages.
	 *
	 * @param array $args {
	 *     Panel arguments.
	 *
	 *     @type int    $issue_id          The issue ID.
	 *     @type bool   $is_ignored        Whether the issue is currently dismissed.
	 *     @type int    $ignore_user       The user ID who dismissed the issue.
	 *     @type string $ignore_date       The UTC datetime string when the issue was dismissed.
	 *     @type string $ignore_comment    The dismiss comment.
	 *     @type string $ignore_reason     The dismiss reason key (e.g. 'false_positive').
	 *     @type int    $ignore_global     Whether the issue was globally ignored (1 or 0).
	 *     @type string $ignore_type       The rule type ('error' or 'warning').
	 *     @type bool   $ignore_permission Whether the current user can ignore issues.
	 * }
	 * @return string The ignore panel HTML.
	 */
	public static function render_ignore_panel( array $args ): string {

		$issue_id          = (int) ( $args['issue_id'] ?? 0 );
		$is_ignored        = (bool) ( $args['is_ignored'] ?? false );
		$ignore_user       = (int) ( $args['ignore_user'] ?? 0 );
		$ignore_date_raw   = (string) ( $args['ignore_date'] ?? '' );
		$ignore_comment    = (string) ( $args['ignore_comment'] ?? '' );
		$ignore_reason     = (string) ( $args['ignore_reason'] ?? '' );
		$ignore_global     = (int) ( $args['ignore_global'] ?? 0 );
		$ignore_type       = (string) ( $args['ignore_type'] ?? '' );
		$ignore_permission = (bool) ( $args['ignore_permission'] ?? true );

		// Derive display values.
		$ignore_user_info = get_userdata( $ignore_user );
		$username_html    = is_object( $ignore_user_info )
			? '<strong>' . esc_html__( 'Username:', 'accessibility-checker' ) . '</strong> ' . esc_html( $ignore_user_info->user_login )
			: '';

		$date_text = '';
		if ( $ignore_date_raw && '0000-00-00 00:00:00' !== $ignore_date_raw ) {
			$date_text = function_exists( 'edac_format_datetime_from_utc' )
				? edac_format_datetime_from_utc( $ignore_date_raw )
				: '';
		}
		$date_html = $date_text
			? '<strong>' . esc_html__( 'Date:', 'accessibility-checker' ) . '</strong> ' . esc_html( $date_text )
			: '';

		$ignore_action       = $is_ignored ? 'disable' : 'enable';
		$ignore_submit_label = $is_ignored
			? __( 'Reopen Issue', 'accessibility-checker' )
			: __( 'Dismiss Issue', 'accessibility-checker' );
		$comment_disabled    = $is_ignored ? 'disabled' : '';

		$ignore_icon = defined( 'EDAC_SVG_IGNORE_ICON' ) ? EDAC_SVG_IGNORE_ICON : '';

		// Build the HTML.
		$html = '<div id="edac-details-rule-records-record-ignore-' . $issue_id . '" class="edac-details-rule-records-record-ignore">';

		// Info section.
		$html .= '<div class="edac-details-rule-records-record-ignore-info">';
		$html .= '<span class="edac-details-rule-records-record-ignore-info-user">' . $username_html . '</span>';
		$html .= ' <span class="edac-details-rule-records-record-ignore-info-date">' . $date_html . '</span>';
		$html .= '</div>';

		// Dismiss reason radios.
		if ( true === $ignore_permission ) {
			$html .= self::render_reason_fieldset( $issue_id, $ignore_reason, $is_ignored );
		}

		// Comment.
		if ( true === $ignore_permission || ! empty( $ignore_comment ) ) {
			$html .= '<label for="edac-details-rule-records-record-ignore-comment-' . $issue_id . '">' . esc_html__( 'Comment', 'accessibility-checker' ) . '</label><br>';
			$html .= '<textarea rows="4" class="edac-details-rule-records-record-ignore-comment" id="edac-details-rule-records-record-ignore-comment-' . $issue_id . '" ' . $comment_disabled . '>' . esc_html( $ignore_comment ) . '</textarea>';
		}

		// Submit button or manage link.
		if ( $ignore_global && function_exists( 'edac_is_pro' ) && edac_is_pro() ) {
			if ( true === $ignore_permission ) {
				$html .= '<a href="' . esc_url( admin_url( 'admin.php?page=accessibility_checker_ignored&tab=global' ) ) . '" target="_blank" rel="noreferrer noopener" class="edac-details-rule-records-record-ignore-global button button-primary">' . esc_html__( 'Manage Globally Dismissed', 'accessibility-checker' ) . '<span style="margin-left: 0.5rem"><span aria-hidden="true"> ↗</span><span class="screen-reader-text">' . esc_html__( ', opens a new window', 'accessibility-checker' ) . '</span></span></a>';
			}
		} elseif ( true === $ignore_permission ) {
				$html .= '<button class="edac-details-rule-records-record-ignore-submit button button-primary" data-id="' . $issue_id . '" data-action="' . esc_attr( $ignore_action ) . '" data-type="' . esc_attr( $ignore_type ) . '">' . $ignore_icon . ' <span class="edac-details-rule-records-record-ignore-submit-label">' . esc_html( $ignore_submit_label ) . '</span></button>';
		}

		// No permission message.
		if ( false === $ignore_permission && ! $is_ignored ) {
			$html .= esc_html__( 'Your user account doesn\'t have permission to dismiss this issue.', 'accessibility-checker' );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the dismiss reason radio fieldset HTML.
	 *
	 * Returns a <fieldset> with radio buttons for each dismiss reason.
	 *
	 * @param int    $issue_id       The issue ID (used for unique input IDs/names).
	 * @param string $current_reason The currently saved dismiss reason value, or empty string.
	 * @param bool   $is_ignored     Whether the issue is currently dismissed.
	 *
	 * @return string The fieldset HTML.
	 */
	public static function render_reason_fieldset( int $issue_id, string $current_reason = '', bool $is_ignored = false ): string {
		$reasons = self::get_reasons();
		$html    = '<fieldset class="edac-details-rule-records-record-ignore-reason">';
		$html   .= '<legend>' . esc_html__( 'Dismiss issue as:', 'accessibility-checker' ) . '</legend>';

		foreach ( $reasons as $reason_value => $reason_data ) {
			$radio_id       = 'edac-ignore-reason-' . $issue_id . '-' . $reason_value;
			$description_id = $radio_id . '-description';
			$checked        = ( $current_reason === $reason_value ) ? ' checked' : '';

			// Default to the first option when not yet dismissed.
			if ( ! $is_ignored && '' === $current_reason && array_key_first( $reasons ) === $reason_value ) {
				$checked = ' checked';
			}

			$disabled_attr = $is_ignored ? ' disabled' : '';

			$html .= '<div class="edac-ignore-reason-option">';
			$html .= '<input type="radio" id="' . esc_attr( $radio_id ) . '" name="edac-ignore-reason-' . $issue_id . '" value="' . esc_attr( $reason_value ) . '" class="edac-details-rule-records-record-ignore-reason-input" aria-describedby="' . esc_attr( $description_id ) . '"' . $checked . $disabled_attr . ' />';
			$html .= '<label for="' . esc_attr( $radio_id ) . '">' . esc_html( $reason_data['label'] ) . '</label>';
			$html .= '<p class="edac-ignore-reason-description" id="' . esc_attr( $description_id ) . '">' . esc_html( $reason_data['description'] ) . '</p>';
			$html .= '</div>';
		}

		$html .= '</fieldset>';

		return $html;
	}
}
