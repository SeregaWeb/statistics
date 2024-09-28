// eslint-disable-next-line import/prefer-default-export
export const toggleSidebarInit = () => {
    const toggleElements = document.querySelectorAll('.js-toggle-sidebar');

    toggleElements &&
        toggleElements.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();
                const { target } = event;
                // @ts-ignore
                const toggleContainer = target.closest('.js-sidebar');

                if (!target || !toggleContainer) return;

                // @ts-ignore
                target.classList.toggle('small');
                toggleContainer.classList.toggle('small');
            });
        });
};
