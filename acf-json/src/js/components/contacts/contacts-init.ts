// eslint-disable-next-line import/prefer-default-export

// Добавление одного блока
import { printMessage } from '../info-messages';
import { LoadingBtn } from '../common/loading-btn';
import Popup from '../../parts/popup-window';
import { addSearchAction } from '../search-action';
import { telMaskInit } from '../tel-mask';
import { debounce } from '../../parts/helpers';
import { generateCustomerTemplate } from '../common/customer-tamplate';
import { addActionsDeleteUniversalCard, addActionsEditAdditionalCard } from '../create-report';
import { decodeHtmlEntities } from '../common/decode-html-helper';

function addAdditionalContact(container) {
    const row = document.createElement('div');
    row.className = 'additional-contact row g-1 mt-2';
    row.innerHTML = `
    <div class="col">
      <input type="text" name="" class="form-control" placeholder="Name">
    </div>
    <div class="col">
      <input type="text" name="" class="form-control js-tel-mask" placeholder="Phone">
    </div>
    <div class="col-1">
      <input type="text" name="" class="form-control" placeholder="Ext">
    </div>
    <div class="col">
      <input type="email" name="" class="form-control" placeholder="Email">
    </div>
    <div class="col-md-1 d-flex align-items-center">
      <button type="button" class="btn btn-outline-danger btn-sm js-remove-contact" title="Remove">
        &times;
      </button>
    </div>
  `;
    container.appendChild(row);
    telMaskInit();
}

// Обновление всех name атрибутов
function reindexAdditionalContacts(container) {
    const rows = container.querySelectorAll('.additional-contact');
    rows.forEach((row, index) => {
        const inputs = row.querySelectorAll('input');
        if (inputs.length === 4) {
            inputs[0].name = `additional_contacts[${index}][name]`;
            inputs[1].name = `additional_contacts[${index}][phone]`;
            inputs[2].name = `additional_contacts[${index}][ext]`;
            inputs[3].name = `additional_contacts[${index}][email]`;
        }
    });
}

export function initAdditionalContactHandler(formSelector = '.js-add-new-contact') {
    const form = document.querySelector(formSelector);
    if (!form) return;

    const addBtn = form.querySelector('.js-add-contact-btn');
    const container = form.querySelector('.js-additional-contacts');

    if (!addBtn || !container) return;

    addBtn.addEventListener('click', () => {
        addAdditionalContact(container);
        reindexAdditionalContacts(container);
    });

    container.addEventListener('click', (e) => {
        const { target } = e;

        if (!target) return;
        // @ts-ignore
        if (target.classList.contains('js-remove-contact')) {
            // @ts-ignore
            const contactRow = target.closest('.additional-contact');
            if (contactRow) {
                contactRow.remove();
                reindexAdditionalContacts(container);
            }
        }
    });
}

function addNewContact(ajaxUrl) {
    const form = document.querySelector('.js-add-new-contact');
    if (!form) return;

    const addBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        LoadingBtn(addBtn, true);

        const { target } = e;
        // @ts-ignore
        const formData = new FormData(target);
        formData.append('action', 'add_new_contact');

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
                    LoadingBtn(addBtn, false);
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    LoadingBtn(addBtn, false);
                    // eslint-disable-next-line no-alert
                    printMessage(`Error update:${requestStatus.data.message}`, 'danger', 8000);
                }
            })
            .catch((error) => {
                LoadingBtn(addBtn, false);
                printMessage(`Request failed: ${error}`, 'danger', 8000);
                console.error('Request failed:', error);
            });
    });
}

function editContact(ajaxUrl) {
    const form = document.querySelector('.js-edit-contact');
    if (!form) return;

    const addBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        LoadingBtn(addBtn, true);

        const { target } = e;
        // @ts-ignore
        const formData = new FormData(target);
        formData.append('action', 'edit_contact');

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
                    LoadingBtn(addBtn, false);
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    LoadingBtn(addBtn, false);
                    // eslint-disable-next-line no-alert
                    printMessage(`Error update:${requestStatus.data.message}`, 'danger', 8000);
                }
            })
            .catch((error) => {
                LoadingBtn(addBtn, false);
                printMessage(`Request failed: ${error}`, 'danger', 8000);
                console.error('Request failed:', error);
            });
    });
}

export function openEdit(ajaxUrl) {
    const popupInstance = new Popup();
    const editBtns = document.querySelectorAll('.js-open-popup-edit');

    if (editBtns && editBtns.length) {
        editBtns.forEach((btn) => {
            btn.addEventListener('click', (event) => {
                event.preventDefault();

                // @ts-ignore
                const tr = event.target.closest('tr');
                if (!tr) return;

                const form = tr.querySelector('.js-popup-edit-content');
                if (!form) return;

                const content = form.innerHTML;
                const popup = document.querySelector('#popup_contacts_edit');
                if (!popup) return;

                const popupForm = popup.querySelector('form');
                if (!popupForm) return;

                // Очищаем и вставляем контент
                popupForm.innerHTML = '';
                popupForm.innerHTML = content;

                // Открываем попап
                popupInstance.openOnePopup('#popup_contacts_edit');
                initAdditionalContactHandler('.js-edit-contact');
                addSearchAction(ajaxUrl);
                editContact(ajaxUrl);
                telMaskInit();
            });
        });
    }
}
function createContactCard(name, phone, ext, email) {
    return `<div class="additional-card js-additional-card">
        <input type="text" name="additional_contact_name[]" readonly value="${name}" class="form-control js-additional-field-name">
        <input type="text" name="additional_contact_phone[]" readonly value="${phone}" class="form-control js-additional-field-phone">
        <input type="text" name="additional_contact_phone_ext[]" readonly value="${ext}" class="form-control js-additional-field-phone">
        <input type="text" name="additional_contact_email[]" readonly value="${email}" class="form-control js-additional-field-email">
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
}

function setPreset() {
    const clickInit = document.querySelectorAll('.js-preset-click');

    clickInit &&
        clickInit.forEach((item) => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const { target } = e;
                if (!target) return;

                // @ts-ignore
                const oldActive = document.querySelector('.js-preset-click.active');
                oldActive?.classList.remove('active');
                // @ts-ignore
                target.classList.add('active');
                // @ts-ignore
                const presetJsonSearch = target.querySelector('.js-preset-json');
                const jsonStr = presetJsonSearch.innerHTML;
                const jsonObj = JSON.parse(jsonStr);

                const selectedPrest = document.querySelector('input[name="preset-select"]');

                if (selectedPrest) {
                    // @ts-ignore
                    selectedPrest.value = jsonObj.main_id;
                }

                // Контактное имя
                const searchCompany = document.querySelector('.js-search-company');
                const searchCompanyContainer = document.querySelector('.js-result-search');
                const additionalResult = document.querySelector('.js-additional-contact-wrap');
                const additionalContainer = document.querySelector('.js-additional-contact');

                const contactNameInput = document.querySelector('#contact-input-firstname');

                const contactPhoneInput = document.querySelector('#contact-input-phone');

                const contactPhoneExtInput = document.querySelector('#contact-input-phone_ext');

                const contactEmailInput = document.querySelector('#contact-input-email');

                if (contactNameInput && contactPhoneInput && contactPhoneExtInput && contactEmailInput) {
                    // @ts-ignore
                    contactNameInput.value = `${jsonObj.name}` ?? '';
                    // @ts-ignore
                    contactPhoneInput.value = jsonObj.direct_number || jsonObj.office_number || '';
                    // @ts-ignore
                    contactPhoneExtInput.valuцe = `${jsonObj.direct_ext}` ?? '';
                    // @ts-ignore
                    contactEmailInput.value = `${jsonObj.direct_email}` ?? '';
                }
                if (searchCompany && searchCompanyContainer) {
                    const customerData = {
                        id: jsonObj?.id,
                        name: jsonObj?.company_name,
                        address: jsonObj?.address1,
                        mc: jsonObj?.mc_number,
                        dot: jsonObj?.dot_number,
                        contact: `${jsonObj?.contact_first_name} ${jsonObj?.contact_last_name}`,
                        phone: jsonObj?.phone_number,
                        email: jsonObj?.company_email,
                    };
                    // @ts-ignore
                    searchCompany.value = decodeHtmlEntities(jsonObj.company_name) ?? '';
                    searchCompanyContainer.innerHTML = generateCustomerTemplate(customerData);
                }

                const contacts = jsonObj?.additional_contacts;

                const templates = contacts.map((contact) => {
                    const valueName = contact.contact_name || '';
                    const valuePhone = contact.contact_phone || '';
                    const valueEmail = contact.contact_email || '';
                    const valueExt = contact.contact_ext || '';

                    return createContactCard(valueName, valuePhone, valueExt, valueEmail);
                });

                // Добавим support contact если хотя бы одно поле существует
                const supportExists =
                    jsonObj.support_contact || jsonObj.support_phone || jsonObj.support_ext || jsonObj.support_email;

                if (supportExists) {
                    const supportName = jsonObj.support_contact || '';
                    const supportPhone = jsonObj.support_phone || '';
                    const supportExt = jsonObj.support_ext || '';
                    const supportEmail = jsonObj.support_email || '';
                    templates.unshift(createContactCard(supportName, supportPhone, supportExt, supportEmail));
                }

                const finalTemplate = templates.join('');

                if (additionalContainer && additionalResult) {
                    if (templates) {
                        additionalContainer?.classList.remove('d-none');
                        additionalResult.innerHTML = templates;
                        addActionsDeleteUniversalCard('.js-remove-contact', '.js-additional-card');
                        addActionsEditAdditionalCard();
                    } else {
                        additionalContainer?.classList.add('d-none');
                        additionalResult.innerHTML = '';
                    }
                }
            });
        });
}

function removeContact(ajaxUrl) {
    const removeBtns = document.querySelectorAll('.js-remove-contact');

    removeBtns &&
        removeBtns.forEach((item) => {
            item.addEventListener('click', (evt) => {
                evt.preventDefault();
                const { target } = evt;

                if (!target) return;
                // @ts-ignore
                const valueDel = target.dataset.value;
                // eslint-disable-next-line no-alert
                const del = window.confirm('Confirm contact deletion');

                if (del) {
                    const action = 'delete_one_contact';
                    const formData = new FormData();

                    formData.append('action', action);
                    formData.append('id', valueDel);

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
                                LoadingBtn(target, false);
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                // @ts-ignore
                                LoadingBtn(target, false);
                                // eslint-disable-next-line no-alert
                                printMessage(`Error update:${requestStatus.data.message}`, 'danger', 8000);
                            }
                        })
                        .catch((error) => {
                            // @ts-ignore
                            LoadingBtn(target, false);
                            printMessage(`Request failed: ${error}`, 'danger', 8000);
                            console.error('Request failed:', error);
                        });
                }
            });
        });
}

function usePreset(ajaxUrl) {
    const inputsPreset = document.querySelectorAll('.js-use-preset');

    inputsPreset &&
        inputsPreset.forEach((inputSearch) => {
            inputSearch.addEventListener(
                'input',
                debounce((event: Event) => {
                    // @ts-ignore
                    console.log('Search preset', event.target.value);

                    const formData = new FormData();
                    formData.append('action', 'search_contact');
                    // @ts-ignore
                    formData.append('search', event.target.value);

                    const options = {
                        method: 'POST',
                        body: formData,
                    };

                    fetch(ajaxUrl, options)
                        .then((res) => res.json())
                        .then((requestStatus) => {
                            if (requestStatus.success) {
                                console.log('select search', requestStatus);
                                const result = document.querySelector('.js-result-search-preset');
                                // @ts-ignore
                                result.innerHTML = requestStatus.data;
                                setPreset();
                            }
                        })
                        .catch((error) => {
                            console.error('Request failed:', error);
                        });
                }, 1000) as EventListener
            );
        });
}

export function initContactsHandler(ajaxUrl) {
    openEdit(ajaxUrl);
    initAdditionalContactHandler();
    addNewContact(ajaxUrl);
    // eslint-disable-next-line react-hooks/rules-of-hooks
    usePreset(ajaxUrl);
    removeContact(ajaxUrl);
}
