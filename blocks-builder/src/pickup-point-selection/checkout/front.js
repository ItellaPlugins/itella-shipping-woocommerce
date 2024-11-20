import { useEffect, useState, useRef } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';

import { getTxt } from '../global/text';
import { itellaParams, isItellaMethod, getItellaStaticData } from '../global/params';
import { getActiveShippingRates } from '../global/wc';
import { compareObjects, useDebounce } from '../global/utils';
import { getLocations, filterLocations, getLocationInfo, getGroupedLocationsList } from '../global/locations';
import { itellaCustomSelection } from './front-custom-selection';

export const Block = ({ checkoutExtensionData, extension }) => {
    const pickupValidationErrorId = 'itella_pickup_point';
    const { setExtensionData } = checkoutExtensionData;
    const [ activeRates, setActiveRates ] = useState([]);
    const [ destination, setDestination ] = useState(
        {
            country: '',
            address: '',
            city: '',
            postcode: '',
        }
    );
    const [ selectedRateId, setSelectedRateId ] = useState('');
    const [ pickupList, setPickupList ] = useState([]);
    const [ pickupOptions, setPickupOptions ] = useState({});
    const [ selectedRateHavePickups, setSelectedRateHavePickups ] = useState(false);
    const [ selectedPickupPoint, setSelectedPickupPoint ] = useState('');
    const pickupSelectRef = useRef(null);
    const customSelectContainerRef = useRef(null);
    const itellaSelection = itellaCustomSelection();

    /* Get data from WC */
    const { setValidationErrors, clearValidationError } = useDispatch(
        'wc/store/validation'
    );
    const validationError = useSelect((select) => {
        const store = select('wc/store/validation');
        return store.getValidationError(pickupValidationErrorId);
    });
    const { shippingRates, shippingAddress } = useSelect((select) => {
        const store = select('wc/store/cart');
        return {
            shippingRates: store.getCartData().shippingRates,
            shippingAddress: store.getCartData().shippingAddress,
        };
    });
    const debouncedShippingAddress = useDebounce(shippingAddress, 1500);

    /* Detect if shipping rates was changed */
    useEffect(() => {
        if ( shippingRates.length ) {
            setActiveRates(getActiveShippingRates(shippingRates));
        }
    }, [
        shippingRates
    ]);

    /* Detect if shipping address was changed */
    useEffect(() => {
        if ( shippingAddress.country ) {
            let temp_dest = {
                country: shippingAddress.country,
                address: shippingAddress?.address_1 || '',
                city: shippingAddress?.city || '',
                postcode: shippingAddress?.postcode || '',
            };
            if ( ! compareObjects(destination, temp_dest) ) {
                setDestination(temp_dest);
            }
        }
    }, [
        debouncedShippingAddress
    ]);

    /* Get selected rate ID */
    useEffect(() => {
        if ( activeRates.length ) {
            for ( let i = 0; i < activeRates.length; i++ ) {
                if ( activeRates[i].selected && selectedRateId != activeRates[i].rate_id ) {
                    setSelectedRateId(activeRates[i].rate_id)
                }
            }
        }
    }, [
        activeRates
    ]);

    /* Check if selected rate is Itella method with pickup points */
    useEffect(() => {
        if ( selectedRateId.trim() == "" ) {
            return;
        }
        if ( isItellaMethod(selectedRateId, true) ) {
            setSelectedRateHavePickups(true);
        } else {
            setSelectedRateHavePickups(false);
        }
    }, [
        selectedRateId
    ]);

    /* Get pickups list */
    useEffect(() => {
        if ( selectedRateHavePickups && destination.country !== '' ) {
            getLocations(destination.country).then(data => {
                setPickupList(filterLocations(data));
            });
        }
    }, [
        selectedRateHavePickups,
        destination
    ]);

    /* Update pickups options */
    useEffect(() => {
        let groupedPickupList = getGroupedLocationsList(pickupList, destination.country);

        let groupedPickupOptions = {};
        groupedPickupOptions["-"] = [{
            label: getTxt('pickup_select_field_default'),
            value: ''
        }];
        for ( let city in groupedPickupList ) {
            if ( ! groupedPickupOptions[city] ) {
                groupedPickupOptions[city] = [];
            }
            for ( let i = 0; i < groupedPickupList[city].length; i++ ) {
                groupedPickupOptions[city].push({
                    label: groupedPickupList[city][i].publicName + ", " + groupedPickupList[city][i].address + ", " + groupedPickupList[city][i].city,
                    value: groupedPickupList[city][i].id
                });
            }
        }
        
        setPickupOptions(groupedPickupOptions);
    }, [
        pickupList
    ]);

    /* Load custom selection */
    useEffect(() => {
        if ( ! Object.values(pickupOptions).length || ! customSelectContainerRef.current ) {
            return;
        }

        customSelectContainerRef.current.innerHTML = '';

        let staticData = getItellaStaticData();

        itellaSelection.load_data({
            org_field: pickupSelectRef.current,
            container: customSelectContainerRef.current,
            selection_style: staticData.selection_style,
            images_url: staticData.images_url,
            country: destination.country,
            postcode: destination.postcode
        }).init(pickupList);
    }, [
        pickupOptions
    ]);

    /* Save selected pickup point and show error message if not selected */
    useEffect(() => {
        if ( pickupValidationErrorId ) {
            clearValidationError(pickupValidationErrorId);
        }

        if ( selectedRateId.trim() == "" || ! isItellaMethod(selectedRateId) ) {
            return;
        }

        setExtensionData(
            itellaParams.id,
            'selected_rate_id',
            selectedRateId
        );

        if ( ! isItellaMethod(selectedRateId, true) ) {
            return;
        }

        setExtensionData(
            itellaParams.id,
            'selected_pickup_id',
            selectedPickupPoint
        );

        if ( selectedPickupPoint === "" ) {
            setValidationErrors({
                [pickupValidationErrorId]: {
                    message: getTxt('pickup_error'),
                    hidden: false
                }
            });
        }
    }, [
        setExtensionData,
        selectedRateId,
        selectedPickupPoint
    ]);


    /* Render this block */
    if ( ! selectedRateHavePickups ) {
        return <></>
    }

    return (
        <div className="itella-shipping-block">
            <SelectControl
                id="itella-pickup-points-list"
                label={getTxt('pickup_block_title')}
                //help={getTxt('checkout_pickup_info')}
                value={selectedPickupPoint}
                onChange={(value) => setSelectedPickupPoint(value)}
                ref={pickupSelectRef}
            >
                {Object.keys(pickupOptions).map((city) => 
                    city === "-" ? (
                        <option key="default" value="">
                            {pickupOptions[city][0].label}
                        </option>
                    ) : (
                    <optgroup key={city} label={city}>
                        {pickupOptions[city].map((location) => (
                            <option key={location.value} value={location.value}>
                                {location.label}
                            </option>
                        ))}
                    </optgroup>
                ))}
            </SelectControl>
            <div id="itella-pickup-points-selection" ref={customSelectContainerRef}></div>
            {(validationError?.hidden || selectedPickupPoint !== "") ? null : (
                <div className="wc-block-components-validation-error">
                    <span>{validationError?.message}</span>
                </div>
            )}
        </div>
    );
};
