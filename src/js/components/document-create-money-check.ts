import IMask from 'imask';
import { debounce } from '../parts/helpers';

export const createDocumentInvoice = () => {
    const moneyElements = document.querySelectorAll<HTMLInputElement>('.custom4');
    // eslint-disable-next-line @wordpress/no-unused-vars-before-return
    const procentElements = document.querySelectorAll<HTMLInputElement>('.js-procent');
    // eslint-disable-next-line @wordpress/no-unused-vars-before-return
    const quickPayChangeElements = document.querySelectorAll<HTMLInputElement>('.js-quick_pay_change');
    const valueElement = document.querySelector<HTMLInputElement>('.js-value');

    // Проверяем наличие элементов перед дальнейшей обработкой
    if (!moneyElements.length || !valueElement) return;

    // Функция для применения маски к полю
    const applyMoneyMask = (element: HTMLInputElement) => {
        IMask(element, {
            mask: '$num',
            blocks: {
                num: {
                    mask: Number,
                    thousandsSeparator: ',',
                    radix: '.',
                    mapToRadix: ['.'],
                    min: 0,
                    max: 9999999.99,
                },
            },
        });
    };

    // Применение маски ко всем полям с классом .custom4
    moneyElements.forEach((money) => {
        applyMoneyMask(money);

        // @ts-ignore
        money.addEventListener(
            'input',
            // @ts-ignore
            debounce((e: Event) => {
                const target = e.target as HTMLInputElement;
                const valueMasked = target.value;
                const container = target.closest('tr');
                const procent = container?.querySelector<HTMLInputElement>('.js-procent')?.value || '0';
                // eslint-disable-next-line no-use-before-define
                setValueTotal(procent, valueMasked);
            }, 500)
        );
    });

    // Обработка изменений в процентах
    procentElements.forEach((input) => {
        // @ts-ignore
        input.addEventListener(
            'input',
            // @ts-ignore
            debounce((e: Event) => {
                const target = e.target as HTMLInputElement;
                const container = target.closest('tr');
                const valueMasked = container?.querySelector<HTMLInputElement>('.custom4')?.value || '0';
                // eslint-disable-next-line no-use-before-define
                setValueTotal(target.value, valueMasked);
            }, 500)
        );
    });

    // Обработка изменений в чекбоксах
    quickPayChangeElements.forEach((checkbox) => {
        checkbox.addEventListener('input', () => {
            const container = checkbox.closest('tr');
            const procent = container?.querySelector<HTMLInputElement>('.js-procent')?.value || '0';
            const valueMasked = container?.querySelector<HTMLInputElement>('.custom4')?.value || '0';
            // eslint-disable-next-line no-use-before-define
            setValueTotal(procent, valueMasked);
        });
    });

    // Основная функция расчёта и отображения суммы
    function setValueTotal(procent: string, valueMasked: string) {
        const value = parseFloat(valueMasked.replace(/\s|[$,]/g, '') || '0');
        const procentValue = (value / 100) * parseFloat(procent);
        const checked = document.querySelector<HTMLInputElement>('.js-quick_pay_change')?.checked;

        const sum = checked ? value - procentValue : value;
        // @ts-ignore
        valueElement.value = sum.toFixed(2);

        // @ts-ignore
        applyMoneyMask(valueElement);
    }
};

export const createDocumentInvoiceActions = (urlAjax) => {
    const formInv = document.querySelector('.js-generate-invoice');

    if (formInv) {
        formInv.addEventListener('submit', (event) => {
            event.preventDefault();
            const action = 'generate_invoice';
            const form = event.target;

            if (form) {
                // @ts-ignore
                const formData = new FormData(form);
                formData.append('action', action);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(urlAjax, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            window.open(requestStatus.data, '_blank');
                        } else {
                            console.log('error');
                        }
                    });
            }
        });
    }
};
export const createDocumentBolActions = (urlAjax) => {
    const formInv = document.querySelector('.js-generate-bol');

    if (formInv) {
        formInv.addEventListener('submit', (event) => {
            event.preventDefault();
            const action = 'generate_bol';
            const form = event.target;

            if (form) {
                // @ts-ignore
                const formData = new FormData(form);
                formData.append('action', action);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(urlAjax, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            window.open(requestStatus.data, '_blank');
                        } else {
                            console.log('error');
                        }
                    });
            }
        });
    }
};

export const createDocumentSettlementSummaryActions = (urlAjax) => {
    const formInv = document.querySelector('.js-generate-settlement-summary');

    if (formInv) {
        formInv.addEventListener('submit', (event) => {
            event.preventDefault();
            const action = 'generate_settlement_summary';
            const form = event.target;

            if (form) {
                // @ts-ignore
                const formData = new FormData(form);
                formData.append('action', action);

                const options = {
                    method: 'POST',
                    body: formData,
                };

                fetch(urlAjax, options)
                    .then((res) => res.json())
                    .then((requestStatus) => {
                        if (requestStatus.success) {
                            window.open(requestStatus.data, '_blank');
                        } else {
                            console.log('error');
                        } 
                    });
            }
        });
    }
};
