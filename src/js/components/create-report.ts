// eslint-disable-next-line import/prefer-default-export
import { Tab } from 'bootstrap';
import Popup from '../parts/popup-window';
import { printMessage } from './info-messages';

export function setUpTabInUrl(tab) {
    const url = new URL(window.location.href);
    // Set the 'post_id' parameter
    url.searchParams.set('tab', tab);
    // Update the URL without reloading the page
    window.history.pushState({}, '', url);
    window.location.href = <string>url?.href;
}

export const updateStatusPost = (ajaxUrl) => {
    const btns = document.querySelectorAll('.js-update-post-status');
    btns &&
        btns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();

                const { target } = event;

                // @ts-ignore
                const formData = new FormData();
                const action = 'update_post_status';

                const postId = document.querySelector('.js-post-id');

                if (!postId) {
                    printMessage('Post id not found', 'danger', 8000);
                    return;
                }

                formData.append('action', action);
                // @ts-ignore
                formData.append('post_id', postId.value);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                // @ts-ignore
                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            printMessage(requestStatus.data.message, 'success', 8000);
                            if (requestStatus.data.send_email?.success) {
                                console.log(requestStatus.data);
                                printMessage(requestStatus.data.send_email?.message, 'success', 8000);
                                setTimeout(() => {
                                    window.location.reload();
                                }, 4000);
                            } else {
                                printMessage(requestStatus.data.send_email?.message, 'danger', 8000);
                            }

                            const container = document.querySelector('.js-update-status');

                            if (!container) return;
                            container.innerHTML = '';

                            // eslint-disable-next-line no-use-before-define
                            setUpTabInUrl('pills-customer-tab');
                        } else {
                            printMessage(requestStatus.data.message, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};

const updateStatusRechange = (ajaxUrl) => {
    const container = document.querySelector('.js-update-status');
    if (!container) return;

    const formData = new FormData();
    const action = 'rechange_status_load';

    // @ts-ignore
    // eslint-disable-next-line @wordpress/no-unused-vars-before-return
    const postId = document.querySelector('.js-post-id');
    const postStatus = document.querySelector('.js-post-status');

    // @ts-ignore
    if (postStatus && postStatus.value === 'publish') {
        // @ts-ignore
        console.log('postStatus.value', postStatus.value);
        return;
    }

    if (!postId) {
        printMessage('Post id not found', 'danger', 8000);
        return;
    }

    formData.append('action', action);
    // @ts-ignore
    formData.append('post_id', postId.value);

    const options = {
        method: 'POST',
        body: formData,
    };

    fetch(ajaxUrl, options)
        .then((res) => res.json())
        .then((requestStatus) => {
            if (requestStatus.success) {
                container.innerHTML = requestStatus.data.template;
                updateStatusPost(ajaxUrl);
            }
        })
        .catch((error) => {
            printMessage(`Request failed: ${error}`, 'danger', 8000);
            console.error('Request failed:', error);
        });
};

export const fullRemovePost = (ajaxUrl) => {
    const btnsRemove = document.querySelectorAll('.js-remove-load');

    btnsRemove &&
        btnsRemove.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();

                const { target } = event;

                // @ts-ignore
                // eslint-disable-next-line no-alert,no-restricted-globals
                const question = window.confirm(
                    'Are you sure you want to delete this load? \nIf you agree it will be deleted permanently'
                );

                if (target instanceof HTMLElement && question) {
                    const idLoad = target.getAttribute('data-id');

                    if (!idLoad) {
                        printMessage(`Error remove Load: reload this page and try again`, 'danger', 8000);
                        return;
                    }

                    const action = 'remove_one_load';

                    const formData = new FormData();

                    formData.append('action', action);
                    formData.append('id_load', idLoad);

                    const options = {
                        method: 'POST',
                        body: formData,
                    };

                    fetch(ajaxUrl, options)
                        .then((res) => res.json())
                        .then((requestStatus) => {
                            if (requestStatus.success) {
                                console.log('Load remove successfully:', requestStatus.data);
                                const contain = target.closest('tr');

                                if (contain) {
                                    contain.remove();
                                }
                                printMessage(requestStatus.data.message, 'success', 8000);
                            } else {
                                // eslint-disable-next-line no-alert
                                printMessage(`Error remove Load:${requestStatus.data.message}`, 'danger', 8000);
                            }
                        })
                        .catch((error) => {
                            printMessage(`Request failed: ${error}`, 'danger', 8000);
                            console.error('Request failed:', error);
                        });
                }
            });
        });
};

/**
 * function use in load tab
 * update isset post
 *
 * @param ajaxUrl
 */

export const actionCreateReportInit = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-add-new-report');
    const popupInstance = new Popup();

    forms &&
        forms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;

                // @ts-ignore
                const btnSubmit = target.querySelector('.js-submit-and-next-tab');
                btnSubmit && btnSubmit.setAttribute('disabled', 'true');

                const nextTargetTab = 'pills-trip-tab';

                // @ts-ignore
                const formData = new FormData(target);
                formData.append('action', 'add_new_report');

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            console.log('Load added successfully:', requestStatus.data);
                            popupInstance.forceCloseAllPopup();

                            console.log('requestStatus.data?.read_only', requestStatus.data?.read_only);

                            if (requestStatus.data.data?.read_only !== 'true') {
                                updateStatusRechange(ajaxUrl);
                            }
                            printMessage(requestStatus.data.message, 'success', 8000);

                            setUpTabInUrl(nextTargetTab);
                            btnSubmit && btnSubmit.removeAttribute('disabled');
                        } else {
                            // eslint-disable-next-line no-alert
                            printMessage(`Error adding Load:${requestStatus.data.message}`, 'danger', 8000);
                            btnSubmit && btnSubmit.removeAttribute('disabled');
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                        btnSubmit && btnSubmit.removeAttribute('disabled');
                    });
            });
        });
};

/**
 * check isset post use param in url address
 */
function hasReportIdInUrl() {
    // Get the current URL
    const url = new URL(window.location.href);

    // Check if the 'post_id' parameter exists
    return url.searchParams.has('post_id');
}

/**
 * first create post report
 *
 * @param ajaxUrl
 */

export const createDraftPosts = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-create-not-publish-report');
    console.log('forms', forms);
    forms &&
        forms.forEach((item) => {
            // eslint-disable-next-line consistent-return
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const nextTargetTab = 'pills-load-tab';
                // const tab = new Tab(document.getElementById(nextTargetTab));
                let action = 'add_new_draft_report';
                // @ts-ignore
                const btnSubmit = event.target.querySelector('.js-submit-and-next-tab');
                btnSubmit && btnSubmit.setAttribute('disabled', 'true');

                if (hasReportIdInUrl()) {
                    action = 'update_new_draft_report';
                }

                // @ts-ignore
                const clearFormBeforeSubmit = event.target.querySelector('.js-container-search-list');

                if (clearFormBeforeSubmit) {
                    clearFormBeforeSubmit.innerHTML = '';
                }

                // @ts-ignore
                const searchSelectCompany = event.target.querySelector('input[name="customer_id"]');

                if (!searchSelectCompany) {
                    printMessage(`Your need select company or add new and select`, 'danger', 8000);
                    return false;
                }

                const { target } = event;
                // @ts-ignore
                const formData = new FormData(target);
                formData.append('action', action);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            btnSubmit && btnSubmit.removeAttribute('disabled');
                            console.log('Report added successfully:', requestStatus.data);
                            if (!hasReportIdInUrl()) {
                                console.log('1');
                                printMessage(requestStatus.data?.message, 'success', 8000);
                                const url = new URL(window.location.href);
                                // Set the 'post_id' parameter
                                url.searchParams.set('post_id', requestStatus.data.id_created_post);
                                url.searchParams.set('tab', 'pills-load-tab');
                                // Update the URL without reloading the page
                                window.history.pushState({}, '', url);
                                console.log('url', url);
                                // @ts-ignore
                                window.location.href = <string>url?.href;
                            } else {
                                console.log('2');
                                if (requestStatus.data.data?.read_only !== 'true') {
                                    updateStatusRechange(ajaxUrl);
                                }
                                printMessage(requestStatus.data?.message, 'success', 8000);
                                setUpTabInUrl(nextTargetTab);
                            }
                        } else {
                            // eslint-disable-next-line no-alert
                            btnSubmit && btnSubmit.removeAttribute('disabled');
                            printMessage(`Error adding report:${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        btnSubmit && btnSubmit.removeAttribute('disabled');
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};

/**
 * function helper update input file new values
 *
 * @param inputElement
 * @param filesArray
 */

function updateFileInput(inputElement: HTMLInputElement, filesArray: File[]) {
    const dataTransfer = new DataTransfer();
    filesArray.forEach((file) => dataTransfer.items.add(file));
    console.log('inputElement', inputElement, filesArray);
    if (!inputElement.files) return;
    // eslint-disable-next-line no-param-reassign
    inputElement.files = dataTransfer.files;
}

/**
 * add new image in isset post
 *
 * @param ajaxUrl
 */

export const updateFilesReportInit = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-uploads-files');
    forms &&
        forms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;
                // @ts-ignore
                const btnSubmit = target.querySelector('.js-submit-and-next-tab');
                btnSubmit && btnSubmit.setAttribute('disabled', 'true');
                // @ts-ignore
                const formData = new FormData(target);
                formData.append('action', 'update_files_report');

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            console.log('upload files successfully:', requestStatus.data);
                            printMessage(requestStatus.data.message, 'success', 8000);
                            setUpTabInUrl('pills-documents-tab');
                        } else {
                            // eslint-disable-next-line no-alert
                            btnSubmit && btnSubmit.removeAttribute('disabled');
                            printMessage(`Error upload files:${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        btnSubmit && btnSubmit.removeAttribute('disabled');
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};

export const updateBillingReportInit = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-uploads-billing');
    forms &&
        forms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;

                // @ts-ignore
                const btnSubmit = target.querySelector('.js-submit-and-next-tab');
                btnSubmit && btnSubmit.setAttribute('disabled', 'true');
                // @ts-ignore
                const formData = new FormData(target);
                formData.append('action', 'update_billing_report');

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            console.log('update successfully:', requestStatus.data);
                            printMessage(requestStatus.data.message, 'success', 8000);
                            if (requestStatus.data.data?.read_only !== 'true') {
                                updateStatusRechange(ajaxUrl);
                            }
                            setUpTabInUrl('pills-billing-tab');
                        } else {
                            btnSubmit && btnSubmit.removeAttribute('disabled');
                            // eslint-disable-next-line no-alert
                            printMessage(`Error update:${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        btnSubmit && btnSubmit.removeAttribute('disabled');
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};

export const updateAccountingReportInit = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-uploads-accounting');
    forms &&
        forms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;

                // @ts-ignore
                const btnSubmit = target.querySelector('.js-submit-and-next-tab');
                btnSubmit && btnSubmit.setAttribute('disabled', 'true');
                // @ts-ignore
                const formData = new FormData(target);
                formData.append('action', 'update_accounting_report');

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            console.log('update successfully:', requestStatus.data);
                            printMessage(requestStatus.data.message, 'success', 8000);
                            setUpTabInUrl('pills-accounting-tab');
                        } else {
                            // eslint-disable-next-line no-alert
                            btnSubmit && btnSubmit.removeAttribute('disabled');
                            printMessage(`Error update:${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        btnSubmit && btnSubmit.removeAttribute('disabled');
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};

/**
 * remove one image in page documents
 *
 * @param ajaxUrl
 */

export const removeOneFileInitial = (ajaxUrl) => {
    const deleteForms = document.querySelectorAll('.js-remove-one');

    deleteForms &&
        deleteForms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;
                // @ts-ignore
                const formData = new FormData(target);
                const action = 'delete_open_image';

                formData.append('action', action);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            const container = document.querySelector('.js-uploads-files') as HTMLElement;

                            printMessage(requestStatus.data.message, 'success', 8000);

                            setUpTabInUrl('pills-documents-tab');
                        } else {
                            printMessage(`Error adding report:${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};

export function uploadFilePreview(target) {
    if (!target || !target.files) return;

    // Create an array to store all selected files
    const allFiles: File[] = [];

    const container = target.closest('.js-add-new-report');
    const previewContainer = container?.querySelector('.js-preview-photo-upload');

    if (!previewContainer) return;

    // Add new files to the allFiles array
    Array.from(target.files).forEach((file) => {
        // @ts-ignore
        allFiles.push(file);
    });
    console.log(target, allFiles);
    updateFileInput(target, allFiles); // Update input with combined files

    // Clear previous previews and render new ones
    previewContainer.innerHTML = '';
    allFiles.forEach((file, index) => {
        const fileReader = new FileReader();
        const fileWrapper = document.createElement('div');
        fileWrapper.classList.add('file-preview');

        // Add delete button
        const deleteButton = document.createElement('button');
        deleteButton.innerHTML = `<svg width="32px" height="32px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 22C7.28595 22 4.92893 22 3.46447 20.5355C2 19.0711 2 16.714 2 12C2 7.28595 2 4.92893 3.46447 3.46447C4.92893 2 7.28595 2 12 2C16.714 2 19.0711 2 20.5355 3.46447C22 4.92893 22 7.28595 22 12C22 16.714 22 19.0711 20.5355 20.5355C19.0711 22 16.714 22 12 22ZM8.96965 8.96967C9.26254 8.67678 9.73742 8.67678 10.0303 8.96967L12 10.9394L13.9696 8.96969C14.2625 8.6768 14.7374 8.6768 15.0303 8.96969C15.3232 9.26258 15.3232 9.73746 15.0303 10.0303L13.0606 12L15.0303 13.9697C15.3232 14.2625 15.3232 14.7374 15.0303 15.0303C14.7374 15.3232 14.2625 15.3232 13.9696 15.0303L12 13.0607L10.0303 15.0303C9.73744 15.3232 9.26256 15.3232 8.96967 15.0303C8.67678 14.7374 8.67678 14.2626 8.96967 13.9697L10.9393 12L8.96965 10.0303C8.67676 9.73744 8.67676 9.26256 8.96965 8.96967Z" fill="#1C274C"/></svg>`;
        deleteButton.type = 'button';
        deleteButton.classList.add('file-delete-btn');

        deleteButton.addEventListener('click', () => {
            allFiles.splice(index, 1);
            updateFileInput(target, allFiles); // Update input again after deletion
            fileWrapper.remove(); // Remove the file preview
        });

        if (file.type.startsWith('image/')) {
            fileReader.onload = function (e) {
                const img = document.createElement('img');
                img.src = e.target?.result as string;
                img.alt = file.name;
                fileWrapper.appendChild(img);
            };
            fileReader.readAsDataURL(file);
        } else {
            const icon = document.createElement('div');
            icon.innerHTML = `
                            <svg version="1.0" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                             width="80px" height="80px" viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve">
                            <g>
                            <path fill="#231F20" d="M56,0H8C5.789,0,4,1.789,4,4v56c0,2.211,1.789,4,4,4h48c2.211,0,4-1.789,4-4V4C60,1.789,58.211,0,56,0z
                             M58,60c0,1.104-0.896,2-2,2H8c-1.104,0-2-0.896-2-2V4c0-1.104,0.896-2,2-2h48c1.104,0,2,0.896,2,2V60z"/>
                            <path fill="#231F20" d="M49,25H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,25,49,25z"/>
                            <path fill="#231F20" d="M49,19H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,19,49,19z"/>
                            <path fill="#231F20" d="M49,37H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,37,49,37z"/>
                            <path fill="#231F20" d="M49,43H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,43,49,43z"/>
                            <path fill="#231F20" d="M49,49H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,49,49,49z"/>
                            <path fill="#231F20" d="M49,31H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,31,49,31z"/>
                            <path fill="#231F20" d="M15,15h16c0.553,0,1-0.447,1-1s-0.447-1-1-1H15c-0.553,0-1,0.447-1,1S14.447,15,15,15z"/>
                            </g>
                            </svg>
                            <p>${file.name}</p>
                        `;
            icon.classList.add('file-name');
            fileWrapper.appendChild(icon);
        }

        fileWrapper.appendChild(deleteButton);
        previewContainer.appendChild(fileWrapper);
    });

    // After processing the files, we reset the input so it doesn't show "No file chosen"
    updateFileInput(target, allFiles);
}

/**
 * preview image after upload on page
 */
export const previewFileUpload = () => {
    const controlUploads = document.querySelectorAll('.js-control-uploads');

    controlUploads &&
        controlUploads.forEach((control) => {
            control.addEventListener('change', function (event) {
                const target = event.target as HTMLInputElement;
                uploadFilePreview(target);
            });
        });
};

const addActionsDeleteUniversalCard = (selectorBtn, selectorCard) => {
    if (!selectorBtn || !selectorCard) return;
    // @ts-ignore
    const btnsSelectors = document.querySelectorAll(selectorBtn);

    btnsSelectors &&
        btnsSelectors.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();
                const { target } = event;

                if (target instanceof HTMLElement) {
                    const container = target.closest(selectorCard);

                    if (!container) return;

                    container.remove();
                }
            });
        });
};

/**
 * add actions edit after dynamic add in page
 */
const addActionsEditAdditionalCard = () => {
    // eslint-disable-next-line @wordpress/no-unused-vars-before-return
    const batonsEdit = document.querySelectorAll('.js-edit-contact');

    const btnEndEdit = document.querySelector('.js-edit-additional-contact');
    const btnAdd = document.querySelector('.js-add-additional-contact');

    if (!btnEndEdit && !btnAdd) return;

    batonsEdit &&
        batonsEdit.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();
                const { target } = event;

                const activeItem = document.querySelector('.js-additional-card.edit');

                if (activeItem) {
                    activeItem.classList.remove('edit');
                }

                if (target instanceof HTMLElement) {
                    const container = target.closest('.js-additional-card');

                    if (!container) return;

                    container.classList.add('edit');

                    if (btnEndEdit && btnAdd) {
                        btnEndEdit.classList.remove('d-none');
                        btnAdd.classList.add('d-none');
                    }

                    const containerMain = target.closest('.js-additional-contact');

                    if (!containerMain) return;

                    const contactName = containerMain.querySelector('#contact-input-firstname');
                    const contactPhone = containerMain.querySelector('#contact-input-phone');
                    const contactEmail = containerMain.querySelector('#contact-input-email');
                    const contactExt = containerMain.querySelector('#contact-input-ext');

                    const additionalName = container.querySelector('.js-additional-field-name');
                    const additionalPhone = container.querySelector('.js-additional-field-phone');
                    const additionalEmail = container.querySelector('.js-additional-field-email');
                    const additionalExt = container.querySelector('.js-additional-field-ext');

                    if (contactExt && additionalExt) {
                        // @ts-ignore
                        contactExt.value = additionalExt.value.trim() === '' ? 'unset' : additionalExt.value;
                    }

                    if (contactName && additionalName) {
                        // @ts-ignore
                        contactName.value = additionalName.value.trim() === '' ? 'unset' : additionalName.value;
                    }
                    if (contactPhone && additionalPhone) {
                        // @ts-ignore
                        contactPhone.value = additionalPhone.value.trim() === '' ? 'unset' : additionalPhone.value;
                    }
                    if (contactEmail && additionalEmail) {
                        // @ts-ignore
                        contactEmail.value = additionalEmail.value.trim() === '' ? 'unset' : additionalEmail.value;
                    }
                }
            });
        });

    // @ts-ignore
    btnEndEdit.addEventListener('click', (event) => {
        event.preventDefault();
        const { target } = event;

        // eslint-disable-next-line @wordpress/no-unused-vars-before-return
        const activeItem = document.querySelector('.js-additional-card.edit');

        if (!target) return;

        // @ts-ignore
        const containerMain = target.closest('.js-additional-contact');

        if (!containerMain || !activeItem) return;

        const contactName = containerMain.querySelector('#contact-input-firstname');
        const contactPhone = containerMain.querySelector('#contact-input-phone');
        const contactEmail = containerMain.querySelector('#contact-input-email');
        const contactExt = containerMain.querySelector('#contact-input-ext');

        // eslint-disable-next-line @wordpress/no-unused-vars-before-return
        const additionalName = activeItem.querySelector('.js-additional-field-name');
        // eslint-disable-next-line @wordpress/no-unused-vars-before-return
        const additionalPhone = activeItem.querySelector('.js-additional-field-phone');
        // eslint-disable-next-line @wordpress/no-unused-vars-before-return
        const additionalEmail = activeItem.querySelector('.js-additional-field-email');
        // eslint-disable-next-line @wordpress/no-unused-vars-before-return
        const additionalExt = activeItem.querySelector('.js-additional-field-ext');

        // @ts-ignore
        if (
            contactName.value.trim() === '' &&
            contactPhone.value.trim() === '' &&
            contactEmail.value.trim() === '' &&
            contactExt.value.trim() === ''
        ) {
            printMessage(`All fields empty`, 'danger', 8000);
            // eslint-disable-next-line consistent-return
            return false;
        }

        if (activeItem) {
            // @ts-ignore
            btnEndEdit.classList.add('d-none');
            // @ts-ignore
            btnAdd.classList.remove('d-none');

            containerMain.classList.remove('edit-now');

            activeItem.classList.remove('edit');
        }

        if (contactExt && additionalExt) {
            // @ts-ignore
            additionalExt.value = contactExt.value;
            contactExt.value = '';
        }

        if (contactName && additionalName) {
            // @ts-ignore
            additionalName.value = contactName.value;
            contactName.value = '';
        }
        if (contactPhone && additionalPhone) {
            // @ts-ignore
            additionalPhone.value = contactPhone.value;
            contactPhone.value = '';
        }
        if (contactEmail && additionalEmail) {
            // @ts-ignore
            additionalEmail.value = contactEmail.value;
            contactEmail.value = '';
        }
    });
};

/**
 * initial additions contact actions and first init
 */
export const additionalContactsInit = () => {
    const btn = document.querySelector('.js-add-additional-contact');

    const containerNewContact = document.querySelector('.js-additional-contact-wrap');

    btn &&
        btn.addEventListener('click', (event) => {
            event.preventDefault();
            const { target } = event;

            if (target instanceof HTMLElement) {
                const container = target.closest('.js-additional-contact');

                if (!container) return;

                const contactName = container.querySelector('#contact-input-firstname');
                const contactPhone = container.querySelector('#contact-input-phone');
                const contactEmail = container.querySelector('#contact-input-email');
                const contactExt = container.querySelector('#contact-input-ext');

                if (!contactName || !contactPhone || !contactEmail || !contactExt) return;

                // @ts-ignore
                let valueName = contactName.value;
                // @ts-ignore
                let valuePhone = contactPhone.value;
                // @ts-ignore
                let valueEmail = contactEmail.value;
                // @ts-ignore
                let valueExt = contactExt.value;

                console.log('valueName', valueName);
                console.log('valuePhone', valuePhone);
                console.log('valueEmail', valueEmail);

                if (valueName.trim() === '') {
                    valueName = 'unset';
                }
                if (valueExt.trim() === '') {
                    valueExt = 'unset';
                }

                if (valuePhone.trim() === '') {
                    valuePhone = 'unset';
                }

                if (valueEmail.trim() === '') {
                    valueEmail = 'unset';
                }

                if (valueExt === 'unset' && valueName === 'unset' && valuePhone === 'unset' && valueEmail === 'unset') {
                    printMessage(`All fields empty`, 'danger', 8000);
                    // eslint-disable-next-line consistent-return
                    return false;
                }

                const template = `<div class="additional-card js-additional-card">
                            <input type="text" name="additional_contact_name[]" readonly="" value="${valueName}" class="form-control js-additional-field-name">
                            <input type="text" name="additional_contact_phone[]" readonly="" value="${valuePhone}" class="form-control js-additional-field-phone">
                            <input type="text" name="additional_contact_phone_ext[]" readonly
                                               value="${valueExt}" class="form-control js-additional-field-phone">
                            <input type="text" name="additional_contact_email[]" readonly="" value="${valueEmail}" class="form-control js-additional-field-email">
                            <button class="additional-card__edit js-edit-contact">
                        
                            <svg width="668" height="668" viewBox="0 0 668 668" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M640.46 27.5413C676.29 63.3746 676.29 121.472 640.46 157.305L623.94 173.823C619.13 172.782 613.073 171.196 606.17 168.801C587.693 162.391 563.41 150.276 540.567 127.433C517.723 104.591 505.61 80.3076 499.2 61.8299C496.803 54.9269 495.22 48.8696 494.177 44.0596L510.697 27.5413C546.53 -8.29175 604.627 -8.29175 640.46 27.5413Z" fill="#1C274C"></path>
                                <path d="M420.003 377.76C406.537 391.227 399.803 397.96 392.377 403.753C383.62 410.583 374.143 416.44 364.117 421.22C355.617 425.27 346.583 428.28 328.513 434.303L233.236 466.063C224.345 469.027 214.542 466.713 207.915 460.087C201.287 453.457 198.973 443.657 201.937 434.763L233.696 339.487C239.719 321.417 242.73 312.383 246.781 303.883C251.56 293.857 257.416 284.38 264.248 275.623C270.04 268.197 276.773 261.465 290.24 247.998L454.11 84.1284C462.9 107.268 478.31 135.888 505.21 162.789C532.113 189.69 560.733 205.099 583.873 213.891L420.003 377.76Z" fill="#1C274C"></path>
                                <path d="M618.517 618.516C667.333 569.703 667.333 491.133 667.333 334C667.333 282.39 667.333 239.258 665.603 202.87L453.533 414.943C441.823 426.656 433.027 435.456 423.127 443.176C411.507 452.243 398.933 460.013 385.627 466.353C374.293 471.756 362.487 475.686 346.777 480.92L249.048 513.496C222.189 522.45 192.578 515.46 172.559 495.44C152.54 475.423 145.55 445.81 154.503 418.953L187.078 321.223C192.312 305.513 196.244 293.706 201.645 282.373C207.986 269.066 215.757 256.493 224.822 244.871C232.543 234.972 241.344 226.176 253.058 214.468L465.13 2.39583C428.743 0.6665 385.61 0.666504 334 0.666504C176.865 0.666504 98.2977 0.6665 49.4824 49.4822C0.666744 98.2975 0.666748 176.865 0.666748 334C0.666748 491.133 0.666744 569.703 49.4824 618.516C98.2977 667.333 176.865 667.333 334 667.333C491.133 667.333 569.703 667.333 618.517 618.516Z" fill="#1C274C"></path>
                            </svg>
                            </button>
                            <button class="additional-card__remove js-remove-contact">
                                
                            <svg width="668" height="668" viewBox="0 0 668 668" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M334 667.333C176.865 667.333 98.2976 667.333 49.4823 618.516C0.666622 569.703 0.666626 491.133 0.666626 334C0.666626 176.865 0.666622 98.2975 49.4823 49.4822C98.2976 0.6665 176.865 0.666504 334 0.666504C491.133 0.666504 569.703 0.6665 618.517 49.4822C667.333 98.2975 667.333 176.865 667.333 334C667.333 491.133 667.333 569.703 618.517 618.516C569.703 667.333 491.133 667.333 334 667.333ZM232.988 232.989C242.751 223.226 258.581 223.226 268.343 232.989L334 298.646L399.653 232.99C409.417 223.227 425.247 223.227 435.01 232.99C444.773 242.753 444.773 258.582 435.01 268.343L369.353 334L435.01 399.656C444.773 409.416 444.773 425.246 435.01 435.01C425.247 444.773 409.417 444.773 399.653 435.01L334 369.357L268.343 435.01C258.581 444.773 242.752 444.773 232.989 435.01C223.226 425.246 223.226 409.42 232.989 399.656L298.643 334L232.988 268.343C223.225 258.581 223.225 242.752 232.988 232.989Z" fill="#1C274C"></path>
                            </svg>
                            </button>
                        </div>`;

                // @ts-ignore
                contactName.value = '';
                // @ts-ignore
                contactPhone.value = '';
                // @ts-ignore
                contactEmail.value = '';

                // @ts-ignore
                containerNewContact.innerHTML += template;
                addActionsDeleteUniversalCard('.js-remove-contact', '.js-additional-card');
                addActionsEditAdditionalCard();
            }
        });

    addActionsDeleteUniversalCard('.js-remove-contact', '.js-additional-card');
    addActionsEditAdditionalCard();
};

const editShipperStopInit = () => {
    const btnsEditPoint = document.querySelectorAll('.js-edit-ship');

    btnsEditPoint &&
        btnsEditPoint.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();

                const { target } = event;

                if (!target) return;

                const btnAdd = document.querySelector('.js-add-ship');
                const btnEdit = document.querySelector('.js-end-edit-ship');

                // @ts-ignore
                const containerMain = target.closest('.js-shipper');

                // @ts-ignore
                if (btnAdd && btnEdit) {
                    containerMain.classList.add('edit-now');
                    btnAdd.classList.add('d-none');
                    btnEdit.classList.remove('d-none');
                }

                // @ts-ignore
                const card = target.closest('.js-current-shipper');

                // @ts-ignore
                const form = target.closest('.js-shipper');

                if (form && card) {
                    card.classList.add('active');
                    const resultSearch = form.querySelector('.js-result-search');
                    const stopType = form.querySelector('.js-shipper-stop-type');
                    const contact = form.querySelector('.js-shipper-contact');
                    const date = form.querySelector('.js-shipper-date');
                    const info = form.querySelector('.js-shipper-info');
                    const addressSearch = form.querySelector('.js-search-shipper');

                    const dateStart = form.querySelector('.js-shipper-time-start');
                    const dateEnd = form.querySelector('.js-shipper-time-end');
                    const strict = form.querySelector('.js-shipper-time-strict');

                    const currentID = card.querySelector('.js-current-shipper_address_id');
                    const currentAddress = card.querySelector('.js-current-shipper_address');
                    const currentContact = card.querySelector('.js-current-shipper_contact');
                    const currentDate = card.querySelector('.js-current-shipper_date');
                    const currentInfo = card.querySelector('.js-current-shipper_info');
                    const currentType = card.querySelector('.js-current-shipper_type');
                    const currentStart = card.querySelector('.js-current-shipper_start');
                    const currentEnd = card.querySelector('.js-current-shipper_end');
                    const currentStrict = card.querySelector('.js-current-shipper_strict');
                    const currentShortAddress = card.querySelector('.js-current-shipper_short_address');
                    const timeEndContainer = document.querySelector<HTMLElement>('.js-hide-end-date');

                    const templateInputEdit = `
                        <input type="hidden" class="js-full-address" data-current-address="${currentAddress.value}" data-short-address="${currentShortAddress.value}" name="shipper_id" value="${currentID.value}">
                    `;

                    if (!resultSearch) return;

                    resultSearch.innerHTML = templateInputEdit;
                    stopType.value = currentType.value;
                    addressSearch.value = currentAddress.value;
                    contact.value = currentContact.value;
                    date.value = currentDate.value;
                    info.value = currentInfo.value;
                    dateStart.value = currentStart.value;
                    dateEnd.value = currentEnd.value;
                    strict.checked = currentStrict.value === 'true';

                    if (timeEndContainer && strict.checked) {
                        timeEndContainer.classList.add('d-none');
                    } else if (timeEndContainer && !strict.checked) {
                        timeEndContainer.classList.remove('d-none');
                    }

                    card.remove();
                }
            });
        });
};

export const timeStrictChange = () => {
    const strictCheckbox = document.querySelector<HTMLInputElement>('.js-shipper-time-strict');
    const timeEndContainer = document.querySelector<HTMLElement>('.js-hide-end-date');
    const timeEndInput = document.querySelector<HTMLInputElement>('.js-shipper-time-end');

    // Если все элементы найдены, навешиваем обработчик события
    if (strictCheckbox && timeEndContainer && timeEndInput) {
        strictCheckbox.addEventListener('change', () => {
            if (strictCheckbox.checked) {
                // Если чекбокс выбран: добавляем класс d-none и очищаем значение инпута
                timeEndContainer.classList.add('d-none');
                timeEndInput.value = '';
            } else {
                // Если чекбокс снят: убираем класс d-none, чтобы отобразить контейнер
                timeEndContainer.classList.remove('d-none');
            }
        });
    }
};

export const addShipperPointInit = () => {
    const btnAddPoint = document.querySelectorAll('.js-add-point');

    btnAddPoint &&
        btnAddPoint.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();

                const { target } = event;

                if (!target) return;

                // @ts-ignore
                const form = target.closest('.js-shipper');

                if (form) {
                    const resultSearch = form.querySelector('.js-result-search');
                    const address = resultSearch.querySelector('.js-full-address');
                    const stopType = form.querySelector('.js-shipper-stop-type');
                    const contact = form.querySelector('.js-shipper-contact');
                    const date = form.querySelector('.js-shipper-date');
                    const info = form.querySelector('.js-shipper-info');
                    const addressSearch = form.querySelector('.js-search-shipper');
                    const dateStart = form.querySelector('.js-shipper-time-start');
                    const dateEnd = form.querySelector('.js-shipper-time-end');
                    const dateStrict = form.querySelector('.js-shipper-time-strict');

                    const timeEndContainer = document.querySelector<HTMLElement>('.js-hide-end-date');

                    if (!address) {
                        printMessage(`The address must be selected from the drop-down list`, 'danger', 5000);
                        // eslint-disable-next-line consistent-return
                        return false;
                    }

                    const addressValueID = address.value;
                    const addressValueFullAddrres = address.getAttribute('data-current-address');
                    const addressValueShortAddrres = address.getAttribute('data-short-address');
                    const stopTypeValue = stopType.value;
                    const contactValue = contact.value;
                    const dateValue = date.value;
                    const start = dateStart.value;
                    const end = dateEnd.value;
                    const strict = dateStrict.checked;
                    let infoValue = info.value;

                    const shipperContacts = form.querySelector('.js-table-shipper');

                    if (!addressValueID || addressValueFullAddrres === '') {
                        printMessage(`Address empty`, 'danger', 5000);
                        // eslint-disable-next-line consistent-return
                        return false;
                    }

                    if (!start) {
                        printMessage(`Need fill time start`, 'danger', 5000);
                        // eslint-disable-next-line consistent-return
                        return false;
                    }

                    if (stopTypeValue === '') {
                        printMessage(`Stop type empty`, 'danger', 5000);
                        // eslint-disable-next-line consistent-return
                        return false;
                    }

                    // if (contactValue === '') {
                    //     printMessage(`Contact shipper empty`, 'danger', 5000);
                    //     // eslint-disable-next-line consistent-return
                    //     return false;
                    // }

                    if (infoValue === '') {
                        infoValue = 'unset';
                    }

                    // if (info.value === '') {
                    //     printMessage(`Specific information empty`, 'danger', 5000);
                    //     // eslint-disable-next-line consistent-return
                    //     return false;
                    // }

                    let typeDelivery = 'Delivery';

                    if (stopTypeValue === 'pick_up_location') {
                        typeDelivery = 'Pick Up';
                    }

                    let time = '';
                    if (strict === 'false' || !strict) {
                        time = `${start} - ${end}`;
                    } else {
                        time = `${start} - strict`;
                    }

                    const template = `
                <div class="row js-current-shipper card-shipper">
                    <div class="d-none">
                        <input type="hidden" class="js-current-shipper_address_id" name="${stopTypeValue}_address_id[]" value="${addressValueID}" >
                        <input type="hidden" class="js-current-shipper_address" name="${stopTypeValue}_address[]" value="${addressValueFullAddrres}" >
                        <input type="hidden" class="js-current-shipper_short_address" name="${stopTypeValue}_short_address[]" value="${addressValueShortAddrres}" >
                        <input type="hidden" class="js-current-shipper_contact" name="${stopTypeValue}_contact[]" value="${contactValue}" >
                        <input type="hidden" class="js-current-shipper_date" name="${stopTypeValue}_date[]" value="${dateValue}" >
                        <input type="hidden" class="js-current-shipper_info" name="${stopTypeValue}_info[]" value="${infoValue}" >
                        <input type="hidden" class="js-current-shipper_type" name="${stopTypeValue}_type[]" value="${stopTypeValue}" >
                        <input type="hidden" class="js-current-shipper_start" name="${stopTypeValue}_start[]" value="${start}">
                        <input type="hidden" class="js-current-shipper_end" name="${stopTypeValue}_end[]" value="${end}">
                        <input type="hidden" class="js-current-shipper_strict" name="${stopTypeValue}_strict[]" value="${strict}">
                    </div>
                    <div class="col-12 col-md-1">${typeDelivery}</div>
                    <div class="col-12 col-md-2">
                         <div class="d-flex flex-column">
                                <p class="m-0">${dateValue}</p>
                                <span class="small-text">
                                    ${time}
                                </span>
                            </div>
                    </div>
                    <div class="col-12 col-md-3">${addressValueFullAddrres}</div>
                    <div class="col-12 col-md-2">${contactValue}</div>
                    <div class="col-12 col-md-3">${infoValue}</div>
                    <div class="col-12 col-md-1 p-0 card-shipper__btns">
                        <button class="additional-card__edit js-edit-ship">
                            <svg width="668" height="668" viewBox="0 0 668 668" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M640.46 27.5413C676.29 63.3746 676.29 121.472 640.46 157.305L623.94 173.823C619.13 172.782 613.073 171.196 606.17 168.801C587.693 162.391 563.41 150.276 540.567 127.433C517.723 104.591 505.61 80.3076 499.2 61.8299C496.803 54.9269 495.22 48.8696 494.177 44.0596L510.697 27.5413C546.53 -8.29175 604.627 -8.29175 640.46 27.5413Z" fill="#1C274C"/>
                                <path d="M420.003 377.76C406.537 391.227 399.803 397.96 392.377 403.753C383.62 410.583 374.143 416.44 364.117 421.22C355.617 425.27 346.583 428.28 328.513 434.303L233.236 466.063C224.345 469.027 214.542 466.713 207.915 460.087C201.287 453.457 198.973 443.657 201.937 434.763L233.696 339.487C239.719 321.417 242.73 312.383 246.781 303.883C251.56 293.857 257.416 284.38 264.248 275.623C270.04 268.197 276.773 261.465 290.24 247.998L454.11 84.1284C462.9 107.268 478.31 135.888 505.21 162.789C532.113 189.69 560.733 205.099 583.873 213.891L420.003 377.76Z" fill="#1C274C"/>
                                <path d="M618.517 618.516C667.333 569.703 667.333 491.133 667.333 334C667.333 282.39 667.333 239.258 665.603 202.87L453.533 414.943C441.823 426.656 433.027 435.456 423.127 443.176C411.507 452.243 398.933 460.013 385.627 466.353C374.293 471.756 362.487 475.686 346.777 480.92L249.048 513.496C222.189 522.45 192.578 515.46 172.559 495.44C152.54 475.423 145.55 445.81 154.503 418.953L187.078 321.223C192.312 305.513 196.244 293.706 201.645 282.373C207.986 269.066 215.757 256.493 224.822 244.871C232.543 234.972 241.344 226.176 253.058 214.468L465.13 2.39583C428.743 0.6665 385.61 0.666504 334 0.666504C176.865 0.666504 98.2977 0.6665 49.4824 49.4822C0.666744 98.2975 0.666748 176.865 0.666748 334C0.666748 491.133 0.666744 569.703 49.4824 618.516C98.2977 667.333 176.865 667.333 334 667.333C491.133 667.333 569.703 667.333 618.517 618.516Z" fill="#1C274C"/>
                            </svg>
                        </button>
                        <button class="additional-card__remove js-remove-ship">
                            <svg width="668" height="668" viewBox="0 0 668 668" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M334 667.333C176.865 667.333 98.2976 667.333 49.4823 618.516C0.666622 569.703 0.666626 491.133 0.666626 334C0.666626 176.865 0.666622 98.2975 49.4823 49.4822C98.2976 0.6665 176.865 0.666504 334 0.666504C491.133 0.666504 569.703 0.6665 618.517 49.4822C667.333 98.2975 667.333 176.865 667.333 334C667.333 491.133 667.333 569.703 618.517 618.516C569.703 667.333 491.133 667.333 334 667.333ZM232.988 232.989C242.751 223.226 258.581 223.226 268.343 232.989L334 298.646L399.653 232.99C409.417 223.227 425.247 223.227 435.01 232.99C444.773 242.753 444.773 258.582 435.01 268.343L369.353 334L435.01 399.656C444.773 409.416 444.773 425.246 435.01 435.01C425.247 444.773 409.417 444.773 399.653 435.01L334 369.357L268.343 435.01C258.581 444.773 242.752 444.773 232.989 435.01C223.226 425.246 223.226 409.42 232.989 399.656L298.643 334L232.988 268.343C223.225 258.581 223.225 242.752 232.988 232.989Z" fill="#1C274C"/>
                            </svg>
                        </button>
                    </div>
                </div>
                `;
                    if (stopTypeValue === 'pick_up_location') {
                        shipperContacts.innerHTML = template + shipperContacts.innerHTML;
                    } else {
                        shipperContacts.innerHTML += template;
                    }

                    address.remove();
                    resultSearch.innerHTML = '';
                    contact.value = '';
                    date.value = '';
                    info.value = '';
                    addressSearch.value = '';
                    dateStart.value = '';
                    dateEnd.value = '';
                    dateStrict.checked = false;
                    timeEndContainer && timeEndContainer.classList.remove('d-none');

                    const btnAdd = document.querySelector('.js-add-ship');
                    const btnEdit = document.querySelector('.js-end-edit-ship');

                    addActionsDeleteUniversalCard('.js-remove-ship', '.js-current-shipper');
                    editShipperStopInit();
                    // @ts-ignore
                    if (btnAdd && btnEdit && target.classList.contains('js-end-edit-ship')) {
                        form.classList.remove('edit-now');
                        btnAdd.classList.remove('d-none');
                        btnEdit.classList.add('d-none');
                        // @ts-ignore
                        console.log(btnAdd, btnEdit, target.classList.contains('js-end-edit-ship'));
                    }
                }
            });
        });

    addActionsDeleteUniversalCard('.js-remove-ship', '.js-current-shipper');
    editShipperStopInit();
};

export const sendShipperFormInit = (ajaxUrl) => {
    const shipperForm = document.querySelector('.js-shipper');

    shipperForm &&
        shipperForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const { target } = event;

            // @ts-ignore
            const btnSubmit = target.querySelector('.js-submit-and-next-tab');
            btnSubmit && btnSubmit.setAttribute('disabled', 'disabled');

            const nextTargetTab = 'pills-documents-tab';
            // @ts-ignore
            const formData = new FormData(target);
            const action = 'update_shipper_info';

            formData.append('action', action);

            const options = {
                method: 'POST',
                body: formData,
            };

            fetch(ajaxUrl, options)
                .then((res) => res.json())
                .then((requestStatus) => {
                    if (requestStatus.success) {
                        printMessage(requestStatus.data.message, 'success', 8000);
                        if (requestStatus.data.data?.read_only !== 'true') {
                            updateStatusRechange(ajaxUrl);
                        }
                        btnSubmit && btnSubmit.removeAttribute('disabled');
                        setUpTabInUrl(nextTargetTab);
                    } else {
                        btnSubmit && btnSubmit.removeAttribute('disabled');
                        printMessage(`Error adding shipper info:${requestStatus.data.message}`, 'danger', 8000);
                    }
                })
                .catch((error) => {
                    btnSubmit && btnSubmit.removeAttribute('disabled');
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                });
        });
};

const selectCheckedLoads = () => {
    const selectLoads = document.querySelectorAll('.js-select-load');
    const selectedValues = Array.from(selectLoads)
        // @ts-ignore
        .filter((checkbox) => checkbox.checked)
        // @ts-ignore
        .map((checkbox) => checkbox.value)
        .join(',');

    return selectedValues;
};

export const quickEditInit = (ajaxUrl, selector, action) => {
    const form = document.querySelector(selector);

    form &&
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const selectedValues = selectCheckedLoads();
            console.log('Selected values:', selectedValues);
            // @ts-ignore
            const formData = new FormData(event.target);

            if (!selectedValues) {
                printMessage('Posts id not select', 'danger', 8000);
                return;
            }

            formData.append('action', action);
            // @ts-ignore
            formData.append('post_ids', selectedValues);

            const options = {
                method: 'POST',
                body: formData,
            };

            // @ts-ignore
            fetch(ajaxUrl, options)
                .then((res) => res.json())
                .then((requestStatus) => {
                    if (requestStatus.success) {
                        printMessage(requestStatus.data.message, 'success', 8000);
                        const container = document.querySelector('.js-update-status');
                        window.location.reload();
                    } else {
                        printMessage(requestStatus.data.message, 'danger', 8000);
                    }
                })
                .catch((error) => {
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                });
        });
};

export const quickEditTrackingStatus = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-save-status');

    forms &&
        forms.forEach((item) => {
            item.addEventListener('submit', (e) => {
                e.preventDefault();

                // @ts-ignore
                const formData = new FormData(e.target);

                formData.append('action', 'quick_update_status');

                const options = {
                    method: 'POST',
                    body: formData,
                };

                // @ts-ignore
                const btn = e.target.querySelector('button');
                btn && btn.setAttribute('disabled', true);

                // @ts-ignore
                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            printMessage(requestStatus.data.message, 'success', 8000);
                            window.location.reload();
                        } else {
                            printMessage(requestStatus.data.message, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            });
        });
};

export const triggerDisableBtnInit = () => {
    const triggers = document.querySelectorAll('.js-trigger-disable-btn');
    const inputs = document.querySelectorAll('.js-disable-container-trigger');
    const saveAll = document.querySelectorAll('.js-save-all-tracking');
    triggers &&
        triggers.forEach((item) => {
            item.addEventListener('change', (e) => {
                e.preventDefault();

                // @ts-ignore
                const form = e.target.closest('form');

                if (form) {
                    const btn = form.querySelector('button');

                    btn.removeAttribute('disabled');

                    saveAll &&
                        saveAll.forEach((itemSaveAll) => {
                            itemSaveAll.classList.remove('d-none');
                        });
                }
            });
        });

    inputs &&
        inputs.forEach((item) => {
            item.addEventListener('input', (e) => {
                e.preventDefault();

                // @ts-ignore
                const form = e.target.closest('.js-disable-container');

                if (form) {
                    const btn = form.querySelector('button');

                    btn.removeAttribute('disabled');
                    saveAll &&
                        saveAll.forEach((itemSaveAll) => {
                            itemSaveAll.classList.remove('d-none');
                        });
                }
            });
        });
};
