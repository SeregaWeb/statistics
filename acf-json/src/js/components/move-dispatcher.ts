import { printMessage } from './info-messages';

// eslint-disable-next-line import/prefer-default-export
export const moveDispatcher = (ajaxurl: string) => {
    const moveFromRadios = document.querySelectorAll<HTMLInputElement>('input[name="move-from"]');
    const moveToRadios = document.querySelectorAll<HTMLInputElement>('input[name="move-to"]');
    const dispatcherSections = document.querySelectorAll<HTMLElement>('.dispatcher-section');
    const dispatcherCheckboxes = document.querySelectorAll<HTMLInputElement>('input[name="dispatcher[]"]');
    const weekendExclusions = document.querySelectorAll<HTMLElement>('.weekend-exclusions');

    function updateDispatcherOptions() {
        const selectedRadio = document.querySelector<HTMLInputElement>('input[name="move-from"]:checked');
        if (selectedRadio) {
            const teamData = JSON.parse(selectedRadio.getAttribute('data-team') || '[]');
            const teamArray: (string | number)[] = Array.isArray(teamData) ? teamData : Object.values(teamData || {});
            dispatcherSections.forEach((section) => {
                // eslint-disable-next-line no-param-reassign
                section.style.display = 'none';
            });
            dispatcherSections.forEach((section) => {
                const dispatcherOption = section.querySelector<HTMLElement>('.dispatcher-option');
                if (!dispatcherOption) return;
                const userId = dispatcherOption.getAttribute('data-user-id') || '';
                const userIdNumber = userId.replace('user_', '');

                console.log(userIdNumber, userIdNumber);

                if (teamArray.includes(parseInt(userIdNumber, 10))) {
                    // eslint-disable-next-line no-param-reassign
                    section.style.display = 'block';
                }
            });
            document.querySelectorAll<HTMLInputElement>('input[name="dispatcher[]"]').forEach((checkbox) => {
                // eslint-disable-next-line no-param-reassign
                checkbox.checked = false;
            });
            // eslint-disable-next-line no-use-before-define
            hideAllWeekendExclusions();

            // --- AJAX: получить выходные выбранного пользователя и обновить чекбоксы ---
            const moveFromUserId = selectedRadio.value;
            const formData = new FormData();
            formData.append('action', 'get_dispatchers_weekends');
            formData.append('user_id', moveFromUserId);
            fetch(ajaxurl, {
                method: 'POST',
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        const weekends = data.data;
                        document.querySelectorAll<HTMLInputElement>('.exclude-checkbox').forEach((cb) => {
                            // eslint-disable-next-line no-param-reassign
                            cb.checked = false;
                        });
                        Object.entries(weekends).forEach(([day, ids]) => {
                            (ids as string[]).forEach((id) => {
                                const cb = document.querySelector<HTMLInputElement>(
                                    `.exclude-checkbox[data-user-id="user_${id}"][data-day="${day}"]`
                                );
                                if (cb) cb.checked = true;
                            });
                        });
                    }
                });
            // --- END AJAX ---
        } else {
            dispatcherSections.forEach((section) => {
                // eslint-disable-next-line no-param-reassign
                section.style.display = 'none';
            });
            // eslint-disable-next-line no-use-before-define
            hideAllWeekendExclusions();
        }
        // eslint-disable-next-line no-use-before-define
        updateMoveToRadios();
    }

    function updateMoveToRadios() {
        const selectedMoveFrom = document.querySelector<HTMLInputElement>('input[name="move-from"]:checked');
        const selectedId = selectedMoveFrom ? selectedMoveFrom.value : null;
        const selectedDispatchers = Array.from(document.querySelectorAll<HTMLInputElement>('input[name="dispatcher[]"]:checked')).map(cb => cb.value);

        moveToRadios.forEach((radio) => {
            if (radio.value === selectedId) {
                // eslint-disable-next-line no-param-reassign
                radio.disabled = true;
                // eslint-disable-next-line no-param-reassign
                if (radio.checked) radio.checked = false;
            } else {
                // eslint-disable-next-line no-param-reassign
                radio.disabled = false;
            }
        });
    }

    function showWeekendExclusions(selectedDispatcherCheckbox: HTMLInputElement) {
        const dispatcherSection = selectedDispatcherCheckbox.closest('.dispatcher-section');
        if (!dispatcherSection) return;
        const exclusionsDiv = dispatcherSection.querySelector<HTMLElement>('.weekend-exclusions');
        if (exclusionsDiv) {
            exclusionsDiv.style.display = 'block';
        }
    }

    function hideWeekendExclusions(selectedDispatcherCheckbox: HTMLInputElement) {
        const dispatcherSection = selectedDispatcherCheckbox.closest('.dispatcher-section');
        if (!dispatcherSection) return;
        const exclusionsDiv = dispatcherSection.querySelector<HTMLElement>('.weekend-exclusions');
        if (exclusionsDiv) {
            exclusionsDiv.style.display = 'none';
        }
    }

    function hideAllWeekendExclusions() {
        weekendExclusions.forEach((exclusion) => {
            // eslint-disable-next-line no-param-reassign
            exclusion.style.display = 'none';
        });
    }

    moveFromRadios.forEach((radio) => {
        radio.addEventListener('change', () => {
            updateDispatcherOptions();
        });
    });
    dispatcherCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', function (this: HTMLInputElement) {
            if (this.checked) {
                showWeekendExclusions(this);
            } else {
                hideWeekendExclusions(this);
            }
            // Обновляем move-to при изменении выбора диспетчеров
            updateMoveToRadios();
        });
    });

    const saveAllWeekendsBtn = document.getElementById('save-all-weekends-btn');
    if (saveAllWeekendsBtn) {
        saveAllWeekendsBtn.addEventListener('click', function (event) {
            // Собираем все чекбоксы
            const target = event.target as HTMLElement;
            target.setAttribute('disabled', 'disabled');
            const form = target.closest('form');
            if (!form) return;
            const formData = new FormData(form as HTMLFormElement);
            formData.append('action', 'debug_save_weekends');
            fetch(ajaxurl, {
                method: 'POST',
                body: formData,
            })
                .then((res) => res.json())
                .then((requestStatus) => {
                    if (requestStatus.success) {
                        printMessage(requestStatus.data.message, 'success', 8000);
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        if (target) {
                            target.removeAttribute('disabled');
                        }

                        printMessage(requestStatus.data.message, 'danger', 8000);
                    }
                })
                .catch((error) => {
                    printMessage(`Request failed: ${error}`, 'danger', 8000);
                    console.error('Request failed:', error);
                });
        });
    }
};
