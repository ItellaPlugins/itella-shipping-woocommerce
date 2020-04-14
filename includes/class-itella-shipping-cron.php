<?php


/** This class defines all code necessary to schedule and un-schedule cron jobs.
 *
 * @since      1.0.0
* @package    Itella Shipping
* @subpackage Itella_Shipping/includes
* @author     Your Name <email@example.com>
 */
class Itella_Shipping_Cron {

  const ITELLA_SHIPPING_EVENT_DAILY = 'itella_shipping_update_locations_daily';

  /**
   * Check if already scheduled, and schedule if not.
   */
  public static function schedule() {
    if ( ! self::next_scheduled_daily() ) {
      self::daily_schedule();
    }
  }

  /**
   * Unschedule.
   */
  public static function unschedule() {
    wp_clear_scheduled_hook( self::ITELLA_SHIPPING_EVENT_DAILY );
  }

  /**
   * @return false|int Returns false if not scheduled, or timestamp of next run.
   */
  private static function next_scheduled_daily() {
    return wp_next_scheduled( self::ITELLA_SHIPPING_EVENT_DAILY );
  }

  /**
   * Create new schedule.
   */
  private static function daily_schedule() {
    wp_schedule_event( time(), 'daily', self::ITELLA_SHIPPING_EVENT_DAILY );
  }
}