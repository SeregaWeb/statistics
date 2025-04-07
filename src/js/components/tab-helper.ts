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
            console.log('nextTargetTab', container, nextTargetTab, document.getElementById(nextTargetTab));
            // @ts-ignore
            const tab = new Tab(document.getElementById(nextTargetTab));
            tab.show();
        });
    });
};

export const tabUrlUpdeter = () => {
    const tabs = document.querySelectorAll('.js-change-url-tab');

    tabs.forEach((tab) => {
        tab.addEventListener('shown.bs.tab', (event) => {
            // @ts-ignore
            const activeTabId = event.target.id; // Получаем ID активного таба
            const url = new URL(window.location.href);
            url.searchParams.set('tab', activeTabId); // Обновляем GET-параметр
            // eslint-disable-next-line no-restricted-globals
            history.replaceState(null, '', url); // Меняем URL без перезагрузки
        });
    });
};
