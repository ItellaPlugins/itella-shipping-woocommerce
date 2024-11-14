import { useEffect, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';

import { txt } from '../global/text';
import { getActiveShippingRates } from '../global/wc';
import { isItellaMethod } from '../global/params';

export const Block = ({ className }) => {
    const [ showBlock, setShowBlock ] = useState(false);
    const [ activeRates, setActiveRates ] = useState([]);
    const [ selectedRateId, setSelectedRateId ] = useState('');
    const [ selectedRateHavePickups, setSelectedRateHavePickups ] = useState(false);

    const shippingRates = useSelect((select) => {
        const store = select('wc/store/cart');
        return store.getCartData().shippingRates;
    });

    /* Detect if shipping rates was changed */
    useEffect(() => {
        if ( shippingRates.length ) {
            setActiveRates(getActiveShippingRates(shippingRates));
        }
    }, [
        shippingRates
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

    /* Render this block */
    if ( ! selectedRateHavePickups ) {
        return <></>
    }

    return (
        <div className={'wc-block-components-totals-wrapper'}>
            <span className={'wc-block-components-totals-item'}>{txt.cart_pickup_info}</span>
        </div>
    );
};
