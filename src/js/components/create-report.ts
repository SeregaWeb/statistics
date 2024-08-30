// eslint-disable-next-line import/prefer-default-export
import Popup from '../parts/popup-window';

export const actionCreateReportInit = (ajaxUrl) => {
    const forms = document.querySelectorAll('.js-add-new-report');
    const popupInstance = new Popup();

    forms &&
        forms.forEach((item) => {
            item.addEventListener('submit', (event) => {
                event.preventDefault();
                const { target } = event;
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
                            console.log('Report added successfully:', requestStatus.data);
                            popupInstance.forceCloseAllPopup();
                        } else {
                            // eslint-disable-next-line no-alert
                            alert(`Error adding report:${requestStatus.data.message}`);
                        }
                    })
                    .catch((error) => {
                        console.error('Request failed:', error);
                    });
            });
        });
};

function updateFileInput(inputElement: HTMLInputElement, filesArray: File[]) {
    const dataTransfer = new DataTransfer();
    filesArray.forEach((file) => dataTransfer.items.add(file));

    if (!inputElement.files) return;
    // eslint-disable-next-line no-param-reassign
    inputElement.files = dataTransfer.files;
}

export const previewFileUpload = () => {
    const controlUploads = document.querySelectorAll('.js-control-uploads');

    // Create an array to store all selected files
    const allFiles: File[] = [];

    controlUploads &&
        controlUploads.forEach((control) => {
            control.addEventListener('change', function (event) {
                const target = event.target as HTMLInputElement;
                if (!target || !target.files) return;

                const container = target.closest('.js-add-new-report');
                const previewContainer = container?.querySelector('.js-preview-photo-upload');

                if (!previewContainer) return;

                // Add new files to the allFiles array
                Array.from(target.files).forEach((file) => {
                    allFiles.push(file);
                });

                updateFileInput(target, allFiles); // Update input with combined files

                // Clear previous previews and render new ones
                previewContainer.innerHTML = '';
                allFiles.forEach((file, index) => {
                    const fileReader = new FileReader();
                    const fileWrapper = document.createElement('div');
                    fileWrapper.classList.add('file-preview');

                    // Add delete button
                    const deleteButton = document.createElement('button');
                    deleteButton.textContent = 'Delete';
                    deleteButton.type = 'button';
                    deleteButton.classList.add('btn', 'btn-danger', 'btn-sm', 'file-delete-btn');

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
                            \t width="80px" height="80px" viewBox="0 0 64 64" enable-background="new 0 0 64 64" xml:space="preserve">
                            <g>
                            \t<path fill="#231F20" d="M56,0H8C5.789,0,4,1.789,4,4v56c0,2.211,1.789,4,4,4h48c2.211,0,4-1.789,4-4V4C60,1.789,58.211,0,56,0z
                            \t\t M58,60c0,1.104-0.896,2-2,2H8c-1.104,0-2-0.896-2-2V4c0-1.104,0.896-2,2-2h48c1.104,0,2,0.896,2,2V60z"/>
                            \t<path fill="#231F20" d="M49,25H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,25,49,25z"/>
                            \t<path fill="#231F20" d="M49,19H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,19,49,19z"/>
                            \t<path fill="#231F20" d="M49,37H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,37,49,37z"/>
                            \t<path fill="#231F20" d="M49,43H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,43,49,43z"/>
                            \t<path fill="#231F20" d="M49,49H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,49,49,49z"/>
                            \t<path fill="#231F20" d="M49,31H15c-0.553,0-1,0.447-1,1s0.447,1,1,1h34c0.553,0,1-0.447,1-1S49.553,31,49,31z"/>
                            \t<path fill="#231F20" d="M15,15h16c0.553,0,1-0.447,1-1s-0.447-1-1-1H15c-0.553,0-1,0.447-1,1S14.447,15,15,15z"/>
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
            });
        });
};
