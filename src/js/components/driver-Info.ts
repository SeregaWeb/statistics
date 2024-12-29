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

                const { value } = input; // Driver ID
                if (!value) {
                    console.warn('Input value (Driver ID) is empty.');
                    return;
                }

                // Find the project selector
                const useProject = document.querySelector('.js-select-current-table');
                if (!useProject) {
                    console.warn('Project selector not found.');
                    return;
                }

                // @ts-ignore
                const valueProject = useProject.value;

                console.log('Fetching driver for ID:', value, 'and project:', valueProject);

                try {
                    // Call the async function and wait for its result
                    const formData = new FormData();
                    formData.append('action', 'get_driver_by_id');
                    // @ts-ignore
                    formData.append('id', value);
                    // @ts-ignore
                    formData.append('project', ProjectsLinks[valueProject]);

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
                                const driverPhone = document.querySelector('.js-phone-driver');
                                if (driverPhone) {
                                    // @ts-ignore
                                    driverPhone.value = driver.phone;
                                }
                                // @ts-ignore
                                input.value = `(${value}) ${driver.driver}`;
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
