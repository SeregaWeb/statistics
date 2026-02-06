/**
 * Utility helpers for file delete confirmation across different modules.
 */

/**
 * Returns value of hidden input image-fields inside given container (form or div).
 */
export const getImageFieldsValue = (container: Element): string => {
    const input = container.querySelector<HTMLInputElement>('input[name="image-fields"]');
    return (input?.value?.trim() ?? '') || '';
};

/**
 * Returns label text (file name) from container using provided selector.
 * By default it looks for element with class .required-label.
 */
export const getFileLabelFromContainer = (
    container: Element,
    selector: string = '.required-label',
): string => {
    const label = container.querySelector(selector);
    return (label?.textContent?.trim() ?? '') || '';
};

/**
 * Builds a human-readable confirmation message for delete action.
 */
export const getDeleteConfirmMessage = (container: Element): string => {
    const fileName = getFileLabelFromContainer(container);
    return fileName ? `Confirm delete file: ${fileName}?` : 'Confirm delete file?';
};

/**
 * Checks if current container's image-fields value is included in provided list.
 */
export const shouldConfirmDelete = (
    container: Element,
    fieldsRequiringConfirm: Set<string> | string[],
): boolean => {
    const fieldsSet =
        fieldsRequiringConfirm instanceof Set ? fieldsRequiringConfirm : new Set(fieldsRequiringConfirm);
    const fieldValue = getImageFieldsValue(container);
    return fieldsSet.has(fieldValue);
};

/**
 * Runs confirmation dialog if container's image-fields is in provided list.
 * Returns true if deletion should proceed, false if it should be cancelled.
 */
export const confirmDeleteIfNeeded = (
    container: Element,
    fieldsRequiringConfirm: Set<string> | string[],
): boolean => {
    if (!shouldConfirmDelete(container, fieldsRequiringConfirm)) {
        return true;
    }

    return window.confirm(getDeleteConfirmMessage(container));
};

