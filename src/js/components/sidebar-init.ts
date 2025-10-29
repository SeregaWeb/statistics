// eslint-disable-next-line import/prefer-default-export
export const toggleSidebarInit = () => {
    const toggleElements = document.querySelectorAll('.js-toggle-sidebar');

    toggleElements &&
        toggleElements.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.stopPropagation();
                event.preventDefault();
                const { target } = event;
                // @ts-ignore
                const toggleContainer = target.closest('.js-sidebar');

            
                if (!target || !toggleContainer) return;

                // @ts-ignore
                target.classList.toggle('small');
                toggleContainer.classList.toggle('small');

                console.log('toggleContainer', toggleContainer);
                console.log('target', target);

                let val = 0;
                if (toggleContainer.classList.contains('small')) {
                    val = 1;
                }
                document.cookie = `sidebar=${val}; path=/; max-age=86400`;
            });
        });
};
