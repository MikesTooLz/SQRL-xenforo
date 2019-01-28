// Steve's adding of old browser support
Date.now = Date.now || function () { return (+new Date()) };
// Rasmus' adding of old browser support
console = console || {log: function(){}, warn: function(){}};

!function ($, window, document, _undefined) {
    "use strict";

    XF.SqrlLogin = XF.Element.newHandler({
        options: {
            hostname: null,
        },

        init: function () {
            this.latestData = null;

            if (!this.options.hostname || !this.options.hostname)
            {
                console.warn('Unable to find query URL or hostname. Failed.');
                return;
            }

            // Get nut
            $.ajax({
                url:      'https://' + this.options.hostname + '/nut.sqrl', 
                success:  this.gotNut.bind(this),
                dataType: 'text',
            });
        },

        gotNut: function (nutAndCan, textStatus, jqXHR) {
            var nut;
            var nameAndValuePairs = nutAndCan.split('&');
            for (var i = 0; i < nameAndValuePairs.length; i++)
            {
                var nameAndValue = nameAndValuePairs[i].split('=');
                if (nameAndValue[0] == 'nut')
                {
                    nut = nameAndValue[1];
                    break;
                }
            }
            this.latestData = {nut: nut, nutAndCan: nutAndCan};

            this.renderNut();

            // Disabled to reduce noise
            this.startQrAuthCheck();
        },

        renderNut: function () {
            var link = 'sqrl://' + this.options.hostname + '/cli.sqrl?' + this.latestData.nutAndCan;
            var png = 'https://' + this.options.hostname + '/png.sqrl?nut=' + this.latestData.nut;

            this.$target.find('a.button')
                .attr('href', link)
                .click(this.linkClicked.bind(this));
            this.$target.find('img').attr('src', png);
        },

        linkClicked: function(e) {
            var encodedSqrlUrl = window.btoa('sqrl://' + this.options.hostname + '/cli.sqrl?' + this.latestData.nutAndCan)
                .replace(/\//, "_")
                .replace(/\+/, "-")
                .replace(/=+$/, "");
            var probeImage = new Image();
            var probeError = function (err) {
                console.log('Probe error', err);
                setTimeout(
                    function () {
                        probeImage.src = 'http://localhost:25519/' + Date.now() + '.gif';
                    },
                    250
                );
            };
            probeImage.onerror = probeError;
            probeImage.onload = function() {
                console.log('Probe load');
                document.location.href = 'http://localhost:25519/' + encodedSqrlUrl;
            };
            probeError();
            return true;
        },

        startQrAuthCheck: function() {
            setInterval(this.qrAuthCheck.bind(this), 3000);
        },

        qrAuthCheck: function() {
            $.ajax({
                url: 'https://' + this.options.hostname + '/pag.sqrl',
                success: this.handleQrAuthCheckResponse.bind(this),
                error: function (jqXHR, textStatus, errorThrown) { console.log('pag.sqrl ->', jqXHR.status); }
            });
        },

        handleQrAuthCheckResponse: function (body, textStatus, jqXHR) {
            console.log('pag.sqrl ->', body);
            document.location.href = body;
        }
    });

    XF.Element.register('sqrl-login', 'XF.SqrlLogin');
}
(jQuery, window, document);
