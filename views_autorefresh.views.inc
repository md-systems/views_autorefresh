<?php

/**
 * Implementation of hook_views_data_alter().
 */
function views_autorefresh_views_data_alter(&$data) {
  // Add our autorefresh area to Global.
  $data['views']['autorefresh'] = array(
    'title' => t('Autorefresh'),
    'help' => t('Enable autorefresh for this view. NOTE: This will automatically turn AJAX support ON.'),
    'area' => array(
      'id' => 'views_autorefresh_area',
    ),
  );

  foreach ($data as $table_name => $table_info) {
    foreach ($table_info as $field_name => $field_info) {
      // Add an operator-based date handler to all date arguments.
      if (!empty($field_info['field']['handler']) && $field_info['field']['handler'] == 'views_handler_field_date') {
        $date = $field_info;
        $date['title'] = $field_info['title'] . ' (with operator)';
        $date['argument']['handler'] = 'views_autorefresh_handler_argument_date';
        $date['argument']['field'] = !empty($date['field']['field']) ? $date['field']['field'] :
          (!empty($date['real field']) ? $date['real field'] : $field_name);
        unset($date['filter'], $date['field'], $date['relationship'], $date['sort']);
        $data[$table_name][$field_name . '_operator'] = $date;
      }
    }

    // Add an operator-based argument on the primary field of each base table.
    if (!empty($table_info['table']['base']['field']) && is_string($table_info['table']['base']['field']) && !empty($table_info[$table_info['table']['base']['field']])) {
      $field_name = $table_info['table']['base']['field'];
      $field_info = $table_info[$field_name];
      if (!empty($field_info['argument']['id'])) {
        $base = $field_info;
        $base['title'] = $field_info['title'] . ' (with operator)';
        $base['argument']['id'] = 'views_autorefresh_argument_base';
        $base['argument']['field'] = $field_name;
        unset($base['filter'], $base['field'], $base['relationship'], $base['sort']);
        $data[$table_name][$field_name . '_operator'] = $base;
      }
    }
  }
}
