/**
 * Auto-submit form on select change
 * Universal function that can be applied to any form
 * 
 * @param formSelector - CSS selector for the form element
 */
export const initAutoSubmitForm = (formSelector: string): void => {
    const forms = document.querySelectorAll<HTMLFormElement>(formSelector);
    
    forms.forEach((form) => {
        // Find all select elements within the form
        const selects = form.querySelectorAll<HTMLSelectElement>('select');
        
        selects.forEach((select) => {
            // Add change event listener to each select
            select.addEventListener('change', () => {
                // Submit the form automatically
                form.submit();
            });
        });
    });
};

