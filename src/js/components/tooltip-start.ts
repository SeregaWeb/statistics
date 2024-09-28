import { Tooltip } from 'bootstrap';

export const updateTooltip = () => {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');

    if (tooltipTriggerList.length) {
        tooltipTriggerList.forEach((tooltipTriggerEl) => {
            new Tooltip(tooltipTriggerEl);
        });
    }
};
