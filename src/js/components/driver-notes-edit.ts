/**
 * Driver Notes Edit Component
 * Handles inline editing of driver notes in the documents table
 */

export const initDriverNotesEdit = (urlAjax: string) => {
    // Handle Edit button click
    document.addEventListener('click', (e) => {
        const editBtn = (e.target as HTMLElement).closest('.js-edit-notes') as HTMLButtonElement | null;
        if (editBtn) {
            e.preventDefault();
            const cell = editBtn.closest('.js-driver-notes-cell') as HTMLElement | null;
            if (!cell) return;

            const displayDiv = cell.querySelector('.js-driver-notes-display') as HTMLElement | null;
            const editDiv = cell.querySelector('.js-driver-notes-edit') as HTMLElement | null;
            const textarea = cell.querySelector('.js-notes-textarea') as HTMLTextAreaElement | null;

            if (displayDiv && editDiv && textarea) {
                displayDiv.classList.add('d-none');
                editDiv.classList.remove('d-none');
                textarea.focus();
            }
        }
    });

    // Handle Cancel button click
    document.addEventListener('click', (e) => {
        const cancelBtn = (e.target as HTMLElement).closest('.js-cancel-notes') as HTMLButtonElement | null;
        if (cancelBtn) {
            e.preventDefault();
            const cell = cancelBtn.closest('.js-driver-notes-cell') as HTMLElement | null;
            if (!cell) return;

            const displayDiv = cell.querySelector('.js-driver-notes-display') as HTMLElement | null;
            const editDiv = cell.querySelector('.js-driver-notes-edit') as HTMLElement | null;
            const textarea = cell.querySelector('.js-notes-textarea') as HTMLTextAreaElement | null;

            if (displayDiv && editDiv && textarea) {
                // Restore original value
                const originalNotes = cell.getAttribute('data-original-notes') || '';
                textarea.value = originalNotes;
                displayDiv.classList.remove('d-none');
                editDiv.classList.add('d-none');
            }
        }
    });

    // Handle Save button click
    document.addEventListener('click', (e) => {
        const saveBtn = (e.target as HTMLElement).closest('.js-save-notes') as HTMLButtonElement | null;
        if (saveBtn) {
            e.preventDefault();
            const cell = saveBtn.closest('.js-driver-notes-cell') as HTMLElement | null;
            if (!cell) return;

            const driverId = cell.getAttribute('data-driver-id');
            const textarea = cell.querySelector('.js-notes-textarea') as HTMLTextAreaElement | null;

            if (!driverId || !textarea) return;

            const notes = textarea.value.trim();

            // Disable button during save
            saveBtn.disabled = true;
            saveBtn.textContent = 'Saving...';

            // Save original value for cancel
            if (!cell.getAttribute('data-original-notes')) {
                const displayText = cell.querySelector('.js-driver-notes-display .text-small')?.textContent || '';
                cell.setAttribute('data-original-notes', displayText);
            }

            // AJAX request
            const formData = new FormData();
            formData.append('action', 'update_driver_notes');
            formData.append('driver_id', driverId);
            formData.append('recruiter_notes', notes);

            fetch(urlAjax, {
                method: 'POST',
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        // Update display
                        const displayDiv = cell.querySelector('.js-driver-notes-display') as HTMLElement | null;
                        const editDiv = cell.querySelector('.js-driver-notes-edit') as HTMLElement | null;
                        const flexGrowDiv = displayDiv?.querySelector('.flex-grow-1') as HTMLElement | null;
                        const notesDisplay = cell.querySelector('.js-driver-notes-display .text-small') as HTMLElement | null;

                        if (displayDiv && editDiv && flexGrowDiv) {
                            // Update display content
                            if (data.data.recruiter_notes) {
                                // Create or update notes display element
                                if (notesDisplay) {
                                    notesDisplay.className = 'text-small';
                                    notesDisplay.innerHTML = data.data.recruiter_notes.replace(/\n/g, '<br>');
                                } else {
                                    // Create new element if it doesn't exist
                                    const newNotesDiv = document.createElement('div');
                                    newNotesDiv.className = 'text-small';
                                    newNotesDiv.innerHTML = data.data.recruiter_notes.replace(/\n/g, '<br>');
                                    flexGrowDiv.appendChild(newNotesDiv);
                                }
                            } else {
                                // Remove the display element if notes are empty
                                if (notesDisplay) {
                                    notesDisplay.remove();
                                }
                            }

                            // Switch back to display mode
                            displayDiv.classList.remove('d-none');
                            editDiv.classList.add('d-none');

                            // Update original value
                            cell.setAttribute('data-original-notes', data.data.recruiter_notes || '');
                        }
                    } else {
                        alert('Error saving notes: ' + (data.data?.message || 'Unknown error'));
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('Error saving notes. Please try again.');
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                });
        }
    });

    // Store original notes value on page load for cancel functionality
    document.querySelectorAll('.js-driver-notes-cell').forEach((cell) => {
        const notesDisplay = cell.querySelector('.text-small') as HTMLElement | null;
        if (notesDisplay && !cell.getAttribute('data-original-notes')) {
            const originalText = notesDisplay.textContent || '';
            cell.setAttribute('data-original-notes', originalText);
        } else if (!notesDisplay && !cell.getAttribute('data-original-notes')) {
            // Store empty string if no notes display element exists
            cell.setAttribute('data-original-notes', '');
        }
    });
};

