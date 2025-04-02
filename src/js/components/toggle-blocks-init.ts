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

export const toggleBlocksRadio = () => {
    const toggleElements = document.querySelectorAll('.js-toggle-radio');
    toggleElements &&
        toggleElements.forEach((item) => {
            item.addEventListener('change', (event) => {
                const { target } = event;
                event.preventDefault();

                // @ts-ignore
                const toggleContainerSelectors = target.getAttribute('data-target');
                const toggleContainer = document.querySelectorAll(`.${toggleContainerSelectors}`);
                if (!target || !toggleContainer) return;

                // eslint-disable-next-line no-shadow
                toggleContainer.forEach((item) => {
                    item.classList.add('d-none');
                });
                console.log('toggleContainer', toggleContainer, toggleContainerSelectors);

                // @ts-ignore
                const activeContainer = document.querySelectorAll(`.${toggleContainerSelectors}-${target.value}`);
                // eslint-disable-next-line no-shadow
                activeContainer.forEach((item) => {
                    item.classList.remove('d-none');
                });
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
