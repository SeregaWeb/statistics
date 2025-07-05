// Function to collect form data from a row with the class js-fake-form
import { printMessage } from './info-messages';

function collectFormData(parentRow: HTMLElement): FormData {
    const formData = new FormData();

    // Collect all inputs inside the js-fake-form
    const inputs = parentRow.querySelectorAll('input, select, textarea');

    inputs.forEach((input) => {
        const { name } = input as HTMLInputElement;
        const { value } = input as HTMLInputElement;

        // Add the input's name and value to the FormData object
        formData.append(name, value);
    });

    return formData;
}

// eslint-disable-next-line import/prefer-default-export
export const sendUpdatePerformance = (ajaxUrl) => {
    const fakeFormSendButtons = document.querySelectorAll('.js-fake-form-send');

    if (fakeFormSendButtons) {
        fakeFormSendButtons.forEach((item) => {
            item.addEventListener('click', (event: Event) => {
                const parentRow = (event.target as HTMLElement).closest('.js-fake-form');

                if (parentRow) {
                    // @ts-ignore
                    const formData = collectFormData(parentRow);
                    formData.append('action', 'update_performance');
                    console.log(formData); // For debugging: outputs the FormData object

                    const options = {
                        method: 'POST',
                        body: formData,
                    };

                    fetch(ajaxUrl, options)
                        .then((res) => res.json())
                        .then((requestStatus) => {
                            if (requestStatus.success) {
                                console.log('Performance update successfully:', requestStatus.data);
                                printMessage(requestStatus.data.message, 'success', 8000);
                                window.location.reload();
                            } else {
                                // eslint-disable-next-line no-alert
                                printMessage(`Error update performance: ${requestStatus.data.message}`, 'danger', 8000);
                            }
                        })
                        .catch((error) => {
                            printMessage(`Request failed: ${error}`, 'danger', 8000);
                            console.error('Request failed:', error);
                        });
                }
            });
        });
    }
};
