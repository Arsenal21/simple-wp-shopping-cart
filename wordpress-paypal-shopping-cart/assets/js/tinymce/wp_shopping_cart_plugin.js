(function () {

    tinymce.create('tinymce.plugins.wpCartShortcode', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init: function (ed, url) {
            ed.addButton('wp_cart_shortcode', {
                icon: 'wp-cart-tinymce',
                tooltip: 'WP Cart Shortcode',
                cmd: 'wp_cart_shortcode'
            });

            ed.addCommand('wp_cart_shortcode', function () {
                // bind event on modal close
                jQuery(window).one('tb_unload', function () {
                    jQuery('div#wpCartAjaxContainer').html(wpCartLoadingTpl.html());
                });
                var width = jQuery(window).width(),
                        H = jQuery(window).height(),
                        W = (720 < width) ? 720 : width;
                // W = W - 80;
                tb_show('WP Cart Insert Shortcode', '#TB_inline?width=' + W + '&height=' + H + '&inlineId=wpCartHighlightForm');
                jQuery.post(
                        wp_cart_admin_ajax_url,
                        {
                            action: 'wp_cart_get_tinymce_form',
                            dataType: 'html',
                        },
                        function (response) {
                            if (response) {  // ** If response was successful

                                jQuery('div#wpCartAjaxContainer').html(response).hide().fadeIn('fast');

                            } else {  // ** Else response was unsuccessful
                                alert('WP Cart AJAX Error! Please deactivate the plugin to permanently dismiss this alert.');
                            }
                        }
                );
            });
        },

        /**
         * Creates control instances based in the incomming name. This method is normally not
         * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
         * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
         * method can be used to create those.
         *
         * @param {String} n Name of the control to create.
         * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
         * @return {tinymce.ui.Control} New control instance or null if no control was created.
         */
        createControl: function (n, cm) {
            return null;
        },

        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo: function () {
            return {
                longname: 'WP Simple Paypal Shopping cart',
                author: 'Tips and Tricks HQ',
                authorurl: 'http://www.tipsandtricks-hq.com/development-center',
                infourl: 'http://www.tipsandtricks-hq.com/development-center',
                version: "1.0"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('wp_cart_shortcode', tinymce.plugins.wpCartShortcode);
})();

var wpCartLoadingTpl = jQuery('<p><i style="float: none; vertical-align: bottom;" class="spinner is-active"></i> Loading content, please wait...</p>');

jQuery(function () {
    var container = jQuery('<div id="wpCartHighlightForm"><div id="wpCartAjaxContainer"></div></div>');
    container.find('#wpCartAjaxContainer').html(wpCartLoadingTpl.html());
    container.appendTo('body').hide();
});
