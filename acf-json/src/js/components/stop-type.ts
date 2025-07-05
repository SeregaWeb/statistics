// eslint-disable-next-line import/prefer-default-export
export const changeStopType = () => {
    const stopType = document.querySelector<HTMLSelectElement>('.js-shipper-stop-type');
    // eslint-disable-next-line @wordpress/no-unused-vars-before-return
    const dateDelivery = document.querySelector<HTMLInputElement>('.js-delivery-date-setup');
    // eslint-disable-next-line @wordpress/no-unused-vars-before-return
    const datePickUp = document.querySelector<HTMLInputElement>('.js-pick-up-date-setup');
    const shipperDate = document.querySelector<HTMLInputElement>('.js-shipper-date');

    if (!stopType || !shipperDate) return;

    stopType.addEventListener('change', (event) => {
        const selectedValue = (event.target as HTMLSelectElement).value;

        if (selectedValue === 'pick_up_location' && datePickUp) {
            shipperDate.value = datePickUp.value;
        } else if (selectedValue === 'delivery_location' && dateDelivery) {
            shipperDate.value = dateDelivery.value;
        }
    });
};
