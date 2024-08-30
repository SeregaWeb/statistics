const initBlockExample = () => {
    const blocks = document.querySelectorAll('.block-example');
    if (blocks) {
        blocks.forEach((block) => {
            block.classList.add('active');
        });
    }
};

console.log('sdfsdf3');

document.addEventListener(
    'DOMContentLoaded',
    initBlockExample,
    false
);

// Initialize dynamic block preview (editor).
if (window['acf']) {
    window['acf']?.addAction('render_block_preview', initBlockExample);
}

export {};