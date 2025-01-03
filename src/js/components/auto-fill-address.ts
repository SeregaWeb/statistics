import { printMessage } from './info-messages';

function fillFormFields({ city, state, country }, container) {
    if (!container) return;
    // Fill the city input
    const cityInput = container.querySelector('.js-city');
    if (cityInput) {
        cityInput.value = city;
    }

    let countryCorrectVal = 'USA';
    let stateCorrectVal = state.toUpperCase();

    if (country === 'CAN') {
        countryCorrectVal = 'Canada';
    }

    if (country === 'MEX') {
        countryCorrectVal = 'Mexico';

        if (stateCorrectVal === 'NL') {
            stateCorrectVal = 'NLE';
        }
    }

    // Set the state in the select field
    const stateSelect = container.querySelector('.js-state');
    if (stateSelect) {
        stateSelect.value = stateCorrectVal;
    }

    // Select the correct radio button for the country
    const countryRadios = container.querySelectorAll('.js-country');
    countryRadios.forEach((radio) => {
        if (radio.value === countryCorrectVal) {
            // eslint-disable-next-line no-param-reassign
            radio.checked = true;
        }
    });
}

async function getLocationDataByZipCode(zipCode, apiKey) {
    const endpoint = `https://geocode.search.hereapi.com/v1/geocode?q=${zipCode}&in=countryCode%3AUSA%2CCAN%2CMEX&apiKey=${apiKey}`;

    try {
        const response = await fetch(endpoint);
        const data = await response.json();

        if (data.items && data.items.length > 0) {
            const location = data.items[0];
            const city = location.address.city || '';
            const state = location.address.stateCode || '';
            const country = location.address.countryCode || '';

            return {
                city,
                state,
                country,
            };
        }

        return null;
    } catch (error) {
        console.error('Error fetching data from Here API:', error);
        printMessage(`Error fetching data from Here API: ${error}`, 'danger', 8000);
        return null;
    }
}

// eslint-disable-next-line import/prefer-default-export
export const autoFillAddress = (key) => {
    const btns = document.querySelectorAll('.js-fill-auto');

    btns.forEach((item) => {
        item.addEventListener('click', async (event) => {
            event.preventDefault();
            const { target } = event;
            if (target instanceof HTMLElement) {
                const container = target.closest('.js-zip');
                const currentForm = target.closest('form');
                if (!container) return;
                const input = container.querySelector('input');

                if (!input) return;
                const { value } = input;

                if (value) {
                    try {
                        const locationData = await getLocationDataByZipCode(value, key);
                        if (locationData) {
                            console.log('City:', locationData.city);
                            console.log('State:', locationData.state);
                            console.log('Country:', locationData.country);
                            fillFormFields(locationData, currentForm);
                            printMessage(`Autofill completed successfully, check City State Country`, 'success', 8000);
                        } else {
                            printMessage(`Location data not found.`, 'danger', 8000);
                        }
                    } catch (error) {
                        printMessage(`Error fetching location data: ${error}`, 'danger', 8000);
                    }
                }
            }
        });
    });
};
