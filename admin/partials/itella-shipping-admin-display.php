<?php

/**
 * Provide a dashboard view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @since      1.4.1
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/admin/partials
 */

class Itella_Shipping_Admin_Display
{
    private $settings_field_prefix;
    private $helper;
    private $wc;

    public function __construct($method_id)
    {
        $this->settings_field_prefix = 'woocommerce_' . $method_id . '_';
        $this->helper = new Itella_Shipping_Method_Helper();
        $this->wc = new Itella_Shipping_Wc();
    }

    public function settings_row_title( $title )
    {
        ob_start();
        ?>
        <th scope="row" class="titledesc">
            <label><?php echo esc_html($title); ?></label>
        </th>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        
        return $html;
    }

    public function settings_cart_size_row( $params )
    {
        $field_title = $params['title'] ?? '';
        $field_id_prefix = $params['id_prefix'] ?? '';
        $field_values = $params['values'] ?? '';
        $field_class = $params['class'] ?? '';
        $desciption = $params['description'] ?? '';
        $units = $this->wc->get_units();

        ob_start();
        ?>
        <tr valign="top">
          <?php echo $this->settings_row_title($field_title); ?>
          <td class="forminp itella-size">
            <fieldset class="field-dimensions">
                <?php
                $first = true;
                foreach ( $this->helper->get_dimmensions_names() as $dimm_key => $dimm_title ) {
                    if ( ! $first ) echo ' × ';
                    echo $this->field_number(array(
                        'name' => $field_id_prefix . '[' . $dimm_key . ']',
                        'id' => $field_id_prefix . '_' . $dimm_key,
                        'value' => $field_values[$dimm_key] ?? '',
                        'class' => $field_class,
                        'placeholder' => $dimm_title,
                        'min' => 0,
                        'step' => 0.001
                    ));
                    $first = false;
                }
                echo ' ' . $units->dimension;
                ?>
            </fieldset>
            <fieldset class="field-weight">
                <?php
                echo $this->field_number(array(
                    'name' => $field_id_prefix . '[weight]',
                    'id' => $field_id_prefix . '_weight',
                    'value' => $field_values['weight'] ?? '',
                    'class' => $field_class,
                    'placeholder' => __('Weight', 'itella-shipping'),
                    'min' => 0,
                    'step' => 0.001
                ));
                echo ' ' . $units->weight;
                ?>
            </fieldset>
            <?php if ( ! empty($desciption) ) : ?>
                <fieldset><p class="description"><?php echo $desciption; ?></p></fieldset>
            <?php endif; ?>
          </td>
        </tr>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        
        return $html;
    }

    private function field_number( $params )
    {
        $class = $params['class'] ?? '';
        $name = $params['name'] ?? 'unknown[]';
        $id = $params['id'] ?? '';
        $value = $params['value'] ?? '';
        $style = $params['style'] ?? '';
        $placeholder = $params['placeholder'] ?? '';
        $min = $params['min'] ?? '';
        $max = $params['max'] ?? '';
        $step = $params['step'] ?? 1;

        ob_start();
        ?>
        <input class="input-number <?php echo $class; ?>" type="number" name="<?php echo $this->settings_field_prefix . $name; ?>" id="<?php echo $this->settings_field_prefix . $id; ?>" style="<?php echo $style; ?>" value="<?php echo $value; ?>" placeholder="<?php echo $placeholder; ?>" min="<?php echo $min; ?>" max="<?php echo $max; ?>" step="<?php echo $step; ?>">
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        
        return $html;
    }
}
