import { getItellaStaticData } from './params';

export const getLocations = async ( country ) => {
    try {
        const pluginData = getItellaStaticData();

        const headResponse = await fetch(`${pluginData.locations_url}locations${country}.json`, { method: 'HEAD' });
        if ( ! headResponse.ok ) {
            throw new Error(`File not found: ${headResponse.status}`);
        }

        const response = await fetch(`${pluginData.locations_url}locations${country}.json`);
        if ( ! response.ok ) {
            throw new Error(`Error: ${response.status}`);
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Failed to get locations.', error);
    }

    return null;
};

export const filterLocations = ( locationsJson ) => {
    let pluginData = getItellaStaticData();
    let locations = Array.isArray(locationsJson) ? JSON.parse(JSON.stringify(locationsJson)) : [];

    if ( ! pluginData.hasOwnProperty('locations_filter') ) {
        return locations;
    }

    let i = locations.length;
    while ( i-- ) {
        if ( ! Object.hasOwn(locations[i], 'capabilities') ) {
            continue;
        }
        for ( let j = 0; j < locations[i].capabilities.length; j++ ) {
            if ( pluginData.locations_filter.exclude_outdoors == 'yes'
                && locations[i].capabilities[j].name == 'outdoors'
                && locations[i].capabilities[j].value == 'OUTDOORS'
            ) {
                locations.splice(i, 1);
            }
        }
    }

    return locations;
};

export const getLocationInfo = ( location ) => {
    return {
        id: location.id,
        pupCode: location?.pupCode || '',
        publicName: location?.publicName || '-', 
        address: location?.address?.address || '',
        city: location?.address?.municipality || '',
        postcode: location?.address?.postalCode || '',
    };
};

export const getGroupedLocationsList = ( orgLocationsList, locale ) => {
    let tempList = [];

    if ( orgLocationsList.length ) {
        for ( let i = 0; i < orgLocationsList.length; i++ ) {
            tempList.push(getLocationInfo(orgLocationsList[i]));
        }
    }

    let sortedList = tempList.sort((a, b) => 
        a.city.localeCompare(b.city, locale.toLowerCase(), { sensitivity: 'base' })
    );

    let groupedList = sortedList.reduce((cities, location) => {
        let city = location.city.trim();

        if ( ! cities[city] ) {
            cities[city] = [];
        }
        cities[city].push(location);

        return cities;
    }, {});

    return groupedList;
};
