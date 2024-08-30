import { fadeIn, fadeOut } from './helpers';

export default class Popup {
    constructor() {
        this.body = window.document.querySelector('body');
        this.html = window.document.querySelector('html');
    }

    /**
     * Force Close All opened popup window
     * and clear the traces of an opened popup window
     */
    forceCloseAllPopup() {
        [...window.document.querySelectorAll('.popup')].forEach((item) => {
            const allErrorsInPopup = item.querySelectorAll(
                '.wpcf7-not-valid-tip'
            );
            const bottomMessage = item.querySelectorAll(
                '.wpcf7-response-output'
            );
            const form = item.querySelectorAll('form'); // reset()

            if (allErrorsInPopup) {
                // eslint-disable-next-line no-shadow
                allErrorsInPopup.forEach((item) => {
                    item.remove();
                });
            }

            if (bottomMessage) {
                // eslint-disable-next-line no-shadow
                bottomMessage.forEach((item) => {
                    item.remove();
                });
            }

            if (form) {
                // eslint-disable-next-line no-shadow
                form.forEach((item) => {
                    item.reset();
                });
            }

            fadeOut(item);
            const MAIL_SENT_OK_BOX = item.querySelector('.wpcf7-mail-sent-ok');
            if (MAIL_SENT_OK_BOX) {
                MAIL_SENT_OK_BOX.style.display = 'none';
            }
        });

        this.body.classList.remove('popup-opened');
        this.html.classList.remove('popup-opened');
    }

    /**
     * Open selected popup window
     *
     * @param {string} popupSelector - css selector of popup that should be opened
     * @param {number} timeOut - ms
     */
    openOnePopup(popupSelector = null, timeOut = 1000) {
        this.forceCloseAllPopup();

        setTimeout(() => {
            this.body.classList.add('popup-opened');
            this.html.classList.add('popup-opened');
            fadeIn(popupSelector);
        }, timeOut);
    }

    /**
     * Opening popup window
     */
    openPopup() {
        this.body.addEventListener('click', (event) => {
            if (
                ![...event.target.classList].includes('js-open-popup-activator')
            ) {
                return false;
            }

            event.preventDefault();
            const elHref =
                event.target.nodeName === 'A'
                    ? event.target.getAttribute('href')
                    : event.target.dataset.href;

            // this.body.classList.add('popup-opened');
            // this.html.classList.add('popup-opened');
            fadeIn(elHref);
            return true;
        });
    }

    /**
     * Closing popup window
     */
    closePopup() {
        this.body.addEventListener('click', (event) => {
            // Check if user click on close element
            if (![...event.target.classList].includes('js-popup-close')) {
                return false;
            }
            const popupLockPost = document.querySelectorAll('.js-popup-form');

            popupLockPost &&
                popupLockPost.forEach((popup) => {
                    popup.classList.remove('sent');
                });

            event.preventDefault();
            this.forceCloseAllPopup();
            return true;
        });

        // Checking ESC button for closing opened popup window
        window.document.addEventListener('keydown', (event) => {
            if (event.keyCode === 27) {
                this.forceCloseAllPopup();
            }
        });
    }

    init() {
        this.openPopup();
        this.closePopup();
    }
}
