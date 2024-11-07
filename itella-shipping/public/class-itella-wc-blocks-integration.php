<?php
use \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class Itella_Wc_blocks_Integration implements IntegrationInterface
{
    private $version = '0.0.1';

    private $plugin;

    public function __construct( $plugin )
    {
        $this->plugin = $plugin;
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
        //
    }

    /**
     * Array of script handles to enqueue in the frontend context
     * 
     * @return array
     */
    public function get_script_handles()
    {
        return array();
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles()
    {
        return array();
    }

    /**
     * An array of key, value pairs of data made available to the block on the client side.
     *
     * @return array
     */
    public function get_script_data()
    {
        return array();
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
}
