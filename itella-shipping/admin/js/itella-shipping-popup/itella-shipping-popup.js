window.itellaPopup = (function() {
    'use strict';

    var overlayId = 'itella-popup-messages';
    var debug = false;

    function log(msg) {
        if ( debug ) {
            console.log('[itellaPopup]', msg);
        }
    }

    return {
        setDebug: function(value) {
            debug = !!value;
            log('Debug mode ' + (debug ? 'enabled' : 'disabled'));
        },

        _createSpinnerBlock: function() {
            log('Creating spinner block');
            var spinnerBlock = document.createElement('div');
            spinnerBlock.className = 'popup-spinner';
            spinnerBlock.style.display = 'none';
            var spinner = document.createElement('span');
            spinner.className = 'spinner is-active';
            spinnerBlock.appendChild(spinner);
            return spinnerBlock;
        },

        _ensurePopupElements: function(overlay) {
            log('Ensuring popup elements exist');
            var popup = overlay.querySelector('.popup');
            if ( ! popup ) return;

            var closeButton = popup.querySelector('.popup-close');
            if ( ! closeButton ) {
                closeButton = document.createElement('div');
                closeButton.className = 'popup-close';
                closeButton.style.display = 'none';
                closeButton.textContent = '\u00d7';
                popup.prepend(closeButton);
            }

            if ( ! closeButton.hasAttribute('data-itella-listener') ) {
                closeButton.setAttribute('data-itella-listener', '1');
                closeButton.addEventListener('click', function() {
                    window.itellaPopup.hide();
                });
            }

            if ( ! overlay.hasAttribute('data-itella-listener') ) {
                overlay.setAttribute('data-itella-listener', '1');
                overlay.addEventListener('click', function(e) {
                    if (e.target !== overlay) return;
                    if (overlay.classList.contains('force-display')) return;

                    window.itellaPopup.hide();
                });
            }

            if ( ! popup.querySelector('.popup-message') ) {
                var message = document.createElement('div');
                message.className = 'popup-message';
                popup.appendChild(message);
            }

            if ( ! popup.querySelector('.popup-spinner') ) {
                popup.appendChild(this._createSpinnerBlock());
            }
        },

        _createModal: function() {
            log('Creating modal');
            var overlay = document.createElement('div');
            overlay.id = overlayId;
            overlay.classList.add('itella-popup', 'popup-overlay');
            overlay.style.display = 'none';

            var popup = document.createElement('div');
            popup.className = 'popup';

            var closeButton = document.createElement('div');
            closeButton.className = 'popup-close';
            closeButton.style.display = 'none';
            closeButton.textContent = '\u00d7';

            var spinnerBlock = this._createSpinnerBlock();

            var message = document.createElement('div');
            message.className = 'popup-message';

            popup.appendChild(closeButton);
            popup.appendChild(message);
            popup.appendChild(spinnerBlock);
            overlay.appendChild(popup);

            document.body.appendChild(overlay);
        },

        show: function( msg, type, allow_close, hide_after, show_spinner ) {
            var self = this;

            if ( typeof type === 'undefined' ) type = 'info';
            if ( typeof allow_close === 'undefined' ) allow_close = true;
            if ( typeof hide_after === 'undefined' ) hide_after = 0;
            if ( typeof show_spinner === 'undefined' ) show_spinner = false;

            log('Showing message (type: ' + type + ', allow_close: ' + allow_close + ', hide_after: ' + hide_after + ', show_spinner: ' + show_spinner + ')');

            if ( ! document.getElementById(overlayId) ) {
                this._createModal();
            }

            var overlay = document.getElementById(overlayId);

            this._ensurePopupElements(overlay);

            var messageBlock = overlay.querySelector('.popup-message');
            var closeButton = overlay.querySelector('.popup-close');
            var spinner = overlay.querySelector('.popup-spinner');

            messageBlock.innerHTML = msg;
            messageBlock.className = 'popup-message';
            messageBlock.classList.add('notice-' + type);

            if ( allow_close ) {
                this.fadeIn(closeButton);
                overlay.classList.remove('force-display');
            } else {
                closeButton.style.display = 'none';
                overlay.classList.add('force-display');
            }

            if ( show_spinner ) {
                spinner.style.display = 'block';
                messageBlock.classList.add('have-spinner');
            } else {
                spinner.style.display = 'none';
                messageBlock.classList.remove('have-spinner');
            }

            if (window.getComputedStyle(overlay).display === 'none') {
                this.fadeIn(overlay);
            }

            if ( hide_after ) {
                setTimeout(function() {
                    self.fadeOut(overlay);
                }, hide_after);
            }
        },

        hide: function() {
            var overlay = document.getElementById(overlayId);
            if ( overlay ) {
                this.fadeOut(overlay);
            }
        },

        fadeIn: function( element, duration ) {
            if ( typeof duration === 'undefined' ) duration = 300;

            element.style.opacity = 0;
            element.style.display = 'block';

            var startTime = performance.now();

            function animate(currentTime) {
                var elapsed = currentTime - startTime;
                var progress = Math.min(elapsed / duration, 1);

                element.style.opacity = progress;

                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            }

            requestAnimationFrame(animate);
        },

        fadeOut: function(element, duration) {
            if ( ! element ) return;
            if ( typeof duration === 'undefined' ) duration = 300;
            log('Fading out element (duration: ' + duration + ')');

            element.style.opacity = 1;

            var startTime = performance.now();

            function animate(currentTime) {
                var elapsed = currentTime - startTime;
                var progress = Math.min(elapsed / duration, 1);
                element.style.opacity = 1 - progress;

                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    element.style.display = 'none';
                }
            }

            requestAnimationFrame(animate);
        }
    };
})();
