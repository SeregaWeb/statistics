// eslint-disable-next-line import/prefer-default-export

// Добавление одного блока
import { printMessage } from '../info-messages';
import { LoadingBtn } from '../common/loading-btn';
import Popup from '../../parts/popup-window';
import { addSearchAction } from '../search-action';
import { telMaskInit } from '../tel-mask';

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
        if (inputs.length === 3) {
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

export function initContactsHandler(ajaxUrl) {
    openEdit(ajaxUrl);
    initAdditionalContactHandler();
    addNewContact(ajaxUrl);
}
