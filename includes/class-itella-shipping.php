<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link
 * @since      1.0.0
 *
 * @package    Itella_Woocommerce
 * @subpackage Itella_Woocommerce/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Itella_Woocommerce
 * @subpackage Itella_Woocommerce/includes
 * @author     Your Name <email@example.com>
 */
class Itella_Shipping
{

  /**
   * The class is a singleton
   * 
   * @access  public
   * @var     Itella_Shipping $instance This class
   */
  public static $instance;

  /**
   * The loader that's responsible for maintaining and registering all hooks that power
   * the plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      Itella_Shipping_Loader $loader Maintains and registers all hooks for the plugin.
   */
  protected $loader;

  /**
   * The unique identifier of this plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string $plugin_name The string used to uniquely identify this plugin.
   */
  protected $plugin_name;

  /**
   * URL of this plugin directory.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string $plugin_url The URL of this plugin directory.
   */
  protected $plugin_url;

  /**
   * Path of this plugin directory.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string $plugin_path The path in server of this plugin directory.
   */
  protected $plugin_path;

  /**
   * The current version of the plugin.
   *
   * @since    1.0.0
   * @access   protected
   * @var      string $version The current version of the plugin.
   */
  protected $version;

  /**
   * @var array $available_methods
   */
  protected $available_methods;

  /**
   * Define the core functionality of the plugin.
   *
   * Set the plugin name and the plugin version that can be used throughout the plugin.
   * Load the dependencies, define the locale, and set the hooks for the Dashboard and
   * the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function __construct($plugin)
  {
    $this->plugin_name = 'itella-shipping';
    $this->version = $plugin['version'];
    $this->available_methods = $this->get_available_methods();
    $this->plugin_basename = $plugin['basename'];
    $this->plugin_url = $plugin['url'];
    $this->plugin_path = $plugin['path'];

    add_action('plugins_loaded', array($this, 'run'));
    add_action('admin_notices', array($this, 'notify_on_activation'));

    self::$instance = $this;
  }

  public static function get_instance() {
    return self::$instance;
  }

  public function get_plugin_data()
  {
    return (object) array(
      'name' => $this->get_plugin_name(),
      'version' => $this->get_version(),
      'url' => $this->get_plugin_url(),
      'path' => $this->get_plugin_path(),
      'methods' => $this->get_available_methods(),
      'countries' => $this->get_available_methods('all'),
      'countries_grouped' => $this->get_available_methods(true),
      'sender_countries' => $this->get_sender_methods(true),
    );
  }

  public function get_method_short_key( $method_key )
  {
    switch ($method_key) {
      case 'pickup_point':
        return 'pp';
        break;
      case 'courier':
        return 'c';
        break;
      default:
        return $method_key;
        break;
    }
  }

  /**
   * Get available methods of dispatch and sender countries
   * 
   * @param boolean $get_countries - Get available sender countries list.
   * @return array
   */
  private function get_sender_methods( $get_countries = false )
  {
    $sender_countries = array('LT', 'LV', 'EE', 'FI');

    if ( $get_countries ) {
      return $sender_countries;
    }

    return array();
  }

  /**
   * Get available shipping methods and receiver countries
   * 
   * @param boolean|string $get_countries - Get available receiver countries list.
   * @return array
   */
  private function get_available_methods( $get_countries = false )
  {
    $countries = array(
      'courier' => array('LT', 'LV', 'EE', 'FI', 'AT', 'BE', 'BG', 'HR', 'CZ', 'DK', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LU', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE'),
      'pickup_point' => array('LT', 'LV', 'EE', 'FI')
    );

    $methods_names = array(
      'courier' => __('Courier', 'itella-shipping'),
      'pickup_point' => __('Parcel locker', 'itella-shipping'),
    );

    // Return countries list
    if ( $get_countries ) {
      if ( $get_countries === true ) {
        return $countries;
      }

      if ( isset($countries[$get_countries]) ) {
        return $countries[$get_countries];
      }

      $all_countries = array();
      foreach ( $countries as $type => $list ) {
        foreach ( $list as $c ) {
          if ( ! in_array($c, $all_countries) ) {
            $all_countries[] = $c;
          }
        }
      }
      return $all_countries;
    }
    
    // Return methods list
    $methods = array();

    foreach ( $countries as $type => $list ) {
      foreach ( $list as $c ) {
        $methods[$c][$type] = $methods_names[$type];
      }
    }

    return $methods;
  }

  /**
   * Load the required dependencies for this plugin.
   *
   * Include the following files that make up the plugin:
   *
   * - Itella_Shipping_Loader. Orchestrates the hooks of the plugin.
   * - Itella_Shipping_i18n. Defines internationalization functionality.
   * - Itella_Shipping_Admin. Defines all hooks for the dashboard.
   * - Itella_Shipping_Public. Defines all hooks for the public side of the site.
   * - Itella_Shipping_Cron. Orchestrates scheduling and un-scheduling cron jobs.
   *
   * Create an instance of the loader which will be used to register the hooks
   * with WordPress.
   *
   * @since    1.0.0
   * @access   private
   */
  private function load_dependencies()
  {

    if (!class_exists('WooCommerce')) {
      return;
    }

    /**
     * The class responsible for orchestrating the actions and filters of the
     * core plugin.
     */
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-itella-shipping-loader.php';

    /**
     * The class responsible for defining internationalization functionality
     * of the plugin.
     */
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-itella-shipping-i18n.php';

    /**
     * The class responsible for defining all actions that occur in the Dashboard.
     */
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-itella-shipping-method.php';

    /**
     * The class is designed to reduce the size of the Itella_Shipping_Method class by moved out custom functions.
     */
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-itella-shipping-method-helper.php';

    /**
     * The class responsible for defining all actions that occur in the public-facing
     * side of the site.
     */
    require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-itella-shipping-public.php';

    /**
     * Load Itella API
     */
    require_once plugin_dir_path(dirname(__FILE__)) . 'libs/itella-api/vendor/autoload.php';

    /**
     * Load manifest script
     */
    require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-itella-shipping-manifest-page.php';

    $this->loader = new Itella_Shipping_Loader();

    $this->set_locale();
    $this->define_admin_hooks();
    $this->loader->add_filter('woocommerce_shipping_methods', $this, 'add_itella_shipping_method');
    $this->define_public_hooks();
    $this->define_manifest_hooks();
    $this->loader->run();

  }

  /**
   * Define the locale for this plugin for internationalization.
   *
   * Uses the Itella_Shipping_i18n class in order to set the domain and to register the hook
   * with WordPress.
   *
   * @since    1.0.0
   * @access   private
   */
  private function set_locale()
  {

    $plugin_i18n = new Itella_Shipping_i18n();
    $plugin_i18n->set_domain($this->get_plugin_name());

    $this->loader->add_action('init', $plugin_i18n, 'load_plugin_textdomain');

  }

  /**
   * Register all of the hooks related to the dashboard functionality
   * of the plugin.
   *
   * @since    1.0.0
   * @access   private
   */
  private function define_admin_hooks()
  {
    $plugin_admin = new Itella_Shipping_Method();

    $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
    $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    $this->loader->add_action('plugin_action_links_' . $this->plugin_basename, $plugin_admin, 'plugin_links');
    $this->loader->add_action('woocommerce_update_options_shipping_' . $plugin_admin->id, $plugin_admin, 'process_admin_options');
    $this->loader->add_action('woocommerce_admin_order_data_after_shipping_address', $plugin_admin, 'add_shipping_details_to_order');
    $this->loader->add_action('woocommerce_process_shop_order_meta', $plugin_admin, 'save_shipping_settings');
    $this->loader->add_action('admin_notices', $plugin_admin, 'itella_shipping_notices');
    $this->loader->add_action('woocommerce_after_checkout_validation', $plugin_admin, 'validate_pickup_point');
    $this->loader->add_action('wp_after_admin_bar_render', $plugin_admin, 'update_locations');
    $this->loader->add_action('woocommerce_email_order_meta', $plugin_admin, 'add_itella_shipping_info_to_email');
    $this->loader->add_action('woocommerce_email_styles', $plugin_admin, 'itella_shipping_info_css_in_email');
    $this->loader->add_action('woocommerce_admin_order_preview_end', $plugin_admin, 'display_custom_data_in_admin_order_preview');
    $this->loader->add_action('wp_ajax_single_register_shipment', $plugin_admin, 'itella_ajax_single_register_shipment');
    $this->loader->add_action('wp_ajax_nopriv_single_register_shipment', $plugin_admin, 'itella_ajax_single_register_shipment');
    $this->loader->add_action('wp_ajax_bulk_register_shipments', $plugin_admin, 'itella_ajax_bulk_register_shipments');
    $this->loader->add_action('wp_ajax_nopriv_bulk_register_shipments', $plugin_admin, 'itella_ajax_bulk_register_shipments');
    $this->loader->add_action('woocommerce_after_shipping_rate', $plugin_admin, 'itella_shipping_method_description', 20, 2);

    $this->loader->add_filter('admin_post_itella_labels', $plugin_admin, 'itella_post_label_actions', 20);
    $this->loader->add_filter('admin_post_itella_shipments', $plugin_admin, 'itella_post_shipment_actions', 20);
    $this->loader->add_filter('admin_post_itella_manifests', $plugin_admin, 'itella_post_manifest_actions', 20);
    $this->loader->add_filter('admin_post_itella-call-courier', $plugin_admin, 'itella_post_call_courier_actions', 20);
    //$this->loader->add_filter('bulk_actions-edit-shop_order', $plugin_admin, 'itella_register_orders_bulk_actions', 20); //Disabled while handle filter not working
    //$this->loader->add_filter('handle_bulk_actions-edit-shop_order', $plugin_admin, 'itella_handle_orders_bulk_actions', 20, 3); //Need to create
    $this->loader->add_filter('woocommerce_admin_order_preview_get_order_details', $plugin_admin, 'add_custom_admin_order_preview_meta', 10, 2);

  }

  /**
   * Register all of the hooks related to the public-facing functionality
   * of the plugin.
   *
   * @since    1.0.0
   * @access   private
   */
  private function define_public_hooks()
  {
    $plugin_public = new Itella_Shipping_Public($this->get_plugin_data());

    $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
    $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    $this->loader->add_action('woocommerce_order_details_after_order_table', $plugin_public, 'show_pp_details', 10, 1);
    $this->loader->add_action('woocommerce_checkout_update_order_meta', $plugin_public, 'add_pp_id_to_order');
    //$this->loader->add_action('woocommerce_order_status_completed', $plugin_public, 'show_pp');
    $this->loader->add_action('woocommerce_checkout_before_order_review', $plugin_public, 'itella_checkout_hidden_fields');
    $this->loader->add_action('woocommerce_cart_totals_before_shipping', $plugin_public, 'itella_checkout_hidden_fields');

    $this->loader->add_filter('woocommerce_package_rates', $plugin_public, 'show_itella_shipping_methods', 10, 1);
  }

  private function define_manifest_hooks()
  {
    $plugin_manifest = new Itella_Manifest($this->get_plugin_data());

    $this->loader->add_action('admin_enqueue_scripts', $plugin_manifest, 'enqueue_styles');
    $this->loader->add_action('admin_enqueue_scripts', $plugin_manifest, 'enqueue_scripts');
    $this->loader->add_action('admin_menu', $plugin_manifest, 'register_itella_manifest_menu_page');

    $this->loader->add_filter('woocommerce_order_data_store_cpt_get_orders_query', $plugin_manifest, 'handle_custom_itella_query_var', 10, 2);
  }



  /**
   * Run the loader to execute all of the hooks with WordPress.
   *
   * @since    1.0.0
   */
  public function run()
  {
    $this->load_dependencies();
  }

  /**
   * The name of the plugin used to uniquely identify it within the context of
   * WordPress and to define internationalization functionality.
   *
   * @return    string    The name of the plugin.
   * @since     1.0.0
   */
  public function get_plugin_name()
  {
    return $this->plugin_name;
  }

  /**
   * The plugin URL used for publicly available files.
   *
   * @return    string    The URL of the plugin.
   * @since     1.3.7
   */
  public function get_plugin_url()
  {
    return $this->plugin_url;
  }

  /**
   * The plugin path used for get files in server context.
   *
   * @return    string    The path of the plugin.
   * @since     1.3.7
   */
  public function get_plugin_path()
  {
    return $this->plugin_path;
  }

  /**
   * The reference to the class that orchestrates the hooks with the plugin.
   *
   * @return    Itella_Shipping_Loader    Orchestrates the hooks of the plugin.
   * @since     1.0.0
   */
  public function get_loader()
  {
    return $this->loader;
  }

  /**
   * Retrieve the version number of the plugin.
   *
   * @return    string    The version number of the plugin.
   * @since     1.0.0
   */
  public function get_version()
  {
    return $this->version;
  }

  public function notify_on_activation()
  {

    if (get_transient('itella-shipping-activated')) : ?>
        <div class="updated notice is-dismissible">
            <p>Setup Itella Shipping
                <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=shipping&section=itella-shipping'); ?>">here</a>.
            </p>
        </div>
      <?php
      delete_transient('itella-shipping-activated');
    endif;
  }

  public function add_itella_shipping_method($methods)
  {

    $methods['itella-shipping'] = 'Itella_Shipping_Method';

    return $methods;
  }

}
