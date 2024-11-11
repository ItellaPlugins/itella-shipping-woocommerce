import { useEffect, useState } from '@wordpress/element';
import { SelectControl } from '@wordpress/components';

import { txt } from '../global/text';
import { itellaParams } from '../global/params';

export const Block = ({ checkoutExtensionData, extension }) => {
    const { setExtensionData } = checkoutExtensionData;
    const [ selectedRateId, setSelectedRateId ] = useState('');
    const [ pickupOptions, setPickupOptions ] = useState([
        {
            label: txt.pickup_select_field_default,
            value: ''
        }
    ]);
    const [ selectedPickupPoint, setSelectedPickupPoint ] = useState('');

    useEffect(() => {
        setExtensionData(
            itellaParams.id,
            'selected-pickup-id',
            selectedRateId
        );
    }, [
        setExtensionData,
        selectedRateId
    ]);

    return (
        <div className={`pakettikauppa-shipping-pickup-point`}>
            <SelectControl
                id="pakettikauppa_pickup_point"
                label={txt.pickup_block_title}
                help={txt.checkout_pickup_info}
                value={selectedPickupPoint}
                options={pickupOptions}
                onChange={(value) => setSelectedPickupPoint(value)}
                //ref=""
            />
        </div>
    );
};
