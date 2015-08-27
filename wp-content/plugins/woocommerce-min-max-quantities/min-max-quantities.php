<?php
/*
Plugin Name: WooCommerce Min/Max Quantities
Plugin URI: http://woothemes.com/woocommerce
Description: Lets you define minimum/maximum allowed quantities for products, variations and orders. Requires 2.0+
Version: 2.3.7
Author: WooThemes
Author URI: http://woothemes.com
Requires at least: 4.0
Tested up to: 4.1

	Copyright: © 2009-2011 WooThemes.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), '2b5188d90baecfb781a5aa2d6abb900a', '18616' );

/**
 * woocommerce_min_max_quantities class
 **/
if ( ! class_exists( 'WC_Min_Max_Quantities' ) ) :

class WC_Min_Max_Quantities {

	var $minimum_order_quantity;
	var $maximum_order_quantity;
	var $minimum_order_value;
	var $maximum_order_value;
	var $excludes = array();

	/** @var object Class Instance */
	private static $instance;

	/**
	 * Get the class instance
	 */
	public static function get_instance() {
		return null === self::$instance ? ( self::$instance = new self ) : self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( ! is_woocommerce_active() ) {
			return;
		}

		/**
		 * Localisation
		 **/
		$this->load_plugin_textdomain();

		if ( is_admin() ) {
			include_once( 'classes/class-wc-min-max-quantities-admin.php' );
		}

		$this->minimum_order_quantity = absint( get_option( 'woocommerce_minimum_order_quantity' ) );
		$this->maximum_order_quantity = absint( get_option( 'woocommerce_maximum_order_quantity' ) );
		$this->minimum_order_value    = absint( get_option( 'woocommerce_minimum_order_value' ) );
		$this->maximum_order_value    = absint( get_option( 'woocommerce_maximum_order_value' ) );

		// Check items
		add_action( 'woocommerce_check_cart_items', array( $this, 'check_cart_items' ) );

		// quantity selelectors (2.0+)
		add_filter( 'woocommerce_quantity_input_args', array( $this, 'update_quantity_args' ), 10, 2 );
		add_filter( 'woocommerce_available_variation',  array( $this, 'available_variation' ), 10, 3 );

		// Prevent add to cart
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'add_to_cart' ), 10, 4 );

		// Min add to cart ajax
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_to_cart_link' ), 10, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
	}

	public function load_scripts() {
		// only load on single product page and cart page
		if ( is_product() || is_cart() ) {
			wc_enqueue_js( "
				jQuery( 'body' ).on( 'show_variation', function( event, variation ) {
					jQuery( 'form.variations_form' ).find( 'input[name=quantity]' ).prop( 'step', variation.step ).val( variation.input_value );
				});
			" );
		}
	}
	
	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Frontend/global Locales found in:
	 * 		- WP_LANG_DIR/woocommerce-min-max-quantities/woocommerce-min-max-quantities-LOCALE.mo
	 * 	 	- woocommerce-min-max-quantities/woocommerce-min-max-quantities-LOCALE.mo (which if not found falls back to:)
	 * 	 	- WP_LANG_DIR/plugins/woocommerce-min-max-quantities-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'woocommerce-min-max-quantities' );

		load_textdomain( 'woocommerce-min-max-quantities', WP_LANG_DIR . '/woocommerce-min-max-quantities/woocommerce-min-max-quantities-' . $locale . '.mo' );
		load_plugin_textdomain( 'woocommerce-min-max-quantities', false, plugin_basename( dirname( __FILE__ ) ) . '/' );
	}

	/**
	 * Add an error
	 * @todo remove deprecated add error in future wc versions
	 */
	public function add_error( $error ) {
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $error, 'error' );
		} else {
			WC()->add_error( $error );
		}
	}

	/**
	 * Add quantity property to add to cart button on shop loop for simple products.
	 *
	 * @access public
	 * @return void
	 */
	public function add_to_cart_link( $html, $product ) {

		if ( 'variable' !== $product->product_type ) {

			$quantity_attribute = 1;
			$minimum_quantity   = absint( get_post_meta( $product->id, 'minimum_allowed_quantity', true ) );
			$group_of_quantity  = absint( get_post_meta( $product->id, 'group_of_quantity', true ) );

			if ( $minimum_quantity || $group_of_quantity ) {

			    $quantity_attribute = $minimum_quantity;

				if ( $group_of_quantity > 0 && $minimum_quantity < $group_of_quantity ) {
			    	$quantity_attribute = $group_of_quantity;
			    }

			    $html = str_replace( '<a ', '<a data-quantity="' . $quantity_attribute . '" ', $html );
			}
		}

		return $html;
	}

	/**
	 * Get product or variation ID to check
	 * @return int
	 */
	public function get_id_to_check( $values ) {
		if ( $values['variation_id'] ) {
			$min_max_rules = get_post_meta( $values['variation_id'], 'min_max_rules', true );

			if ( 'yes' === $min_max_rules ) {
				$checking_id = $values['variation_id'];
			} else {
				$checking_id = $values['product_id'];
			}
		} else {
			$checking_id = $values['product_id'];
		}
		return $checking_id;
	}

	/**
	 * Validate cart items against set rules
	 *
	 * @todo remove deprecated woocommerce global and use WC() in future wc versions
	 */
	public function check_cart_items() {
		$checked_ids      = $product_quantities = $category_quantities = array();
		$total_quantity   = $total_cost = 0;
		$apply_cart_rules = false;

		// Count items + variations first
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			$product     = $values['data'];
			$checking_id = $this->get_id_to_check( $values );

			if ( ! isset( $product_quantities[ $checking_id ] ) ) {
				$product_quantities[ $checking_id ] = $values['quantity'];
			} else {
				$product_quantities[ $checking_id ] += $values['quantity'];
			}

			// do_not_count and cart_exclude from variation or product
			$minmax_do_not_count = apply_filters( 'wc_min_max_quantity_minmax_do_not_count', ( 'yes' === get_post_meta( $checking_id, 'variation_minmax_do_not_count', true ) ? 'yes' : get_post_meta( $values['product_id'], 'minmax_do_not_count', true ) ), $checking_id, $cart_item_key, $values );
			$minmax_cart_exclude = apply_filters( 'wc_min_max_quantity_minmax_cart_exclude', ( 'yes' === get_post_meta( $checking_id, 'variation_minmax_cart_exclude', true ) ? 'yes' : get_post_meta( $values['product_id'], 'minmax_cart_exclude', true ) ), $checking_id, $cart_item_key, $values );

			if ( 'yes' !== $minmax_do_not_count && 'yes' !== $minmax_cart_exclude ) {
				$total_cost += $product->get_price() * $values['quantity'];
			}
		}

		// Check cart items
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
			$checking_id    = $this->get_id_to_check( $values );
			$terms          = get_the_terms( $values['product_id'], 'product_cat' );
			$found_term_ids = array();

			if ( $terms ) {

				foreach ( $terms as $term ) {

					if ( 'yes' === get_post_meta( $checking_id, 'minmax_category_group_of_exclude', true ) ) {
						continue;
					}

					if ( in_array( $term->term_id, $found_term_ids ) ) {
						continue;
					}

					$found_term_ids[] = $term->term_id;
					$category_quantities[ $term->term_id ] = isset( $category_quantities[ $term->term_id ] ) ? $category_quantities[ $term->term_id ] + $values['quantity'] : $values['quantity'];

					// Record count in parents of this category too
					$parents = get_ancestors( $term->term_id, 'product_cat' );

					foreach ( $parents as $parent ) {
						if ( in_array( $parent, $found_term_ids ) ) {
							continue;
						}

						$found_term_ids[] = $parent;
						$category_quantities[ $parent ] = isset( $category_quantities[ $parent ] ) ? $category_quantities[ $parent ] + $values['quantity'] : $values['quantity'];
					}
				}
			}

			// Check item rules once per product ID
			if ( in_array( $checking_id, $checked_ids ) ) {
				continue;
			}

			$product = $values['data'];

			// do_not_count and cart_exclude from variation or product
			$minmax_do_not_count = apply_filters( 'wc_min_max_quantity_minmax_do_not_count', ( 'yes' === get_post_meta( $checking_id, 'variation_minmax_do_not_count', true ) ? 'yes' : get_post_meta( $values['product_id'], 'minmax_do_not_count', true ) ), $checking_id, $cart_item_key, $values );
			$minmax_cart_exclude = apply_filters( 'wc_min_max_quantity_minmax_cart_exclude', ( 'yes' === get_post_meta( $checking_id, 'variation_minmax_cart_exclude', true ) ? 'yes' : get_post_meta( $values['product_id'], 'minmax_cart_exclude', true ) ), $checking_id, $cart_item_key, $values );

			if ( 'yes' === $minmax_do_not_count || 'yes' === $minmax_cart_exclude ) {
				// Do not count
				$this->excludes[] = $product->get_title();

			} else {
				$total_quantity += $product_quantities[ $checking_id ];
			}

			if ( 'yes' !== $minmax_cart_exclude ) {
				$apply_cart_rules = true;
			}

			$checked_ids[] = $checking_id;

			if ( $values['variation_id'] ) {

				$minimum_quantity    = absint( apply_filters( 'wc_min_max_quantity_minimum_allowed_quantity', get_post_meta( $checking_id, 'variation_minimum_allowed_quantity', true ), $checking_id, $cart_item_key, $values ) );

				$maximum_quantity    = absint( apply_filters( 'wc_min_max_quantity_maximum_allowed_quantity', get_post_meta( $checking_id, 'variation_maximum_allowed_quantity', true ), $checking_id, $cart_item_key, $values ) );

				$group_of_quantity   = absint( apply_filters( 'wc_min_max_quantity_group_of_quantity', get_post_meta( $checking_id, 'variation_group_of_quantity', true ), $checking_id, $cart_item_key, $values ) );
			} else {

				$minimum_quantity    = absint( apply_filters( 'wc_min_max_quantity_minimum_allowed_quantity', get_post_meta( $checking_id, 'minimum_allowed_quantity', true ), $checking_id, $cart_item_key, $values ) );

				$maximum_quantity    = absint( apply_filters( 'wc_min_max_quantity_maximum_allowed_quantity', get_post_meta( $checking_id, 'maximum_allowed_quantity', true ), $checking_id, $cart_item_key, $values ) );

				$group_of_quantity   = absint( apply_filters( 'wc_min_max_quantity_group_of_quantity', get_post_meta( $checking_id, 'group_of_quantity', true ), $checking_id, $cart_item_key, $values ) );
			}

			$this->check_rules( $product, $product_quantities[ $checking_id ], $minimum_quantity, $maximum_quantity, $group_of_quantity );
		}

		// Cart rules
		if ( $apply_cart_rules ) {

			$excludes = '';

			if ( sizeof( $this->excludes ) > 0 ) {
				$excludes = ' (' . __( 'excludes ', 'woocommerce-min-max-quantities' ) . implode( ', ', $this->excludes ) . ')';
			}

			// Check cart quantity
			$quantity = $this->minimum_order_quantity;

			if ( $quantity > 0 && $total_quantity < $quantity ) {

				$this->add_error( sprintf( __( 'The minimum allowed order quantity is %s - please add more items to your cart', 'woocommerce-min-max-quantities' ), $quantity ) . $excludes );

				return;

			}

			$quantity = $this->maximum_order_quantity;

			if ( $quantity > 0 && $total_quantity > $quantity ) {

				$this->add_error( sprintf( __( 'The maximum allowed order quantity is %s - please remove some items from your cart.', 'woocommerce-min-max-quantities' ), $quantity ) );

				return;

			}

			// Check cart value
			if ( $this->minimum_order_value && $total_cost && $total_cost < $this->minimum_order_value ) {

				$this->add_error( sprintf( __( 'The minimum allowed order value is %s - please add more items to your cart', 'woocommerce-min-max-quantities' ), woocommerce_price( $this->minimum_order_value ) ) . $excludes );

				return;
			}

			if ( $this->maximum_order_value && $total_cost && $total_cost > $this->maximum_order_value ) {

				$this->add_error( sprintf( __( 'The maximum allowed order value is %s - please remove some items from your cart.', 'woocommerce-min-max-quantities' ), woocommerce_price( $this->maximum_order_value ) ) );

				return;
			}
		}

		// Check category rules
		foreach ( $category_quantities as $category => $quantity ) {
			$group_of_quantity = get_woocommerce_term_meta( $category, 'group_of_quantity', true );

			if ( $group_of_quantity > 0 && ( $quantity % $group_of_quantity ) > 0 ) {

				$term          = get_term_by( 'id', $category, 'product_cat' );
				$product_names = array();

				foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

					if ( 'yes' === get_post_meta( $values['product_id'], 'minmax_category_group_of_exclude', true ) || 'yes' === get_post_meta( $values['variation_id'], 'minmax_category_group_of_exclude', true ) ) {
						continue;
					}

					if ( has_term( $category, 'product_cat', $values['product_id'] ) ) {
						$product_names[] = $values['data']->get_title();
					}
				}

				$this->add_error( sprintf( __( 'Items in the <strong>%s</strong> category (<em>%s</em>) must be bought in groups of %d. Please add another %d to continue.', 'woocommerce-min-max-quantities' ), $term->name, implode( ', ', $product_names ), $group_of_quantity, $group_of_quantity - ( $quantity % $group_of_quantity ) ) );

				return;
			}
		}
	}

	/**
	 * Add respective error message dpeending on rules checked
	 *
	 * @todo remove deprecated woocommerce global and use WC() in future wc versions
	 * @access public
	 * @return void
	 */
	public function check_rules( $product, $quantity, $minimum_quantity, $maximum_quantity, $group_of_quantity ) {
		if ( $minimum_quantity > 0 && $quantity < $minimum_quantity ) {

			$this->add_error( sprintf( __( 'The minimum allowed quantity for %s is %s - please increase the quantity in your cart.', 'woocommerce-min-max-quantities' ), $product->get_title(), $minimum_quantity ) );

		} elseif ( $maximum_quantity > 0 && $quantity > $maximum_quantity ) {

			$this->add_error( sprintf( __( 'The maximum allowed quantity for %s is %s - please decrease the quantity in your cart.', 'woocommerce-min-max-quantities' ), $product->get_title(), $maximum_quantity ) );

		}

		if ( $group_of_quantity > 0 && ( $quantity % $group_of_quantity ) ) {

			$this->add_error( sprintf( __( '%s must be bought in groups of %d. Please add or decrease another %d to continue.', 'woocommerce-min-max-quantities' ), $product->get_title(), $group_of_quantity, $group_of_quantity - ( $quantity % $group_of_quantity ) ) );

		}
	}

	/**
	 * Add to cart validation
	 *
	 * @todo remove deprecated woocommerce global and use WC() in future wc versions
	 * @access public
	 * @param mixed $pass
	 * @param mixed $product_id
	 * @param mixed $quantity
	 * @return void
	 */
	public function add_to_cart( $pass, $product_id, $quantity, $variation_id = 0 ) {
		$rule_for_variaton = false;

		if ( $variation_id ) {

			$min_max_rules = get_post_meta( $variation_id, 'min_max_rules', true );

			if ( 'yes' === $min_max_rules ) {

				$maximum_quantity  = absint( get_post_meta( $variation_id, 'variation_maximum_allowed_quantity', true ) );
				$minimum_quantity  = absint( get_post_meta( $variation_id, 'variation_minimum_allowed_quantity', true ) );
				$rule_for_variaton = true;

			} else {

				$maximum_quantity = absint( get_post_meta( $product_id, 'maximum_allowed_quantity', true ) );
				$minimum_quantity = absint( get_post_meta( $product_id, 'minimum_allowed_quantity', true ) );

			}

		} else {

			$maximum_quantity = absint( get_post_meta( $product_id, 'maximum_allowed_quantity', true ) );
			$minimum_quantity = absint( get_post_meta( $product_id, 'minimum_allowed_quantity', true ) );

		}

		$total_quantity = $quantity;

		// Count items
		foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

			if ( $rule_for_variaton ) {

				if ( $values['variation_id'] == $variation_id ) {

					$total_quantity += $values['quantity'];
				}

			} else {

				if ( $values['product_id'] == $product_id ) {

					$total_quantity += $values['quantity'];
				}
			}
		}

		if ( isset( $maximum_quantity ) && $maximum_quantity > 0 ) {
			if ( $total_quantity > 0 && $total_quantity > $maximum_quantity ) {

				if ( function_exists( 'get_product' ) ) {

					$_product = get_product( $product_id );
				} else {

					$_product = new WC_Product( $product_id );
				}

				$this->add_error( sprintf( __( 'The maximum allowed quantity for %s is %d (you currently have %s in your cart).', 'woocommerce-min-max-quantities' ), $_product->get_title(), $maximum_quantity, $total_quantity - $quantity ) );

				$pass = false;
			}
		}

		if ( isset( $minimum_quantity ) && $minimum_quantity > 0 ) {
			if ( $total_quantity < $minimum_quantity ) {

				if ( function_exists( 'get_product' ) ) {

					$_product = get_product( $product_id );
				} else {

					$_product = new WC_Product( $product_id );
				}

				$this->add_error( sprintf( __( 'The minimum allowed quantity for %s is %d (you currently have %s in your cart).', 'woocommerce-min-max-quantities' ), $_product->get_title(), $minimum_quantity, $total_quantity - $quantity ) );

				$pass = false;
			}
		}

		return $pass;
	}

	/**
	 * Updates the quantity arguments
	 *
	 * @return array
	 */
	function update_quantity_args( $data, $product ) {

		$group_of_quantity = get_post_meta( $product->id, 'group_of_quantity', true );
		$minimum_quantity  = get_post_meta( $product->id, 'minimum_allowed_quantity', true );
		$maximum_quantity  = get_post_meta( $product->id, 'maximum_allowed_quantity', true );

		// if variable product, only apply in cart
		if ( is_cart() && isset( $product->variation_id ) ) {

			$min_max_rules = get_post_meta( $product->variation_id, 'min_max_rules', true );

			if ( 'no' === $min_max_rules || empty( $min_max_rules ) ) {
				$min_max_rules = false;

			} else {
				$min_max_rules = true;

			}

			$variation_minimum_quantity  = get_post_meta( $product->variation_id, 'variation_minimum_allowed_quantity', true );
			$variation_maximum_quantity  = get_post_meta( $product->variation_id, 'variation_maximum_allowed_quantity', true );
			$variation_group_of_quantity = get_post_meta( $product->variation_id, 'variation_group_of_quantity', true );

			// override product level
			if ( $min_max_rules && $variation_minimum_quantity ) {
				$minimum_quantity = $variation_minimum_quantity;

			}

			// override product level
			if ( $min_max_rules && $variation_maximum_quantity ) {
				$maximum_quantity = $variation_maximum_quantity;
			}

			// override product level
			if ( $min_max_rules && $variation_group_of_quantity ) {
				$group_of_quantity = $variation_group_of_quantity;

			}

		}

		if ( $minimum_quantity ) {

			if ( $product->managing_stock() && $product->backorders_allowed() && absint( $minimum_quantity ) > $product->get_stock_quantity() ) {
				$data['min_value'] = $product->get_stock_quantity();

			} else {
				$data['min_value'] = $minimum_quantity;
			}
		}

		if ( $maximum_quantity ) {

			if ( $product->managing_stock() && $product->backorders_allowed() ) {
				$data['max_value'] = $maximum_quantity;

			} elseif ( $product->managing_stock() && absint( $maximum_quantity ) > $product->get_stock_quantity() ) {
				$data['max_value'] = $product->get_stock_quantity();

			} else {
				$data['max_value'] = $maximum_quantity;
			}
		}

		if ( $group_of_quantity ) {
			$data['step'] = 1;

			// if both minimum and maximum quantity are set, make sure both are equally divisble by qroup of quantity
			if ( $maximum_quantity && $minimum_quantity ) {

				if ( absint( $maximum_quantity ) % absint( $group_of_quantity ) === 0 && absint( $minimum_quantity ) % absint( $group_of_quantity ) === 0 ) {
					$data['step'] = $group_of_quantity;

				}

			} elseif ( ! $maximum_quantity || absint( $maximum_quantity ) % absint( $group_of_quantity ) === 0 ) {

				$data['step'] = $group_of_quantity;
			}

			// set a new minimum if group of is set but not minimum
			if ( ! $minimum_quantity ) {
				$data['min_value'] = $group_of_quantity;
			}
		}

		// don't apply for cart as cart has qty already pre-filled
		if ( ! is_cart() ) {
			$data['input_value'] = ! empty( $minimum_quantity ) ? $minimum_quantity : $data['input_value'];
		}

		return $data;
	}

	/**
	 * Adds variation min max settings to the localized variation parameters to be used by JS
	 *
	 * @access public
	 * @param array $data
	 * @param obhect $product
	 * @param object $variation
	 * @return array $data
	 */
	function available_variation( $data, $product, $variation ) {
		$min_max_rules = get_post_meta( $variation->variation_id, 'min_max_rules', true );

		if ( 'no' === $min_max_rules || empty( $min_max_rules ) ) {
			$min_max_rules = false;

		} else {
			$min_max_rules = true;

		}

		$minimum_quantity  = get_post_meta( $product->id, 'minimum_allowed_quantity', true );
		$maximum_quantity  = get_post_meta( $product->id, 'maximum_allowed_quantity', true );
		$group_of_quantity = get_post_meta( $product->id, 'group_of_quantity', true );

		$variation_minimum_quantity  = get_post_meta( $variation->variation_id, 'variation_minimum_allowed_quantity', true );
		$variation_maximum_quantity  = get_post_meta( $variation->variation_id, 'variation_maximum_allowed_quantity', true );
		$variation_group_of_quantity = get_post_meta( $variation->variation_id, 'variation_group_of_quantity', true );

		// override product level
		if ( $variation->managing_stock() ) {
			$product = $variation;

		}

		// override product level
		if ( $min_max_rules && $variation_minimum_quantity ) {
			$minimum_quantity = $variation_minimum_quantity;

		}

		// override product level
		if ( $min_max_rules && $variation_maximum_quantity ) {
			$maximum_quantity = $variation_maximum_quantity;
		}

		// override product level
		if ( $min_max_rules && $variation_group_of_quantity ) {
			$group_of_quantity = $variation_group_of_quantity;

		}

		if ( $minimum_quantity ) {

			if ( $product->managing_stock() && $product->backorders_allowed() && absint( $minimum_quantity ) > $product->get_stock_quantity() ) {
				$data['min_qty'] = $product->get_stock_quantity();

			} else {
				$data['min_qty'] = $minimum_quantity;
			}
		}

		if ( $maximum_quantity ) {

			if ( $product->managing_stock() && $product->backorders_allowed() ) {
				$data['max_qty'] = $maximum_quantity;

			} elseif ( $product->managing_stock() && absint( $maximum_quantity ) > $product->get_stock_quantity() ) {
				$data['max_qty'] = $product->get_stock_quantity();

			} else {
				$data['max_qty'] = $maximum_quantity;
			}
		}

		if ( $group_of_quantity ) {
			$data['step'] = 1;

			// if both minimum and maximum quantity are set, make sure both are equally divisble by qroup of quantity
			if ( $maximum_quantity && $minimum_quantity ) {

				if ( absint( $maximum_quantity ) % absint( $group_of_quantity ) === 0 && absint( $minimum_quantity ) % absint( $group_of_quantity ) === 0 ) {
					$data['step'] = $group_of_quantity;

				}

			} elseif ( ! $maximum_quantity || absint( $maximum_quantity ) % absint( $group_of_quantity ) === 0 ) {

				$data['step'] = $group_of_quantity;
			}
		}

		// don't apply for cart as cart has qty already pre-filled
		if ( ! is_cart() ) {
			$data['input_value'] = ! empty( $minimum_quantity ) ? $minimum_quantity : 1;
		}

		return $data;
	}
}

add_action( 'plugins_loaded', array( 'WC_Min_Max_Quantities', 'get_instance' ) );

endif;
