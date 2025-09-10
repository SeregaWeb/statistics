import { autoFillAddressHero, initCopyDriverPhones } from './driver-hero';
import { printMessage } from '../info-messages';
import { cleanUrlByFilterDriverSearch } from '../filter-clean';

// eslint-disable-next-line import/prefer-default-export
export const initialSearchDriver = (varFromPhp) => {
    // @ts-ignore
    const hereApi = varFromPhp.here_api_key; // @ts-ignore
    const useDriver = varFromPhp.use_driver; // @ts-ignore
    const urlPelias = varFromPhp.url_pelias; // @ts-ignore
    const urlOrs = varFromPhp.url_ors; // @ts-ignore
    // eslint-disable-next-line camelcase
    const { geocoder } = varFromPhp; // @ts-ignore
    const ajaxUrl = varFromPhp.ajax_url;

    console.log('useDriver', geocoder);

    const btnsFillByZipcode = document.querySelectorAll('.js-fill-new-location');

    btnsFillByZipcode.forEach((btn) => {
        btn.addEventListener('click', (event) => {
            const form = btn.closest('form');
            const state = form?.querySelector('.js-state-new') as HTMLInputElement | null;
            const city = form?.querySelector('.js-city-new') as HTMLInputElement | null;
            const zip = form?.querySelector('.js-zip-code') as HTMLInputElement | null;
            const latitude = form?.querySelector('.js-latitude') as HTMLInputElement | null;
            const longitude = form?.querySelector('.js-longitude') as HTMLInputElement | null;
            const country = form?.querySelector('.js-country') as HTMLInputElement | null;
            const countrySearch = form?.querySelector('.js-search-country') as HTMLInputElement | null;

            const zipVal = zip?.value || '';
            const countrySearchVal = countrySearch?.value || 'USA';

            if (geocoder === 'here') {
                // @ts-ignore
                autoFillAddressHero(zipVal, hereApi, countrySearchVal)
                    .then((locationData) => {
                        if (locationData && typeof locationData === 'object') {
                            // Заполняем поля формы полученными данными
                            if (city && 'city' in locationData) {
                                // @ts-ignore
                                city.value = locationData.city || '';
                            }
                            if (state && 'state' in locationData) {
                                // @ts-ignore
                                state.value = locationData.state || '';
                            }
                            if (country && 'country' in locationData) {
                                // @ts-ignore
                                country.value = locationData.country || '';
                            }
                            if (latitude && 'lat' in locationData) {
                                // @ts-ignore
                                latitude.value = locationData.lat?.toString() || '';
                            }
                            if (longitude && 'lng' in locationData) {
                                // @ts-ignore
                                longitude.value = locationData.lng?.toString() || '';
                            }

                            console.log('Form fields updated with location data:', locationData);
                        }
                    })
                    .catch((error) => {
                        console.error('Error filling address:', error);
                    });
            }
        });
    });

    const form = document.querySelector('.js-update-location-driver');

    form &&
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const { target } = event;

            // @ts-ignore
            const formData = new FormData(target);
            formData.append('action', 'update_location_driver');

            const options = {
                method: 'POST',
                body: formData,
            };

            fetch(ajaxUrl, options)
                .then((res) => res.json())
                .then((requestStatus) => {
                    if (requestStatus.success) {
                        console.log('Table update', requestStatus.data);
                        printMessage(requestStatus.data.message, 'success', 8000);
                        window.location.reload();
                    } else {
                        // eslint-disable-next-line no-alert
                        printMessage(`Error change project: ${requestStatus.data.message}`, 'danger', 8000);
                    }
                })
                .catch((error) => {
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                });
        });

    const extendedSearchCheckbox = document.getElementById('search_type') as HTMLInputElement | null;
    const extendedSearchInput = document.getElementById('extended_search_input') as HTMLInputElement | null;
    const regularSearchInput = document.getElementById('regular_search_input') as HTMLInputElement | null;

    function updateSearchInput() {
        if (extendedSearchCheckbox && extendedSearchInput && regularSearchInput) {
            if (extendedSearchCheckbox.checked) {
                // Show extended search, hide regular search
                extendedSearchInput.style.display = '';
                regularSearchInput.style.display = 'none';
                // Clear regular search input
                regularSearchInput.value = '';
            } else {
                // Show regular search, hide extended search
                regularSearchInput.style.display = '';
                extendedSearchInput.style.display = 'none';
                // Clear extended search input
                extendedSearchInput.value = '';
            }
        }
    }

    updateSearchInput();

    if (extendedSearchCheckbox) {
        extendedSearchCheckbox.addEventListener('change', function () {
            updateSearchInput();
        });
    }

    // Initialize copy driver phones functionality
    initCopyDriverPhones();
};
