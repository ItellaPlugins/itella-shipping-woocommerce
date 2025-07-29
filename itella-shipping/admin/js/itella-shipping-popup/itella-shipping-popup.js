window.itellaPopup = {
    create_modal: function() {
        const overlay = document.createElement('div');
        overlay.id = 'itella-popup-messages';
        overlay.classList.add('itella-popup', 'popup-overlay');
        overlay.style.display = 'none';

        const popup = document.createElement('div');
        popup.className = 'popup';

        const closeButton = document.createElement('div');
        closeButton.className = 'popup-close';
        closeButton.style.display = 'none';
        closeButton.textContent = '×';

        const spinnerBlock = document.createElement('div');
        spinnerBlock.className = 'popup-spinner';
        spinnerBlock.style.display = 'none';
        const spinner = document.createElement('span');
        spinner.className = 'spinner is-active';
        spinnerBlock.appendChild(spinner);

        const message = document.createElement('div');
        message.className = 'popup-message';

        popup.appendChild(closeButton);
        popup.appendChild(message);
        popup.appendChild(spinnerBlock);
        overlay.appendChild(popup);

        document.body.appendChild(overlay);

        closeButton.addEventListener('click', () => {
            this.fadeOut(overlay);
        });

        overlay.addEventListener('click', (e) => {
            if (e.target !== overlay) return;

            const isCloseVisible = getComputedStyle(closeButton).display !== 'none';

            if (isCloseVisible) {
                this.fadeOut(overlay, 300);
            }
        });
    },

    show: function( msg, type = 'info', allow_close = true, hide_after = 0, show_spinner = false ) {
        if ( ! document.getElementById('itella-popup-messages') ) {
            this.create_modal();
        }

        const overlay = document.getElementById('itella-popup-messages');
        const messageBlock = overlay.querySelector('.popup-message');
        const closeButton = overlay.querySelector('.popup-close');
        const spinner = overlay.querySelector('.popup-spinner')

        messageBlock.innerHTML = msg;
        messageBlock.className = 'popup-message';
        messageBlock.classList.add('notice-' + type);

        if ( allow_close ) {
            this.fadeIn(closeButton);
        } else {
            closeButton.style.display = 'none';
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
            setTimeout(() => {
               this.fadeOut(overlay);
            }, hide_after);
        }
    },

    fadeIn: function( element, duration = 300 ) {
        element.style.opacity = 0;
        element.style.display = 'block';

        const startTime = performance.now();

        function animate(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            element.style.opacity = progress;

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        }

        requestAnimationFrame(animate);
    },

    fadeOut: function(element, duration = 300) {
        if (!element) return;

        element.style.opacity = 1;

        const startTime = performance.now();

        function animate(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
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
