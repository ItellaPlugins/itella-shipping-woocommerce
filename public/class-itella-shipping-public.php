<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/admin
 * @author     Your Name <email@example.com>
 */
class Itella_Shipping_Public
{

  /**
   * The ID of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string $name The ID of this plugin.
   */
  private $name;

  /**
   * The version of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string $version The current version of this plugin.
   */
  private $version;

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @var      string $name The name of the plugin.
   * @var      string $version The version of this plugin.
   */
  public function __construct($name, $version)
  {

    $this->name = $name;
    $this->version = $version;

  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_styles()
  {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Itella_Shipping_Public_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Itella_Shipping_Public_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_style($this->name . 'itella-shipping-public.css', plugin_dir_url(__FILE__) . 'css/itella-shipping-public.css', array(), $this->version, 'all');
    wp_enqueue_style($this->name . 'leaflet.css', "https://unpkg.com/leaflet@1.5.1/dist/leaflet.css", array(), $this->version, 'all');
    wp_enqueue_style($this->name . 'itella-mapping.css', plugin_dir_url(__FILE__) . 'css/itella-mapping.css', array(), $this->version, 'all');
  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts()
  {

    /**
     * This function is provided for demonstration purposes only.
     *
     * An instance of this class should be passed to the run() function
     * defined in Itella_Shipping_Public_Loader as all of the hooks are defined
     * in that particular class.
     *
     * The Itella_Shipping_Public_Loader will then create the relationship
     * between the defined hooks and the functions defined in this
     * class.
     */

    wp_enqueue_script($this->name . 'leaflet.js', "https://unpkg.com/leaflet@1.5.1/dist/leaflet.js", array(), $this->version, TRUE);
    wp_enqueue_script($this->name . 'itella-mapping.js', plugin_dir_url(__FILE__) . 'js/itella-mapping.js', array(), $this->version, TRUE);
    wp_enqueue_script($this->name . 'itella-shipping-public.js', plugin_dir_url(__FILE__) . 'js/itella-shipping-public.js', array(), $this->version, TRUE);
    wp_localize_script($this->name . 'itella-shipping-public.js', 'mapScript', array('pluginsUrl' => plugin_dir_url(__FILE__)));
    wp_enqueue_script($this->name . 'itella-init-map.js', plugin_dir_url( __FILE__ ) . 'js/itella-init-map.js', array( 'jquery' ), $this->version, TRUE );

  }

  public function show_pp($method)
  {


  }

  public function show_pp_details($order)
  {

//    var_dump(get_post_meta($order->get_id(), '_pp_id', true));
//    var_dump(get_post_meta($order->get_id(), '_itella_method', true));

    $chosen_itella_method = get_post_meta($order->get_id(), '_itella_method', true);
    if ($chosen_itella_method === 'itella_pp') {
      echo "<p>" . __('Itella pickup point', 'itella_shipping') . ": " . getOmnivaTerminalAddress($order) . "</p>";
    }

//    if ($chosen_itella_method) {
//      printTrackingLink($order, false, true);
//    }
  }

  public function add_pp_id_to_order($order_id)
  {
//    var_dump($order_id);
    if (isset($_POST['itella-chosen-point-id']) && $order_id) {
      update_post_meta($order_id, '_pp_id', $_POST['itella-chosen-point-id']);
    }
    if (isset($_POST['shipping_method'][0]) && ($_POST['shipping_method'][0] === "itella_pp" || $_POST['shipping_method'][0] === "itella_c")) {
      update_post_meta($order_id, '_itella_method', $_POST['shipping_method'][0]);
    }
  }

//  public function add_terminal_to_session()
//  {
//    if (isset($_POST['terminal_id']) && is_numeric($_POST['terminal_id'])) WC()->session->set('omnivalt_terminal_id', $_POST['terminal_id']);
//    wp_die();
//  }

}
