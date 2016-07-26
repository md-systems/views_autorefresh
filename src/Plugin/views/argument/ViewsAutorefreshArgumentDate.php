<?php

namespace Drupal\views_autorefresh\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Date argument handler.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("views_autorefresh_argument_date")
 */
class ViewsAutorefreshArgumentDate extends ArgumentPluginBase {

  /**
   * The operator used for the query.
   *
   * @var string
   */
  protected $operator;

  /**
   * The format of the query.
   *
   * @var string
   */
  protected $format;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    // @todo  Hardcode in the query?
    $this->format = 'r';
    $this->operator = '>';
  }

  /**
   * {@inheritdoc}
   */
  function summaryName($data) {
    // @todo  Not sure about name_alias.
    $created = $data->{$this->name_alias};
    return empty($created) ? NULL : \Drupal::service('date.formatter')->format(strtotime($created), 'custom', $this->format, NULL);
  }

  /**
   * {@inheritdoc}
   */
  function title() {
    return empty($this->argument) ? NULL : \Drupal::service('date.formatter')->format($this->argument, 'custom', $this->format, NULL);
  }

  /**
   * {@inheritdoc}
   */
  function query($group_by = FALSE) {
    $this->ensureMyTable();
    $this->query->addWhereExpression(0, "{$this->tableAlias}.{$this->realField} {$this->operator} :date", array(':date' => $this->argument));
  }

}
