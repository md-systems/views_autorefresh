<?php

namespace Drupal\views_autorefresh\Plugin\views\area;

use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\Component\Utility\UrlHelper;
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
    $form['interval'] = array(
      '#type' => 'textfield',
      '#title' => t('Interval to check for new items'),
      '#default_value' => $this->options['interval'],
      '#field_suffix' => 'milliseconds',
      '#required' => TRUE,
      '#dependency' => array(
        'edit-options-nodejs' => array(0),
      ),
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
      '#dependency' => array(
        'edit-options-incremental' => array(1),
      ),
    );
    $form['incremental_advanced']['emptySelector'] = array(
      '#type' => 'textfield',
      '#title' => t('Empty selector'),
      '#default_value' => $this->options['incremental_advanced']['emptySelector'],
      '#description' => t('A jQuery selector expression representing the main view container in case of empty results.'),
      '#dependency' => array(
        'edit-options-incremental' => array(1),
      ),
    );
    $form['incremental_advanced']['afterSelector'] = array(
      '#type' => 'textfield',
      '#title' => t('Header selector'),
      '#default_value' => $this->options['incremental_advanced']['afterSelector'],
      '#description' => t('A jQuery selector expression representing the view header, in case the header is displayed with empty results.'),
      '#dependency' => array(
        'edit-options-incremental' => array(1),
      ),
    );
    $form['incremental_advanced']['targetStructure'] = array(
      '#type' => 'textfield',
      '#title' => t('Target structure'),
      '#default_value' => $this->options['incremental_advanced']['targetStructure'],
      '#description' => t('An HTML fragment describing the view skeleton in case of empty results.'),
      '#dependency' => array(
        'edit-options-incremental' => array(1),
      ),
    );
    $form['incremental_advanced']['firstClass'] = array(
      '#type' => 'textfield',
      '#title' => t('First row class'),
      '#default_value' => $this->options['incremental_advanced']['firstClass'],
      '#description' => t('A class to be added to the first result row.'),
      '#dependency' => array(
        'edit-options-incremental' => array(1),
      ),
    );
    $form['incremental_advanced']['lastClass'] = array(
      '#type' => 'textfield',
      '#title' => t('Last row class'),
      '#default_value' => $this->options['incremental_advanced']['lastClass'],
      '#description' => t('A class to be added to the last result row.'),
      '#dependency' => array(
        'edit-options-incremental' => array(1),
      ),
    );
    $form['incremental_advanced']['oddClass'] = array(
      '#type' => 'textfield',
      '#title' => t('Odd rows class'),
      '#default_value' => $this->options['incremental_advanced']['oddClass'],
      '#description' => t('A class to be added to each odd result row.'),
      '#dependency' => array(
        'edit-options-incremental' => array(1),
      ),
    );
    $form['incremental_advanced']['evenClass'] = array(
      '#type' => 'textfield',
      '#title' => t('Even rows class'),
      '#default_value' => $this->options['incremental_advanced']['evenClass'],
      '#description' => t('A class to be added to each even result row.'),
      '#dependency' => array(
        'edit-options-incremental' => array(1),
      ),
    );
    $form['incremental_advanced']['rowClassPrefix'] = array(
      '#type' => 'textfield',
      '#title' => t('Row class prefix'),
      '#default_value' => $this->options['incremental_advanced']['rowClassPrefix'],
      '#description' => t('The prefix of a class to be added to each result row. The row number will be appended to this prefix.'),
      '#dependency' => array(
        'edit-options-incremental' => array(1),
      ),
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
      '#dependency' => array(
        'edit-options-ping' => array(1),
      ),

    );
    $form['ping_arguments'] = array(
      '#type' => 'textarea',
      '#title' => t('Ping arguments'),
      '#default_value' => $this->options['ping_arguments'],
      '#description' => t('A PHP script that generates arguments that will be sent on the ping URL as query parameters. Do not surround with <code>&lt;?php&gt;</code> tag.'),
      '#dependency' => array(
        'edit-options-ping' => array(1),
      ),
    );
  }

// @todo  Validation.
//  function options_validate(&$form, &$form_state) {
//    if (!is_numeric($form_state['values']['options']['interval'])) {
//      form_set_error('interval', t('Invalid interval.'));
//    }
//    if ($form_state['values']['options']['ping']) {
//      $ping_base_path = DRUPAL_ROOT . '/' . $form_state['values']['options']['ping_base_path'];
//      if (!file_exists($ping_base_path)) {
//        form_set_error('ping_base_path', t('Ping script not found at %path.', array('%path' => $ping_base_path)));
//      }
//      $args = $this->eval_ping_arguments($form_state['values']['options']['ping_arguments']);
//      if (!is_array($args)) {
//        form_set_error('ping_arguments', t('Error in ping arguments script: %error', array('%error' => $args)));
//      }
//    }
//  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
    // @todo Enable AJAX here ? statement below isn't working
    $this->view->display_handler->setOption('use_ajax', TRUE);

    $interval = $this->options['interval'];
    $view = $this->view;
    $view = empty($view) ? views_get_current_view() : $view;

    // Attach the Javascript and the settings.
    $build['#attached']['library'][] = 'views_autorefresh/views_autorefresh';
    $build['#attached']['drupalSettings']['viewsAutorefresh'][$view->id() . '-' . $view->current_display] = [
      'interval' => $interval,
      //'ping' => $ping,
      //'incremental' => $incremental,
      //'nodejs' => $nodejs,
      'timestamp' => $this->views_autorefresh_get_timestamp($view),
    ];

    // Return link to autorefresh.
    $query = UrlHelper::filterQueryParameters($_REQUEST, array_merge(array('q', 'pass'), array_keys($_COOKIE)));
    $build['href'] = Link::createFromRoute('', '<current>', ['query' => $query])
      ->toRenderable();
    $build['href']['#prefix'] = '<div class="auto-refresh">';
    $build['href']['#suffix'] = '</div>';

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
  function views_autorefresh_get_timestamp($view) {
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

}
