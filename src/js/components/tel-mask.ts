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
