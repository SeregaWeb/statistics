// eslint-disable-next-line import/prefer-default-export
interface HTMLButtonElement {
    classList: DOMTokenList;

    setAttribute(name: string, value: string): void;

    removeAttribute(name: string): void;
}

export function LoadingBtn(btn: HTMLButtonElement | null, state: boolean): void {
    if (!btn) return;

    const buttonClasses = ['disabled', 'loading'];

    if (state) {
        btn.setAttribute('disabled', 'disabled');
        btn.classList.add(...buttonClasses);
    } else {
        btn.removeAttribute('disabled');
        btn.classList.remove(...buttonClasses);
    }
}

export default LoadingBtn;
