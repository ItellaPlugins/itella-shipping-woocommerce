<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/admin>
 */
class Itella_Manifest
{
  /**
   * Plugin data
   * 
   * @access  private
   * @var     object $plugin Plugin data
   */
  private $plugin;

  private $wc;

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @var      string $plugin Plugin data.
   */

  public function __construct($plugin)
  {
    $this->plugin = $plugin;
    $this->wc = new Itella_Shipping_Wc_Itella();
  }

  /**
   * Get plugin name
   */
  private function get_plugin_name()
  {
    return $this->plugin->name;
  }

  /**
   * Get plugin version
   */
  private function get_plugin_version()
  {
    return $this->plugin->version;
  }

  /**
   * Register the stylesheets for the Dashboard.
   *
   * @since    1.0.0
   */
  public function enqueue_styles($hook)
  {

    if ( $hook == 'woocommerce_page_itella-manifest') {
      wp_enqueue_style($this->get_plugin_name() . 'css/itella-shipping-manifest.css', plugin_dir_url(__FILE__) . 'css/itella-shipping-manifest.css', array(), $this->get_plugin_version(), 'all');
      wp_enqueue_style($this->get_plugin_name() . 'bootstrap-datetimepicker', plugins_url('/js/datetimepicker/bootstrap-datetimepicker.min.css', __FILE__));
    }

  }

  /**
   * Register the JavaScript for the dashboard.
   *
   * @since    1.0.0
   */
  public function enqueue_scripts($hook)
  {

    if ( $hook == 'woocommerce_page_itella-manifest') {
      wp_enqueue_script($this->get_plugin_name() . 'itella-shipping-manifest.js', plugin_dir_url(__FILE__) . 'js/itella-shipping-manifest.js', array('jquery'), $this->get_plugin_version(), TRUE);
      wp_localize_script($this->get_plugin_name() . 'itella-shipping-manifest.js', 'translations', array(
        'select_orders' => __('Select at least one order to perform this action.', 'itella-shipping'),
        'switch_confirm' => __("Generating a manifest for a large number of orders can take a long time.\nAre you sure you want to continue?", 'itella-shipping'),
        'registering_shipments' => __('Shipments registration is in progress. Please wait…', 'itella-shipping'),
        'left_actions' => __('Still %d requests in queue...', 'itella-shipping'),
        'register_completed' => __('Shipments registration is complete. Reloading page...', 'itella-shipping'),
        'check_fail' => sprintf(__('Unable to check if shipments registration is still in progress. You can see the queue on the "%s" page.', 'itella-shipping'), '<a href="' . Itella_Shipping_Cron::get_queue_page_url('itella_cronjob_register_shipment') . '" target="_blank">' . __('Scheduled Actions', 'woocommerce') . '</a>'),
      ));
      wp_enqueue_script($this->get_plugin_name() . 'moment', plugin_dir_url(__FILE__) . 'js/datetimepicker/moment.min.js', array(), null, true);
      wp_enqueue_script($this->get_plugin_name() . 'bootstrap-datetimepicker', plugin_dir_url(__FILE__) . 'js/datetimepicker/bootstrap-datetimepicker.min.js', array('jquery', 'moment'), null, true);
      wp_localize_script( $this->get_plugin_name() . 'itella-shipping-manifest.js', 'manifest_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
    }

  }

  /**
   * Show Itella shipments as Woocommerce submenu
   */
  public function register_itella_manifest_menu_page()
  {
    add_submenu_page(
        'woocommerce',
        __('Smartposti shipments', 'itella-shipping'),
        __('Smartposti shipments', 'itella-shipping'),
        'manage_woocommerce',
        'itella-manifest',
        'itella_manifest_page',
        10
    );

    function itella_manifest_page()
    {
      Itella_Manifest::load_manifest_page();
    }
  }

  /**
   * Load Itella shipments page
   */
  public static function load_manifest_page()
  {

    // Manifest page defaults
    $tab_strings = array(
        'new_orders' => __('New orders', 'itella-shipping'),
        'all_orders' => __('All orders', 'itella-shipping'),
        'completed_orders' => __('Completed orders', 'itella-shipping')
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
    $pp_values = array( 10, 25, 50, 100 );
    $max_per_page = (isset($_GET['show_pp'])) ? intval($_GET['show_pp']) : 25;

    // prep access to Itella shipping class
    $itella_shipping = new Itella_Shipping_Method();
    $wc = new Itella_Shipping_Wc_Itella();

    $extra_services_names = $itella_shipping->all_additional_services_names();
    ?>
      <div class="wrap">
      <h1><?php _e('Smartposti shipments', 'itella-shipping'); ?></h1>
    <?php

    $paged = 1;
    if (isset($_GET['paged']))
      $paged = filter_input(INPUT_GET, 'paged');

    $action = 'new_orders';
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

    $args = array(
      'paginate' => true,
      'limit' => $max_per_page,
      'paged' => $paged,
      'meta_query' => array(
        'relation' => 'AND',
        array(
          'key' => 'itella_method',
          'value' => array('itella_pp', 'itella_c', 'itella'),
          'compare' => 'IN',
        ),
      ),
      'itella_method' => ['itella_pp', 'itella_c', 'itella'], //Compatible without HPOS
    );

    // Handle query variables depending on selected tab
    switch ($action) {
      case 'new_orders':
        $page_title = $tab_strings[$action];
        $args['meta_query'][] = array(
          'relation' => 'OR',
          array(
            'key' => 'itella_manifest_generation_date',
            'value' => '',
            'compare' => '=',
          ),
          array(
            'key' => 'itella_manifest_generation_date',
            'compare' => 'NOT EXISTS',
          ),
        );
        $args['itella_manifest'] = false; //Compatible without HPOS
        break;
      case 'completed_orders':
        $page_title = $tab_strings[$action];
        $args['meta_query'][] = array(
          'key' => 'itella_manifest_generation_date',
          'value' => '',
          'compare' => '!=',
        );
        $args['meta_key'] = 'itella_manifest_generation_date';
        $args['orderby'] = 'meta_value';
        $args['order'] = 'DESC';
        $args['itella_manifest'] = true; //Compatible without HPOS
        break;
      case 'all_orders':
      default:
        $action = 'all_orders';
        $page_title = $tab_strings['all_orders'];
        break;
    }

    foreach ($filters as $key => $filter) {
      if ($filter) {
        switch ($key) {
          case 'status':
            $args['status'] = $filter;
            break;
          case 'tracking_code':
            $args['meta_query'][] = array(
              'key' => 'itella_tracking_code',
              'value' => $filter,
              'compare' => 'LIKE',
            );
            $args['itella_tracking_code'] = $filter; //Compatible without HPOS
            break;
          case 'customer':
            $args['field_query'][] = array(
              'relation' => 'OR',
              array(
                'field' => 'billing_first_name',
                'value' => $filter,
                'compare' => 'LIKE'
              ),
              array(
                'field' => 'billing_last_name',
                'value' => $filter,
                'compare' => 'LIKE'
              ),
            );
            $args['itella_customer'] = $filter; //Compatible without HPOS
            break;
        }
      }
    }
    // date filter is a special case
    if ($filters['start_date'] || $filters['end_date']) {
      $args = Itella_Manifest::get_custom_itella_meta_query($args, array(
        'itella_manifest_date' => array($filters['start_date'], $filters['end_date']),
      ));
      $args['itella_manifest_date'] = array($filters['start_date'], $filters['end_date']); //Compatible without HPOS
    }

    // Searching by ID takes priority
    $singleOrder = false;
    if ($filters['id']) {
      $singleOrder = $wc->get_order($filters['id']);
      if ($singleOrder) {
        $orders = array($singleOrder); // table printer expects array
        $paged = 1;
      }
    }

    // if there is no search by ID use to custom query
    $results = false;
    if (!$singleOrder) {
      $results = $wc->get_orders($args);
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

    $order_statuses = $wc->get_all_order_statuses();
    ?>
    <div id="itella-popup-messages" class="popup-overlay" style="display:none;">
      <div class="popup">
        <div class="popup-close" style="display:none;">×</div>
        <div class="popup-message"></div>
      </div>
    </div>
    <?php if ($action !== 'completed_orders') : ?>
        <div class="call-courier-container">
            <form id="call-courier-form" action="admin-post.php" method="GET">
                <input type="hidden" name="action" value="itella-call-courier"/>
            </form>
            <button id="itella-call-btn" class="button action">
              <?php _e('Call Smartposti courier', 'itella-shipping') ?>
            </button>
        </div>
    <?php endif; ?>
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
          <form id="itella-show-pp-form" method="post">
            <?php _e('Show', 'itella-shipping') ?>
            <select id="itella-show-pp" name="show_pp">
              <?php foreach ($pp_values as $pp) {
                echo '<option value="' . $pp . '"';
                echo ($max_per_page == $pp) ? 'selected' : '';
                echo '>' . $pp . '</option>';
              } ?>
            </select>
          </form>
          <div class="itella-bulk-block itella-bulk-register">
            <form id="register-print-form" action="admin-post.php" method="GET">
                <input type="hidden" name="action" value="itella_shipments"/>
                <?php wp_nonce_field('itella_shipments', 'itella_shipments_nonce'); ?>
            </form>
            <button id="submit_shipments_register" title="<?php echo __('Register shipments', 'itella-shipping'); ?>"
                    type="button" class="button action has-spinner">
              <span class="spinner-holder"><span class="spinner is-active"></span></span>
              <?php echo __('Register shipments', 'itella-shipping'); ?>
            </button>
          </div>
          <div class="itella-bulk-block itella-bulk-labels">
            <form id="labels-print-form" action="admin-post.php" method="GET">
                <input type="hidden" name="action" value="itella_labels"/>
                <?php wp_nonce_field('itella_labels', 'itella_labels_nonce'); ?>
            </form>
            <button id="submit_manifest_labels" title="<?php echo __('Print labels', 'itella-shipping'); ?>"
                    type="button" class="button action">
              <?php echo __('Print labels', 'itella-shipping'); ?>
            </button>
          </div>
          <?php if ($action !== 'completed_orders') : ?>
            <div class="itella-bulk-block itella-bulk-manifest">
              <form id="manifest-print-form" action="admin-post.php" method="GET" target="_blank">
                  <input type="hidden" name="action" value="itella_manifests"/>
                <?php wp_nonce_field('itella_manifest', 'itella_manifest_nonce'); ?>
              </form>
              <label class="itella-manifest-switch" title="<?php _e('Generate manifests for ...', 'itella-shipping') ?>">
                <input id="itella-manifest-cb" type="checkbox" data-tab="<?php echo $action; ?>">
                <span class="slider"><span class="on"><?php _ex('All', 'for', 'itella-shipping') ?></span><span class="off"><?php _ex('Checked', 'for', 'itella-shipping') ?></span></span>
              </label>
              <button id="submit_manifest_items" title="<?php echo __('Generate manifests', 'itella-shipping'); ?>"
                      type="button" class="button action itella-bulk-button-manifest">
                <?php echo __('Generate manifests', 'itella-shipping'); ?>
              </button>
            </div>
          <?php endif; ?>
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
                                 placeholder="<?php echo __('ID', 'itella-shipping'); ?>" aria-label="Order ID filter">
                      </th>
                      <th class="manage-column">
                          <input type="text" class="d-inline" name="filter_customer" id="filter_customer"
                                 value="<?php echo $filters['customer']; ?>"
                                 placeholder="<?php echo __('Customer', 'itella-shipping'); ?>"
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
                                 placeholder="<?php echo __('Tracking code', 'itella-shipping'); ?>"
                                 aria-label="Order tracking_code filter">
                      </th>
                      <th class="manage-column">
                          <div class='datetimepicker'>
                              <div>
                                  <input name="filter_start_date" type='text' class="" id='datetimepicker1'
                                         data-date-format="YYYY-MM-DD" value="<?php echo $filters['start_date']; ?>"
                                         placeholder="<?php echo __('From', 'itella-shipping'); ?>" autocomplete="off"/>
                              </div>
                              <div>
                                  <input name="filter_end_date" type='text' class="" id='datetimepicker2'
                                         data-date-format="YYYY-MM-DD" value="<?php echo $filters['end_date']; ?>"
                                         placeholder="<?php echo __('To', 'itella-shipping'); ?>" autocomplete="off"/>
                              </div>
                          </div>
                      </th>
                      <th class="manage-column">
                          <div class="itella-action-buttons-container">
                              <button class="button action"
                                      type="submit"><?php echo __('Filter', 'itella-shipping'); ?></button>
                              <button id="clear_filter_btn" class="button action"
                                      type="submit"><?php echo __('Reset', 'itella-shipping'); ?></button>
                          </div>
                      </th>
                  </tr>

                  <tr class="table-header">
                      <td class="manage-column column-cb check-column"></td>
                      <th scope="col" class="manage-column"><?php echo __('ID', 'itella-shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Customer', 'itella-shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Order Status', 'itella-shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Service', 'itella-shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Tracking code', 'itella-shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Manifest date', 'itella-shipping'); ?></th>
                      <th scope="col" class="manage-column"><?php echo __('Actions', 'itella-shipping'); ?></th>
                  </tr>

                  </thead>
                  <tbody>
                  <?php $date_tracker = false; ?>
                  <?php foreach ($orders as $order) : ?>
                    <?php
                    $itella_data = $wc->get_itella_data($order);
                    $manifest_date = $itella_data->manifest->date;
                    $date = date('Y-m-d H:i', strtotime($manifest_date));
                    ?>
                    <?php if ($action == 'completed_orders' && $date_tracker !== $date) : ?>
                          <tr>
                              <td colspan="8">
                                  <div class="itella-grid-row">
                                      <div class="itella-grid-row-element-4 itella-manifest-date-title">
                                        <?php echo $date_tracker = $date; ?>
                                      </div>
                                      <div class="itella-grid-row-element-7">
                                          <form class="manifest-print-form" action="admin-post.php" method="GET"
                                                target="_blank">
                                              <input type="hidden" name="action" value="itella_manifests"/>
                                            <?php wp_nonce_field('itella_manifest', 'itella_manifest_nonce'); ?>
                                          </form>
                                          <button
                                                  title="<?php echo __('Generate manifest', 'itella-shipping'); ?>"
                                                  type="button"
                                                  class="submit_manifest_items button action button-itella">
                                            <?php echo __('Generate manifest', 'itella-shipping'); ?>
                                          </button>
                                          <form id="call-courier-form" action="admin-post.php" method="GET">
                                              <input type="hidden" name="action" value="itella-call-courier"/>
                                          </form>
                                          <button id="itella-call-btn" class="button action button-itella">
                                            <?php _e('Call Itella courier', 'itella-shipping') ?>
                                          </button>
                                      </div>
                                  </div>
                              </td>
                          </tr>
                    <?php endif; ?>
                    <?php if ( ! method_exists($order, 'get_order_number') ) : ?>
                      <?php $this_order_data = $order->get_data(); ?>
                      <tr class="data-row">
                        <th scope="row" class="check-column"></th>
                        <td class="manage-column">#<?php echo $order->get_id(); ?></td>
                        <td class="column-order_number">
                          <div class="data-grid-cell-content">
                            <?php _e('Order not exists.', 'itella-shipping'); ?>
                            <?php if ( ! empty($this_order_data['reason']) ) : ?>
                              <br/>
                              <?php echo $this_order_data['reason']; ?>
                            <?php endif; ?>
                          </div>
                        </td>
                        <td colspan="5"></td>
                      </tr>
                      <?php continue; ?>
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
                                <mark class="order-status status-<?php echo $order->get_status(); ?>">
                                  <span><?php echo $wc->get_order_status_name($order->get_status()); ?></span>
                                </mark>
                              </div>
                          </td>
                          <td class="manage-column">
                              <div class="data-grid-cell-content">
                                <?php
                                $shipping_parameters = Itella_Manifest::get_shipping_parameters($order->get_id());
                                if ($shipping_parameters) {
                                  if ($shipping_parameters['itella_shipping_method'] === 'itella_pp') {
                                    $chosen_pickup_point = $itella_shipping->get_chosen_pickup_point(
                                      Itella_Manifest::order_getCountry($order),
                                      $shipping_parameters['pickup_point_id'],
                                      $shipping_parameters['pickup_point_pupcode']
                                    );
                                    echo '<strong>' . __('Smartposti Parcel locker', 'itella-shipping') . ':</strong>';
                                    if ( isset($chosen_pickup_point->address) ) {
                                      echo '<br><span class="param-title">' . __('City', 'itella-shipping') . ':</span> ';
                                      echo '<em>' . $chosen_pickup_point->address->municipality . '</em>';
                                      echo '<br><span class="param-title">' . __('Public Name', 'itella-shipping') . ':</span> ';
                                      echo '<em>' . $chosen_pickup_point->publicName . '</em>';
                                      echo '<br><span class="param-title">' . __('Address', 'itella-shipping') . ':</span> ';
                                      echo '<em>' . $chosen_pickup_point->address->address . '</em>';
                                      echo '<br><span class="param-title">' . __('Postal Code', 'itella-shipping') . ':</span> ';
                                      echo '<em>' . $chosen_pickup_point->address->postalCode . '</em>';
                                      echo '<br><span class="param-title">' . __('Country', 'itella-shipping') . ':</span> ';
                                      echo '<em>' . $wc->get_country_name($order->get_shipping_country()) . '</em>';
                                    } else {
                                      echo '<br>' . '—';
                                    }
                                    echo '<br><span class="param-title">' . __('Weight', 'itella-shipping') . ':</span> ';
                                    echo '<em>' . $shipping_parameters['total_weight'] . ' kg</em>';
                                  }
                                  if ($shipping_parameters['itella_shipping_method'] === 'itella_c') {
                                    echo '<strong>' . __('Smartposti Courier', 'itella-shipping') . ':</strong>';
                                    echo '<br><span class="param-title">' . __('Weight', 'itella-shipping') . ':</span> ';
                                    echo '<em>' . $shipping_parameters['total_weight'] . ' kg</em>';
                                    echo '<br><span class="param-title">' . __('Packages', 'itella-shipping') . ': </span>';
                                    echo '<em>' . $shipping_parameters['packet_count'] . ' × 📦' . $shipping_parameters['packet_weight'] . ' kg</em>';
                                    /*for ($i=0; $i<$shipping_parameters['packet_count']; $i++) { // when packages will be different weights
                                      echo '<br><em>📦' . $shipping_parameters['packet_weight'] . ' kg</em>';
                                    }*/
                                    if ($shipping_parameters['extra_services'] || $shipping_parameters['multi_parcel']) {
                                      echo '<br><span class="param-title">' . __('Extra services', 'itella-shipping') . ':</span> ';
                                      if ($shipping_parameters['multi_parcel']) {
                                        echo '<br><em> - ' . $shipping_parameters['multi_parcel'] . '</em>';
                                      }
                                      foreach ($shipping_parameters['extra_services'] as $extra_service) {
                                        echo '<br><em> - ' . $itella_shipping->get_additional_service_name($extra_service) . '</em>';
                                      }
                                    }
                                  }
                                  echo '<br><span class="param-title">' . __('COD', 'itella-shipping') . ':</span> ';
                                  if ($shipping_parameters['is_cod']) {
                                    echo '<em>' . __('Yes', 'woocommerce') . '</em>';
                                    echo '<br><span class="param-title">' . __('COD amount', 'itella-shipping') . ':</span> ';
                                    echo '<em>' . $shipping_parameters['cod_amount'] . '</em>';
                                  } else {
                                    echo '<em>' . __('No', 'woocommerce') . '</em>';
                                  }
                                }
                                ?>
                              </div>
                          </td>
                          <td class="column-tracking_number">
                              <div class="data-grid-cell-content">
                                <?php
                                $tracking_code = $itella_data->tracking->code;
                                $tracking_url = $itella_data->tracking->url;
                                $error = $itella_data->tracking->error;
                                ?>
                                <?php if ($tracking_code) : ?>
                                    <a href="<?= $tracking_url ? $tracking_url : '#' ?>" target="_blank">
                                      <?= $tracking_code; ?>
                                    </a>
                                <?php endif; ?>
                                <?php if ($error) : ?>
                                    <span class="error"><?php echo '<b>' . __('Error', 'itella-shipping') . ':</b> ' . $error; ?></span>
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
                                  <?php echo __('Register shipment', 'itella-shipping'); ?>
                                </span>
                                <a href="admin-post.php?action=itella_labels&post=<?php echo $order->get_id(); ?>"
                                   class="button action button-itella">
                                  <?php echo __('Print label', 'itella-shipping'); ?>
                                </a>
                              <?php if ($action !== 'completed_orders') : ?>
                                    <a href="admin-post.php?action=itella_manifests&post=<?php echo $order->get_id(); ?>"
                                       class="button action button-itella" target="_blank">
                                      <?php echo __('Generate manifest', 'itella-shipping'); ?>
                                    </a>
                              <?php endif; ?>
                            <?php else : ?>
                                <button title="<?php echo __('Register shipment', 'itella-shipping'); ?>"
                                        data-id="<?php echo $order->get_id(); ?>"
                                        type="button" class="itella-register-shipment button button-itella action has-spinner">
                                  <span class="spinner-holder"><span class="spinner is-active"></span></span>
                                  <?php echo __('Register shipment', 'itella-shipping'); ?>
                                </button>
                                <span class="button action button-itella button-itella-disabled">
                                  <?php echo __('Print label', 'itella-shipping'); ?>
                                </span>
                              <?php if ($action !== 'completed_orders') : ?>
                                    <span class="button action button-itella button-itella-disabled">
                                  <?php echo __('Generate manifest', 'itella-shipping'); ?>
                                </span>
                              <?php endif; ?>
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
                      <span><?php _e('Important!', 'itella-shipping') ?></span> <?php _e('Check your credentials.', 'itella-shipping') ?>
                  </p>
                  <p><?php _e('Address and contact information can be changed in Smartposti settings.', 'itella-shipping') ?></p>
              </div>
              <form id="itella-call" action="admin-post.php" method="GET">
                <?php wp_nonce_field('itella-call-courier', 'itella-call-courier_nonce'); ?>
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
                  <div class="field-block">
                    <span><?php echo __("Pickup time", 'itella_shipping'); ?>:</span>
                    <input id="modaldatetimepicker" name="itella_call_courier_date" type="text" data-date-format="YYYY-MM-DD" value="" autocomplete="off"/>
                    <select id="itella_call_courier_time_from" name="itella_call_courier_time_from">
                      <?php
                      for ($h = 0; $h < 24; $h++) {
                        for ($m = 0; $m < 60; $m += 30) {
                          $disabled = ($h === 23 && $m === 30) ? 'disabled' : '';
                          $time = sprintf('%02d:%02d', $h, $m);
                          echo "<option value=\"$time\" $disabled>$time</option>";
                        }
                      }
                      ?>
                    </select>
                    -
                    <select id="itella_call_courier_time_to" name="itella_call_courier_time_to">
                      <?php
                      for ($h = 0; $h < 24; $h++) {
                        for ($m = 0; $m < 60; $m += 30) {
                          $time = sprintf('%02d:%02d', $h, $m);
                          echo "<option value=\"$time\">$time</option>";
                        }
                      }
                      ?>
                    </select>
                  </div>
                  <div class="field-block">
                    <?php $pickup_note = (isset($itella_shipping->settings['call_courier_message'])) ? $itella_shipping->settings['call_courier_message'] : ''; ?>
                    <span><?php echo __("Pickup note", 'itella_shipping'); ?>:</span>
                    <input type="text" name="itella_call_courier_info" value="<?php echo $pickup_note; ?>" class="w-full"/>
                  </div>
                  <div class="modal-footer">
                      <button type="submit" id="itella-call-btn"
                              class="button action"><?php _e('Call Smartposti courier', 'itella-shipping') ?></button>
                      <button type="button" id="itella-call-cancel-btn"
                              class="button action"><?php _e('Cancel') ?></button>
                  </div>
              </form>
          </div>
          <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
              const itellaDateSelect = document.getElementById('modaldatetimepicker');
              const itellaFromSelect = document.getElementById('itella_call_courier_time_from');
              const itellaToSelect = document.getElementById('itella_call_courier_time_to');

              function itellaDisablePastTimes() {
                const today = new Date();
                const todayDate = today.toISOString().split('T')[0];
                const selectedDate = itellaDateSelect.value || todayDate;
                const nowHours = today.getHours();
                const nowMinutes = today.getMinutes();
                const delay = 2 * 60; //If want the call time to start not immediately from now, but after a certain time in minutes
                const currentTotalMinutes = nowHours * 60 + nowMinutes + delay;

                //Change time_from field
                Array.from(itellaFromSelect.options).forEach(option => {
                  const [h, m] = option.value.split(':').map(Number);
                  const optionMinutes = h * 60 + m;
                  const isLastSlot = option.value === '23:30';
                  option.disabled = (selectedDate === today.toISOString().split('T')[0] && optionMinutes <= currentTotalMinutes) || isLastSlot;
                });

                //Change time_to field
                Array.from(itellaToSelect.options).forEach(option => {
                  const [h, m] = option.value.split(':').map(Number);
                  const optionMinutes = h * 60 + m;
                  option.disabled = optionMinutes <= currentTotalMinutes;
                });

                //Fix options if incorrect
                [itellaFromSelect, itellaToSelect].forEach(select => {
                  if (select.selectedOptions.length && select.selectedOptions[0].disabled) {
                    const firstValid = Array.from(select.options).find(opt => !opt.disabled);
                    if (firstValid) {
                      select.value = firstValid.value;
                    }
                  }
                });

                itellaUpdateToOptions();
              }

              function itellaUpdateToOptions() {
                const fromValue = itellaFromSelect.value;

                Array.from(itellaToSelect.options).forEach(option => {
                  option.disabled = option.value <= fromValue;
                });

                if (itellaToSelect.selectedOptions.length && itellaToSelect.selectedOptions[0].disabled) {
                  const firstValid = Array.from(itellaToSelect.options).find(opt => !opt.disabled);
                  if (firstValid) {
                    itellaToSelect.value = firstValid.value;
                  }
                }
              }

              jQuery('#modaldatetimepicker').on('change, dp.change', function (e) {
                itellaDisablePastTimes();
              });
              itellaFromSelect.addEventListener('change', itellaUpdateToOptions);

              itellaDisablePastTimes();
            });
          </script>
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

  /**
   * Get order's shipping parameters
   *
   * @param $order_id
   * @return array
   */
  public static function get_shipping_parameters($order_id)
  {
    $wc = new Itella_Shipping_Wc_Itella();

    $shipping_parameters = array();
    $order_data = $wc->get_order_data($order_id);
    $itella_data = $wc->get_itella_data($order_id);
    $is_shipping_updated = !empty($itella_data->shipping_method);

    $itella_method = $is_shipping_updated ? $itella_data->shipping_method : $itella_data->itella_method;
    if ( ! $itella_method ) {
      return $shipping_parameters;
    }

    // defaults
    $default_extra_services = array();
    $default_packet_count = '1';
    $default_multi_parcel = false;
    $default_weight = 1;
    $default_is_cod = self::is_cod_payment($order_data->payment_method);
    $default_cod_amount = $order_data->total;

    $order_weight = 0;
    foreach ( $wc->get_order_items($order_id) as $item ) {
      $order_weight += floatval($item->weight * $item->quantity);
    }

    $packet_count = $itella_data->packet_count;
    if (empty($packet_count)) {
      $packet_count = $default_packet_count;
    }
    $multi_parcel = $itella_data->multi_parcel;
    if (empty($multi_parcel)) {
      $multi_parcel = $default_multi_parcel;
    }

    $weight = $wc->get_order_meta($order_id, 'weight_total');
    $weight = (empty($weight)) ? $order_weight : $weight;
    $weight = $wc->convert_weight($weight, 'kg');
    $weight = (empty($weight)) ? $default_weight : $weight;
    $packet_weight = (float) $weight / (int) $packet_count;
    $packet_weight = round($packet_weight, 3);
    
    $is_cod = $itella_data->cod->enabled === 'yes';
    if (!$is_cod) {
      $is_cod = $default_is_cod;
    }
    $cod_amount = $itella_data->cod->amount;
    if (empty($cod_amount)) {
      $cod_amount = $default_cod_amount;
    }
    $extra_services = $itella_data->extra_services;
    if (empty($extra_services)) {
      $extra_services = $default_extra_services;
    }
    $pickup_point_id = $itella_data->pickup->id;
    $pickup_point_pupcode = (isset($itella_data->pickup->pupcode)) ? $itella_data->pickup->pupcode : '';

    $shipping_parameters = array(
        'itella_shipping_method' => $itella_method,
        'packet_count' => $packet_count,
        'multi_parcel' => $multi_parcel ? __('Multi Parcel', 'itella-shipping') : false,
        'packet_weight' => $packet_weight,
        'total_weight' => $weight,
        'is_cod' => $is_cod,
        'cod_amount' => $cod_amount,
        'extra_services' => $extra_services,
        'pickup_point_id' => $pickup_point_id,
        'pickup_point_pupcode' => $pickup_point_pupcode
    );

    return $shipping_parameters;
  }

  public static function is_cod_payment($payment_method)
  {
    $cod_payments = array('itella_cod', 'cod');

    return in_array($payment_method, $cod_payments);
  }

  /**
   * Get country from order
   *
   * @param $order
   * @return string
   */
  public static function order_getCountry($order)
  {
    $order_country = $order->get_shipping_country();
    if ( empty($order_country) ) $order_country = $order->get_billing_country();
    if ( empty($order_country) ) $order_country = 'LT';

    return $order_country;
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
          'key' => 'itella_method',
          'value' => $query_vars['itella_method']//esc_attr( $query_vars['itella_method'] ),
      );
    }

    if (isset($query_vars['itella_tracking_code'])) {
      $query['meta_query'][] = array(
          'key' => 'itella_tracking_code',
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
      if ( $query_vars['itella_manifest'] ) {
        $query['meta_query'][] = array(
            'key' => 'itella_manifest_generation_date',
            'compare' => 'EXISTS',
        );
        $query['meta_query'][] = array(
            'key' => 'itella_manifest_generation_date',
            'value' => '',
            'compare' => '!=',
        );
      } else {
        $query['meta_query'][] = array(
          'relation' => 'OR',
          array(
            'key' => 'itella_manifest_generation_date',
            'compare' => 'NOT EXISTS',
          ),
          array(
            'key' => 'itella_manifest_generation_date',
            'value' => '',
            'compare' => '=',
          ),
        );
      }
    }

    return Itella_Manifest::get_custom_itella_meta_query($query, $query_vars);
  }

  public static function get_custom_itella_meta_query($query, $query_vars)
  {
    if (isset($query_vars['itella_manifest_date'])) {
      if ($query_vars['itella_manifest_date'][0] && $query_vars['itella_manifest_date'][1]) {
        $filter_by_date = array(

            'key' => 'itella_manifest_generation_date',
            'value' => $query_vars['itella_manifest_date'],
            'compare' => 'BETWEEN'
        );
      } elseif ($query_vars['itella_manifest_date'][0] && !$query_vars['itella_manifest_date'][1]) {
        $filter_by_date = array(
            'key' => 'itella_manifest_generation_date',
            'value' => $query_vars['itella_manifest_date'][0],
            'compare' => '>='
        );
      } elseif (!$query_vars['itella_manifest_date'][0] && $query_vars['itella_manifest_date'][1]) {
        $filter_by_date = array(
            'key' => 'itella_manifest_generation_date',
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
