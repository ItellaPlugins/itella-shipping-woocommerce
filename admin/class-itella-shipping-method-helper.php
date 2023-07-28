<?php

/**
 * The Helper class for Itella_Shipping_Method class.
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/admin
 */
class Itella_Shipping_Method_Helper
{
    private $wc;

    public function __construct()
    {
        $this->wc = new Itella_Shipping_Wc();
    }

    /**
     * Check if string is json code.
     *
     * @access public
     * @param string $string
     * @return boolean
     */
    public function is_json( $string )
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public function get_woo_dimmension_unit() //TODO: Patikrint ar kur naudojama ir kur bus, pakeisti į WC klasės
    {
        return get_option('woocommerce_dimension_unit');
    }

    public function get_dimmensions_names()
    {
        return array(
            'length' => __('Length', 'itella-shipping'),
            'width' => __('Width', 'itella-shipping'),
            'height' => __('Height', 'itella-shipping'),
        );
    }

    public function get_woo_weight_unit() //TODO: Patikrint ar kur naudojama ir kur bus, pakeisti į WC klasės
    {
        return get_option('woocommerce_weight_unit');
    }

    public function predict_cart_dimmension( $products, $max_dimension )
    {
        $cart_dimmensions = array(
            'length' => 0,
            'width' => 0,
            'height' => 0,
        );
        $max_dimension = array(
            'length' => $max_dimension['length'] ?? 999999,
            'width' => $max_dimension['width'] ?? 999999,
            'height' => $max_dimension['height'] ?? 999999,
        );

        foreach ( $products as $prod ) {
            $rotated_prod = $this->rotate_product($prod);

            // Add to length until max
            if ( ($rotated_prod['length'] + $cart_dimmensions['length']) <= $max_dimension['length']
                && $rotated_prod['width'] <= $max_dimension['width']
                && $rotated_prod['height'] <= $max_dimension['height']
            ) {
                $cart_dimmensions['length'] = $this->increase_dimension($cart_dimmensions['length'], $rotated_prod['length']);
                $cart_dimmensions['width'] = $this->renew_dimension($cart_dimmensions['width'], $rotated_prod['width']);
                $cart_dimmensions['height'] = $this->renew_dimension($cart_dimmensions['height'], $rotated_prod['height']);
            }
            // Add to width until max
            else if ( ($rotated_prod['width'] + $cart_dimmensions['width']) <= $max_dimension['width']
                && $rotated_prod['length'] <= $max_dimension['length']
                && $rotated_prod['height'] <= $max_dimension['height']
            ) {
                $cart_dimmensions['length'] = $this->renew_dimension($cart_dimmensions['length'], $rotated_prod['length']);
                $cart_dimmensions['width'] = $this->increase_dimension($cart_dimmensions['width'], $rotated_prod['width']);
                $cart_dimmensions['height'] = $this->renew_dimension($cart_dimmensions['height'], $rotated_prod['height']);
            }
            // Add to height until max
            else if ( ($rotated_prod['height'] + $cart_dimmensions['height']) <= $max_dimension['height']
                && $rotated_prod['length'] <= $max_dimension['length']
                && $rotated_prod['width'] <= $max_dimension['width']
            ) {
                $cart_dimmensions['length'] = $this->renew_dimension($cart_dimmensions['length'], $rotated_prod['length']);
                $cart_dimmensions['width'] = $this->renew_dimension($cart_dimmensions['width'], $rotated_prod['width']);
                $cart_dimmensions['height'] = $this->increase_dimension($cart_dimmensions['height'], $rotated_prod['height']);
            }
            // If all fails
            else {
                return false;
            }
        }

        return $cart_dimmensions;
    }

    private function rotate_product( $product_dimensions )
    {
        if ( ! is_array($product_dimensions) ) {
            return $product_dimensions;
        }

        $edges = array('length', 'width', 'height');

        $new_product_dims = array();
        foreach ( $edges as $edge ) {
            $new_product_dims[$edge] = $product_dimensions[$edge] ?? 0;
        }

        // Find lowest value and add to height
        asort($new_product_dims);
        $prod_order = array_keys($new_product_dims);
        if ( $prod_order[0] != 'height' ) {
            $value = $new_product_dims['height'];
            $new_product_dims['height'] = $new_product_dims[$prod_order[0]];
            $new_product_dims[$prod_order[0]] = $value;
        }

        // Find biggest value and add to length
        asort($new_product_dims);
        $prod_order = array_keys($new_product_dims);
        if ( $prod_order[2] != 'length' ) {
            $value = $new_product_dims['length'];
            $new_product_dims['length'] = $new_product_dims[$prod_order[2]];
            $new_product_dims[$prod_order[2]] = $value;
        }

        // Add rotated dimensions
        foreach ( $edges as $edge ) {
            $product_dimensions[$edge] = $new_product_dims[$edge];
        }

        return $product_dimensions;
    }

    private function increase_dimension( $cart_dimension, $product_dimmension )
    {
        return $cart_dimension + $product_dimmension;
    }

    private function renew_dimension( $cart_dimension, $product_dimmension )
    {
        return ($product_dimmension > $cart_dimension) ? $product_dimmension : $cart_dimension;
    }

    /**
     * Build HTML for methods price type param
     * 
     * @access public
     * @param array $params
     * @return string
     */
    public function methods_select_field_html( $params )
    {
        $label = $params['label'] ?? '';
        $id = $params['id'] ?? '';
        $name = $params['name'] ?? '';
        $class = $params['class'] ?? '';
        $value = $params['value'] ?? '';
        $options = $params['options'] ?? array();
        $description = $params['description'] ?? '';

        ob_start();
        ?>
        <div class="param_row <?php echo esc_attr($class); ?>">
            <?php echo $this->methods_param_label_html($id, $label, $description); ?>
            <div class="param_value">
                <select id="<?php echo esc_attr($id);?>" name="<?php echo esc_attr($name);?>">
                    <?php foreach ( $options as $value_key => $value_title ) : ?>
                        <?php $selected = ($value_key == $value) ? 'selected' : ''; ?>
                        <option value="<?php echo esc_attr($value_key); ?>" <?php echo $selected; ?>><?php echo $value_title; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Build HTML for methods number field
     * 
     * @access public
     * @param array $params
     * @return string
     */
    public function methods_number_field_html( $params )
    {
        $label = $params['label'] ?? '';
        $id = $params['id'] ?? '';
        $name = $params['name'] ?? '';
        $class = $params['class'] ?? '';
        $value = $params['value'] ?? null;
        $default = $params['default'] ?? '';
        $step = $params['step'] ?? '';
        $min = $params['min'] ?? '';
        $max = $params['max'] ?? '';
        $description = $params['description'] ?? '';

        $field_value = ($value !== null) ? $value : $default;

        ob_start();
        ?>
        <div class="param_row <?php echo esc_attr($class); ?>">
            <?php echo $this->methods_param_label_html($id, $label, $description); ?>
            <div class="param_value">
                <input type="number" id="<?php echo esc_attr($id);?>" name="<?php echo esc_attr($name);?>" value="<?php echo esc_attr($field_value);?>" step="<?php echo esc_attr($step);?>" min="<?php echo esc_attr($min);?>" max="<?php echo esc_attr($max);?>">
            </div>
        </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Build HTML for methods text field
     * 
     * @access public
     * @param array $params
     * @return string
     */
    public function methods_text_field_html( $params )
    {
        $label = $params['label'] ?? '';
        $id = $params['id'] ?? '';
        $name = $params['name'] ?? '';
        $class = $params['class'] ?? '';
        $value = $params['value'] ?? null;
        $default = $params['default'] ?? '';
        $description = $params['description'] ?? '';

        $field_value = ($value !== null) ? $value : $default;

        ob_start();
        ?>
        <div class="param_row <?php echo esc_attr($class); ?>">
            <?php echo $this->methods_param_label_html($id, $label, $description); ?>
            <div class="param_value">
                <input type="text" id="<?php echo esc_attr($id);?>" name="<?php echo esc_attr($name);?>" value="<?php echo esc_attr($field_value);?>">
            </div>
        </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Build HTML for methods textarea field
     * 
     * @access public
     * @param array $params
     * @return string
     */
    public function methods_textarea_field_html( $params )
    {
        $label = $params['label'] ?? '';
        $id = $params['id'] ?? '';
        $name = $params['name'] ?? '';
        $class = $params['class'] ?? '';
        $value = $params['value'] ?? null;
        $default = $params['default'] ?? '';
        $rows = $params['rows'] ?? '';
        $cols = $params['cols'] ?? '';
        $description = $params['description'] ?? '';

        $field_value = ($value !== null) ? $value : $default;

        ob_start();
        ?>
        <div class="param_row <?php echo esc_attr($class); ?>">
            <?php echo $this->methods_param_label_html($id, $label, $description); ?>
            <div class="param_value">
                <textarea id="<?php echo esc_attr($id);?>" name="<?php echo esc_attr($name);?>" rows="<?php echo esc_attr($rows);?>" cols="<?php echo esc_attr($cols);?>"><?php echo esc_html($field_value);?></textarea>
            </div>
        </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Build HTML for dynamic rows table
     * 
     * @access public
     * @param array $params
     * @return string
     */
    public function methods_multirows_field_html( $params )
    {
        $field_type = $params['field_type'] ?? 'range'; //Available types: range, select
        $label = $params['label'] ?? '';
        $type = $params['type'] ?? '';
        $id_prefix = $params['id'] ?? '';
        $name_prefix = $params['name'] ?? '';
        $class = $params['class'] ?? '';
        $values = $params['value'] ?? '';
        $options = $params['options'] ?? array();
        $step = $params['step'] ?? '';
        $min_value = $params['min'] ?? '';
        $title_col_1 = $params['title_col_1'] ?? '';
        $title_col_2 = $params['title_col_2'] ?? '';
        $description = $params['description'] ?? '';

        if ( ! is_array($values) ) {
            $values = array();
        }
        $values = $this->fix_multirows_values($values);

        $hide_table_header_on = array('select');
        $step_lenght = strlen(substr(strrchr((string)$step, "."), 1));
        $show_default_row = (empty($values));

        ob_start();
        ?>
        <div class="param_row <?php echo esc_attr($class); ?>">
            <?php echo $this->methods_param_label_html('', $label, $description); ?>
            <div class="param_value">
                <div class="values_table">
                    <table>
                        <?php if ( ! in_array($field_type, $hide_table_header_on) ) : ?>
                            <tr class="table_row-title">
                                <?php
                                switch ( $field_type ) {
                                    case 'range':
                                        echo $this->methods_table_columns_controller($field_type, array(false, $title_col_1, $title_col_2, ''), true);
                                        break;
                                }
                                ?>
                            </tr>
                        <?php endif; ?>
                        <tr class="table_row-default" style="<?php echo ($show_default_row) ? '' : 'display:none;'; ?>">
                            <?php
                            $columns = array();
                            switch ( $field_type ) {
                                case 'range':
                                    $columns[0] = number_format(0, $step_lenght, '.', '') . ' - ';
                                    $columns[1] = $this->methods_table_input_html(array(
                                        'type' => 'number',
                                        'step' => $step,
                                        'min' => 0,
                                        'disabled' => true,
                                    ));
                                    break;
                                case 'select':
                                    $columns[0] = false;
                                    $columns[1] = $this->methods_table_select_html(array(
                                        'first' => '-',
                                        'disabled' => true,
                                        'options' => $options,
                                    ));
                                    break;
                            }
                            $columns[2] = $this->methods_table_input_html(array(
                                'type' => 'number',
                                'step' => 0.01,
                                'min' => 0,
                                'disabled' => true,
                            ));
                            $columns[3] = '';
                            echo $this->methods_table_columns_controller($field_type, $columns);
                            ?>
                        </tr>
                        <?php for ( $i = 0; $i < count($values); $i++ ) : ?>
                            <?php
                            $field_id = $id_prefix . '_' . $i;
                            $field_name = $name_prefix . '[' . $i . ']';

                            if ( $field_type == 'range' ) {
                                $last_value = (isset($values[$i - 1])) ? $values[$i - 1]['value'] : 0;
                                $next_value = (isset($values[$i + 1])) ? $values[$i + 1]['value'] : false;
                                $from_value = ($last_value > 0) ? $last_value + $step : $last_value;
                                $to_value = ($next_value) ? $next_value - $step : '';
                            }
                            ?>
                            <tr class="table_row-field table_row_<?php echo $i; ?>">
                                <?php
                                $columns = array();
                                switch ( $field_type ) {
                                    case 'range':
                                        $columns[0] = number_format((float)$from_value, $step_lenght, '.', '') . ' - ';
                                        $columns[1] = $this->methods_table_input_html(array(
                                            'type' => 'number',
                                            'id' => $field_id . '_value',
                                            'name' => $field_name . '[value]',
                                            'value' => $values[$i]['value'],
                                            'step' => $step,
                                            'min' => $from_value,
                                            'max' => $to_value,
                                        ));
                                        break;
                                    case 'select':
                                        $columns[0] = false;
                                        $columns[1] = $this->methods_table_select_html(array(
                                            'id' => $field_id . '_value',
                                            'name' => $field_name . '[value]',
                                            'selected' => $values[$i]['value'],
                                            'options' => $options,
                                            'first' => false,
                                        ));
                                        break;
                                }
                                $columns[2] = $this->methods_table_input_html(array(
                                    'type' => 'number',
                                    'id' => $field_id . '_price',
                                    'name' => $field_name . '[price]',
                                    'value' => $values[$i]['price'],
                                    'step' => 0.01,
                                    'min' => 0,
                                ));
                                $columns[3] = '<button class="remove_row">X</button>';
                                echo $this->methods_table_columns_controller($field_type, $columns);
                                ?>
                            </tr>
                        <?php endfor; ?>
                    </table>
                    <div class="table_global_actions">
                        <button class="insert_row" data-id="<?php echo esc_attr($id_prefix); ?>" data-name="<?php echo esc_attr($name_prefix); ?>" data-type="<?php echo esc_attr($field_type); ?>" data-step="<?php echo esc_attr($step); ?>"><?php echo __('Add row', 'itella-shipping'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Create empty values in table values when they do not exist
     * 
     * @access private
     * @param array $values
     * @return array
     */
    private function fix_multirows_values( $values )
    {
        $table_values = array();
        foreach ( $values as $value_data ) {
            $v = array();
            $v['value'] = (isset($value_data['value'])) ? $value_data['value'] : '';
            $v['price'] = (isset($value_data['price'])) ? $value_data['price'] : '';
            $table_values[] = $v;
        }

        return $table_values;
    }

    /**
     * A controller that creates a dynamic number of columns
     * 
     * @access private
     * @param string $type
     * @param array $columns_data
     * @param boolean $th
     * @return string
     */
    private function methods_table_columns_controller( $type, $columns_data, $th = false )
    {
        $output = '';
        
        $tag = ($th) ? 'th' : 'td';
        $colspan = 1;
        foreach ( $columns_data as $col_id => $col_data ) {
            if ( $col_data === false ) {
                $colspan++;
                continue;
            }
            $output .= '<' . $tag . ' class="table_col_' . $col_id . '"';
            if ( $colspan > 1 ) {
                $output .= ' colspan="' . $colspan . '"';
                $colspan = 1;
            }
            $output .= '>' . $col_data . '</' . $tag . '>';
        }

        return $output;
    }

    /**
     * Build HTML for methods param label
     * 
     * @access private
     * @param string $for_id
     * @param string $label_text
     * @return string
     */
    private function methods_param_label_html( $for_id, $label_text, $tip = '' )
    {
        ob_start();
        ?>
        <div class="param_title">
            <label for="<?php echo esc_attr($for_id); ?>"><?php echo $label_text; ?></label>
            <?php if ( ! empty($tip) ) echo $this->wc->get_help_tip($tip); ?>
        </div>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Build HTML for table input field
     * 
     * @access private
     * @param array $params
     * @return string
     */
    private function methods_table_input_html( $params )
    {
        $type = $params['type'] ?? 'text';
        $id = $params['id'] ?? '';
        $name = $params['name'] ?? '';
        $class = $params['class'] ?? '';
        $value = $params['value'] ?? '';
        $step = $params['step'] ?? 1;
        $min = $params['min'] ?? '';
        $max = $params['max'] ?? '';
        $disabled = $params['disabled'] ?? false;

        ob_start();
        ?>
        <input
            type="<?php echo esc_attr($type); ?>"
            id="<?php echo esc_attr($id); ?>"
            name="<?php echo esc_attr($name); ?>"
            class="<?php echo esc_attr($class); ?>"
            value="<?php echo esc_attr($value); ?>"
            <?php if ( $type == 'number' ) : ?>
                step="<?php echo esc_attr($step); ?>"
                min="<?php echo esc_attr($min); ?>"
                max="<?php echo esc_attr($max); ?>"
            <?php endif; ?>
            <?php if ( $disabled ) : ?>
                disabled
            <?php endif; ?>
        >
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }

    /**
     * Build HTML for table select field
     * 
     * @access private
     * @param array $params
     * @return string
     */
    private function methods_table_select_html( $params )
    {
        $id = $params['id'] ?? '';
        $name = $params['name'] ?? '';
        $class = $params['class'] ?? '';
        $options = $params['options'] ?? array();
        $selected_value = $params['selected'] ?? '';
        $first_option = $params['first'] ?? __('Select value'. 'itella-shipping'); //First option title or false if dont show
        $disabled = $params['disabled'] ?? false;

        ob_start();
        ?>
        <select
            id="<?php echo esc_attr($id); ?>"
            name="<?php echo esc_attr($name); ?>"
            class="<?php echo esc_attr($class); ?>"
            <?php if ( $disabled ) : ?>
                disabled
            <?php endif; ?>
        >
            <?php if ( $first_option !== false ) : ?>
                <?php $selected = ($selected_value === '') ? 'selected' : ''; ?>
                <option <?php echo $selected; ?>><?php echo esc_html($first_option); ?></option>
            <?php endif; ?>
            <?php foreach ( $options as $value => $title ) : ?>
                <?php $selected = ($selected_value == $value) ? 'selected' : ''; ?>
                <option value="<?php echo esc_attr($value); ?>" <?php echo $selected; ?>><?php echo esc_html($title); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
}
