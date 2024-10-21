import * as querystring from 'node:querystring';
import { debounce } from '../parts/helpers';
import { printMessage } from './info-messages';

/**
 * click by searched value in customer and shipper
 */
function searchResultActions() {
    const visibleLinks = document.querySelectorAll('.js-link-search-result');

    if (visibleLinks) {
        visibleLinks.forEach((item) => {
            item.addEventListener('click', (event) => {
                event.preventDefault();
                const { target } = event;
                if (!target) return;
                // @ts-ignore
                const findElement = target.querySelector('.js-content-company');
                if (!findElement) return;
                const value = findElement.innerHTML;
                if (!value) return;
                // @ts-ignore
                const contaier = target.closest('.js-result-search-wrap');
                if (!contaier) return;
                const wrap = contaier.querySelector('.js-result-search');
                if (!wrap) return;
                wrap.innerHTML = value;
                // @ts-ignore
                const ulElement = target.closest('.js-container-search-list');
                ulElement.classList.add('d-none');

                // shipper special value
                const input = wrap.querySelector('.js-full-address');
                const shipperInput = contaier.querySelector('.js-search-shipper');
                console.log('input', input, target);
                if (input && shipperInput) {
                    // @ts-ignore
                    shipperInput.value = input.getAttribute('data-current-address');
                }
            });
        });
    }
}

/**
 * universal function add click / focus / input actions in search input
 * im use this function in search customers and shipper
 *
 * @param seachInputsSelector
 * @param action
 * @param ajaxUrl
 */

function searchHelperActions(seachInputsSelector, action, ajaxUrl) {
    const seachInputs = document.querySelectorAll(seachInputsSelector);

    if (!seachInputs) return;
    // this action close dropdown list searched values
    document.addEventListener('click', (event) => {
        const { target } = event;
        if (!target) return;
        const container = document.querySelector('.js-container-search');
        if (!container) return;
        const containerList = container.querySelector('.js-container-search-list');
        if (!containerList) return;

        // @ts-ignore
        if (container && !container.contains(target)) {
            containerList.classList.add('d-none');
        }
    });

    seachInputs.forEach((items) => {
        items.addEventListener('focus', (event) => {
            const { target } = event;
            // @ts-ignore
            const container = target.closest('.js-container-search');
            if (!container) return;

            const containerList = container.querySelector('.js-container-search-list');
            // @ts-ignore
            if (window.lastTemplate) {
                if (action === 'search_company') {
                    // @ts-ignore
                    containerList.innerHTML = window.lastTemplate;
                }

                if (action === 'search_shipper') {
                    // @ts-ignore
                    containerList.innerHTML = window.lastTemplateShipper;
                }

                searchResultActions();
            }
            containerList.classList.remove('d-none');
        });
        // @ts-ignore
        items.addEventListener(
            'input',
            debounce((event) => {
                const { target } = event;

                const container = target.closest('.js-container-search');

                if (!container) return;

                const containerList = container.querySelector('.js-container-search-list');

                const formData = new FormData();

                formData.append('action', action);
                formData.append('search', target.value);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(ajaxUrl, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            console.log('search_result:', requestStatus.data);
                            if (action === 'search_company') {
                                // @ts-ignore
                                window.lastTemplate = requestStatus.data.template;
                            }

                            if (action === 'search_shipper') {
                                // @ts-ignore
                                window.lastTemplateShipper = requestStatus.data.template;
                            }

                            containerList.innerHTML = requestStatus.data.template;
                            searchResultActions();
                        } else {
                            containerList.innerHTML = requestStatus.data.template;
                        }
                    })
                    .catch((error) => {
                        printMessage(`Request failed: ${error}`, 'danger', 8000);
                        console.error('Request failed:', error);
                    });
            }, 1200)
        );
    });
}

/**
 * function init search in page add-load
 *
 * @param ajaxUrl
 */
// eslint-disable-next-line import/prefer-default-export
export const addSearchAction = (ajaxUrl) => {
    console.log('ajaxUrl', ajaxUrl);

    searchHelperActions('.js-search-company', 'search_company', ajaxUrl);
    searchHelperActions('.js-search-shipper', 'search_shipper', ajaxUrl);
};
