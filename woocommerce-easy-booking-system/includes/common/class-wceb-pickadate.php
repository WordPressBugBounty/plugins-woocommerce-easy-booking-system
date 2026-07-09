<?php

namespace EasyBooking;

/**
*
* Functions to register pickadate scripts and styles.
 *
* @version 3.4.8
*/

defined( 'ABSPATH' ) || exit;

class Pickadate {

	/**
	 *
	 * Register pickadate.js script and its translation.
	 **/
	public static function register_scripts() {

		// Debugging mode
		if ( true === wceb_script_debug() ) {

			wp_register_script(
				'picker',
				plugins_url( 'assets/js/dev/picker.js', WCEB_PLUGIN_FILE ),
				array( 'jquery' ),
				WCEB_VERSION,
				true
			);

			wp_register_script(
				'legacy',
				plugins_url( 'assets/js/dev/legacy.js', WCEB_PLUGIN_FILE ),
				array( 'jquery' ),
				WCEB_VERSION,
				true
			);

			wp_register_script(
				'pickadate',
				plugins_url( 'assets/js/dev/picker.date.js', WCEB_PLUGIN_FILE ),
				array( 'jquery', 'picker', 'legacy' ),
				WCEB_VERSION,
				true
			);

		} else {

			// Concatenated and minified script including picker.js, picker.date.js and legacy.js
			wp_register_script(
				'pickadate',
				plugins_url( 'assets/js/pickadate.min.js', WCEB_PLUGIN_FILE ),
				array( 'jquery' ),
				WCEB_VERSION,
				true
			);

		}

		// Get page language in order to load Pickadate translation
		$site_language = str_replace( '-', '_', get_bloginfo( 'language' ) );

		// Pickadate.js translation. If it doesn't exist, load English translation file instead.
		$lang = file_exists( plugin_dir_path( WCEB_PLUGIN_FILE ) . 'assets/js/translations/' . $site_language . '.js' ) ? $site_language : 'en_US';

		wp_register_script(
			'pickadate.language',
			plugins_url( "assets/js/translations/{$lang}.js", WCEB_PLUGIN_FILE ),
			array( 'jquery', 'pickadate' ),
			WCEB_VERSION,
			true
		);

		wp_localize_script(
			'pickadate.language',
			'params',
			array(
				'first_day' => absint( get_option( 'start_of_week' ) ),
			)
		);
	}

	/**
	 *
	 * Register pickadate.js CSS.
	 **/
	public static function register_styles() {

		// Get calendar theme - Force "Default" theme in admin.
		$theme = is_admin() ? 'default' : get_option( 'wceb_calendar_theme' );

		wp_register_style(
			'picker',
			plugins_url( 'assets/css/' . $theme . '.min.css', WCEB_PLUGIN_FILE ),
			true
		);

		// Pickadate right-to-left CSS
		if ( is_rtl() ) {

			wp_register_style(
				'rtl-style',
				wceb_get_file_path( '', 'rtl', 'css' ),
				true
			);
		}

		// Add custom color variables to picker CSS
		$bg_color       = wc_format_hex( get_option( 'wceb_background_color', '#ffffff' ) );
		$text_color     = wc_format_hex( get_option( 'wceb_text_color', '#000000' ) );
		$accent_color   = wc_format_hex( get_option( 'wceb_main_color', '#999999' ) );
		$accent_lighter = wc_hex_lighter( $accent_color, 75 );

		$css = sprintf(
			':root {
                --wceb-bg: %1$s;
                --wceb-text: %2$s;
                --wceb-accent: %3$s;
                --wceb-accent-lighter:%4$s;
            }',
			$bg_color,
			$text_color,
			$accent_color,
			$accent_lighter
		);

		wp_add_inline_style( 'picker', $css );
	}
}

new Pickadate();
