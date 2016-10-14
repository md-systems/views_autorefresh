<?php

namespace Drupal\views_autorefresh\EventSubscriber;

use Drupal\views\Ajax\ViewAjaxResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to handle view ajax responses.
 */
class ViewsAjaxResponseSubscriber implements EventSubscriberInterface {

  /**
   * Processes markup for HtmlResponse responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    /** @var \Drupal\views\Ajax\ViewAjaxResponse $response */
    $response = $event->getResponse();

    if (!$response instanceof ViewAjaxResponse) {
      return;
    }

    $view = $event->getResponse()->getView();
    $autorefresh = views_autorefresh_get_settings($view);
    if (\Drupal::request()->get('autorefresh') && isset($autorefresh)) {
      foreach ($response->getCommands() as $key => &$command) {
        if (!empty($autorefresh['incremental']) &&
          $command['command'] == 'insert' &&
          $command['selector'] == ('.view-dom-id-' . $view->dom_id)
        ) {
          $command['command'] = 'viewsAutoRefreshIncremental';
          $command['view_name'] = $view->name . '-' . $view->current_display;
        }
        if ($command['command'] == 'viewsScrollTop') {
          unset($response->getCommands()[$key]);
        }
      }
      $timestamp = views_autorefresh_get_timestamp($view);
      if ($timestamp) {
        $response->getCommands()[] = array(
          'command' => 'viewsAutoRefreshTriggerUpdate',
          'selector' => '.view-dom-id-' . $view->dom_id,
          'timestamp' => $timestamp,
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond', -200];
    return $events;
  }

}
