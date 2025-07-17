// eslint-disable-next-line import/prefer-default-export
export function updateFillZipcode(
    form: HTMLElement | null,
    cityData = '',
    stateData = '',
    countryData = '',
    latitudeData = '',
    longitudeData = ''
): void {
    if (!form) {
        console.warn('Form element not found');
        return;
    }

    const sity = form.querySelector('.js-sity') as HTMLInputElement | null;
    const state = form.querySelector('.js-state') as HTMLInputElement | null;
    const latitude = form.querySelector('.js-latitude') as HTMLInputElement | null;
    const longitude = form.querySelector('.js-longitude') as HTMLInputElement | null;
    const country = form.querySelector('.js-country') as HTMLInputElement | null;

    if (sity) sity.value = cityData || '';
    if (state) state.value = stateData || '';
    if (country) country.value = countryData || '';
    if (latitude) latitude.value = latitudeData || '';
    if (longitude) longitude.value = longitudeData || '';
}
