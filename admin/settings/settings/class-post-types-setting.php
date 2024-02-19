<?php
/**
 * Class file for managing the registration and rendering of the "Post Types To Be Checked" setting.
 *
 * @package EDAC\Admin\Settings
 */

namespace EDAC\Admin\Settings;

/**
 * Manages the registration and rendering of the "Post Types To Be Checked" setting.
 */
class Post_Types_Setting implements Setting_Interface {

	/**
	 * Registers the settings field and the associated setting in WordPress.
	 */
	public function add_settings_field() {
		add_settings_field(
			'edac_post_types',
			__( 'Post Types To Be Checked', 'accessibility-checker' ),
			array( $this, 'callback' ),
			'edac_settings',
			'edac_general',
			array( 'label_for' => 'edac_post_types' )
		);

		register_setting( 'edac_settings', 'edac_post_types', array( $this, 'sanitize' ) );
	}

	/**
	 * Render the checkbox input field for post_types option
	 */
	public function callback() {
		$selected_post_types = get_option( 'edac_post_types' ) ? get_option( 'edac_post_types' ) : array();
		$post_types          = edac_post_types();
		$custom_post_types   = edac_custom_post_types();
		$all_post_types      = ( is_array( $post_types ) && is_array( $custom_post_types ) ) ? array_merge( $post_types, $custom_post_types ) : array();
		?>
		<fieldset>
			<?php
			if ( $all_post_types ) {
				foreach ( $all_post_types as $post_type ) {
					$disabled = in_array( $post_type, $post_types, true ) ? '' : 'disabled';
					?>
					<label>
						<input type="checkbox" name="<?php echo 'edac_post_types[]'; ?>" value="<?php echo esc_attr( $post_type ); ?>"
							<?php
							checked( in_array( $post_type, $selected_post_types, true ), 1 );
							echo esc_attr( $disabled );
							?>
						>
						<?php echo esc_html( $post_type ); ?>
					</label>
					<br>
					<?php
				}
			}
			?>
		</fieldset>
		<?php if ( EDAC_KEY_VALID === false ) { ?>
			<p class="edac-description">
				<?php
				echo esc_html__( 'To check content other than posts and pages, please ', 'accessibility-checker' );
				?>
				<a href="https://my.equalizedigital.com/" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'upgrade to pro', 'accessibility-checker' ); ?></a>
				<?php esc_html_e( ' (opens in a new window)', 'accessibility-checker' ); ?>
			</p>
		<?php } else { ?>
			<p class="edac-description">
				<?php
				esc_html_e( 'Choose which post types should be checked during a scan. Please note, removing a previously selected post type will remove its scanned information and any custom ignored warnings that have been setup.', 'accessibility-checker' );
				?>
			</p>
			<?php
		}
	}

	/**
	 * Sanitize the post type value before being saved to database
	 *
	 * @param array $selected_post_types Post types to sanitize.
	 *
	 * @return array
	 */
	public function sanitize( $selected_post_types ) {
		$post_types = edac_post_types();

		if ( $selected_post_types ) {
			foreach ( $selected_post_types as $key => $post_type ) {
				if ( ! in_array( $post_type, $post_types, true ) ) {
					unset( $selected_post_types[ $key ] );
				}
			}
		}

		// get unselected post types.
		$unselected_post_types = $post_types;
		if ( $selected_post_types ) {
			$unselected_post_types = array_diff( $post_types, $selected_post_types );
		}

		// delete unselected post type issues.
		if ( $unselected_post_types ) {
			foreach ( $unselected_post_types as $unselected_post_type ) {
				edac_delete_cpt_posts( $unselected_post_type );
			}
		}

		// clear cached stats if selected posts types change.
		if ( get_option( 'edac_post_types' ) !== $selected_post_types ) {
			$scan_stats = new \EDAC\Admin\Scans_Stats();
			$scan_stats->clear_cache();

			if ( class_exists( '\EDACP\Scans' ) ) {
				delete_option( 'edacp_fullscan_completed_at' );
			}
		}

		return $selected_post_types;
	}
}
