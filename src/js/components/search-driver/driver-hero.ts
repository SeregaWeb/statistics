import { printMessage } from '../info-messages';

export async function getLocationDataByZipCodeHero(zipCode, apiKey, CountrySearch = 'USA') {
    console.log('CountrySearch', CountrySearch);
    
    // Формируем параметр in в зависимости от переданной страны
    let countryParam;
    if (CountrySearch === 'USA' || CountrySearch === 'CAN' || CountrySearch === 'MEX') {
        // Если передана конкретная страна, ищем только по ней
        countryParam = `countryCode%3A${CountrySearch}`;
    } else {
        // По умолчанию ищем по всем 3 странам
        countryParam = 'countryCode%3AUSA%2CCAN%2CMEX';
    }
    
    const endpoint = `https://geocode.search.hereapi.com/v1/geocode?q=${zipCode}&in=${countryParam}&apiKey=${apiKey}`;

    try {
        const response = await fetch(endpoint);
        const data = await response.json();

        if (data.items && data.items.length > 0) {
            const location = data.items[0];
            const city = location.address.city || '';
            const state = location.address.stateCode || '';
            const country = location.address.countryCode || '';
            const lat = location.position.lat || '';
            const lng = location.position.lng || '';

            return {
                city,
                state,
                country,
                lat,
                lng,
            };
        }

        return null;
    } catch (error) {
        console.error('Error fetching data from Here API:', error);
        printMessage(`Error fetching data from Here API: ${error}`, 'danger', 8000);
        return null;
    }
}

export const autoFillAddressHero = async (zipCode, apiKey, countrySearch = 'USA') => {
    let result: any = false;
    if (zipCode) {
        try {
            const locationData = await getLocationDataByZipCodeHero(zipCode, apiKey, countrySearch);
            if (locationData) {
                console.log(locationData);
                console.log('City:', locationData.city);
                console.log('State:', locationData.state);
                console.log('Country:', locationData.country);
                result = locationData;
                printMessage(`Autofill completed successfully, check City State Country`, 'success', 8000);
            } else {
                printMessage(`Location data not found.`, 'danger', 8000);
            }
        } catch (error) {
            printMessage(`Error fetching location data: ${error}`, 'danger', 8000);
        }
    }

    return result;
};
