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

            const allValue = parseNumber(allValueInput.value);
            const driverValue = parseNumber(driverValueInput.value);

            const remaining = allValue - driverValue;
            // @ts-ignore
            moneyTotalMask.value = remaining.toString();
        }

        if (allValueInput && driverValueInput) {
            allValueInput.addEventListener('input', calculateRemaining);
            driverValueInput.addEventListener('input', calculateRemaining);
        }
    }
}
