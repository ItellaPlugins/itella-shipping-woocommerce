<?php
//TODO: Neaisku ar reikes
class Itella_Wc_blocks_Extend_Store_Endpoint
{
    /**
     * Stores Rest Extending instance.
     *
     * @var ExtendRestApi
     */
    private static $extend;

    /**
     * Plugin Identifier, unique to each plugin.
     *
     * @var string
     */
    const IDENTIFIER = 'itella-shipping';

    /**
     * Bootstraps the class and hooks required data.
     *
     */
    public static function init()
    {
        self::$extend = \Automattic\WooCommerce\StoreApi\StoreApi::container()->get( \Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema::class );
        self::extend_store();
    }

    public static function extend_store()
    {

    }

    public static function extend_checkout_schema()
    {

    }
}
