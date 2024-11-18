import { txt } from '../global/text';

export const itellaCustomSelection = () => {
    return {
        lib: null,
        elements: {},
        params: {},
        text: {},

        load_data: function ( params ) {
            this.elements = {
                org_field: this.set_param(params, 'org_field', null),
                container: this.set_param(params, 'container', null),
            };
            this.params = {
                images_url: this.set_param(params, 'images_url', '/'),
                selection_style: this.set_param(params, 'selection_style', 'map'),
                country: this.set_param(params, 'country', ''),
                postcode: this.set_param(params, 'postcode', '')
            };
            this.text = txt.mapping;

            return this;
        },

        set_param: function ( all_params, param_key, fail_value = null ) {
            if ( ! (param_key in all_params) ) {
                return fail_value;
            }
            return all_params[param_key];
        },

        init: function ( locations ) {
            this.lib = new itellaMapping(this.elements.container);
            this.lib.setImagesUrl(this.params.images_url);
            this.lib.setStrings(this.text);
            this.lib.init((this.params.selection_style == 'map') ? true : false);
            this.lib.setCountry(this.params.country);
            this.lib.setLocations(locations, true);
            this.lib.registerCallback(function (manual) {
                localStorage.setItem('itellaPickupPoint', JSON.stringify({
                    id: this.lib.selectedPoint.id
                }));
                this.update_org_field_value(this.lib.selectedPoint.id);
            }.bind(this));

            this.elements.org_field.parentElement.style.display = 'none';
            this.sort_dropdown_by_postcode();
        },

        update_org_field_value: function( location_id ) {
            if ( ! this.elements.org_field ) {
                return;
            }
            const changeEvent = new Event('change', { bubbles: true });

            this.elements.org_field.value = location_id;
            this.elements.org_field.dispatchEvent(changeEvent);
        },

        sort_dropdown_by_postcode: function() {
            this.lib.UI[this.lib.isModal ? 'modal' : 'container'].getElementsByClassName('search-input')[0].value = this.params.postcode;
            this.lib.searchNearest("" + this.params.postcode);
        }
    };
};
