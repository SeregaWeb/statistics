import IMask from 'imask';
import flatpickr from 'flatpickr';

const maskTel = (target) => {
    const x1 = target.value.replace(/\D/g, '').match(/(\d{3})/);
    const x2 = target.value.replace(/\D/g, '').match(/(\d{3})(\d{3})/);
    const x3 = target.value.replace(/\D/g, '').match(/(\d{3})(\d{3})(\d{4})/);

    if (x3) {
        target.classList.remove('error-tel');
        // eslint-disable-next-line no-param-reassign
        target.value = `(${x3[1]}) ${x3[2]}-${x3[3]}`;
    } else {
        target.classList.add('error-tel');
    }
};

// eslint-disable-next-line import/prefer-default-export
export const telMaskInit = () => {
    const telInputs = document.querySelectorAll('.js-tel-mask');

    telInputs &&
        telInputs.forEach((tel) => {
            tel.addEventListener('input', (event) => {
                maskTel(event.target);
            });

            tel.addEventListener('change', (event) => {
                maskTel(event.target);
            });
        });
};

export const dateMaskInit = () => {
    flatpickr('.js-new-format-date', {
        dateFormat: 'm/d/Y', // Американский формат
        allowInput: true,
    });
    
    // Инициализация селектора времени
    flatpickr('.js-new-format-time', {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i', // Формат времени HH:MM
        time_24hr: true, // 24-часовой формат
        allowInput: true,
        defaultHour: 8, // Время по умолчанию: 8:00
        defaultMinute: 0,
    });
    
    // Инициализация селектора даты и времени
    flatpickr('.js-new-format-datetime', {
        enableTime: true,
        dateFormat: 'm/d/Y H:i', // Дата и время в американском формате
        time_24hr: true, // 24-часовой формат
        allowInput: true,
        defaultHour: 8, // Время по умолчанию: 8:00
        defaultMinute: 0,
    });

    // Tracking filter: pickup and delivery date only (American format m/d/Y)
    flatpickr('.js-tracking-date-pickup', {
        dateFormat: 'm/d/Y',
        allowInput: true,
    });
    flatpickr('.js-tracking-date-delivery', {
        dateFormat: 'm/d/Y',
        allowInput: true,
    });
};

export const masksAllSite = () => {
    // Маска для SSN (000-00-0000)
    const ssnInputs = document.querySelectorAll('.js-ssn-mask');
    if (ssnInputs.length) {
        ssnInputs.forEach((input) => {
            // @ts-ignore
            IMask(input, {
                mask: '000-00-0000',
            });
        });
    }

    // Маска для EIN (00-0000000)
    const einInputs = document.querySelectorAll('.js-ein-mask');
    if (einInputs.length) {
        einInputs.forEach((input) => {
            // @ts-ignore
            IMask(input, {
                mask: '00-0000000',
            });
        });
    }
};
