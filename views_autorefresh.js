/**
 * @file
 * Javascript behaviors, ajax commands and function of views_autorefresh.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.views_autorefresh = Drupal.views_autorefresh || {};

  Drupal.behaviors.views_autorefresh = {
    attach: function(context) {


      // Close timers on page unload.
      window.addEventListener('unload', function(event) {
        $.each(drupalSettings.viewsAutorefresh, function(index, entry) {
          clearTimeout(entry.timer);
        });
      });

      if (drupalSettings && drupalSettings.views && drupalSettings.views.ajaxViews) {
        var ajax_path = drupalSettings.views.ajax_path;
        // If there are multiple views this might've ended up showing up multiple times.
        if (ajax_path.constructor.toString().indexOf("Array") != -1) {
          ajax_path = ajax_path[0];
        }
        $.each(drupalSettings.views.ajaxViews, function(i, settings) {
          var view = settings.view_name + '-' + settings.view_display_id;
          if (!(view in drupalSettings.viewsAutorefresh)) {
            // This view has not got views_autorefresh behavior enabled, so exit
            // early to avoid potential errors.
            return;
          }
          var viewDom = '.view-dom-id-' + settings.view_dom_id;
          if (!$(viewDom).size()) {
            // Backward compatibility: if 'views-view.tpl.php' is old and doesn't
            // contain the 'view-dom-id-#' class, we fall back to the old way of
            // locating the view:
            viewDom = '.view-id-' + settings.view_name + '.view-display-id-' + settings.view_display_id;
          }
          $(viewDom).filter(':not(.views-autorefresh-processed)')
            // Don't attach to nested views. Doing so would attach multiple behaviors
            // to a given element.
            .filter(function() {
              // If there is at least one parent with a view class, this view
              // is nested (e.g., an attachment). Bail.
              return !$(this).parents('.view').size();
            })
            .each(function() {
              // Set a reference that will work in subsequent calls.
              var target = this;
              $('select,input,textarea', target)
                .click(function () {
                  if (drupalSettings.viewsAutorefresh[view] && !drupalSettings.viewsAutorefresh[view].incremental && drupalSettings.viewsAutorefresh[view].timer) {
                    clearTimeout(drupalSettings.viewsAutorefresh[view].timer);
                  }
                })
                .change(function () {
                  if (drupalSettings.viewsAutorefresh[view] && !drupalSettings.viewsAutorefresh[view].incremental && drupalSettings.viewsAutorefresh[view].timer) {
                    clearTimeout(drupalSettings.viewsAutorefresh[view].timer);
                  }
                });
              $(this)
                .addClass('views-autorefresh-processed')
                // Process pager, tablesort, and attachment summary links.
                .find('.auto-refresh a')
                .each(function () {
                  var viewData = { 'js': 1 };
                  var anchor = this;
                  // Construct an object using the settings defaults and then overriding
                  // with data specific to the link.
                  $.extend(
                    viewData,
                    Drupal.Views.parseQueryString($(this).attr('href')),
                    // Extract argument data from the URL.
                    Drupal.Views.parseViewArgs($(this).attr('href'), settings.view_base_path),
                    // Settings must be used last to avoid sending url aliases to the server.
                    settings
                  );
                  drupalSettings.viewsAutorefresh[view].view_args = viewData.view_args;
                  // Setup the click response with Drupal.Ajax.
                  var element_settings = {};
                  element_settings.url = ajax_path;
                  element_settings.event = 'click';
                  element_settings.selector = view;
                  element_settings.submit = viewData;
                  drupalSettings.viewsAutorefresh[view].ajax = new Drupal.Ajax(view, this, element_settings);

                  // Activate refresh timer if not using nodejs.
                  if (!drupalSettings.viewsAutorefresh[view].nodejs) {
                    clearTimeout(drupalSettings.viewsAutorefresh[view].timer);
                    Drupal.views_autorefresh.timer(view, anchor, target);
                  }
                  else { // otherwise prepare to use nodejs
                    drupalSettings.viewsAutorefresh[view].anchor = anchor;
                    drupalSettings.viewsAutorefresh[view].target = target;
                  }
                }); // .each function () {
          }); // $view.filter().each
        });
      }
    }
  };

  Drupal.views_autorefresh.timer = function(view_name, anchor, target) {
    drupalSettings.viewsAutorefresh[view_name].timer = setTimeout(function() {
      clearTimeout(drupalSettings.viewsAutorefresh[view_name].timer);
      Drupal.views_autorefresh.refresh(view_name, anchor, target)
    }, drupalSettings.viewsAutorefresh[view_name].interval);
  };

  Drupal.views_autorefresh.refresh = function(view_name, anchor, target) {
    // Turn off "new" items class.
    $('.views-autorefresh-new', target).removeClass('views-autorefresh-new');

    // Handle ping path.
    var ping_base_path;
    if (drupalSettings.viewsAutorefresh[view_name].ping) {
      ping_base_path = drupalSettings.viewsAutorefresh[view_name].ping.ping_base_path;
    }

    // Handle secondary view for incremental refresh.
    // http://stackoverflow.com/questions/122102/what-is-the-most-efficient-way-to-clone-a-javascript-object
    var viewData = drupalSettings.viewsAutorefresh[view_name].ajax.submit;
    var viewArgs = drupalSettings.viewsAutorefresh[view_name].view_args;
    if (drupalSettings.viewsAutorefresh[view_name].incremental) {
      if (!viewData.original_view_data) viewData.original_view_data = $.extend(true, {}, viewData);
      viewData.view_args = viewArgs + (viewArgs.length ? '/' : '') + drupalSettings.viewsAutorefresh[view_name].timestamp;
      viewData.view_base_path = drupalSettings.viewsAutorefresh[view_name].incremental.view_base_path;
      viewData.view_display_id = drupalSettings.viewsAutorefresh[view_name].incremental.view_display_id;
      viewData.view_name = drupalSettings.viewsAutorefresh[view_name].incremental.view_name;
    }
    viewData.autorefresh = true;
    drupalSettings.viewsAutorefresh[view_name].ajax.submit = viewData;

    // If there's a ping URL, hit it first.
    if (ping_base_path) {
      var pingData = { 'timestamp': drupalSettings.viewsAutorefresh[view_name].timestamp };
      $.extend(pingData, drupalSettings.viewsAutorefresh[view_name].ping.ping_args);
      $.ajax({
        url: drupalSettings.basePath + ping_base_path,
        data: pingData,
        success: function(response) {
          if (response.pong && parseInt(response.pong) > 0) {
            $(target).trigger('autorefresh_ping', parseInt(response.pong));
            $(anchor).trigger('click');
          }
          else if (!drupalSettings.viewsAutorefresh[view_name].nodejs) {
            Drupal.views_autorefresh.timer(view_name, anchor, target);
          }
        },
        error: function(xhr) {},
        dataType: 'json',
      });
    }
    else {
      $(anchor).trigger('click');
    }
  };

  Drupal.AjaxCommands.prototype.viewsAutoRefreshTriggerUpdate = function (ajax, response, status) {
    $(response.selector).trigger('autorefresh_update', response.timestamp);
  };

  // http://stackoverflow.com/questions/1394020/jquery-each-backwards
  jQuery.fn.reverse = [].reverse;

  Drupal.AjaxCommands.prototype.viewsAutoRefreshIncremental = function (ajax, response, status) {
    var $view = $(response.selector);
    if (response.data) {
      // jQuery removes script tags, so let's mask them now and later unmask.
      // http://stackoverflow.com/questions/4430707/trying-to-select-script-tags-from-a-jquery-ajax-get-response/4432347#4432347
      response.data = response.data.replace(/<(\/?)script([^>]*)>/gi, '<$1scripttag$2>');

      var emptySelector = drupalSettings.viewsAutorefresh[response.view_name].incremental.emptySelector || '.view-empty';
      var sourceSelector = drupalSettings.viewsAutorefresh[response.view_name].incremental.sourceSelector || '.view-content';
      var $source = $(response.data).find(sourceSelector).not(sourceSelector + ' ' + sourceSelector).children();
      if ($source.size() > 0 && $(emptySelector, $source).size() <= 0) {
        var targetSelector = drupalSettings.viewsAutorefresh[response.view_name].incremental.targetSelector || '.view-content';
        var $target = $view.find(targetSelector).not(targetSelector + ' ' + targetSelector);

        // If initial view was empty, remove the empty divs then add the target div.
        if ($target.size() == 0) {
          var afterSelector = drupalSettings.viewsAutorefresh[response.view_name].incremental.afterSelector || '.view-header';
          var targetStructure = drupalSettings.viewsAutorefresh[response.view_name].incremental.targetStructure || '<div class="view-content"></div>';
          if ($(emptySelector, $view).size() > 0) {
            // replace empty div with content.
            $(emptySelector, $view).replaceWith(targetStructure);
          }
          else if ($(afterSelector, $view).size() > 0) {
            // insert content after given div.
            $view.find(afterSelector).not(targetSelector + ' ' + afterSelector).after(targetStructure);
          }
          else {
            // insert content as first child of view div.
            $view.prepend(targetStructure);
          }
          // Now that it's inserted, find it for manipulation.
          $target = $view.find(targetSelector).not(targetSelector + ' ' + targetSelector);
        }

        // Remove first, last row classes from items.
        var firstClass = drupalSettings.viewsAutorefresh[response.view_name].incremental.firstClass || 'views-row-first';
        var lastClass = drupalSettings.viewsAutorefresh[response.view_name].incremental.lastClass || 'views-row-last';
        $target.children().removeClass(firstClass);
        $source.removeClass(lastClass);

        // Adjust even-odd classes.
        var oddClass = drupalSettings.viewsAutorefresh[response.view_name].incremental.oddClass || 'views-row-odd';
        var evenClass = drupalSettings.viewsAutorefresh[response.view_name].incremental.evenClass || 'views-row-even';
        var oddness = $target.children(':first').hasClass(oddClass);
        $source.filter('.' + oddClass + ', .' + evenClass).reverse().each(function() {
          $(this).removeClass(oddClass + ' ' + evenClass).addClass(oddness ? evenClass : oddClass);
          oddness = !oddness;
        });

        // Add the new items to the view.
        // Put scripts back first.
        $source.each(function() {
          $target.prepend($(this)[0].outerHTML.replace(/<(\/?)scripttag([^>]*)>/gi, '<$1script$2>'));
        });

        // Adjust row number classes.
        var rowClassPrefix = drupalSettings.viewsAutorefresh[response.view_name].incremental.rowClassPrefix || 'views-row-';
        var rowRegex = new RegExp('views-row-(\\d+)');
        $target.children().each(function(i) {
          $(this).attr('class', $(this).attr('class').replace(rowRegex, rowClassPrefix + (i+1)));
        });

        // Trigger custom event on any plugin that needs to do extra work.
        $view.trigger('autorefresh_incremental', $source.size());
      }

      // Reactivate refresh timer if not using nodejs.
      if (!drupalSettings.viewsAutorefresh[response.view_name].nodejs) {
        Drupal.views_autorefresh.timer(response.view_name, $('.auto-refresh a', $view), $view);
      }

      // Attach behaviors
      Drupal.attachBehaviors($view);
    }
  };

  Drupal.Nodejs = Drupal.Nodejs || { callbacks: {} };

  // Callback for nodejs message.
  Drupal.Nodejs.callbacks.viewsAutoRefresh = {
    callback: function (message) {
      var view_name = message['view_name'];
      Drupal.views_autorefresh.refresh(
        view_name,
        drupalSettings.viewsAutorefresh[view_name].anchor,
        drupalSettings.viewsAutorefresh[view_name].target
      );
    }
  };

})(jQuery, Drupal, drupalSettings);
