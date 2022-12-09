<?php

/**
 * The Helper class for Itella_Shipping_Method class.
 *
 * @package    Itella_Shipping
 * @subpackage Itella_Shipping/admin
 */
class Itella_Shipping_Method_Helper
{
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

    /**
     * Build HTML for row title
     * 
     * @access public
     * @param string $title
     * @return string
     */
    public function row_title_html( $title )
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
            <?php if ( ! empty($tip) ) echo wc_help_tip($tip, false); ?>
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
