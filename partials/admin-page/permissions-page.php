<?php
/**
 * Permissions tab: assign Accessibility Checker capabilities to roles and users.
 *
 * The role matrix is edited one role at a time: pick a role from the dropdown and
 * the capabilities it can be granted appear below, each capability only selectable
 * when the role's live WordPress capabilities meet that capability's floor. The
 * checked state for every role lives in a hidden store (#edac-perm-store) which is
 * the source of truth submitted with the form, so switching roles never loses
 * unsaved changes and the page degrades safely without JavaScript.
 *
 * @package Accessibility_Checker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$edac_metadata = edac_capability_metadata();
$edac_roles    = edac_assignable_roles();
$edac_role_map = get_option( 'edac_capability_role_map', [] );
$edac_role_map = is_array( $edac_role_map ) ? $edac_role_map : [];

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag; the save round-trip is nonce-verified.
$edac_updated_flag  = isset( $_GET['updated'] ) ? sanitize_key( wp_unslash( $_GET['updated'] ) ) : '';
$edac_just_saved    = 'true' === $edac_updated_flag;
$edac_save_conflict = 'conflict' === $edac_updated_flag;
?>

<div class="edac-settings-permissions">

	<?php if ( $edac_just_saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Permissions saved.', 'accessibility-checker' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $edac_save_conflict ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Permissions were changed by someone else while you were editing, so your changes were not saved. Review the current permissions below and try again.', 'accessibility-checker' ); ?></p>
		</div>
	<?php endif; ?>

	<h2 class="edac-settings-permissions__heading">
		<?php esc_html_e( 'Permissions Settings', 'accessibility-checker' ); ?>
	</h2>

	<p class="edac-settings-permissions__intro">
		<?php esc_html_e( 'Choose a user role, then check the Accessibility Checker capabilities to grant it. A capability can only be granted to a role whose existing WordPress permissions meet its requirement. Administrators always have every capability.', 'accessibility-checker' ); ?>
	</p>

	<form class="edac-settings-permissions__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="edac_save_permissions">
		<?php wp_nonce_field( 'edac_save_permissions' ); ?>
		<?php
		/*
		 * A fingerprint of the role map as of this page load - submitted back and
		 * re-checked against the option's current value at save time, so a save
		 * whose starting snapshot is no longer current (someone else saved in
		 * between) is rejected instead of silently overwriting. See
		 * PermissionsPage::role_map_revision().
		 */
		?>
		<input type="hidden" name="edac_role_map_revision" value="<?php echo esc_attr( $this->role_map_revision( $edac_role_map ) ); ?>">

		<div class="edac-perm-role-field">
			<div class="edac-perm-role-field__header">
				<h3 class="edac-perm-role-field__title">
					<?php esc_html_e( 'Choose a Role', 'accessibility-checker' ); ?>
				</h3>
				<p class="edac-perm-role-field__description">
					<?php esc_html_e( 'Select the user role whose Accessibility Checker permissions you want to manage.', 'accessibility-checker' ); ?>
				</p>
			</div>
			<label for="edac-perm-role"><?php esc_html_e( 'Role', 'accessibility-checker' ); ?></label>
			<select id="edac-perm-role">
				<option value=""><?php esc_html_e( '— Select a role —', 'accessibility-checker' ); ?></option>
				<?php foreach ( $edac_roles as $edac_role_slug => $edac_role ) : ?>
					<option value="<?php echo esc_attr( $edac_role_slug ); ?>"><?php echo esc_html( translate_user_role( $edac_role['name'] ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div id="edac-perm-status" class="screen-reader-text" role="status" aria-live="polite" aria-atomic="true"></div>
		<div id="edac-perm-caps" class="edac-perm-caps"></div>

		<?php
		// The hidden store is the source of truth for the role matrix: one input
		// per currently-assigned capability/role pair. The picker adds and removes
		// these as capabilities are toggled.
		?>
		<div id="edac-perm-store" class="screen-reader-text">
			<?php
			foreach ( $edac_role_map as $edac_slug => $edac_assigned_roles ) :
				if ( ! isset( $edac_metadata[ $edac_slug ] ) ) {
					continue;
				}
				foreach ( (array) $edac_assigned_roles as $edac_assigned_role ) :
					if ( ! isset( $edac_roles[ $edac_assigned_role ] ) ) {
						continue;
					}
					?>
					<input type="hidden" name="edac_role_map[<?php echo esc_attr( $edac_slug ); ?>][]" value="<?php echo esc_attr( $edac_assigned_role ); ?>" data-cap="<?php echo esc_attr( $edac_slug ); ?>" data-role="<?php echo esc_attr( $edac_assigned_role ); ?>">
					<?php
				endforeach;
			endforeach;
			?>
		</div>

		<div class="edac-perm-actions">
			<?php submit_button( __( 'Save Permissions', 'accessibility-checker' ), 'primary', 'submit', false ); ?>
		</div>
	</form>
</div>
