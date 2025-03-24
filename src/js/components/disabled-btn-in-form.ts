// eslint-disable-next-line import/prefer-default-export
export const disabledBtnInForm = (form: HTMLFormElement, invertLogic = false): void => {
    const btns: NodeListOf<HTMLButtonElement> = form.querySelectorAll('button');

    btns.forEach((btn: HTMLButtonElement): void => {
        btn.disabled = !invertLogic;
    });
};
