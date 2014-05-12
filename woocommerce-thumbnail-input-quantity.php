<?php
/*
Plugin Name: WooCommerce Thumbnail Input Quantities
Plugin URI: http://www.wpbackoffice.com/plugins/woocommerce-thumbnail-input-quantities/
Description: Allow users to add multiple units of an item from its thumbnail, most commonly on the product category or related product sections. Works with WooCommerce Incremental Product Quantities plugin.
Version: 1.1.2
Author: WP BackOffice
Author URI: http://www.wpbackoffice.com
*/ 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_Thumbnail_Input_Quantity' ) ) :

class WooCommerce_Thumbnail_Input_Quantity {
	
	/* @var bool True if Incremental Product Quantities plugin is active */
	public $incremental_active;
	
	/**
	 * WooCommerce Supplier Constructor.
	 * @access public
	 * @return WooCommerce Supplier
	 */
	public function __construct() {

		// Include required files
		add_action( 'init', array( $this, 'includes' ) );
		
		// Add Scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts' ) );

		// Filter Add to Cart Link
		add_filter('woocommerce_loop_add_to_cart_link', array( $this, 'add_quantity_input' ), 3, 2);
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {

		// Update $this->incremental_active
		$this->check_other_plugin_status();

	}
	
	/*
	*	Detect if WooCommerce Incremental Product Quantities Plugin is Active 
	*/
	public function check_other_plugin_status() {
		
		if ( in_array( 'woocommerce-incremental-product-quantities/product-quantity-rules.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) or 
			 in_array( 'woocommerce-incremental-product-quantities/incremental-product-quantities.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			
			$this->incremental_active = true;
		} else {
			$this->incremental_active = false;
		}
	}
	
	/*
	*	Include JS to validate input boxes
	*/
	public function add_scripts() {
		
		// Add thumbnail script to all front end pages
		wp_enqueue_script( 
			'wpbo_thumbnail_validation', 
			plugins_url() . '/woocommerce-thumbnail-input-quantities/wpbo_thumbnail_input_validation.js',
			array( 'jquery' )
		);
		
	}
	
	/*
	* 	Add Quantity Input box to the Product Thumbnail
	*
	*	@params string	$text Html 'Add to Cart' button
	*	@params object	$product WC_Product Object of current product
	*	@return string	$text Html button with data-quantity paramater 
	*/
	public function add_quantity_input( $text = null, $product = null ) {
		global $product;
	
		if ( $text != null and $product != null  ) {
			
			// Get Product Type
			$type = $product->product_type;
			
			// If Simple Add Input Box and Set data-quantity = min quantity
			if ( $type == 'simple' ){

				// Check if the Incremental Plugin is active and tailer link to that
				if ( $this->incremental_active == false ) {
					$inputbox = $this->print_input_box( null );
				} else {
					$results = $this->add_quantity_input_with_increments( $product );
					$inputbox = $results[0];
					$values = $results[1];
				}
				
				// Set the Minimum Quantity
				if ( $this->incremental_active == false ) {
					$min = 1;
				} elseif ( $values['min_value'] != '' ) {
					$min = $values['min_value'];
				} elseif ( $values['min_value'] == '' and $values['step'] != '' ) {
					$min = $values['step'];
				} else {
					$min = 1;
				}
		
				// Add the data-quantity attribute to the button
				$pos = strrpos( $text, "href" ); 
				$text = substr($text, 0, $pos) . 'data-quantity="' . $min . '" ' . substr($text, $pos);
				
				// Concat inputbox and text
				$text = $inputbox . $text;
			}
			
			// Return Input box with Link
			return $text;
		
		// Return text if the filter isn't working	
		} else {
			return $text;
		}
	}

	/*
	* 	Controls Returning the Input box for Simple Products with Inc Plugin active
	*
	*	@params string	$text Html 'Add to Cart' button
	*	@return string	Html button with data-quantity paramater 
	*/
	public function add_quantity_input_with_increments( $product ) {
		global $product;
		$rule = wpbo_get_applied_rule( $product );
		$values = wpbo_get_value_from_rule( 'all', $product, $rule );
		
		// Check if the product is out of stock 
		$stock = $product->get_stock_quantity();

		// Check stock status and if Out try Out of Stock value	
		if ( strlen( $stock ) != 0 and $stock <= 0 and isset( $values['min_oos'] ) and $values['min_oos'] != '' ) {
			$values['min_value'] = $values['min_oos'];
		}
		
		// Check stock status and if Out try Out of Stock value	
		if ( strlen( $stock ) != 0 and $stock <= 0 and isset( $values['max_oos'] ) and $values['max_oos'] != '' ) {
			$values['max_value'] = $values['max_oos'];
		}
		
		return array( $this->print_input_box( $values ), $values );	
	}	

	/*
	* 	Creates the Input Box given Values
	*
	*	@params array	$values Input box parameters 
	*	@return string	Html button with data-quantity paramater 
	*/
	function print_input_box( $values ) {
		
		if ( $values == null ) {
			return '<input type="number" min="1" step="1" name="thumbnail-quantity" class="thumbnail-quantity quantity" value="1" />';
		} else {
		
			$inputbox = '<input type="number" name="thumbnail-quantity" class="thumbnail-quantity quantity"';
			
			if ( $values['min_value'] != null ) {
				$inputbox .= 'min="' . $values['min_value'] . '"';
				$inputbox .= 'value="' . $values['min_value'] . '"';
			} elseif ( $values['min_value'] == null and $values['step'] != null ) {
				$inputbox .= 'min="' . $values['step'] . '"';
				$inputbox .= 'value="' . $values['step'] . '"';
			} else {
				$inputbox .= 'value="1"';
			}
			
			if ( $values['max_value'] != null ) {
				$inputbox .= 'max="' . $values['max_value'] . '"';
			}
			
			if ( $values['step'] != null ) {
				$inputbox .= 'step="' . $values['step'] . '"';
			}
			
			$inputbox .= '" />';
				
			return $inputbox;
		}
	}
}

endif;

return new WooCommerce_Thumbnail_Input_Quantity();
