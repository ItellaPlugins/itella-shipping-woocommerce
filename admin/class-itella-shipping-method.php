<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/includes
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Itella_Woocommerce
 * @subpackage Itella_Woocommerce/admin
 * @author     Your Name <email@example.com>
 */
class Itella_Shipping_Method extends WC_Shipping_Method {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $name    The ID of this plugin.
	 */
	private $name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public $id;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @var      string    $name       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct() {

	  parent::__construct();

//		$this->name = $name;
//		$this->version = $version;

    $this->id                 = "itella-shipping";
    $this->method_title       = __( 'Itella Shipping' );
    $this->method_description = __( 'Plugin to use with Itella Shipping methods' );

    $this->title              = "Itella Shipping Method";

    $this->init();

	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Itella_Shipping_Method_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Itella_Shipping_Method_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->name, plugin_dir_url( __FILE__ ) . 'css/itella-shipping-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Itella_Shipping_Method_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Itella_Shipping_Method_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->name, plugin_dir_url( __FILE__ ) . 'js/itella-shipping-admin.js', array( 'jquery' ), $this->version, FALSE );

	}

  /**
   * Init your settings
   *
   * @access public
   * @return void
   */
  public function init() {
    // Load the settings API
    $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
    $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

  }

  /**
   * calculate_shipping function.
   *
   * @access public
   * @param mixed $package
   * @return void
   */
  public function calculate_shipping( $package = array() ) {
    $rate = array(
        'id' => $this->id,
        'label' => $this->title,
        'cost' => '10.99',
        'calc_tax' => 'per_item'
    );

    // Register the rate
    $this->add_rate( $rate );
  }

  function init_form_fields()
  {
    $this->form_fields = array(
        'enabled' => array(
            'title' => __('Enable', 'itella_shipping'),
            'type' => 'checkbox',
            'description' => __('Enable this shipping.', 'itella_shipping'),
            'default' => 'yes'
        ),
        'api_user_2711' => array(
            'title' => __('API user for product code 2711', 'itella_shipping'),
            'type' => 'text',
        ),
        'api_pass_2711' => array(
            'title' => __('Api user password for product code 2711', 'itella_shipping'),
            'type' => 'password',
        ),
        'api_user_2317' => array(
            'title' => __('API user for product code 2317', 'itella_shipping'),
            'type' => 'text',
        ),
        'api_pass_2317' => array(
            'title' => __('Api user password for product code 2317', 'itella_shipping'),
            'type' => 'password',
        ),
        'company' => array(
            'title' => __('Company name', 'itella_shipping'),
            'type' => 'text',
        ),
        'bank_account' => array(
            'title' => __('Bank account', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_name' => array(
            'title' => __('Shop name', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_city' => array(
            'title' => __('Shop city', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_address' => array(
            'title' => __('Shop address', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_postcode' => array(
            'title' => __('Shop postcode', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_countrycode' => array(
            'title' => __('Shop country code', 'itella_shipping'),
            'type' => 'text',
        ),
        'shop_phone' => array(
            'title' => __('Shop phone number', 'itella_shipping'),
            'type' => 'text',
        ),
        'pickup_point_method' => array(
            'title' => __('Enable Pickup Point', 'itella_shipping'),
            'type' => 'checkbox',
            'description' => __('Show pickup point shipping method in checkout.', 'itella_shipping'),
            'default' => 'no'
        ),
        'courier_method' => array(
            'title' => __('Enable Courier', 'itella_shipping'),
            'type' => 'checkbox',
            'description' => __('Show courier shipping method in checkout.', 'itella_shipping'),
            'default' => 'no'
        ),
        'pickup_point_price_lt' => array(
            'title' => 'LT ' . __('Pickup Point price', 'itella_shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 2,
        ),
        'courier_price_lt' => array(
            'title' => 'LT ' . __('Courrier price', 'itella_shipping'),
            'type' => 'number',
            'default' => 2,
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
        ),
        'pickup_point_nocharge_amount_lt' => array(
            'title' => 'LT ' . __('Disable pickup point fee if cart amount is greater or equal than this limit', 'itella_shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 100
        ),
        'courier_nocharge_amount_lt' => array(
            'title' => 'LT ' . __('Disable courier fee if cart amount is greater or equal than this limit', 'itella_shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 100
        ),
        'pickup_point_price_lv' => array(
            'title' => 'LV ' . __('Pickup Point price', 'itella_shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 2,
        ),
        'courier_price_lv' => array(
            'title' => 'LV ' . __('Courrier price', 'itella_shipping'),
            'type' => 'number',
            'default' => 2,
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
        ),
        'pickup_point_nocharge_amount_lv' => array(
            'title' => 'LV ' . __('Disable pickup point fee if cart amount is greater or equal than this limit', 'itella_shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 100
        ),
        'courier_nocharge_amount_lv' => array(
            'title' => 'LV ' . __('Disable courier fee if cart amount is greater or equal than this limit', 'itella_shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 100
        ),
        'fee_tax' => array(
            'title' => __('Enable Fee Tax', 'itella_shipping'),
            'type' => 'checkbox',
            'description' => __('Is shipping fee taxable? Use this option if you have taxes enabled in your shop and you want to include tax to COD method.', 'itella_shipping'),
            'default' => 'no',
        ),
    );
  }

}
