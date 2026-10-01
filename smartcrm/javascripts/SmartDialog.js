/**
 * SmartERP Custom Dialog System
 * Professional replacement for browser alerts
 * 
 * Usage:
 * SmartDialog.alert('Message', 'Title', 'info');
 * SmartDialog.confirm('Are you sure?', function(result) { ... });
 * SmartDialog.show(html, { type: 'custom', title: 'Custom', width: '500px' });
 */

var SmartDialog = (function($) {
  'use strict';

  var defaults = {
    type: 'info',
    title: 'Message',
    width: 'auto',
    maxWidth: '600px',
    autoClose: 0,
    animation: true,
    backdrop: true,
    keyboard: true,
    buttons: []
  };

  var types = {
    info: {
      icon: '📋',
      className: 'smart-dialog-info'
    },
    success: {
      icon: '✓',
      className: 'smart-dialog-success'
    },
    warning: {
      icon: '⚠',
      className: 'smart-dialog-warning'
    },
    error: {
      icon: '✕',
      className: 'smart-dialog-error'
    },
    confirm: {
      icon: '❓',
      className: 'smart-dialog-confirm'
    },
    question: {
      icon: '❓',
      className: 'smart-dialog-question'
    }
  };

  var currentDialog = null;

  /**
   * Create the dialog HTML
   */
  function createDialog(message, options) {
    var typeInfo = types[options.type] || types.info;
    
    var html = '<div class="smart-dialog ' + typeInfo.className + '">';
    html += '<div class="smart-dialog-content">';
    
    if (options.title) {
      html += '<div class="smart-dialog-header">';
      html += '<div class="smart-dialog-icon">' + typeInfo.icon + '</div>';
      html += '<h3 class="smart-dialog-title">' + htmlEscape(options.title) + '</h3>';
      html += '<button type="button" class="smart-dialog-close">&times;</button>';
      html += '</div>';
    }
    
    html += '<div class="smart-dialog-body">';
    html += message;
    html += '</div>';
    
    if (options.buttons.length > 0) {
      html += '<div class="smart-dialog-footer">';
      $.each(options.buttons, function(i, btn) {
        html += '<button type="button" class="smart-dialog-btn smart-dialog-btn-' + (btn.className || 'default') + '" data-action="' + i + '">';
        html += htmlEscape(btn.label || 'OK');
        html += '</button>';
      });
      html += '</div>';
    }
    
    html += '</div>';
    html += '</div>';
    
    return html;
  }

  /**
   * Show the dialog
   */
  function show(message, options) {
    // Close any existing dialog
    if (currentDialog) {
      close();
    }

    options = $.extend({}, defaults, options);

    var $html = createDialog(message, options);
    
    // Create backdrop
    if (options.backdrop) {
      $('<div class="smart-dialog-backdrop"></div>')
        .appendTo('body')
        .on('click', function() {
          if (options.keyboard) {
            close();
          }
        });
    }

    // Create dialog container
    var $container = $('<div class="smart-dialog-container"></div>')
      .html($html)
      .appendTo('body');

    if (options.animation) {
      $container.addClass('show');
    }

    // Cache the dialog
    currentDialog = {
      $container: $container,
      options: options,
      callbacks: {}
    };

    // Set width
    $container.find('.smart-dialog').css({
      'width': options.width,
      'max-width': options.maxWidth
    });

    // Attach event handlers
    $container.find('.smart-dialog-close').on('click', function(e) {
      e.preventDefault();
      close();
    });

    $container.find('.smart-dialog-btn').on('click', function(e) {
      e.preventDefault();
      var action = $(this).data('action');
      var btn = options.buttons[action];
      if (btn && btn.callback) {
        btn.callback(action);
      }
      close();
    });

    // Keyboard support
    if (options.keyboard) {
      $(document).on('keydown.smartdialog', function(e) {
        if (e.keyCode === 27) { // ESC
          close();
        } else if (e.keyCode === 13) { // ENTER
          $container.find('.smart-dialog-btn-primary:visible').click();
        }
      });
    }

    // Auto close
    if (options.autoClose > 0) {
      currentDialog.autoCloseTimer = setTimeout(function() {
        close();
      }, options.autoClose);
    }

    return currentDialog;
  }

  /**
   * Close the dialog
   */
  function close() {
    if (!currentDialog) return;

    $(document).off('keydown.smartdialog');

    if (currentDialog.autoCloseTimer) {
      clearTimeout(currentDialog.autoCloseTimer);
    }

    if (currentDialog.options.animation) {
      currentDialog.$container.removeClass('show');
      setTimeout(function() {
        currentDialog.$container.remove();
        $('.smart-dialog-backdrop').remove();
        currentDialog = null;
      }, 300);
    } else {
      currentDialog.$container.remove();
      $('.smart-dialog-backdrop').remove();
      currentDialog = null;
    }
  }

  /**
   * Simple HTML escape
   */
  function htmlEscape(text) {
    if (!text) return '';
    return $('<div/>').text(text).html();
  }

  // Public API
  return {
    show: function(message, options) {
      return show(message, options);
    },

    alert: function(message, title, type, callback) {
      type = type || 'info';
      var options = {
        type: type,
        title: title || 'Alert',
        buttons: [
          {
            label: 'OK',
            className: 'primary',
            callback: callback || function() {}
          }
        ],
        autoClose: 0
      };
      return show(message, options);
    },

    success: function(message, title, callback) {
      return this.alert(message, title || 'Success', 'success', callback);
    },

    error: function(message, title, callback) {
      return this.alert(message, title || 'Error', 'error', callback);
    },

    warning: function(message, title, callback) {
      return this.alert(message, title || 'Warning', 'warning', callback);
    },

    info: function(message, title, callback) {
      return this.alert(message, title || 'Information', 'info', callback);
    },

    confirm: function(message, title, onConfirm, onCancel) {
      var options = {
        type: 'confirm',
        title: title || 'Confirm',
        buttons: [
          {
            label: 'Cancel',
            className: 'secondary',
            callback: function() {
              if (onCancel) onCancel(false);
            }
          },
          {
            label: 'OK',
            className: 'primary',
            callback: function() {
              if (onConfirm) onConfirm(true);
            }
          }
        ]
      };
      return show(message, options);
    },

    prompt: function(message, defaultValue, title, onSubmit) {
      var inputHtml = '<input type="text" class="smart-dialog-input" placeholder="Enter value..." value="' + htmlEscape(defaultValue || '') + '" />';
      var options = {
        type: 'question',
        title: title || 'Enter Value',
        buttons: [
          {
            label: 'Cancel',
            className: 'secondary',
            callback: function() {
              // Dialog will close
            }
          },
          {
            label: 'OK',
            className: 'primary',
            callback: function() {
              if (onSubmit) {
                var value = $('input.smart-dialog-input').val();
                onSubmit(value);
              }
            }
          }
        ]
      };
      return show(inputHtml + '<p>' + htmlEscape(message) + '</p>', options);
    },

    close: function() {
      close();
    },

    isOpen: function() {
      return currentDialog !== null;
    }
  };

})(jQuery);

// Override window.alert, window.confirm, window.prompt if desired
// Uncomment to use SmartDialog for all native alerts
/*
window.originalAlert = window.alert;
window.alert = function(msg) {
  SmartDialog.alert(msg, 'Alert', 'info');
};

window.originalConfirm = window.confirm;
window.confirm = function(msg) {
  var result = false;
  SmartDialog.confirm(msg, 'Confirm', function() {
    result = true;
  });
  return result;
};
*/
