<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/admin
 * @author     Your Name <email@example.com>
 */
class Itella_Manifest
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
   * @var      string $name The name of this plugin.
   * @var      string $version The version of this plugin.
   */

  public function __construct($name, $version)
  {
    $this->name = $name;
    $this->version = $version;
  }

  /**
   * Register the stylesheets for the Dashboard.
   *
   * @since    1.0.0
   */
  public function enqueue_styles()
  {

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

    wp_enqueue_style($this->name . 'css/itella-shipping-manifest.css', plugin_dir_url(__FILE__) . 'css/itella-shipping-manifest.css', array(), $this->version, 'all');
    wp_enqueue_style($this->name . 'bootstrap-datetimepicker', plugins_url('/js/datetimepicker/bootstrap-datetimepicker.min.css', __FILE__));

  }

  /**
   * Register the JavaScript for the dashboard.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts()
  {

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

    wp_enqueue_script($this->name . 'itella-shipping-manifest.js', plugin_dir_url(__FILE__) . 'js/itella-shipping-manifest.js', array('jquery'), $this->version, TRUE);
    wp_localize_script($this->name . 'itella-shipping-manifest.js', 'translations', array(
            'select_orders' => __('Select at least one order to perform this action.', 'itella_shipping')
    ));
    wp_enqueue_script($this->name . 'moment', plugin_dir_url(__FILE__) . 'js/datetimepicker/moment.min.js', array(), null, true);
    wp_enqueue_script($this->name . 'bootstrap-datetimepicker', plugin_dir_url(__FILE__) . 'js/datetimepicker/bootstrap-datetimepicker.min.js', array('jquery', 'moment'), null, true);

  }

  public function register_itella_manifest_menu_page()
  {
    add_submenu_page(
        'woocommerce',
        __('Itella shipments', 'itella_shipping'),
        __('Itella shipments', 'itella_shipping'),
        'manage_options',
        'itella-manifest',
        'itella_manifest_page',
        1
    );

    function itella_manifest_page()
    {
      Itella_Manifest::load_manifest_page();
    }
  }

  public static function load_manifest_page()
  {

    /**
     * Manifest page defaults
     */
    $tab_strings = array(
        'all_orders' => __('All orders', 'itella_shipping'),
        'new_orders' => __('New orders', 'itella_shipping'),
        'completed_orders' => __('Completed orders', 'itella_shipping')
    );

    $filter_keys = array(
        'customer',
        'status',
        'tracking_code',
        'id',
        'start_date',
        'end_date'
    );

// amount of orders to show per page
    $max_per_page = 25;

// prep access to Itella shipping class
//    $wc_shipping = new WC_Shipping();
    $itella_shipping = new Itella_Shipping_Method();
    ?>

      <div class="wrap">
      <h1><?php _e('Itella shipments', 'itella_shipping'); ?></h1>

    <?php

    $paged = 1;
    if (isset($_GET['paged']))
      $paged = filter_input(INPUT_GET, 'paged');

    $action = 'all_orders';
    if (isset($_GET['action'])) {
      $action = filter_input(INPUT_GET, 'action');
    }

    $filters = array();
    foreach ($filter_keys as $filter_key) {
      if (isset($_POST['filter_' . $filter_key]) && intval($_POST['filter_' . $filter_key]) !== -1) {
        $filters[$filter_key] = filter_input(INPUT_POST, 'filter_' . $filter_key); //$_POST['filter_' . $filter_key];
      } else {
        $filters[$filter_key] = false;
      }
    }

    // Handle query variables depending on selected tab
    switch ($action) {
      case 'new_orders':
        $page_title = $tab_strings[$action];
        $args = array(
            'itella_manifest' => false,
        );
        break;
      case 'completed_orders':
        $page_title = $tab_strings[$action];
        $args = array(
            'itella_manifest' => true,
          // latest manifest at the top
            'meta_key' => '_itella_manifest_generation_date',
            'orderby' => 'meta_value',
            'order' => 'DESC'
        );
        break;
      case 'all_orders':
      default:
        $action = 'all_orders';
        $page_title = $tab_strings['all_orders'];
        $args = array();
        break;
    }

    foreach ($filters as $key => $filter) {
      if ($filter) {
        switch ($key) {
          case 'status':
            $args = array_merge(
                $args,
                array('status' => $filter)
            );
            break;
          case 'tracking_code':
            $args = array_merge(
                $args,
                array('itella_tracking_code' => $filter)
            );
            break;
          case 'customer':
            $args = array_merge(
                $args,
                array('itella_customer' => $filter)
            );
            break;
        }
      }
    }
    // date filter is a special case
    if ($filters['start_date'] || $filters['end_date']) {
      $args = array_merge(
          $args,
          array('itella_manifest_date' => array($filters['start_date'], $filters['end_date']))
      );
    }

    // Get orders with extra info about the results.
    $args = array_merge(
        $args,
        array(
            'itella_method' => ['itella_pp', 'itella_c', 'itella'],
            'paginate' => true,
            'limit' => $max_per_page,
            'paged' => $paged,
        )
    );

    // Searching by ID takes priority
    $singleOrder = false;
    if ($filters['id']) {
      $singleOrder = wc_get_order($filters['id']);
      if ($singleOrder) {
        $orders = array($singleOrder); // table printer expects array
        $paged = 1;
      }
    }

    // if there is no search by ID use to custom query
    $results = false;
    if (!$singleOrder) {
      $results = wc_get_orders($args);
      $orders = $results->orders;
    }

    $thereIsOrders = ($singleOrder || ($results && $results->total > 0));

    // make pagination
    $page_links = false;
    if ($results) {
      $page_links = paginate_links(array(
          'base' => add_query_arg('paged', '%#%'),
          'format' => '?paged=%#%',
          'prev_text' => __('&laquo;', 'text-domain'),
          'next_text' => __('&raquo;', 'text-domain'),
          'total' => $results->max_num_pages,
          'current' => $paged,
          'type' => 'plain'
      ));
    }

    $order_statuses = wc_get_order_statuses();
    ?>

      <div class="call-courier-container">
          <form id="call-courier-form" action="admin-post.php" method="GET">
              <input type="hidden" name="action" value="itella-call-courier" />
            <?php wp_nonce_field('itella-call-courier', 'itella-call-courier_nonce'); ?>
          </form>
          <button id="itella-call-btn" class="button action">
            <?php _e('Call Itella courier', 'itella_shipping') ?>
          </button>
      </div>

      <ul class="nav nav-tabs">
        <?php foreach ($tab_strings as $tab => $tab_title) : ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $action == $tab ? 'active' : ''; ?>"
                   href="<?php echo Itella_Manifest::make_link(array('paged' => ($action == $tab ? $paged : 1), 'action' => $tab)); ?>"><?php echo $tab_title; ?></a>
            </li>
        <?php endforeach; ?>
      </ul>

    <?php if ($page_links) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
              <?php echo $page_links; ?>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($thereIsOrders) : ?>
        <div class="mass-print-container">
            <form id="manifest-print-form" action="admin-post.php" method="GET">
                <input type="hidden" name="action" value="itella_manifests"/>
              <?php wp_nonce_field('itella_manifest', 'itella_manifest_nonce'); ?>
            </form>
            <form id="labels-print-form" action="admin-post.php" method="GET">
                <input type="hidden" name="action" value="itella_labels"/>
              <?php wp_nonce_field('itella_labels', 'itella_labels_nonce'); ?>
            </form>
            <button id="submit_manifest_items" title="<?php echo __('Generate manifests', 'itella_shipping'); ?>"
                    type="button" class="button action">
              <?php echo __('Generate manifests', 'itella_shipping'); ?>
            </button>
            <button id="submit_manifest_labels" title="<?php echo __('Print labels', 'itella_shipping'); ?>"
                    type="button" class="button action">
              <?php echo __('Print labels', 'itella_shipping'); ?>
            </button>
        </div>
    <?php endif; ?>

      <div class="table-container">
          <form id="filter-form" class="" action="<?php echo Itella_Manifest::make_link(array('action' => $action)); ?>"
                method="POST">
            <?php
            wp_nonce_field('itella_labels', 'itella_labels_nonce');
            wp_nonce_field('itella_shipments', 'itella_shipments_nonce');
            wp_nonce_field('itella_manifests', 'itella_manifests_nonce');
            ?>
              <table class="wp-list-table widefat fixed striped posts">
                  <thead>

                  <tr class="itella-filter">
                      <td class="manage-column column-cb check-column"><input type="checkbox" class="check-all"/></td>
                      <th class="manage-column">
                          <input type="text" class="d-inline" name="filter_id" id="filter_id"
                                 value="<?php echo $filters['id']; ?>"
                                 placeholder="<?php echo __('ID', 'itella_shipping'); ?>" aria-label="Order ID filter">
                      </th>
                      <th class="manage-column">
                          <input type="text" class="d-inline" name="filter_customer" id="filter_customer"
                                 value="<?php echo $filters['customer']; ?>"
                                 placeholder="<?php echo __('Customer', 'itella_shipping'); ?>"
                                 aria-label="Order ID filter">
                      </th>
                      <th class="manage-column">
                          <select class="d-inline" name="filter_status" id="filter_status"
                                  aria-label="Order status filter">
                              <option value="-1" selected>All</option>
                            <?php foreach ($order_statuses as $status_key => $status) : ?>
                                <option value="<?php echo $status_key; ?>" <?php echo($status_key == $filters['status'] ? 'selected' : ''); ?>><?php echo $status; ?></option>
                            <?php endforeach; ?>
                          </select>
                      </th>
                      <th class="manage-column">
                      </th>
                      <th class="manage-column">
                          <input type="text" class="d-inline" name="filter_tracking_code" id="filter_tracking_code"
                                 value="<?php echo $filters['tracking_code']; ?>"
                                 placeholder="<?php echo __('Tracking code', 'itella_shipping'); ?>"
                                 aria-label="Order tracking_code filter">
                      </th>
                      <th class="manage-column">
                          <div class='datetimepicker'>
                              <div>
                                  <input name="filter_start_date" type='text' class="" id='datetimepicker1'
                                         data-date-format="YYYY-MM-DD" value="<?php echo $filters['start_date']; ?>"
                                         placeholder="<?php echo __('From', 'itella_shipping'); ?>" autocomplete="off"/>
                              </div>
                              <div>
                                  <input name="filter_end_date" type='text' class="" id='datetimepicker2'
                                         data-date-format="YYYY-MM-DD" value="<?php echo $filters['end_date']; ?>"
                                         placeholder="<?php echo __('To', 'itella_shipping'); ?>" autocomplete="off"/>
                              </div>
                          </div>
                      </th>
                      <th class="manage-column">
                          <div class="itella-action-buttons-container">
                              <button class="button action"
                                      type="submit"><?php echo __('Filter', 'itella_shipping'); ?></button>
                              <button id="clear_filter_btn" class="button action"
                                      type="submit"><?php echo __('Reset', 'itella_shipping'); ?></button>
                          </div>
                      </th>
                  </tr>

                  <tr class="table-header">
                      <td class="manage-column column-cb check-column"></td>
                      <th scope="col" class="manage-column"><?php echo __('ID', 'itella_shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Customer', 'itella_shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Order Status', 'itella_shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Service', 'itella_shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Tracking code', 'itella_shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Manifest date', 'itella_shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Actions', 'itella_shipping'); ?></th>
                  </tr>

                  </thead>
                  <tbody>
                  <?php $date_tracker = false; ?>
                  <?php foreach ($orders as $order) : ?>
                    <?php
                    $manifest_date = $order->get_meta('_itella_manifest_generation_date');
                    $date = date('Y-m-d H:i', strtotime($manifest_date));
                    ?>
                    <?php if ($action == 'completed_orders' && $date_tracker !== $date) : ?>
                          <tr>
                              <td colspan="8" class="manifest-date-title">
                                <?php echo $date_tracker = $date; ?>
                              </td>
                          </tr>
                    <?php endif; ?>
                      <tr class="data-row">
                          <th scope="row" class="check-column"><input type="checkbox" name="items[]"
                                                                      class="manifest-item"
                                                                      value="<?php echo $order->get_id(); ?>"/></th>
                          <td class="manage-column">
                              <a href="<?php echo $order->get_edit_order_url(); ?>">#<?php echo $order->get_order_number(); ?></a>
                          </td>
                          <td class="column-order_number">
                              <div class="data-grid-cell-content">
                                <?php echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?>
                              </div>
                          </td>
                          <td class="column-order_status">
                              <div class="data-grid-cell-content">
                                <?php echo wc_get_order_status_name($order->get_status()); ?>
                              </div>
                          </td>
                          <td class="manage-column">
                              <div class="data-grid-cell-content">
                                <?php
                                $shipping_parameters = Itella_Manifest::get_shipping_parameters($order->get_id());
                                if ($shipping_parameters) {
                                  if ($shipping_parameters['itella_shipping_method'] === 'itella_pp') {
                                    $chosen_pickup_point = $itella_shipping->get_chosen_pickup_point(
                                        $order->get_shipping_country(),
                                        $shipping_parameters['pickup_point_id']
                                    );
                                    ?>
                                      <strong> <?= __('Itella Pickup Point:', 'itella_shipping'); ?> </strong>
                                      <br>
                                    <?= __('City: ', 'itella_shipping') ?>
                                      <em> <?= $chosen_pickup_point->address->municipality ?></em><br>
                                    <?= __('Public Name: ', 'itella_shipping') ?>
                                      <em> <?= $chosen_pickup_point->publicName ?></em><br>
                                    <?= __('Address: ', 'itella_shipping') ?>
                                      <em> <?= $chosen_pickup_point->address->address ?></em><br>
                                    <?= __('Postal Code: ', 'itella_shipping') ?>
                                      <em> <?= $chosen_pickup_point->address->postalCode ?></em>
                                    <?php
                                  }
                                }
                                if ($shipping_parameters['itella_shipping_method'] === 'itella_c') {
                                  ?>
                                    <strong> <?= __('Itella Courier:', 'itella_shipping'); ?> </strong>
                                    <br>
                                  <?php
                                  echo __('Packet Count: ', 'itella_shipping') ?>
                                    <em> <?= $shipping_parameters['packet_count']; ?></em><?php
                                  ?> <br> <?php
                                  echo __('Weight: ', 'itella_shipping') ?>
                                    <em> <?= $shipping_parameters['weight']; ?></em><?php
                                  ?> <br> <?php
                                  echo __('COD: ', 'itella_shipping');
                                  ?>
                                    <em> <?= $shipping_parameters['is_cod'] ? __('Yes', 'woocommerce') : __('No', 'woocommerce'); ?></em>
                                    <br> <?php
                                  if ($shipping_parameters['is_cod']) {
                                    echo __('COD amount: ', 'itella_shipping') ?>
                                      <em> <?= $shipping_parameters['cod_amount']; ?></em><?php
                                    ?> <br> <?php
                                  }
                                  if ($shipping_parameters['extra_services']) {
                                    echo __('Extra services:', 'itella_shipping');
                                    ?> <br> <?php
                                    if ($shipping_parameters['multi_parcel']) {
                                      ?><em> <?= $shipping_parameters['multi_parcel']; ?></em>
                                        <br> <?php
                                    }
                                    foreach ($shipping_parameters['extra_services'] as $extra_service) {
                                      ?><em> <?= __(ucfirst($extra_service), 'itella_shipping'); ?></em>
                                        <br> <?php
                                    }
                                  }
                                }
                                ?>
                              </div>
                          </td>
                          <td class="manage-column">
                              <div class="data-grid-cell-content">
                                <?php
                                $tracking_code = $order->get_meta('_itella_tracking_code');
                                $tracking_url = $order->get_meta('_itella_tracking_url');
                                ?>
                                <?php if ($tracking_code) : ?>
                                    <a href="<?= $tracking_url ? $tracking_url : '#' ?>" target="_blank">
                                      <?= $tracking_code; ?>
                                    </a>
                                  <?php $error = $order->get_meta('_itella_tracking_code_error'); ?>
                                  <?php if ($error) : ?>
                                        <br/>Error: <?php echo $error; ?>
                                  <?php endif; ?>
                                <?php endif; ?>
                              </div>
                          </td>
                          <td class="manage-column">
                              <div class="data-grid-cell-content">
                                <?php echo $manifest_date; ?>
                              </div>
                          </td>
                          <td class="manage-column">
                            <?php if ($tracking_code): ?>
                                <span class="button action button-itella button-itella-disabled">
                                  <?php echo __('Register shipment', 'itella_shipping'); ?>
                                </span>
                                <a href="admin-post.php?action=itella_labels&post=<?php echo $order->get_id(); ?>"
                                   class="button action button-itella">
                                  <?php echo __('Print label', 'itella_shipping'); ?>
                                </a>
                                <a href="admin-post.php?action=itella_manifests&post=<?php echo $order->get_id(); ?>"
                                   class="button action button-itella">
                                  <?php echo __('Generate manifest', 'itella_shipping'); ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!$tracking_code): ?>
                                <a href="admin-post.php?action=itella_shipments&post=<?php echo $order->get_id(); ?>"
                                   class="button action button-itella">
                                  <?php echo __('Register shipment', 'itella_shipping'); ?>
                                </a>
                                <span class="button action button-itella button-itella-disabled">
                                  <?php echo __('Print label', 'itella_shipping'); ?>
                                </span>
                                <span class="button action button-itella button-itella-disabled">
                                  <?php echo __('Generate manifest', 'itella_shipping'); ?>
                                </span>
                            <?php endif; ?>
                          </td>
                      </tr>
                  <?php endforeach; ?>

                  <?php if (!$orders) : ?>
                      <tr>
                          <td colspan="8">
                            <?php echo __('No orders found', 'woocommerce'); ?>
                          </td>
                      </tr>
                  <?php endif; ?>
                  </tbody>
              </table>
          </form>
      </div>

      <!-- Modal Carier call-->
      <div id="itella-courier-modal" class="modal" role="dialog">
          <!-- Modal content-->
          <div class="modal-content">
              <div class="alert-info">
                  <p>
                      <span><?php _e('Important!', 'itella_shipping') ?></span> <?php _e('Check your credentials.', 'itella_shipping') ?>
                  </p>
                  <p><?php _e('Address and contact information can be changed in Itella settings.', 'itella_shipping') ?></p>
              </div>
              <form id="itella-call" action="admin-post.php" method="GET">
                  <input type="hidden" name="action" value="itella_call_courier"/>
                <?php wp_nonce_field('itella_call_courier', 'itella_call_courier_nonce'); ?>
                  <div>
                      <span><?php echo __("Shop name", 'itella_shipping'); ?>:</span> <?php echo $itella_shipping->settings['shop_name']; ?>
                  </div>
                  <div>
                      <span><?php echo __("Shop phone number", 'itella_shipping'); ?>:</span> <?php echo $itella_shipping->settings['shop_phone']; ?>
                  </div>
                  <div>
                      <span><?php echo __("Shop postcode", 'itella_shipping'); ?>:</span> <?php echo $itella_shipping->settings['shop_postcode']; ?>
                  </div>
                  <div>
                      <span><?php echo __("Shop address", 'itella_shipping'); ?>:</span> <?php echo $itella_shipping->settings['shop_address'] . ', ' . $itella_shipping->settings['shop_city']; ?>
                  </div>
                  <div class="modal-footer">
                      <button type="submit" id="itella-call-btn"
                              class="button action"><?php _e('Call Itella courier', 'itella_shipping') ?></button>
                      <button type="button" id="itella-call-cancel-btn"
                              class="button action"><?php _e('Cancel') ?></button>
                  </div>
              </form>
          </div>
      </div>
      <!--/ Modal Carier call-->
    <?php
  }

  /**
   * helper function to create links
   * @param $args
   * @return string
   */
  public static function make_link($args)
  {
    $query_args = array('page' => 'itella-manifest');
    $query_args = array_merge($query_args, $args);
    return add_query_arg($query_args, admin_url('/admin.php'));
  }

  public static function get_shipping_parameters($order_id)
  {

    $shipping_parameters = array();
    $order = wc_get_order($order_id);
    $is_shipping_updated = !empty(get_post_meta($order_id, 'itella_shipping_method', true));

    // defaults
    $default_extra_services = array();
    $default_packet_count = '1';
    $default_multi_parcel = false;
    $default_weight = '1.00';
    $default_is_cod = $order->get_payment_method() === 'itella_cod';
    $default_cod_amount = $order->get_total();

    $itella_method = $is_shipping_updated ?
        get_post_meta($order_id, 'itella_shipping_method', true) :
        get_post_meta($order_id, '_itella_method', true);

    $packet_count = get_post_meta($order_id, 'packet_count', true);
    if (empty($packet_count)) {
      $packet_count = $default_packet_count;
    }
    $multi_parcel = get_post_meta($order_id, 'itella_multi_parcel', true);
    if (empty($multi_parcel)) {
      $multi_parcel = $default_multi_parcel;
    }
    $weight = get_post_meta($order_id, 'weight_total', true);
    if (empty($weight)) {
      $weight = $default_weight;
    }
    $is_cod = get_post_meta($order_id, 'itella_cod_enabled', true) === 'yes';
    if (!$is_cod) {
      $is_cod = $default_is_cod;
    }
    $cod_amount = get_post_meta($order_id, 'itella_cod_amount', true);
    if (empty($cod_amount)) {
      $cod_amount = $default_cod_amount;
    }
    $extra_services = get_post_meta($order_id, 'itella_extra_services', true);
    if (empty($extra_services)) {
      $extra_services = $default_extra_services;
    }
    $pickup_point_id = get_post_meta($order_id, '_pp_id', true);

    if ($itella_method) {
      $shipping_parameters = array(
          'itella_shipping_method' => $itella_method,
          'packet_count' => $packet_count,
          'multi_parcel' => $multi_parcel ? __('Multi Parcel', 'itella_shipping') : false,
          'weight' => $weight,
          'is_cod' => $is_cod,
          'cod_amount' => $cod_amount,
          'extra_services' => $extra_services,
          'pickup_point_id' => $pickup_point_id
      );
    }

    return $shipping_parameters;
  }

  /**
   * Handle a custom query variable to get orders.
   * @param array $query - Args for WP_Query.
   * @param array $query_vars - Query vars from WC_Order_Query.
   * @return array modified $query
   */
  public function handle_custom_itella_query_var($query, $query_vars)
  {
    if (!empty($query_vars['itella_method'])) {
      $query['meta_query'][] = array(
          'key' => '_itella_method',
          'value' => $query_vars['itella_method']//esc_attr( $query_vars['itella_method'] ),
      );
    }

    if (isset($query_vars['itella_tracking_code'])) {
      $query['meta_query'][] = array(
          'key' => '_itella_tracking_code',
          'value' => $query_vars['itella_tracking_code'],
          'compare' => 'LIKE'
      );
    }

    if (isset($query_vars['itella_customer'])) {
      $query['meta_query'][] = array(
          'relation' => 'OR',
          array(
              'key' => '_billing_first_name',
              'value' => $query_vars['itella_customer'],
              'compare' => 'LIKE'
          ),
          array(
              'key' => '_billing_last_name',
              'value' => $query_vars['itella_customer'],
              'compare' => 'LIKE'
          )
      );
    }

    if (isset($query_vars['itella_manifest'])) {
      $query['meta_query'][] = array(
          'key' => '_itella_manifest_generation_date',
          'compare' => ($query_vars['itella_manifest'] ? 'EXISTS' : 'NOT EXISTS'),
      );
    }

    if (isset($query_vars['itella_manifest_date'])) {
      $filter_by_date = false;
      if ($query_vars['itella_manifest_date'][0] && $query_vars['itella_manifest_date'][1]) {
        $filter_by_date = array(
            'key' => '_itella_manifest_generation_date',
            'value' => $query_vars['itella_manifest_date'],
            'compare' => 'BETWEEN'
        );
      } elseif ($query_vars['itella_manifest_date'][0] && !$query_vars['itella_manifest_date'][1]) {
        $filter_by_date = array(
            'key' => '_itella_manifest_generation_date',
            'value' => $query_vars['itella_manifest_date'][0],
            'compare' => '>='
        );
      } elseif (!$query_vars['itella_manifest_date'][0] && $query_vars['itella_manifest_date'][1]) {
        $filter_by_date = array(
            'key' => '_itella_manifest_generation_date',
            'value' => $query_vars['itella_manifest_date'][1],
            'compare' => '<='
        );
      }

      if ($filter_by_date) {
        $query['meta_query'][] = $filter_by_date;
      }
    }

    return $query;
  }

}