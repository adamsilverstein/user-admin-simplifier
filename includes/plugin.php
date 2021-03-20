<?php
/**
 * Class User_Admin_Simplifier\Plugin
 *
 * @package User_Admin_Simplifier
*/
namespace User_Admin_Simplifier;

/**
 * Main plugin class.
 *
 * @since 2.0.0
 */
final class Plugin {
	/**
	 * The plugin context object.
	 *
	 * @since 2.0.0
	 * @var Context
	 */
	private $context;

	/**
	 * Sets the plugin main file.
	 *
	 * @since 2.0.0
	 *
	 * @param string $main_file Absolute path to the plugin main file.
	 */
	public function __construct( $main_file ) {
		$this->context = $main_file;
	}
}