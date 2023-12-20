<?php
/**
 * Gets the general information.
 *
 * @since 1.7.1 TODO: Update to the correct version.
 * @package Accessibility_Checker
 */

namespace EDAC\Admin\SiteHealth;

/**
 * Loads general information into Site Health
 *
 * @since 1.7.1 TODO: Update to the correct version.
 */
class General {

	/**
	 * General constructor.
	 */
	public function __construct() {
	}

	/**
	 * Gets the site health section.
	 *
	 * @since 1.7.1 TODO: Update to the correct version.
	 * @return array
	 */
	public function get() {
		return array(
			'label'  => __( 'Accessibility Checker &mdash; General', 'accessibility-checker' ),
			'fields' => array(
				'version'                  => array(
					'label' => 'EDAC Version',
					'value' => EDAC_VERSION,
				),
				'edac_is_pro'               => array(
					'label' => 'EDAC (Pro) Status',
					'value' => '',
				),
				'edac_activated'            => array(
					'label' => 'EDAC Activation Date',
					//'value' => $this->get_date( 'edac_activation_date' ),
                    'value' => get_option( 'edac_activation_date' ),
				),
				'edac_pro_activated'        => array(
					'label' => 'EDAC (Pro) Activation Date',
					//'value' => $this->get_date( 'edacp_activation_date' ),
                    'value' => get_option( 'edacp_activation_date' ),
				),
			),
		);
	}
}
