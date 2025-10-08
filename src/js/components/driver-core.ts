// eslint-disable-next-line import/prefer-default-export
import * as url from 'node:url';
import { printMessage } from './info-messages';
import { setUpTabInUrl } from './create-report';
import { disabledBtnInForm } from './disabled-btn-in-form';
import Popup from '../parts/popup-window';

export const createDriver = (urlAjax) => {
    const form = document.querySelector('.js-create-driver');
    console.log('form', form);
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        console.log('form click');

        const target = e.target as HTMLFormElement;
        disabledBtnInForm(target);

        const formData = new FormData(target);
        formData.append('action', 'add_driver');

        const options = {
            method: 'POST',
            body: formData,
        };

        fetch(urlAjax, options)
            .then((res) => res.json())
            .then((requestStatus) => {
                if (requestStatus.success) {
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.set('driver', requestStatus.data.id_driver);
                    window.location.href = newUrl.toString();
                    return true;
                }
                printMessage(`${requestStatus.data.message}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                // eslint-disable-next-line consistent-return
                return false;
            })
            .catch((error) => {
                printMessage(`'Request failed' ${error}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                return false;
            });
    });
};

export const updateDriverContact = (urlAjax) => {
    const form = document.querySelector('.js-update-driver');
    console.log('form', form);
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        console.log('form click');

        const target = e.target as HTMLFormElement;
        disabledBtnInForm(target);

        const formData = new FormData(target);
        formData.append('action', 'update_driver_contact');

        const options = {
            method: 'POST',
            body: formData,
        };

        const nextTargetTab = 'pills-driver-vehicle-tab';

        fetch(urlAjax, options)
            .then((res) => res.json())
            .then((requestStatus) => {
                if (requestStatus.success) {
                    setUpTabInUrl(nextTargetTab);
                    disabledBtnInForm(target, true);
                    return true;
                }
                printMessage(`${requestStatus.data.message}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                // eslint-disable-next-line consistent-return
                return false;
            })
            .catch((error) => {
                disabledBtnInForm(target, true);
                printMessage(`'Request failed' ${error}`, 'danger', 8000);
                return false;
            });
    });
};

export const updateDriverInformation = (urlAjax) => {
    const form = document.querySelector('.js-update-driver-information');
    console.log('form', form);
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        console.log('form click');

        const target = e.target as HTMLFormElement;
        disabledBtnInForm(target);

        const formData = new FormData(target);
        formData.append('action', 'update_driver_information');

        const options = {
            method: 'POST',
            body: formData,
        };

        const nextTargetTab = 'pills-driver-finance-tab';

        fetch(urlAjax, options)
            .then((res) => res.json())
            .then((requestStatus) => {
                if (requestStatus.success) {
                    setUpTabInUrl(nextTargetTab);
                    disabledBtnInForm(target, true);
                    return true;
                }
                printMessage(`${requestStatus.data.message}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                // eslint-disable-next-line consistent-return
                return false;
            })
            .catch((error) => {
                printMessage(`'Request failed' ${error}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                return false;
            });
    });
};
export const updateDriverFinance = (urlAjax) => {
    const form = document.querySelector('.js-driver-finance-form');
    console.log('form', form);
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        console.log('form click');

        const target = e.target as HTMLFormElement;
        disabledBtnInForm(target);

        const formData = new FormData(target);
        formData.append('action', 'update_driver_finance');

        const options = {
            method: 'POST',
            body: formData,
        };

        const nextTargetTab = 'pills-driver-documents-tab';

        fetch(urlAjax, options)
            .then((res) => res.json())
            .then((requestStatus) => {
                if (requestStatus.success) {
                    setUpTabInUrl(nextTargetTab);
                    disabledBtnInForm(target, true);
                    return true;
                }
                printMessage(`${requestStatus.data.message}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                // eslint-disable-next-line consistent-return
                return false;
            })
            .catch((error) => {
                printMessage(`'Request failed' ${error}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                return false;
            });
    });
};

export const updateDriverDocument = (urlAjax) => {
    const form = document.querySelector('.js-driver-document-form');
    console.log('form', form);
    if (!form) return;

    form.addEventListener('submit', (e: Event) => {
        e.preventDefault();
        console.log('form click');

        const target = e.target as HTMLFormElement;
        disabledBtnInForm(target);

        const formData = new FormData(target);
        formData.append('action', 'update_driver_document');

        const options = {
            method: 'POST',
            body: formData,
        };

        const nextTargetTab = 'pills-driver-documents-tab';

        fetch(urlAjax, options)
            .then((res) => res.json())
            .then((requestStatus) => {
                if (requestStatus.success) {
                    setUpTabInUrl(nextTargetTab);
                    disabledBtnInForm(target, true);
                    return true;
                }
                printMessage(`${requestStatus.data.message}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                // eslint-disable-next-line consistent-return
                return false;
            })
            .catch((error) => {
                printMessage(`'Request failed' ${error}`, 'danger', 8000);
                disabledBtnInForm(target, true);
                return false;
            });
    });
};

export const removeOneFileInitial = (ajaxUrl) => {
    const deleteForms = document.querySelectorAll('.js-remove-one-driver');
    const deleteFormsNoFormBtn = document.querySelectorAll('.js-remove-one-no-form-btn');

    // Handle form-based deletion (existing functionality)
    deleteForms &&
        deleteForms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;
                // @ts-ignore
                disabledBtnInForm(target);
                // @ts-ignore
                const formData = new FormData(target);
                const action = 'delete_open_image_driver';

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
                            // @ts-ignore
                            disabledBtnInForm(target, true);
                            // @ts-ignore
                            setUpTabInUrl(target.dataset.tab);
                        } else {
                            // @ts-ignore
                            disabledBtnInForm(target, true);
                            printMessage(`Error adding report:${requestStatus.data.message}`, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        // @ts-ignore
                        disabledBtnInForm(target, true);
                        console.error('Request failed:', error);
                    });
            });
        });

    // Handle div-based deletion (new functionality)
    deleteFormsNoFormBtn &&
        deleteFormsNoFormBtn.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                
                // Find the parent div with data
                const parentDiv = button.closest('.js-remove-one-no-form');
                if (!parentDiv) return;

                // Disable the button (cast to HTMLButtonElement for type safety)
                (button as HTMLButtonElement).disabled = true;

                // Collect data from hidden inputs in the div
                const formData = new FormData();
                const hiddenInputs = parentDiv.querySelectorAll<HTMLInputElement>('input[type="hidden"]');
                
                hiddenInputs.forEach((input) => {
                    formData.append(input.name, input.value);
                });

                const action = 'delete_open_image_driver';
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
                            // Re-enable button (cast to HTMLButtonElement for type safety)
                            (button as HTMLButtonElement).disabled = false;
                            // Update tab in URL if data-tab exists
                            if ((parentDiv as HTMLElement).dataset && (parentDiv as HTMLElement).dataset.tab) {
                                // @ts-ignore
                                setUpTabInUrl((parentDiv as HTMLElement).dataset.tab);
                            }
                            // Re-enable button
                            (button as HTMLButtonElement).disabled = false;
                        } else {
                            (button as HTMLButtonElement).disabled = false;
                            printMessage(requestStatus.data.message, 'danger', 8000);
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        // Re-enable button
                        (button as HTMLButtonElement).disabled = false;
                        console.error('Request failed:', error);
                    });
            });
        });
};

export const updateStatusDriver = (ajaxUrl) => {
    const btns = document.querySelectorAll('.js-update-driver-status');
    btns &&
        btns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();

                const { target } = event;

                // @ts-ignore
                const formData = new FormData();
                const action = 'update_driver_status';

                const postId = document.querySelector('.js-post-id');

                if (!postId) {
                    printMessage('Driver id not found', 'danger', 8000);
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

                            setTimeout(() => {
                                window.location.reload();
                            }, 4000);

                            const container = document.querySelector('.js-update-status');

                            if (!container) return;
                            container.innerHTML = '';

                            setUpTabInUrl('pills-driver-contact-tab');
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

export const helperDisabledChecbox = () => {
    const checkboxes = document.querySelectorAll('.js-disable-with-logic');

    checkboxes.forEach((item) => {
        item.addEventListener('change', (evt) => {
            const { target } = evt as Event & { target: HTMLInputElement };
            const container = target.closest('.js-container-checkboxes');

            if (!container) return;

            const allCheckboxes = container.querySelectorAll<HTMLInputElement>('input[type="checkbox"]');
            const mainCheckbox = Array.from(allCheckboxes).find((cb) => cb.value === cb.getAttribute('data-value'));
            console.log('mainCheckbox', mainCheckbox);
            if (!mainCheckbox) return;

            // Если главный чекбокс выбран
            if (mainCheckbox.checked) {
                // Дизейблим все кроме главного
                allCheckboxes.forEach((cb) => {
                    if (cb !== mainCheckbox) {
                        // eslint-disable-next-line no-param-reassign
                        cb.disabled = true;
                        // eslint-disable-next-line no-param-reassign
                        cb.checked = true;
                    }
                });
            } else {
                allCheckboxes.forEach((cb) => {
                    if (cb !== mainCheckbox) {
                        // eslint-disable-next-line no-param-reassign
                        cb.disabled = false;
                    }
                });
            }

            if (target !== mainCheckbox) {
                const others = Array.from(allCheckboxes).filter((cb) => cb !== mainCheckbox);
                const allOthersChecked = others.every((cb) => cb.checked);

                if (allOthersChecked) {
                    mainCheckbox.checked = true;
                    allCheckboxes.forEach((cb) => {
                        if (cb !== mainCheckbox) {
                            // eslint-disable-next-line no-param-reassign
                            cb.checked = true;
                            // eslint-disable-next-line no-param-reassign
                            cb.disabled = true;
                        }
                    });
                }
            }
        });
    });
};

export const removeFullDriver = (ajaxUrl) => {
    const btnsRemove = document.querySelectorAll('.js-remove-driver');

    btnsRemove &&
        btnsRemove.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();

                const { target } = event;

                const question = confirm(
                    'Are you sure you want to delete this driver? \nIf you agree it will be deleted permanently'
                );

                if (target instanceof HTMLElement && question) {
                    const idLoad = target.getAttribute('data-id');

                    if (!idLoad) {
                        printMessage(`Error remove Load: reload this page and try again`, 'danger', 8000);
                        return;
                    }

                    const action = 'remove_one_driver';

                    const formData = new FormData();

                    formData.append('action', action);
                    formData.append('id_driver', idLoad);

                    const options = {
                        method: 'POST',
                        body: formData,
                    };

                    fetch(ajaxUrl, options)
                        .then((res) => res.json())
                        .then((requestStatus) => {
                            if (requestStatus.success) {
                                console.log('Driver remove successfully:', requestStatus.data);
                                const contain = target.closest('tr');

                                if (contain) {
                                    contain.remove();
                                }
                                printMessage(requestStatus.data.message, 'success', 8000);
                            } else {
                                // eslint-disable-next-line no-alert
                                printMessage(`Error remove Driver:${requestStatus.data.message}`, 'danger', 8000);
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

export const uploadFileDriver = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-upload-driver-helper');

    forms.forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const action = 'upload_driver_helper';
            const popupInstance = new Popup();
            const formData = new FormData(e.target as HTMLFormElement);

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

                        // @ts-ignore
                        const mainPopup = e.target.closest('.js-upload-popup');

                        if (mainPopup) {
                            const { id } = mainPopup;
                            const searchBtn = document.querySelector<HTMLButtonElement>(`button[data-href="#${id}"]`);

                            if (searchBtn) {
                                searchBtn.textContent = 'Uploaded!';
                                searchBtn.disabled = true;
                            }
                        }

                        popupInstance.forceCloseAllPopup();
                    } else {
                        // eslint-disable-next-line no-alert
                        printMessage(`Error upload file: ${requestStatus.data.message}`, 'danger', 8000);
                    }
                })
                .catch((error) => {
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                });
        });
    });
};

export const copyText = () => {
    const buttons = document.querySelectorAll('.js-copy-text');

    buttons.forEach((button) => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const target = e.target as HTMLElement;
            const textToCopy = target.getAttribute('data-text');

            if (!textToCopy) return;

            navigator.clipboard
                .writeText(textToCopy)
                .then(() => {
                    printMessage('Text copied to clipboard!', 'success', 3000);
                    target.textContent = 'Copied!';
                    target.classList.add('btn-success');
                    target.classList.remove('btn-outline-primary');
                    target.setAttribute('disabled', 'disabled');

                    setTimeout(() => {
                        target.textContent = 'Copy';
                        target.classList.add('btn-outline-primary');
                        target.classList.remove('btn-success');
                        target.removeAttribute('disabled');
                    }, 3000);
                })
                .catch((err) => {
                    printMessage(`Failed to copy text: ${err}`, 'danger', 3000);
                });
        });
    });
};
export const driversActions = (urlAjax) => {
    createDriver(urlAjax);
    removeFullDriver(urlAjax);
    updateDriverContact(urlAjax);
    updateDriverInformation(urlAjax);
    updateDriverFinance(urlAjax);
    updateDriverDocument(urlAjax);
    removeOneFileInitial(urlAjax);
    updateStatusDriver(urlAjax);
    uploadFileDriver(urlAjax);
    copyText();

    helperDisabledChecbox();
};

/**
 * Driver Core Functionality
 * Handles rating and notice functionality for driver statistics
 */

export const driverCoreInit = (urlAjax) => {
    console.log('driverCoreInit called with urlAjax:', urlAjax);
    // Rating functionality
    const ratingBtns = document.querySelectorAll('.rating-btn') as NodeListOf<HTMLButtonElement>;
    const selectedRatingInput = document.getElementById('selectedRating') as HTMLInputElement;
    
    if (ratingBtns.length > 0 && selectedRatingInput) {
        ratingBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating || '0');
                
                // Function to get button color based on rating
                const getRatingBtnColor = (value: number): string => {
                    if (value <= 1) {
                        return 'btn-outline-danger';
                    }
                    if (value <= 4) {
                        return 'btn-outline-warning';
                    }
                    if (value > 4) {
                        return 'btn-outline-success';
                    }
                    return 'btn-outline-secondary';
                };
                
                const getActiveBtnColor = (value: number): string => {
                    if (value <= 1) {
                        return 'btn-danger';
                    }
                    if (value <= 4) {
                        return 'btn-warning';
                    }
                    if (value > 4) {
                        return 'btn-success';
                    }
                    return 'btn-secondary';
                };
                
                // Reset all buttons to their original outline colors
                ratingBtns.forEach(b => {
                    const bRating = parseInt(b.dataset.rating || '0');
                    b.className = `btn ${getRatingBtnColor(bRating)} rating-btn`;
                });
                
                // Set clicked button to active state
                this.className = `btn ${getActiveBtnColor(rating)} rating-btn`;
                
                // Set selected rating
                selectedRatingInput.value = rating.toString();
            });
        });
    }
    
    // Rating form submission - moved to driver-popup-forms.ts
    
    // Notice form submission - moved to driver-popup-forms.ts
    
    // Notice status checkbox functionality
    const noticeCheckboxes = document.querySelectorAll('.notice-status-checkbox') as NodeListOf<HTMLInputElement>;
    noticeCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const noticeId = this.dataset.noticeId;
            if (!noticeId) return;
            
            const formData = new FormData();
            formData.append('action', 'update_notice_status');
            formData.append('notice_id', noticeId);
            
            // Get nonce from the page
            const nonceElement = document.querySelector('input[name="tms_notice_status_nonce"]') as HTMLInputElement;
            if (nonceElement) {
                formData.append('tms_notice_status_nonce', nonceElement.value);
            }
            
            fetch(urlAjax, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then((data: any) => {
                if (data.success) {
                    // Toggle row background color
                    const row = this.closest('tr');
                    if (row) {
                        if (this.checked) {
                            row.classList.add('table-success');
                        } else {
                            row.classList.remove('table-success');
                        }
                    }
                } else {
                    // Revert checkbox state on error
                    this.checked = !this.checked;
                    printMessage('Error: ' + data.data, 'danger', 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.checked = !this.checked;
                printMessage('An error occurred while updating the notice status.', 'danger', 3000);
            });
        });
    });
    
    // Update background date button functionality
    const updateBackgroundDateBtn = document.querySelectorAll('.js-update-background-date') as NodeListOf<HTMLButtonElement>;
    if (updateBackgroundDateBtn.length > 0) {
        updateBackgroundDateBtn.forEach(btn => {
            btn.addEventListener('click', function(e) {
            const driverId = document.querySelector('input[name="driver_id"]') as HTMLInputElement;
            if (!driverId || !driverId.value) {
                printMessage('Driver ID not found', 'danger', 3000);
                return;
            }
            
            const checkbox = document.querySelector('input[name="background_check"]') as HTMLInputElement;
            if (!checkbox) {
                printMessage('Background check checkbox not found', 'danger', 3000);
                return;
            }

            const target = e.target as HTMLButtonElement;
            
            
            const formData = new FormData();
            formData.append('action', 'update_background_check_date'); 
            formData.append('driver_id', driverId.value);
            formData.append('checkbox_status', checkbox.checked ? 'on' : '');

            const isTeamDriver = target.classList.contains('js-team-driver');

            if (isTeamDriver) {
                const checkboxTeamDriver = document.querySelector('input[name="background_check_team_driver"]') as HTMLInputElement;
                if (!checkboxTeamDriver) {
                    printMessage('Background check checkbox not found', 'danger', 3000);
                    return;
                }

                formData.append('checkbox_status_team_driver', checkboxTeamDriver.checked ? 'on' : '');
                formData.append('team_driver', '1');
            }
            
            fetch(urlAjax, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then((data: any) => {
                if (data.success) {
                    printMessage(data.data.message, 'success', 3000);
                    
                    // Update the date input field


                    if (isTeamDriver) {
                        const dateInput = document.querySelector('input[name="background_date_team_driver"]') as HTMLInputElement;
                        if (dateInput) {
                            dateInput.value = data.data.date;
                        }
                    } else {    
                        const dateInput = document.querySelector('input[name="background_date"]') as HTMLInputElement;
                        if (dateInput) {
                            dateInput.value = data.data.date;
                        }
                    }
                } else {
                    printMessage(data.data.message, 'danger', 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                printMessage('An error occurred while updating background check date.', 'danger', 3000);
            });
            });
        });
    }
    
    // Update only date button functionality
    const updateOnlyDateBtn = document.querySelector('.js-update-only-date') as HTMLButtonElement;
    if (updateOnlyDateBtn) {
        updateOnlyDateBtn.addEventListener('click', function() {
            const driverId = document.querySelector('input[name="driver_id"]') as HTMLInputElement;
            if (!driverId || !driverId.value) {
                printMessage('Driver ID not found', 'danger', 3000);
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'update_driver_zipcode_date'); 
            formData.append('driver_id', driverId.value);
            
            fetch(urlAjax, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then((data: any) => {
                if (data.success) {
                    printMessage(data.data.message, 'success', 3000);
                    
                    // Update the date display in the UI
                    const dateDisplay = document.querySelector('.js-update-only-date')?.closest('.d-flex')?.querySelector('p');
                    if (dateDisplay) {
                        const lines = dateDisplay.innerHTML.split('<br>');
                        if (lines.length >= 2) {
                            lines[1] = `last update ${data.data.date}`;
                            dateDisplay.innerHTML = lines.join('<br>');
                        }
                    }
                } else {
                    printMessage(data.data.message, 'danger', 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                printMessage('An error occurred while updating driver zipcode date.', 'danger', 3000);
            });
        });
    }
};

