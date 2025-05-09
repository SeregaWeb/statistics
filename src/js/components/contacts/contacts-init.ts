// eslint-disable-next-line import/prefer-default-export

// Добавление одного блока
import { printMessage } from '../info-messages';
import { LoadingBtn } from '../common/loading-btn';

function addAdditionalContact(container) {
    const row = document.createElement('div');
    row.className = 'additional-contact row g-1 mt-2';
    row.innerHTML = `
    <div class="col">
      <input type="text" name="" class="form-control" placeholder="Name">
    </div>
    <div class="col">
      <input type="text" name="" class="form-control" placeholder="Phone">
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
}

// Обновление всех name атрибутов
function reindexAdditionalContacts(container) {
    const rows = container.querySelectorAll('.additional-contact');
    rows.forEach((row, index) => {
        const inputs = row.querySelectorAll('input');
        if (inputs.length === 3) {
            inputs[0].name = `additional_contacts[${index}][name]`;
            inputs[1].name = `additional_contacts[${index}][phone]`;
            inputs[2].name = `additional_contacts[${index}][email]`;
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

export function initContactsHandler(ajaxUrl) {
    initAdditionalContactHandler();
    addNewContact(ajaxUrl);
}
