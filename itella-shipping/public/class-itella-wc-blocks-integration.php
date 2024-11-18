<?php
use \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class Itella_Wc_blocks_Integration implements IntegrationInterface
{
    private $version;
    private $plugin;
    private $prefix;
    private $assets;

    public function __construct( $plugin )
    {
        $this->plugin = $plugin;
        $this->version = $this->plugin->version;
        $this->prefix = $this->plugin->name . '-';
        $this->assets = (object) array(
            'css' => $this->plugin->url . 'public/assets/css/',
            'js' => $this->plugin->url . 'public/assets/js/',
            'img' => $this->plugin->url . 'public/assets/images/',
        );
        $this->itella_shipping = new Itella_Shipping_Method();
    }

    /**
     * The name of the integration.
     *
     * @return string
     */
    public function get_name()
    {
        return $this->plugin->name . '-blocks';
    }

    /**
     * When called invokes any initialization/setup for the integration.
     */
    public function initialize()
    {
        $this->register_external_scripts();
        $this->register_editor_scripts();
        $this->register_frontend_scripts();
        $this->register_additional_actions();
    }

    /**
     * Array of script handles to enqueue in the frontend context
     * 
     * @return array
     */
    public function get_script_handles()
    {
        return array(
            $this->prefix . 'pickup-point-selection-front-checkout',
            $this->prefix . 'pickup-point-selection-front-cart',
        );
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles()
    {
        return array(
            $this->prefix . 'pickup-point-selection-edit-checkout',
            $this->prefix . 'pickup-point-selection-edit-cart'
        );
    }

    /**
     * An array of key, value pairs of data made available to the block on the client side.
     *
     * @return array
     */
    public function get_script_data()
    {
        $settings = Itella_Shipping_Method::getSettings();

        return array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'images_url' => $this->assets->img,
            'locations_url' => $this->plugin->url . 'locations/',
            'locations_filter' => array(
                'exclude_outdoors' => $settings['disable_outdoors_pickup_points'] ?? 'no'
            ),
            'methods' => $this->plugin->methods_keys,
            'selection_style' => $settings['checkout_show_style'] ?? 'map',
            'txt' => array(
                'block_options' => __('Block options', 'itella-shipping'),
                'pickup_block_title' => __('Parcel locker', 'itella-shipping'),
                'pickup_select_field_default' => __('Select a parcel locker', 'itella-shipping'),
                'cart_pickup_info' => __('You can choose the parcel locker on the Checkout page', 'itella-shipping'),
                'checkout_pickup_info' => __('Choose one of parcel lockers close to the address you entered', 'itella-shipping'),
                'pickup_error' => __('Please choose a parcel locker', 'itella-shipping'),
                'mapping' => array(
                    'nothing_found' => __('Nothing found', 'itella-shipping'),
                    'modal_header' => __('Parcel lockers', 'itella-shipping'),
                    'selector_header' => __('Parcel locker', 'itella-shipping'),
                    'workhours_header' => __('Workhours', 'itella-shipping'),
                    'contacts_header' => __('Contacts', 'itella-shipping'),
                    'search_placeholder' => __('Enter postcode/address', 'itella-shipping'),
                    'select_pickup_point' => __('Select a parcel locker', 'itella-shipping'),
                    'no_pickup_points' => __('No locker to select', 'itella-shipping'),
                    'select_btn' => __('select', 'itella-shipping'),
                    'back_to_list_btn' => __('reset search', 'itella-shipping'),
                    'select_pickup_point_btn' => __('Select parcel locker', 'itella-shipping'),
                    'no_information' => __('No information', 'itella-shipping'),
                    'error_leaflet' => __('Leaflet is required for Itella-Mapping', 'itella-shipping'),
                    'error_missing_mount_el' => __('No mount supplied to itellaShipping', 'itella-shipping'),
                ),
            ),
        );
    }

    /**
     * Get the file modified time as a cache buster if we're in dev mode.
     *
     * @param string $file Local path to the file.
     * @return string The cache buster value to use for the given file.
     */
    protected function get_file_version( $file )
    {
        if ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && file_exists($file) ) {
            return filemtime($file);
        }

        return $this->version;
    }

    /**
     * Get URL of the scripts folder
     * 
     * @return string
     */
    private function get_scripts_url() {
      return $this->plugin->url . 'public/assets/blocks/';
    }

    /**
     * Get path to the scripts folder
     * 
     * @return string
     */
    private function get_scripts_dir() {
      return $this->plugin->path . 'public/assets/blocks/';
    }

    /**
     * List of frontend scripts
     */
    private function register_frontend_scripts()
    {
        $scripts = array(
            'pickup-point-selection-front-checkout' => array(
                'js' => 'pickup-point-selection/checkout/front.js',
                'asset' => 'pickup-point-selection/checkout/front.asset.php',
                'css' => 'pickup-point-selection/checkout/front.css'
            ),
            'pickup-point-selection-front-cart' => array(
                'js' => 'pickup-point-selection/cart/front.js',
                'asset' => 'pickup-point-selection/cart/front.asset.php',
            ),
        );

        $this->register_scripts($scripts);
    }

    /**
     * List of admin area page edit scripts
     */
    private function register_editor_scripts()
    {
        $scripts = array(
            'pickup-point-selection-edit-checkout' => array(
                'js' => 'pickup-point-selection/checkout/index.js',
                'asset' => 'pickup-point-selection/checkout/index.asset.php',
            ),
            'pickup-point-selection-edit-cart' => array(
                'js' => 'pickup-point-selection/cart/index.js',
                'asset' => 'pickup-point-selection/cart/index.asset.php',
            ),
        );

        $this->register_scripts($scripts);
    }

    /**
     * Register received scripts
     * 
     * @param array $scripts_list - List of scripts
     */
    private function register_scripts( $scripts_list )
    {
        foreach ( $scripts_list as $script_id => $script_files ) {
            if ( isset($script_files['js']) && isset($script_files['asset']) ) {
                $script_url = $this->get_scripts_url() . $script_files['js'];
                $script_asset_path = $this->get_scripts_dir() . $script_files['asset'];

                $script_asset = file_exists($script_asset_path) ? require $script_asset_path : array(
                    'dependencies' => array(),
                    'version' => $this->get_file_version($script_asset_path),
                );

                wp_register_script(
                    $this->prefix . $script_id,
                    $script_url,
                    $script_asset['dependencies'],
                    $script_asset['version'],
                    true
                );
            }

            if ( isset($script_files['translations']) ) {
                wp_set_script_translations(
                    $this->prefix . $script_id,
                    $script_files['translations'],
                    $this->plugin->path . 'languages'
                );
            }

            if ( isset($script_files['css']) ) {
                $style_url = $this->get_scripts_url() . $script_files['css'];
                $style_path = $this->get_scripts_dir() . $script_files['css'];

                wp_enqueue_style(
                    $this->prefix . $script_id,
                    $style_url,
                    [],
                    $this->get_file_version($style_path)
                );
            }
        }
    }

    /**
     * Register external scripts
     */
    private function register_external_scripts()
    {
        $scripts = array(
            'itella-library-mapping' => array(
                'js' => 'itella-mapping.js',
                'css' => 'itella-mapping.css'
            ),
            'itella-library-leaflet' => array(
                'js' => 'leaflet.min.js',
                'external_css' => 'https://unpkg.com/leaflet@1.5.1/dist/leaflet.css'
            ),
            'itella-library-markercluster' => array(
                'external_css' => 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css'
            ),
            'itella-library-markercluster-default' => array(
                'external_css' => 'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css'
            ),
        );

        foreach ( $scripts as $script_id => $script_files ) {
            if ( ! empty($script_files['js']) ) {
                wp_enqueue_script($script_id, $this->assets->js . $script_files['js'], array('jquery'), null, true);
            }
            if ( ! empty($script_files['css']) ) {
                wp_enqueue_style($script_id, $this->assets->css . $script_files['css']);
            }
            if ( ! empty($script_files['external_js']) ) {
                wp_enqueue_script($script_id . '-external', $script_files['external_js'], array(), null, true);
            }
            if ( ! empty($script_files['external_css']) ) {
                wp_enqueue_style($script_id . '-external', $script_files['external_css']);
            }
        }
    }

    /**
     * Actions used by blocks
     */
    private function register_additional_actions()
    {
        // Empty
    }
}
