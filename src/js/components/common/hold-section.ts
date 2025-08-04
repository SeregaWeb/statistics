export const holdSectionInit = () => {
    const toggleButton = document.querySelector('.js-toggle-hold-section');
    
    if (!toggleButton) return;
    
    toggleButton.addEventListener('click', () => {
        const section = document.querySelector('.hold-drivers-section');
        const content = section?.querySelector('.hold-section-content');
        
        if (!section || !content) return;
        
        // Toggle visibility of content instead of hiding entire section
        content.classList.toggle('d-none');
        
        // Update button text
        const buttonText = toggleButton.querySelector('.button-text');
        if (buttonText) {
            buttonText.textContent = content.classList.contains('d-none') ? 'Show' : 'Hide';
        }
    });
}; 