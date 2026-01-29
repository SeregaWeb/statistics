import { Tooltip } from 'bootstrap';

export const updateTooltip = () => {
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');

    if (tooltipTriggerList.length) {
        tooltipTriggerList.forEach((tooltipTriggerEl) => {
            // Enable HTML so we can use <br> for newlines
            new Tooltip(tooltipTriggerEl as HTMLElement, {
                html: true,
            });
        });
    }
};
