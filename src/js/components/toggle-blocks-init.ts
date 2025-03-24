// eslint-disable-next-line import/prefer-default-export
export const toggleBlocksInit = () => {
    const toggleElements = document.querySelectorAll('.js-toggle');
    toggleElements &&
        toggleElements.forEach((item) => {
            item.addEventListener('click', (event) => {
                const { target } = event;

                if (!(target instanceof HTMLInputElement && (target.type === 'checkbox' || target.type === 'radio'))) {
                    event.preventDefault();
                }

                // @ts-ignore
                const toggleContainerSelector = target.getAttribute('data-block-toggle');
                const toggleContainer = document.querySelector(`.${toggleContainerSelector}`);

                if (!target || !toggleContainer) return;

                let classToggle = toggleContainer.getAttribute('data-class-toggle');
                if (!classToggle) classToggle = 'd-none';
                // @ts-ignore
                target.classList.toggle('active');
                toggleContainer.classList.toggle(classToggle);
            });
        });
};

export const toggleCheckboxInit = () => {
    const toggleElements = document.querySelectorAll('.js-switch-toggle');
    toggleElements &&
        toggleElements.forEach((item) => {
            item.addEventListener('change', (event) => {
                event.preventDefault();
                const { target } = event;
                // @ts-ignore
                const toggleContainerSelector = target.getAttribute('data-toggle');
                const toggleContainer = document.querySelector(`.${toggleContainerSelector}`);

                if (!target || !toggleContainer) return;

                // @ts-ignore
                toggleContainer.classList.toggle('d-none');
            });
        });
};
