var TickTackk = window.TickTackk || {};
TickTackk.hCaptchaIntegration = TickTackk.hCaptchaIntegration || {};

!function($, window, document, _undefined)
{
    "use strict";

    TickTackk.hCaptchaIntegration.hCaptcha = XF.Element.newHandler({

        options: {
            sitekey: null,
            invisible: null
        },

        $hCaptchaTarget: null,

        hCaptchaId: null,
        invisibleValidated: false,
        reloading: false,

        init: function()
        {
            if (!this.options.sitekey)
            {
                return;
            }

            var $form = this.$target.closest('form');

            if (this.options.invisible)
            {
                var $hCaptchaTarget = $('<div />'),
                    $formRow = this.$target.closest('.formRow');

                $formRow.hide();
                $formRow.after($hCaptchaTarget);
                this.$hCaptchaTarget = $hCaptchaTarget;

                $form.on('ajax-submit:before', XF.proxy(this, 'beforeSubmit'));
            }
            else
            {
                this.$hCaptchaTarget = this.$target;
            }

            $form.on('ajax-submit:error ajax-submit:always', XF.proxy(this, 'reload'));

            if (window.hcaptcha)
            {
                this.create();
            }
            else
            {
                TickTackk.hCaptchaIntegration.Callbacks.push(XF.proxy(this, 'create'));

                $.ajax({
                    url: 'https://hcaptcha.com/1/api.js?onload=TickTackkhCaptchaIntegrationCallback&render=explicit',
                    dataType: 'script',
                    cache: true,
                    global: false
                });
            }
        },

        create: function()
        {
            if (!window.hcaptcha)
            {
                return;
            }

            var options = {
                sitekey: this.options.sitekey
            };
            if (this.options.invisible)
            {
                options.size = 'invisible';
                options.callback = XF.proxy(this, 'complete');

            }
            this.hCaptchaId = hcaptcha.render(this.$hCaptchaTarget[0], options);
        },

        /**
         * @param {Event} e
         * @param {Object} config
         */
        beforeSubmit: function(e, config)
        {
            if (!this.invisibleValidated)
            {
                e.preventDefault();
                config.preventSubmit = true;

                hcaptcha.execute();
            }
        },

        complete: function()
        {
            this.invisibleValidated = true;
            this.$target.closest('form').submit();
        },

        reload: function()
        {
            if (!window.hcaptcha || this.hCaptchaId === null || this.reloading)
            {
                return;
            }

            this.reloading = true;

            var self = this;
            setTimeout(function()
            {
                hcaptcha.reset(self.hCaptchaId);
                self.reloading = false;
                self.invisibleValidated = false;
            }, 50);
        }
    });

    TickTackk.hCaptchaIntegration.Callbacks = [];

    /**
     *
     * This is the callback for hCatcha
     *
     * @constructor
     */
    window.TickTackkhCaptchaIntegrationCallback = function()
    {
        var cb = TickTackk.hCaptchaIntegration.Callbacks;

        for (var i = 0; i < cb.length; i++)
        {
            cb[i]();
        }
    };

    XF.Element.register('tck-hcaptcha-integration-h-captcha', 'TickTackk.hCaptchaIntegration.hCaptcha');
}
(jQuery, window, document);