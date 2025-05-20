// eslint-disable-next-line import/no-extraneous-dependencies
import IMask from 'imask';
import { uploadFilePreview } from './create-report';

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
    const secondDriverValueInput = document.querySelector('.js-driver-second-value') as HTMLInputElement;
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
            const secondDriverValue = parseNumber(secondDriverValueInput.value) || 0;

            const processingFees = parseFloat(processingFeesInput.value) || 0;
            const typePay = typePayInput.value;
            const percentQuickPay = parseFloat(percentQuickPayInput.value) || 0;
            const processing = processingInput.value;

            console.log('values', processingFees, typePay, percentQuickPay, processing, secondDriverValue);

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
                const remaining = allValue - (driverValue + secondDriverValue);
                // @ts-ignore
                moneyTotalMask.value = remaining.toString();
            }
        }

        if (allValueInput && driverValueInput) {
            allValueInput.addEventListener('input', calculateRemaining);
            driverValueInput.addEventListener('input', calculateRemaining);
            secondDriverValueInput.addEventListener('input', calculateRemaining);

            tbd &&
                tbd.addEventListener('change', (event) => {
                    event.preventDefault();

                    const { target } = event;

                    const inputDriver = document.querySelector('.js-container-number input') as HTMLInputElement;
                    const inputDriverPhone = document.querySelector('.js-phone-driver') as HTMLInputElement;
                    const inputDriverValue = document.querySelector('.js-driver-value') as HTMLInputElement;
                    const secondInputDirverValue = document.querySelector(
                        '.js-driver-second-value'
                    ) as HTMLInputElement;

                    const inputTotal = document.querySelector('.js-money-total') as HTMLInputElement;

                    if (!inputDriver || !inputDriverPhone || !inputDriverValue) return;

                    if (target) {
                        // @ts-ignore
                        if (target.checked) {
                            inputDriver.value = 'TBD';
                            inputDriverPhone.value = 'TBD';
                            inputDriverValue.value = '0';
                            secondInputDirverValue.value = '0';
                            inputTotal.value = '0';

                            inputDriver.setAttribute('readonly', 'readonly');
                            inputDriverPhone.setAttribute('readonly', 'readonly');
                            inputDriverValue.setAttribute('readonly', 'readonly');
                            secondInputDirverValue.setAttribute('readonly', 'readonly');
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
                            secondInputDirverValue.value = secondInputDirverValue.getAttribute('data-value');
                            // @ts-ignore
                            inputTotal.value = inputTotal.getAttribute('data-value');

                            inputDriver.removeAttribute('readonly');
                            inputDriverPhone.removeAttribute('readonly');
                            inputDriverValue.removeAttribute('readonly');
                            secondInputDirverValue.removeAttribute('readonly');

                            calculateRemaining();
                        }
                    }
                });
        }

        const valueClear = () => {
            const switches = document.querySelectorAll('.js-switch-and-clear');

            switches.forEach((item) => {
                item.addEventListener('change', (event: Event) => {
                    const target = event.target as HTMLInputElement;
                    const selectorTarget = target.getAttribute('data-target');
                    if (!selectorTarget) return;

                    const sections = document.querySelectorAll(`.${selectorTarget}`);

                    if (!target.checked) {
                        // When unchecked, save current values in data-old-value and then clear the inputs
                        sections.forEach((section) => {
                            const inputs = section.querySelectorAll('input');
                            inputs.forEach((input) => {
                                const htmlInput = input as HTMLInputElement;
                                if (htmlInput.value.trim() !== '') {
                                    htmlInput.setAttribute('data-old-value', htmlInput.value);
                                }
                                htmlInput.value = '';
                            });
                        });
                    } else {
                        // When checked, restore values from data-old-value if they exist
                        sections.forEach((section) => {
                            const inputs = section.querySelectorAll('input');
                            inputs.forEach((input) => {
                                const htmlInput = input as HTMLInputElement;
                                const oldValue = htmlInput.getAttribute('data-old-value');
                                if (oldValue !== null) {
                                    htmlInput.value = oldValue;
                                    // Optionally, remove the data attribute after restoring:
                                    // htmlInput.removeAttribute('data-old-value');
                                }
                            });
                        });
                    }

                    calculateRemaining();
                });
            });
        };

        valueClear();
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
export function quickPayMethod() {
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

// eslint-disable-next-line camelcase
export function triggerCurrentTime() {
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

export function dragAnDropInit() {
    const uploadAreas = document.querySelectorAll('.upload-area');

    uploadAreas.forEach((area) => {
        const fileInput = area.querySelector('.file-input');

        // Клик по области
        // @ts-ignore
        // Наведение файла
        area.addEventListener('dragover', (e) => {
            e.preventDefault();
            area.classList.add('dragover');
        });

        // Увод курсора
        area.addEventListener('dragleave', () => {
            area.classList.remove('dragover');
        });

        // Сброс файла
        area.addEventListener('drop', (e) => {
            e.preventDefault();
            area.classList.remove('dragover');

            // @ts-ignore
            const { files } = e.dataTransfer;
            // @ts-ignore
            const isMultipleAllowed = fileInput.hasAttribute('multiple');

            if (!isMultipleAllowed && files.length > 1) {
                // eslint-disable-next-line no-alert
                alert('Only one file can be uploaded here.');
                return;
            }

            // Устанавливаем файлы в input
            const dataTransfer = new DataTransfer();
            if (isMultipleAllowed) {
                for (let i = 0; i < files.length; i++) {
                    dataTransfer.items.add(files[i]);
                }
            } else {
                dataTransfer.items.add(files[0]);
            }
            // @ts-ignore
            fileInput.files = dataTransfer.files;
            // @ts-ignore
            console.log('Файлы загружены:', fileInput.files);
            uploadFilePreview(fileInput);
        });
    });
}

export function applyZipCodeMask(selector: string, countrySelector = '.js-country') {
    // Функция для очистки не-цифр и обрезки до 5 символов
    function restrictToFiveDigits(this: HTMLInputElement) {
        this.value = this.value.replace(/\D/g, '').slice(0, 5);
    }

    document.querySelectorAll<HTMLInputElement>(selector).forEach((input) => {
        const form = input.closest('form');
        if (!form) return;

        // Список полей country (select или radio)
        const countryFields = Array.from(form.querySelectorAll<HTMLInputElement>(countrySelector));
        if (!countryFields.length) return;

        // Определяет текущую страну
        function getCountry(): string | null {
            // eslint-disable-next-line no-restricted-syntax
            for (const el of countryFields) {
                if (el.type === 'radio') {
                    if (el.checked) return el.value;
                } else {
                    return el.value;
                }
            }
            return null;
        }

        // Применяем нужные атрибуты к инпуту
        function configure() {
            const country = getCountry();
            // сбрасываем текущее значение
            // eslint-disable-next-line no-param-reassign
            input.value = '';
            // убираем старые ограничители
            input.removeAttribute('maxlength');
            input.removeAttribute('pattern');
            input.removeEventListener('input', restrictToFiveDigits);

            if (country === 'Canada') {
                // для Канады: только длина до 7 символов
                input.setAttribute('maxlength', '7');
            } else {
                // для США/Мексики: только 5 цифр
                input.setAttribute('maxlength', '5');
                input.setAttribute('pattern', '\\d{5}');
                input.addEventListener('input', restrictToFiveDigits);
            }
        }

        // инициализируем сразу
        configure();

        // и при каждом change в country поля
        countryFields.forEach((el) => {
            el.addEventListener('change', configure);
        });
    });
}

export function unrequiderInit() {
    const unrequiders = document.querySelectorAll('.js-unrequider');

    unrequiders.forEach((select) => {
        select.addEventListener('change', function (e) {
            const { target } = e;

            if (!target) return;

            // @ts-ignore
            const selectedValue = target.value;
            // @ts-ignore
            const targetSelector = target.dataset.target;
            // @ts-ignore
            const expectedValue = target.dataset.value;

            const targetVal = document.querySelector(targetSelector);
            if (!targetVal) return;

            if (selectedValue === expectedValue) {
                targetVal.removeAttribute('required');
            } else {
                targetVal.setAttribute('required', 'required');
            }
        });
    });
}
