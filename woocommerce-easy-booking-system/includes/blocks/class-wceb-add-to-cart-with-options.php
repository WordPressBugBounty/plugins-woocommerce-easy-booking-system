<?php

namespace EasyBooking\Blocks;

/**
 * Datepicker placement in the Add to Cart + Options block.
 * @version 3.5.8
 */
defined( 'ABSPATH' ) || exit;

class Add_To_Cart_With_Options {

	/**
	 * Form currently being rendered, scoped to its block callback.
	 *
	 * @var \stdClass|null
	 */
	private $form = null;

	public function __construct() {
		add_filter( 'register_block_type_args', array( $this, 'register_block' ), 10, 2 );
		add_filter( 'easy_booking_single_product_html', array( $this, 'capture_datepickers' ), 10, 2 );
		add_filter( 'render_block_woocommerce/add-to-cart-with-options-variation-selector', array( $this, 'mark_position' ), 10, 2 );
		add_filter( 'render_block_woocommerce/add-to-cart-with-options-grouped-product-selector', array( $this, 'mark_position' ), 10, 2 );
		add_filter( 'render_block_woocommerce/add-to-cart-with-options-grouped-product-item-selector', array( $this, 'sync_grouped_quantity_input' ) );
	}

	/**
	 * Scope placement to the WooCommerce block's render callback.
	 *
	 * @param array  $args       Block registration arguments.
	 * @param string $block_name Registered block name.
	 * @return array
	 */
	public function register_block( $args, $block_name ) {
		if ( 'woocommerce/add-to-cart-with-options' !== $block_name || ! is_callable( $args['render_callback'] ?? null ) ) {
			return $args;
		}

		$render_callback = $args['render_callback'];

		$args['render_callback'] = function ( $attributes, $content, $block ) use ( $render_callback ) {
			return $this->render( $render_callback, $attributes, $content, $block );
		};

		return $args;
	}

	/**
	 * Move the existing markup after rendering, preserving WooCommerce's POST detection.
	 *
	 * @param callable  $render_callback Original WooCommerce render callback.
	 * @param array     $attributes      Block attributes.
	 * @param string    $content         Block content.
	 * @param \WP_Block $block           Block instance.
	 * @return string
	 */
	private function render( $render_callback, $attributes, $content, $block ) {
		$previous_form = $this->form;

		try {
			$this->form = null;
			$product    = isset( $block->context['postId'] ) ? wc_get_product( $block->context['postId'] ) : false;

			if ( $product instanceof \WC_Product && $product->is_type( array( 'variable', 'grouped' ) ) && wceb_is_bookable( $product ) ) {
				$id         = wp_unique_id( 'wceb-datepickers-' );
				$this->form = (object) array(
					'product_id' => $product->get_id(),
					'selector'   => 'woocommerce/add-to-cart-with-options-' . ( $product->is_type( 'variable' ) ? 'variation-selector' : 'grouped-product-selector' ),
					'html'       => '',
					'source'     => '',
					'start'      => '<!-- ' . $id . '-start -->',
					'end'        => '<!-- ' . $id . '-end -->',
					'position'   => '<!-- ' . $id . '-position -->',
					'positioned' => false,
				);
			}

			$rendered_content = call_user_func( $render_callback, $attributes, $content, $block );
			$form             = $this->form;

			if ( ! $form || '' === $form->html || ! is_string( $rendered_content ) ) {
				return $rendered_content;
			}

			if ( $form->positioned && false !== strpos( $rendered_content, $form->source ) && false !== strpos( $rendered_content, $form->position ) ) {
				return strtr(
					$rendered_content,
					array(
						$form->source   => '',
						$form->position => $form->html,
					)
				);
			}

			// Keep the original placement if the theme omits the selector or alters the markup.
			return str_replace( array( $form->start, $form->end, $form->position ), '', $rendered_content );
		} finally {
			$this->form = $previous_form;
		}
	}

	/**
	 * Identify only this form's existing datepicker markup.
	 *
	 * @param string      $html    Datepicker markup.
	 * @param \WC_Product $product Current product.
	 * @return string
	 */
	public function capture_datepickers( $html, $product ) {
		if ( ! $this->form || $product->get_id() !== $this->form->product_id || '' === $html || '' !== $this->form->html ) {
			return $html;
		}

		if ( $product->is_type( 'variable' ) ) {
			$html = $this->prepare_variable_datepickers( $html, $product );
		} elseif ( $product->is_type( 'grouped' ) ) {
			$html = $this->prepare_grouped_datepickers( $html );
		}

		$this->form->html   = $html;
		$this->form->source = $this->form->start . $html . $this->form->end;

		return $this->form->source;
	}

	/**
	 * Connect variable block forms to the existing datepicker classes.
	 *
	 * @param string      $html    Datepicker markup.
	 * @param \WC_Product $product Variable product.
	 * @return string
	 */
	private function prepare_variable_datepickers( $html, $product ) {
		if ( ! function_exists( 'wp_enqueue_script_module' ) ) {
			return $html;
		}

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag( array( 'class_name' => 'wceb_picker_wrap' ) ) ) {
			return $html;
		}

		$config     = wp_interactivity_config( 'easy-booking/variable-product' );
		$product_id = $product->get_id();

		if ( ! isset( $config['variations'][ $product_id ] ) ) {
			$variations = array();

			// Reuse WooCommerce's classic variation data and existing FREE/PRO filters.
			foreach ( $product->get_available_variations() as $variation ) {
				$variations[ $variation['variation_id'] ] = $variation;
			}

			wp_interactivity_config(
				'easy-booking/variable-product',
				array( 'variations' => array( $product_id => $variations ) )
			);
		}

		wp_enqueue_script( 'wceb-single-product-variable' );
		wp_enqueue_script_module(
			'easy-booking/variable-product',
			wceb_get_file_path( 'blocks', 'wceb-variable', 'js' ),
			array( '@wordpress/interactivity' ),
			WCEB_VERSION
		);

		$processor->set_attribute( 'data-wp-interactive', 'easy-booking/variable-product' );
		$processor->set_attribute( 'data-wp-watch', 'callbacks.syncVariation' );

		return $processor->get_updated_html();
	}

	/**
	 * Connect grouped block forms to the existing datepicker classes.
	 *
	 * @param string $html Datepicker markup.
	 * @return string
	 */
	private function prepare_grouped_datepickers( $html ) {
		if ( ! function_exists( 'wp_enqueue_script_module' ) ) {
			return $html;
		}

		$processor = new \WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag( array( 'class_name' => 'wceb_picker_wrap' ) ) ) {
			return $html;
		}

		wp_enqueue_script( 'wceb-single-product-grouped' );
		wp_enqueue_script_module(
			'easy-booking/grouped-product',
			wceb_get_file_path( 'blocks', 'wceb-grouped', 'js' ),
			array( '@wordpress/interactivity' ),
			WCEB_VERSION
		);

		$processor->set_attribute( 'data-wp-interactive', 'easy-booking/grouped-product' );
		$processor->set_attribute( 'data-wp-watch', 'callbacks.syncSelection' );

		return $processor->get_updated_html();
	}

	/**
	 * Commit typed grouped quantities using WooCommerce's existing blur handler.
	 *
	 * @param string $content Rendered child product selector.
	 * @return string
	 */
	public function sync_grouped_quantity_input( $content ) {
		if ( ! $this->form || 'woocommerce/add-to-cart-with-options-grouped-product-selector' !== $this->form->selector ) {
			return $content;
		}

		$processor = new \WP_HTML_Tag_Processor( $content );
		while ( $processor->next_tag( array( 'tag_name' => 'INPUT', 'class_name' => 'qty' ) ) ) {
			$handler = $processor->get_attribute( 'data-wp-on--blur' );

			if ( 'number' === $processor->get_attribute( 'type' ) && is_string( $handler ) && '' !== $handler && null === $processor->get_attribute( 'data-wp-on--input' ) ) {
				$processor->set_attribute( 'data-wp-on--input', $handler );
			}
		}

		return $processor->get_updated_html();
	}

	/**
	 * Mark a single insertion point after the complete product selector.
	 *
	 * @param string $content Rendered selector markup.
	 * @param array  $block   Parsed selector block.
	 * @return string
	 */
	public function mark_position( $content, $block ) {
		if ( ! $this->form || $this->form->positioned || '' === $this->form->html || '' === trim( $content ) || $block['blockName'] !== $this->form->selector ) {
			return $content;
		}

		$this->form->positioned = true;

		return $content . $this->form->position;
	}
}

new Add_To_Cart_With_Options();
