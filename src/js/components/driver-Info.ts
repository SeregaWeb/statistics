import { printMessage } from './info-messages';

// eslint-disable-next-line import/prefer-default-export
export const initGetInfoDriver = (urlAjax, ProjectsLinks) => {
    const btns = document.querySelectorAll('.js-fill-driver');

    if (btns) {
        btns.forEach((item) => {
            item.addEventListener('click', async (event) => {
                event.preventDefault();

                const { target } = event;
                if (!target) return;

                // Find the container and input
                // @ts-ignore
                const container = target.closest('.js-container-number');
                if (!container) return;
                const input = container.querySelector('input');
                if (!input) return;

                // @ts-ignore
                const phoneSelector = target.getAttribute('data-phone');

                if (!phoneSelector) {
                    printMessage(`Phone selector not found`, 'danger', 8000);
                    return;
                }

                const { value } = input; // Driver ID
                if (!value) {
                    console.warn('Input value (Driver ID) is empty.');
                    return;
                }

                console.log('Fetching driver for ID:', value);

                try {
                    // Call the internal database function
                    const formData = new FormData();
                    formData.append('action', 'get_driver_by_id_internal');
                    // @ts-ignore
                    formData.append('id', value);

                    const options = {
                        method: 'POST',
                        body: formData,
                    };

                    fetch(urlAjax, options)
                        .then((res) => res.json())
                        .then((requestStatus) => {
                            if (requestStatus.success) {
                                const driver = requestStatus.data;
                                if (!driver) return;
                                
                                // Update phone field
                                const driverPhone = document.querySelector(phoneSelector);
                                if (driverPhone) {
                                    // @ts-ignore
                                    driverPhone.value = driver.phone;
                                }
                                
                                // Update driver name field
                                // @ts-ignore
                                input.value = `(${value}) ${driver.driver}`;
                                
                                // Determine if this is a second driver based on phone selector
                                const isSecondDriver = phoneSelector === '.js-second-phone-driver';
                                const hiddenFieldName = isSecondDriver ? 'attached_second_driver' : 'attached_driver';
                                
                                // Find existing hidden field for driver ID
                                let hiddenField = container.querySelector(`input[name="${hiddenFieldName}"]`);
                                
                                if (hiddenField) {
                                    // Update existing hidden field
                                    // @ts-ignore
                                    hiddenField.value = value;
                                    console.log(`Driver ID ${value} updated in ${hiddenFieldName} (${isSecondDriver ? 'second' : 'main'} driver)`);
                                    
                                    // Verify the value was set
                                    // @ts-ignore
                                    console.log(`Field ${hiddenFieldName} value after update:`, hiddenField.value);
                                } else {
                                    console.warn(`Hidden field ${hiddenFieldName} not found in container`);
                                }
                                
                                // eslint-disable-next-line consistent-return
                                return true;
                            }
                            printMessage(`${requestStatus.data.message}`, 'danger', 8000);
                            // eslint-disable-next-line consistent-return
                            return false;
                        })
                        .catch((error) => {
                            printMessage(`'Request failed' ${error}`, 'danger', 8000);
                            return false;
                        });
                } catch (error) {
                    console.error('Error occurred while fetching driver:', error);
                }
            });
        });
    }
};
