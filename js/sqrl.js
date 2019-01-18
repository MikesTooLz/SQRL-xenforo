// Steve's adding of old browser support
Date.now = Date.now || function () { return (+new Date()) };

!function ($, window, document, _undefined) {
    "use strict";

    XF.SqrlLogin = XF.Element.newHandler({
        options: {
            queryUrl: null,
            hostname: null,
        },

        init: function () {
            this.latestData = null;

            console.log('Initialising SQRL login', this.options.queryUrl)
            console.log('Debug', 5);
            if (!this.options.queryUrl || !this.options.hostname)
            {
                console.warn('Unable to find query URL or hostname. Failed.');
                return;
            }
            console.log(this.$target);
            console.log(this.$target[0]);

            // Get nut
            $.ajax({
                url:      this.options.queryUrl + '/nut.sqrl', 
                success:  this.gotNut.bind(this),
                dataType: 'text',
            });
        },

        gotNut: function (nutAndCan, textStatus, jqXHR) {
            console.log(nutAndCan);
            var nut, can;
            nutAndCan.split('&').map(function(param) {
                var nameAndValue = param.split('=');
                switch (nameAndValue[0]) {
                    case 'nut':
                        nut = nameAndValue[1];
                        console.log('Nut:', nut);
                        break;
                        // case 'can':
                        //     can = nameAndValue[1];
                        //     console.log('Can:', can);
                        //     break;
                    }
                });
            this.latestData = {nut: nut, nutAndCan: nutAndCan};

            this.renderNut();

            // Disabled to reduce noise
            this.startQrAuthCheck();
        },

        renderNut: function () {
            var link = $('<a />');
            link.attr('href', 'sqrl://' + this.options.hostname + '/cli.sqrl?' + this.latestData.nutAndCan);
            link.click(this.linkClicked.bind(this));
            var png = $('<img />');
            png.attr('src', this.options.queryUrl + '/png.sqrl?nut=' + this.latestData.nut);
            link.append(png);
            this.$target.append(link);
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
                url: this.options.queryUrl + '/pag.sqrl',
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
