<?php

namespace Drupal\views_autorefresh\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Defines an area plugin for the Autorefresh header.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("views_autorefresh_area")
 */
class ViewsAutorefreshArea extends AreaPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    // @todo  Remove Node.js and incremental refresh settings or port them.
    $options = parent::defineOptions();
    $options['interval'] = array('default' => '');
    $options['nodejs'] = array('default' => FALSE, 'bool' => TRUE);
    $options['incremental'] = array('default' => FALSE, 'bool' => TRUE);
    $options['incremental_advanced'] = array(
      'contains' => array(
        'sourceSelector' => array('default' => '.view-content'),
        'emptySelector' => array('default' => '.view-empty'),
        'afterSelector' => array('default' => '.view-header'),
        'targetStructure' => array('default' => '<div class="view-content"></div>'),
        'firstClass' => array('default' => 'views-row-first'),
        'lastClass' => array('default' => 'views-row-last'),
        'oddClass' => array('default' => 'views-row-odd'),
        'evenClass' => array('default' => 'views-row-even'),
        'rowClassPrefix' => array('default' => 'views-row-'),
      )
    );

    $options['ping'] = array('default' => FALSE, 'bool' => TRUE);
    $options['ping_base_path'] = array('default' => '');
    $options['ping_arguments'] = array('default' => '');
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // @todo  Remove Node.js and incremental refresh settings or port them.
    if (\Drupal::moduleHandler()->moduleExists('nodejs')) {
      $form['nodejs'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use Node.js to refresh the view instead of interval pings'),
        '#default_value' => $this->options['nodejs'],
      );
    }
    else {
      $form['nodejs'] = array(
        '#type' => 'value',
        '#value' => FALSE,
      );
    }

    $show_if_incremental_enabled = [
      'visible' => [
        ':input[data-drupal-selector="edit-options-incremental"]' => array('checked' => TRUE),
      ],
    ];
    $show_if_ping_enabled = [
      'visible' => [
        ':input[data-drupal-selector="edit-options-ping"]' => array('checked' => TRUE),
      ],
    ];

    $form['interval'] = array(
      '#type' => 'textfield',
      '#title' => t('Interval to check for new items'),
      '#default_value' => $this->options['interval'],
      '#field_suffix' => 'milliseconds',
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[data-drupal-selector="edit-options-nodejs"]' => array('checked' => FALSE),
        ],
      ],
    );
    $form['incremental'] = array(
      '#type' => 'checkbox',
      '#title' => t('Incrementally insert new items. Unless your view is using an overridden template, the defaults below should be fine.'),
      '#default_value' => $this->options['incremental'],
    );
    $form['incremental_advanced']['sourceSelector'] = array(
      '#type' => 'textfield',
      '#title' => t('Container selector'),
      '#default_value' => $this->options['incremental_advanced']['sourceSelector'],
      '#description' => t('A jQuery selector expression representing the main view container of your display.'),
      '#states' => $show_if_incremental_enabled,
    );
    $form['incremental_advanced']['emptySelector'] = array(
      '#type' => 'textfield',
      '#title' => t('Empty selector'),
      '#default_value' => $this->options['incremental_advanced']['emptySelector'],
      '#description' => t('A jQuery selector expression representing the main view container in case of empty results.'),
      '#states' => $show_if_incremental_enabled,
    );
    $form['incremental_advanced']['afterSelector'] = array(
      '#type' => 'textfield',
      '#title' => t('Header selector'),
      '#default_value' => $this->options['incremental_advanced']['afterSelector'],
      '#description' => t('A jQuery selector expression representing the view header, in case the header is displayed with empty results.'),
      '#states' => $show_if_incremental_enabled,
    );
    $form['incremental_advanced']['targetStructure'] = array(
      '#type' => 'textfield',
      '#title' => t('Target structure'),
      '#default_value' => $this->options['incremental_advanced']['targetStructure'],
      '#description' => t('An HTML fragment describing the view skeleton in case of empty results.'),
      '#states' => $show_if_incremental_enabled,
    );
    $form['incremental_advanced']['firstClass'] = array(
      '#type' => 'textfield',
      '#title' => t('First row class'),
      '#default_value' => $this->options['incremental_advanced']['firstClass'],
      '#description' => t('A class to be added to the first result row.'),
      '#states' => $show_if_incremental_enabled,
    );
    $form['incremental_advanced']['lastClass'] = array(
      '#type' => 'textfield',
      '#title' => t('Last row class'),
      '#default_value' => $this->options['incremental_advanced']['lastClass'],
      '#description' => t('A class to be added to the last result row.'),
      '#states' => $show_if_incremental_enabled,
    );
    $form['incremental_advanced']['oddClass'] = array(
      '#type' => 'textfield',
      '#title' => t('Odd rows class'),
      '#default_value' => $this->options['incremental_advanced']['oddClass'],
      '#description' => t('A class to be added to each odd result row.'),
      '#states' => $show_if_incremental_enabled,
    );
    $form['incremental_advanced']['evenClass'] = array(
      '#type' => 'textfield',
      '#title' => t('Even rows class'),
      '#default_value' => $this->options['incremental_advanced']['evenClass'],
      '#description' => t('A class to be added to each even result row.'),
      '#states' => $show_if_incremental_enabled,
    );
    $form['incremental_advanced']['rowClassPrefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Row class prefix'),
      '#default_value' => $this->options['incremental_advanced']['rowClassPrefix'],
      '#description' => t('The prefix of a class to be added to each result row. The row number will be appended to this prefix.'),
      '#states' => $show_if_incremental_enabled,
    );
    $form['ping'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use a ping url'),
      '#default_value' => $this->options['ping'],
      '#description' => t('Use a custom script for faster check of new items. See <code>ping.php.example</code> in <code>views_autorefresh</code> folder for reference.'),
    );
    $form['ping_base_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Path to the ping script'),
      '#default_value' => $this->options['ping_base_path'],
      '#description' => t('This path is relative to the Drupal root.'),
      '#states' => $show_if_ping_enabled,

    );
    $form['ping_arguments'] = array(
      '#type' => 'textarea',
      '#title' => t('Ping arguments'),
      '#default_value' => $this->options['ping_arguments'],
      '#description' => t('A PHP script that generates arguments that will be sent on the ping URL as query parameters. Do not surround with <code>&lt;?php&gt;</code> tag.'),
      '#states' => $show_if_ping_enabled,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    if (!is_numeric($form_state->getValue('interval'))) {
      $form_state->setError($form['interval'], $this->t('Invalid interval.'));
    }
    if ($form_state['values']['options']['ping']) {
      $ping_base_path = DRUPAL_ROOT . '/' . $form_state->getValue('ping_base_path');
      if (!file_exists($ping_base_path)) {
        $form_state->setError(
          $form['ping_base_path'],
          t('Ping script not found at %path.', array('%path' => $ping_base_path))
        );
      }
      $args = $this->evalPingArguments($form_state->getValue('ping_arguments'));
      if (!is_array($args)) {
        $form_state->setError(
          $form['ping_arguments'],
          t('Error in ping arguments script: %error', array('%error' => $args))
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    // @todo Enable AJAX here ? statement below isn't working
    $this->view->display_handler->setOption('use_ajax', TRUE);

    $view = $this->view;
    $view = empty($view) ? views_get_current_view() : $view;

    // Create container with attached Javascript and the settings.
    $build['content'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['auto-refresh']],
      '#attached' => [
        'library' => ['views_autorefresh/views_autorefresh'],
        'drupalSettings' => [
          'viewsAutorefresh' => [
            $view->id() . '-' . $view->current_display => [
              'interval' => $this->options['interval'],
              //'ping' => $variables['ping'],
              //'incremental' => $variables['incremental'],
              //'nodejs' => $variables['nodejs'],
              'timestamp' => $this->getTimestamp($view),
            ]
          ]
        ],
      ],
      'link' => Link::createFromRoute('', '<current>')->toRenderable(),
    ];

    // Allow modules to alter the build.
    \Drupal::moduleHandler()->alter(
      'views_autorefresh_render_build',
      $build,
      $view,
      $empty
    );

    return $build;
  }

  /**
   * Helper function to return view's "timestamp" - either real timestamp or max primary key in view rows.
   */
  protected function getTimestamp($view) {
    $autorefresh = $view->header['autorefresh']->options;
    if (empty($autorefresh)) {
      return FALSE;
    }
    if (empty($autorefresh['incremental'])) {
      return time();
    }
    // @todo  Incremental refresh.
//    foreach ($view->argument as $argument) {
//      //$handler = views_get_handler($argument->table, $argument->field, 'argument');
//      $handler = Views::handlerManager($argument->field)->getHandler($argument->table, 'argument');
//
//      if ($handler->definition['handler'] == 'views_autorefresh_handler_argument_date') {
//        return time();
//      }
//      else if ($handler->definition['handler'] == 'views_autorefresh_handler_argument_base') {
//        // Find the max nid/uid/... of the result set.
//        $max_id = array_reduce($view->result, function($max_id, $row) use ($view) {
//          return max($max_id, $row->{$view->base_field});
//        }, ~PHP_INT_MAX);
//        return $max_id === ~PHP_INT_MAX ? FALSE : $max_id;
//      }
//    }

    return FALSE;
  }

  /**
   * @todo
   */
  protected function evalPingArguments($script) {
    $args = array();
    if (empty($script)) return $args;

    // Avoid Drupal's error handler: http://www.php.net/manual/en/function.restore-error-handler.php#93261
    set_error_handler(create_function('$errno,$errstr', 'return false;'));
    $return = eval($script);
    if ($return === FALSE) {
      $error = error_get_last();
      $args = $error['message'];
    }
    else if (is_array($return)) {
      $args = $return;
    }
    else {
      $args = t('expecting an array of arguments, got a !type instead.', array('!type' => gettype($return)));
    }
    restore_error_handler();
    return $args;
  }

}
