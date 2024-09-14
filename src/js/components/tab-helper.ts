import { Tab } from 'bootstrap';

export const nextTabTrigger = () => {
    const nextSelectors = document.querySelectorAll('.js-next-tab');

    if (!nextSelectors) return;

    nextSelectors.forEach((item) => {
        item.addEventListener('click', (event) => {
            const { target } = event;

            if (!target) return;

            // @ts-ignore
            const container = target.closest('.js-section-tab');

            if (!container) return;

            // @ts-ignore
            const nextTargetTab = target.getAttribute('data-tab-id');

            if (!nextTargetTab) return;
            console.log('nextTargetTab', nextTargetTab, document.getElementById(nextTargetTab));
            // @ts-ignore
            const tab = new Tab(document.getElementById(nextTargetTab));
            tab.show();
        });
    });
};
