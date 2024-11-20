export const itellaParams = {
    id: 'itella-shipping',
    pickup_methods_keys: ['pickup_point']
};

export const getItellaStaticData = () => {
    if ( ! wcSettings || ! wcSettings["itella-shipping-blocks_data"] ) {
        return {};
    }
    return wcSettings["itella-shipping-blocks_data"];
};

export const getCurrentMethod = ( methodId ) => {
    let pluginData = getItellaStaticData();
    if ( 'methods' in pluginData ) {
        for ( let i = 0; i < pluginData.methods.length; i++ ) {
            if ( pluginData.methods[i] == methodId ) {
                return pluginData.methods[i];
            }
        }
    }
    return null;
};

export const isItellaMethod = ( methodId, with_pickups = false ) => {
    let pluginData = getItellaStaticData();
    if ( 'methods' in pluginData ) {
        for ( let method_key in pluginData.methods ) {
            if ( pluginData.methods[method_key] == methodId ) {
                if ( ! with_pickups || (with_pickups && itellaParams.pickup_methods_keys.includes(method_key)) ) {
                    return true;
                }
            }
        }
    }
    return false;
};
