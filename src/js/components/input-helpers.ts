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
