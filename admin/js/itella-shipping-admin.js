'use strict';

(() => {
    window.addEventListener('load', (event) => {

        // toggle extra fee fields
        const pickupPoint = document.querySelector('.pickup-point-method');
        const courier = document.querySelector('.courier-method');
        const pickupPointFields = document.querySelectorAll('.pickup-point');
        const courierFields = document.querySelectorAll('.courier');
        const allfields = document.querySelectorAll('.itella-settings input');
        // const feeTaxField = document.querySelector('.method-fee-tax').parentElement.parentElement.parentElement.parentElement;

        if (!pickupPoint.checked) {
            pickupPointFields.forEach(field => {
                field.parentElement.parentElement.parentElement.classList.toggle('d-none');
            })
        }
        if (!courier.checked) {
            courierFields.forEach(field => {
                field.parentElement.parentElement.parentElement.classList.toggle('d-none');
            })
        }

        pickupPoint.addEventListener('change', function () {
            pickupPointFields.forEach(field => {
                field.parentElement.parentElement.parentElement.classList.toggle('d-none');
            });
			// if (feeTaxField.classList.contains('d-none')) {
			// 	feeTaxField.classList.toggle('d-none');
			// }
        });

        courier.addEventListener('change', function () {
            courierFields.forEach(field => {
                field.parentElement.parentElement.parentElement.classList.toggle('d-none');
            });
			// if (feeTaxField.classList.contains('d-none')) {
			// 	feeTaxField.classList.toggle('d-none');
			// }
        });

        allfields.forEach(field => {
            field.addEventListener('invalid', function () {
                this.parentElement.parentElement.parentElement.classList.add("invalid-value");
                var first_invalid = document.querySelector('.itella-settings .invalid-value input');
                first_invalid.scrollIntoView({
                    behavior: 'auto',
                    block: 'center',
                    inline: 'center'
                });
            });
            field.addEventListener('change', function () {
                this.parentElement.parentElement.parentElement.classList.remove("invalid-value");
            });
        });
    });
})();
