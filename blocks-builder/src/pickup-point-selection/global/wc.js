export const getActiveShippingRates = ( shippingRates ) => {
    if ( ! shippingRates.length ) {
        return [];
    }

    let activeRates = [];
    for ( let i = 0; i < shippingRates.length; i++ ) {
        if ( ! shippingRates[i].shipping_rates ) {
            continue;
        }
        for ( let j = 0; j < shippingRates[i].shipping_rates.length; j++ ) {
            if ( ! shippingRates[i].shipping_rates[j].rate_id ) {
                continue;
            }
            activeRates.push(shippingRates[i].shipping_rates[j]);
        }
    }
    
    return activeRates;
};
