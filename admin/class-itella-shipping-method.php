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
	public function __construct( $name, $version ) {

	  parent::__construct();

		$this->name = $name;
		$this->version = $version;

    $this->id                 = 'itella-shipping'; // Id for your shipping method. Should be uunique.
    $this->method_title       = __( 'Itella Shipping' );  // Title shown in admin
    $this->method_description = __( 'Plugin to use with Itella Shipping methods' ); // Description shown in admin

    $this->title              = "Itella Shipping Method"; // This can be added as an setting but for this example its forced.

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
            'title' => __('Enable', 'itella-shipping'),
            'type' => 'checkbox',
            'description' => __('Enable this shipping.', 'itella-shipping'),
            'default' => 'yes'
        ),
        'api_url' => array(
            'title' => __('Api URL', 'itella-shipping'),
            'type' => 'text',
            'default' => 'https://edixml.post.ee'

        ),
        'api_user' => array(
            'title' => __('Api user', 'itella-shipping'),
            'type' => 'text',
        ),
        'api_pass' => array(
            'title' => __('Api user password', 'itella-shipping'),
            'type' => 'password',
        ),
        'company' => array(
            'title' => __('Company name', 'itella-shipping'),
            'type' => 'text',
        ),
        'bank_account' => array(
            'title' => __('Bank account', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_name' => array(
            'title' => __('Shop name', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_city' => array(
            'title' => __('Shop city', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_address' => array(
            'title' => __('Shop address', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_postcode' => array(
            'title' => __('Shop postcode', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_countrycode' => array(
            'title' => __('Shop country code', 'itella-shipping'),
            'type' => 'text',
        ),
        'shop_phone' => array(
            'title' => __('Shop phone number', 'itella-shipping'),
            'type' => 'text',
        ),
        'pick_up_start' => array(
            'title' => __('Pick up time start', 'itella-shipping'),
            'type' => 'text',
        ),
        'pick_up_end' => array(
            'title' => __('Pick up time end', 'itella-shipping'),
            'type' => 'text',
        ),
        'send_off' => array(
            'title' => __('Send off type', 'itella-shipping'),
            'type' => 'select',
            'description' => __('Send from store type.', 'itella-shipping'),
            'options' => array(
                'pt' => __('Parcel terminal', 'itella-shipping'),
                'c' => __('Courrier', 'itella-shipping')
            )
        ),
        'method_pt' => array(
            'title' => __('Parcel terminal', 'itella-shipping'),
            'type' => 'checkbox',
            'description' => __('Show parcel terminal method in checkout.', 'itella-shipping')
        ),
        'method_c' => array(
            'title' => __('Courrier', 'itella-shipping'),
            'type' => 'checkbox',
            'description' => __('Show courrier method in checkout.', 'itella-shipping')
        ),
        'c_price' => array(
            'title' => 'LT ' . __('Courrier price', 'itella-shipping'),
            'type' => 'number',
            'default' => 2,
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
        ),
        'pt_price' => array(
            'title' => 'LT ' . __('Parcel terminal price', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 2,
        ),
        'pt_priceFREE' => array(
            'title' => 'LT ' . __('Free shipping then price is higher (Terminals)', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 100
        ),
        'pt_price_C_FREE' => array(
            'title' => 'LT ' . __('Free shipping then price is higher (Courier)', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 100
        ),
        'c_priceLV' => array(
            'title' => 'LV ' . __('Courrier price', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 2
        ),
        'pt_priceLV' => array(
            'title' => 'LV ' . __('Parcel terminal price', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 2
        ),
        'pt_priceLV_FREE' => array(
            'title' => 'LV ' . __('Free shipping then price is higher (Terminals)', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 100
        ),
        'pt_price_C_LV_FREE' => array(
            'title' => 'LV ' . __('Free shipping then price is higher (Courier)', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 100
        ),
        'c_priceEE' => array(
            'title' => 'EE ' . __('Courrier price', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 2
        ),
        'pt_priceEE' => array(
            'title' => 'EE ' . __('Parcel terminal price', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 2
        ),
        'pt_priceEE_FREE' => array(
            'title' => 'EE ' . __('Free shipping then price is higher (Terminals)', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 100
        ),
        'pt_price_C_EE_FREE' => array(
            'title' => 'EE ' . __('Free shipping then price is higher (Courier)', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'default' => 100
        ),
        'weight' => array(
            'title' => __('Weight (kg)', 'itella-shipping'),
            'type' => 'number',
            'custom_attributes' => array(
                'step'          => 0.01,
            ),
            'description' => __('Maximum allowed weight', 'itella-shipping'),
            'default' => 100
        ),
        'show_map' => array(
            'title' => __('Map', 'itella-shipping'),
            'type' => 'checkbox',
            'description' => __('Show map of terminals.', 'itella-shipping'),
            'default' => 'yes'
        ),
    );
  }

}
