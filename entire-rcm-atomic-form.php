<?php
/**
 * Plugin Name: Entire RCM — Atomic Form Widget
 * Description: A native Elementor v4 (atomic) form element. Renders any shortcode — Contact Form 7 by default — inside the atomic tree, so forms stop being a v3 island in an otherwise-v4 page.
 * Version:     1.0.0
 * Author:      Entire RCM
 * License:     GPL-2.0-or-later
 * Text Domain: entire-rcm-atomic-form
 *
 * WHY THIS EXISTS
 * ---------------
 * Elementor free has no v4 form element: `e-form` and `e-form-input` are Pro.
 * On free, a v4 page therefore cannot contain a form built from atomic elements,
 * and the official MCP cannot write a v3 widget into a v4 document. This plugin
 * registers a real atomic widget (`e-rcm-form`) so the form is a first-class
 * element in the atomic tree — editable on canvas, addressable by the MCP,
 * styleable with the same classes/variables as everything around it.
 *
 * HOW IT DIFFERS FROM THE CANONICAL EXAMPLE
 * -----------------------------------------
 * Elementor's `Has_Template` trait builds a FIXED Twig context, so a template
 * cannot call `do_shortcode()`. This widget therefore implements `render()`
 * directly in PHP and composes the wrapper itself, using the same public
 * accessors the trait uses (`get_atomic_settings()`, `get_base_styles_dictionary()`,
 * `get_interaction_id()`). Same output contract, PHP available.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ER_RCM_AF_VERSION', '1.0.0' );
define( 'ER_RCM_AF_FILE', __FILE__ );

/**
 * Register the widget once Elementor's widget manager is ready.
 *
 * Guarded three ways: Elementor may be absent, the atomic experiment may be off
 * (in which case `Atomic_Widget_Base` does not exist), and the class may already
 * be registered by another copy of this plugin.
 */
add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
	if ( ! class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
		return;
	}

	require_once __DIR__ . '/includes/class-atomic-form.php';

	$class = '\EntireRCM\AtomicWidgets\Atomic_Form';

	if ( ! class_exists( $class ) ) {
		return;
	}

	/*
	 * `Widgets_Manager` has no `is_registered()` — calling it fatals Elementor on
	 * every boot, which takes the editor and the MCP down with it. This action can
	 * also fire more than once, so guard double registration with our own flag.
	 */
	static $registered = false;

	if ( $registered ) {
		return;
	}

	$registered = true;

	$widgets_manager->register( new $class() );
}, 20 );

/**
 * Surface a clear admin notice when the atomic experiment is off, instead of
 * failing silently — the widget simply not appearing is otherwise baffling.
 */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'activate_plugins' ) || ! is_admin() ) {
		return;
	}

	if ( class_exists( '\Elementor\Modules\AtomicWidgets\Elements\Base\Atomic_Widget_Base' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>Atomic Form Widget:</strong> '
		. 'inactive — enable the <code>Atomic Elements</code> (e_atomic_elements) experiment under '
		. '<em>Elementor &rarr; Settings &rarr; Features</em> to use the <code>Form</code> element.</p></div>';
} );
