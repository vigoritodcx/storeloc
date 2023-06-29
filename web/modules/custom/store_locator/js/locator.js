(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.StoreLocatorHandler = {
    attach: function (context, settings) {

      Drupal.Ajax.prototype.beforeSend = function (xmlhttprequest, options) {

          if (typeof drupalSettings.locator_config_id != 'undefined') {
            options.url = options.url + '&locator_config_id=' + drupalSettings.locator_config_id;
          }

          if (this.$form) {
            options.extraData = options.extraData || {};

            options.extraData.ajax_iframe_upload = '1';

            var v = $.fieldValue(this.element);
            if (v !== null) {
              options.extraData[this.element.name] = v;
            }
          }

          $(this.element).prop('disabled', true);

          if (!this.progress || !this.progress.type) {
            return;
          }

          var progressIndicatorMethod = 'setProgressIndicator' + this.progress.type.slice(0, 1).toUpperCase() + this.progress.type.slice(1).toLowerCase();
          if (progressIndicatorMethod in this && typeof this[progressIndicatorMethod] === 'function') {
            this[progressIndicatorMethod].call(this);
          }

      };
    }
  };
})(jQuery, Drupal, drupalSettings);
