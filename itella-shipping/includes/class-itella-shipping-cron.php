<?php

/**
 * All functions needed to run Cron jobs
 *
 * @link       https://itella.lt
 * @since      1.6.1
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/includes
 * @author     UAB Mijora <support@mijora.lt>
 * @author     Marijus Kundelis
 */

class Itella_Shipping_Cron {
    private $plugin_basename = '';
    private $periodic_cronjobs = array(
        /*'itella_cronjob_test' => array(
            'func' => 'cronjob_test',
            'freq' => '5min',
        ),*/
        'itella_cronjob_register_shipment' => array(
            'func' => 'cronjob_register_shipment',
            'args' => 1
        ),
    );

    public function __construct( $plugin_basename )
    {
        $this->plugin_basename = $plugin_basename;
    }

    public function init()
    {
        add_filter('cron_schedules', array($this, 'add_frequency'));

        foreach ( $this->periodic_cronjobs as $job_key => $job_data ) {
            add_action($job_key, array($this, $job_data['func']), 10, $job_data['args']);
        }

        register_activation_hook(WP_PLUGIN_DIR . '/' . $this->plugin_basename, array($this, 'periodic_jobs_activation'));
        register_deactivation_hook(WP_PLUGIN_DIR . '/' . $this->plugin_basename, array($this, 'periodic_jobs_deactivation'));
    }

    public function periodic_jobs_activation()
    {
        foreach ( $this->periodic_cronjobs as $job_key => $job_data ) {
            if ( ! isset($job_data['freq']) ) {
                continue;
            }
            $time = (! empty($job_data['time'])) ? strtotime(date('Y-m-d') . ' ' . $job_data['time'] . ' +1 day') : time();

            if ( ! as_next_scheduled_action($job_key) ) {
                $freq = $this->get_interval_time($job_data['freq']);
                as_schedule_recurring_action($time, $freq, $job_key);
            }
        }
    }

    public function periodic_jobs_deactivation()
    {
        foreach ( $this->periodic_cronjobs as $job_key => $job_data ) {
            if ( ! isset($job_data['freq']) ) {
                continue;
            }

            as_unschedule_action($job_key);
        }
    }

    public function add_frequency( $schedules )
    {
        if ( ! isset($schedules['5min']) ) {
            $schedules['5min'] = array(
                'interval' => 300,
                'display' => 'Every 5 minutes',
            );
        }
        if ( ! isset($schedules['daily']) ) {
            $schedules['daily'] = array(
                'interval' => 86400,
                'display' => 'Once daily',
            );
        }
        if ( ! isset($schedules['monthly']) ) {
            $schedules['monthly'] = array(
                'interval' => 2592000,
                'display' => 'Once monthly',
            );
        }
        return $schedules;
    }

    public function get_interval_time( $interval_key )
    {
        $all_intervals = apply_filters('cron_schedules', array());

        if ( isset($all_intervals[$interval_key]) ) {
            return $all_intervals[$interval_key]['interval'];
        }

        return 2592000;
    }

    public function cronjob_test()
    {
        file_put_contents(__DIR__.'/test.log', 'Working'.PHP_EOL, FILE_APPEND);
    }

    public function cronjob_register_shipment( $args )
    {
        if ( ! isset($args['order']) ) {
            $this->write_to_log('Order ID not received. $args:' . PHP_EOL . print_r($args, true));
            return;
        }
        
        $order_id = $args['order'];

        $result = Itella_Shipping_Method::getInstance()->register_shipments(array($order_id));
        if ( ! is_array($result) ||
             ! isset($result[$order_id]) ||
             ! is_array($result[$order_id]) ||
             ! isset($result[$order_id]['status'])
        ) {
            $this->write_to_log('Order #' . $order_id . ': ' . 'An unknown result was obtained. $result:' . PHP_EOL . print_r($result, true));
            return;
        }

        $msg = (isset($result[$order_id]['msg'])) ? $result[$order_id]['msg'] : 'Unknown result';
        if ( $result[$order_id]['status'] != 'success' ) {
            $msg = 'FAILED! ' . $msg;
        }
        $this->write_to_log('Order #' . $order_id . ': ' . $msg);
    }

    private function write_to_log( $message, $file_name = 'cronjob' )
    {
        file_put_contents(plugin_dir_path(dirname(__FILE__)) . 'var/log/' . $file_name . '.log',
          '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
          FILE_APPEND
        );
    }
}
