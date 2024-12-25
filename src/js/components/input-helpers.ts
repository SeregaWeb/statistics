// eslint-disable-next-line import/no-extraneous-dependencies
import IMask from 'imask';

// eslint-disable-next-line import/prefer-default-export
export function initMoneyMask() {
    const maskOptions = {
        mask: Number,
        scale: 2,
        signed: false,
        thousandsSeparator: ',',
        padFractionalZeros: false,
        normalizeZeros: false,
        radix: '.',
        mapToRadix: ['.'],
        prefix: '$',
    };

    const moneys = document.querySelectorAll('.js-money');

    moneys &&
        moneys.forEach((element) => {
            const inputElement = element as HTMLInputElement;

            IMask(inputElement, maskOptions);
        });

    const allValueInput = document.querySelector('.js-all-value') as HTMLInputElement;
    const driverValueInput = document.querySelector('.js-driver-value') as HTMLInputElement;
    const moneyTotalInput = document.querySelector('.js-money-total') as HTMLInputElement;
    const processingFeesInput = document.querySelector('.js-processing_fees') as HTMLInputElement;
    const typePayInput = document.querySelector('.js-type_pay') as HTMLInputElement;
    const percentQuickPayInput = document.querySelector('.js-percent_quick_pay') as HTMLInputElement;
    const processingInput = document.querySelector('.js-processing') as HTMLInputElement;
    const tbd = document.querySelector('.js-tbd');

    const modifiPrice = document.querySelector('.js-update-mod-price');

    if (moneyTotalInput) {
        const moneyTotalMask = IMask(moneyTotalInput, maskOptions);
        // eslint-disable-next-line no-inner-declarations
        function formatNumber(value) {
            const [integer, fractional] = value.split('.');
            const integerPart = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

            console.log('integerPart', integerPart);
            if (!fractional || parseFloat(fractional) === 0) {
                return `${integerPart}`;
            }

            const formattedFractional = fractional.replace(/0+$/, '');

            return formattedFractional ? `${integerPart}.${formattedFractional}` : `${integerPart}`;
        }

        // eslint-disable-next-line no-inner-declarations
        function calculateRemaining() {
            function parseNumber(value) {
                const normalized = value.replace(/\s+/g, '').replaceAll(',', '');
                console.log('normalized', normalized);
                return parseFloat(normalized) || 0;
            }

            let allValue = parseNumber(allValueInput.value);
            const driverValue = parseNumber(driverValueInput.value);

            const processingFees = parseFloat(processingFeesInput.value) || 0;
            const typePay = typePayInput.value;
            const percentQuickPay = parseFloat(percentQuickPayInput.value) || 0;
            const processing = processingInput.value;

            console.log('values', processingFees, typePay, percentQuickPay, processing);

            // Step 1: Apply 'processing_fees' if processing is 'direct'
            if (processing === 'direct') {
                if (typePay === 'quick-pay' && percentQuickPay > 0) {
                    const quickPayDiscount = allValue * (percentQuickPay / 100);
                    console.log('% quickPayDiscount', quickPayDiscount);
                    allValue -= quickPayDiscount;
                }

                allValue -= processingFees;

                if (modifiPrice) {
                    modifiPrice.innerHTML = `$${allValue} <span class="text-small"> price including quick pay  ${percentQuickPay}% and processing fees $${processingFees}</span>`;
                }
            }

            // Step 2: Apply 'quick-pay' percentage if selected

            // @ts-ignore
            if (tbd.checked) {
                moneyTotalMask.value = '0';
            } else {
                const remaining = allValue - driverValue;
                // @ts-ignore
                moneyTotalMask.value = remaining.toString();
            }
        }

        if (allValueInput && driverValueInput) {
            allValueInput.addEventListener('input', calculateRemaining);
            driverValueInput.addEventListener('input', calculateRemaining);

            tbd &&
                tbd.addEventListener('change', (event) => {
                    event.preventDefault();

                    const { target } = event;

                    const inputDriver = document.querySelector('.js-container-number input') as HTMLInputElement;
                    const inputDriverPhone = document.querySelector('.js-phone-driver') as HTMLInputElement;
                    const inputDriverValue = document.querySelector('.js-driver-value') as HTMLInputElement;
                    const inputTotal = document.querySelector('.js-money-total') as HTMLInputElement;

                    if (!inputDriver || !inputDriverPhone || !inputDriverValue) return;

                    if (target) {
                        // @ts-ignore
                        if (target.checked) {
                            inputDriver.value = 'TBD';
                            inputDriverPhone.value = 'TBD';
                            inputDriverValue.value = '0';
                            inputTotal.value = '0';

                            inputDriver.setAttribute('readonly', 'readonly');
                            inputDriverPhone.setAttribute('readonly', 'readonly');
                            inputDriverValue.setAttribute('readonly', 'readonly');
                            inputTotal.setAttribute('readonly', 'readonly');
                        } else {
                            const driverVal = inputDriver.getAttribute('data-value');
                            const driverPhoneVal = inputDriverPhone.getAttribute('data-value');

                            // @ts-ignore
                            inputDriver.value = driverVal === 'TBD' ? '' : driverVal;
                            // @ts-ignore
                            inputDriverPhone.value = driverPhoneVal === 'TBD' ? '' : driverPhoneVal;
                            // @ts-ignore
                            inputDriverValue.value = inputDriverValue.getAttribute('data-value');
                            // @ts-ignore
                            inputTotal.value = inputTotal.getAttribute('data-value');

                            inputDriver.removeAttribute('readonly');
                            inputDriverPhone.removeAttribute('readonly');
                            inputDriverValue.removeAttribute('readonly');

                            calculateRemaining();
                        }
                    }
                });
        }
    }
}

export function checboxesHelperInit() {
    const allInputsSelect = document.querySelector('.js-select-load-all') as HTMLInputElement;
    const allInputs = document.querySelectorAll('.js-select-load');

    allInputsSelect &&
        allInputsSelect.addEventListener('change', (event) => {
            allInputs &&
                allInputs.forEach((item) => {
                    // @ts-ignore
                    // eslint-disable-next-line no-param-reassign
                    item.checked = allInputsSelect.checked;
                });
        });
}

// eslint-disable-next-line camelcase
export function quick_pay_method() {
    const selectMethod = document.querySelector('.js-quick-pay-method');

    selectMethod &&
        selectMethod.addEventListener('change', (event) => {
            const { target } = event;

            if (target) {
                // @ts-ignore
                const { value } = target;

                console.log('value', value);
                const quickPayInput = document.querySelector('.js-quick-pay-driver');
                const afterCount = document.querySelector('.js-sum-after-count');
                if (value) {
                    const selectPercent = document.querySelector(`.js-select-quick-${value}`);
                    if (!selectPercent) return;
                    // @ts-ignore
                    const selectPercentValue = selectPercent.value;
                    const driverReit = selectPercent.getAttribute('data-reit');
                    const commission = selectPercent.getAttribute('data-commission');
                    if (!driverReit || !commission) return;
                    const persent = +driverReit * (+selectPercentValue / 100);
                    const persentTotal = persent + parseFloat(commission);
                    if (!quickPayInput) return;
                    // @ts-ignore
                    quickPayInput.value = persentTotal.toFixed(2);

                    if (!afterCount) return;
                    // @ts-ignore
                    afterCount.innerHTML = `Sum to pay $${(parseFloat(driverReit) - persentTotal).toFixed(2)}`;
                } else {
                    // @ts-ignore
                    quickPayInput.value = '';
                }
            }
        });
}

export function trigger_current_time() {
    const trigger = document.querySelector('.js-trigger-set-date');

    // Убедиться, что чекбокс найден
    if (trigger) {
        trigger.addEventListener('change', (event) => {
            // Найти связанное поле с датой
            const dateField = document.querySelector('.js-set-date[type="date"]');

            // Убедиться, что поле найдено
            if (dateField) {
                // @ts-ignore
                if (event.target.checked) {
                    // Установить текущую дату, если чекбокс включен
                    const currentDate = new Date().toISOString().split('T')[0];
                    // @ts-ignore
                    dateField.value = currentDate;
                } else {
                    // Очистить поле даты, если чекбокс выключен
                    // @ts-ignore
                    dateField.value = '';
                }
            }
        });
    }
}
