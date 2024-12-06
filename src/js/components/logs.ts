export const logsInit = () => {
    const btns = document.querySelectorAll('.js-hide-logs');

    btns &&
        btns.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();
                const { target } = event;
                if (target instanceof HTMLElement) {
                    const wrap = target.closest('.js-logs-wrap');

                    if (!wrap) return;
                    const content = wrap.querySelector('.js-logs-content');
                    const container = wrap.querySelector('.js-logs-container');
                    console.log('target', target);

                    if (!content) return;
                    content.classList.toggle('col-lg-9');
                    content.classList.toggle('col-lg-11');

                    if (!container) return;
                    container.classList.toggle('col-lg-3');
                    container.classList.toggle('col-lg-1');
                    container.classList.toggle('hidden-logs');

                    let val = 0;
                    if (container.classList.contains('hidden-logs')) {
                        val = 1;
                    }
                    document.cookie = `logshow=${val}; path=/; max-age=86400`;
                }
            });
        });
};
