<?php

namespace Drupal\views_autorefresh\Plugin\views\argument;

use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Base argument handler.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("views_autorefresh_argument_base")
 */
class ViewsAutorefreshArgumentBase extends ArgumentPluginBase {

  /**
   * The operator used for the query.
   * @var string
   */
  protected $operator;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->operator = '>';
  }

  /**
   * {@inheritdoc}
   */
  function query($group_by = FALSE) {
    $this->ensureMyTable();
    $this->query->addWhereExpression(0, "{$this->tableAlias}.{$this->realField} {$this->operator} :base", array(':base' => $this->argument));
  }

}
