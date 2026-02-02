/**
 * Payment file preview: open driver payment_file (PDF or image) in a Bootstrap modal.
 * PDF: always shown in native iframe (direct URL). Images: via img tag.
 */

const MODAL_ID = 'paymentFilePreviewModal';
const IFRAME_ID = 'paymentFilePreviewIframe';
const IMG_ID = 'paymentFilePreviewImg';
const FALLBACK_ID = 'paymentFilePreviewFallback';
const LINK_ID = 'paymentFilePreviewLink';
const TITLE_ID = 'paymentFilePreviewModalLabel';
const DRIVER_INFO_ID = 'paymentFilePreviewDriverInfo';
const ACCOUNT_TYPE_ID = 'paymentFilePreviewAccountType';
const ACCOUNT_NAME_ID = 'paymentFilePreviewAccountName';
const PAYMENT_INSTRUCTION_ID = 'paymentFilePreviewPaymentInstruction';
const BODY_LOCK_CLASS = 'payment-file-modal-open';

function lockBodyScroll(): void {
    document.body.classList.add(BODY_LOCK_CLASS);
}

function unlockBodyScroll(): void {
    document.body.classList.remove(BODY_LOCK_CLASS);
}

function initPaymentFilePreview(): void {
    const buttons = document.querySelectorAll('.js-payment-file-preview');
    const modalEl = document.getElementById(MODAL_ID);
    if (!buttons.length || !modalEl) return;

    const iframe = document.getElementById(IFRAME_ID) as HTMLIFrameElement | null;
    const img = document.getElementById(IMG_ID) as HTMLImageElement | null;
    const fallback = document.getElementById(FALLBACK_ID);
    const link = document.getElementById(LINK_ID) as HTMLAnchorElement | null;
    const titleEl = document.getElementById(TITLE_ID);
    const driverInfoEl = document.getElementById(DRIVER_INFO_ID);
    const accountTypeEl = document.getElementById(ACCOUNT_TYPE_ID);
    const accountNameEl = document.getElementById(ACCOUNT_NAME_ID);
    const paymentInstructionEl = document.getElementById(PAYMENT_INSTRUCTION_ID);

    if (!iframe || !img || !link) return;

    const iframeEl = iframe;
    const imgEl = img;
    const linkEl = link;

    // Bootstrap 5 Modal (reuse instance if exists)
    const windowAny = window as any;
    const BootstrapModal = typeof windowAny.bootstrap !== 'undefined' && windowAny.bootstrap.Modal;
    let modalInstance: { show: () => void } | null = null;
    if (BootstrapModal) {
        modalInstance = BootstrapModal.getInstance(modalEl) || new BootstrapModal(modalEl);
    }

    function showPdf(url: string): void {
        // Native iframe: browser opens PDF directly (works for localhost, same-origin, and public URLs like Wasabi/S3)
        iframeEl.src = url;
        iframeEl.classList.remove('d-none');
        imgEl.classList.add('d-none');
        imgEl.removeAttribute('src');
        if (fallback) fallback.classList.add('d-none');
    }

    function showImage(url: string): void {
        imgEl.src = url;
        imgEl.classList.remove('d-none');
        iframeEl.src = '';
        iframeEl.classList.add('d-none');
        if (fallback) fallback.classList.add('d-none');
    }

    function hideAll(): void {
        iframeEl.src = '';
        iframeEl.classList.add('d-none');
        imgEl.removeAttribute('src');
        imgEl.classList.add('d-none');
        if (fallback) fallback.classList.remove('d-none');
    }

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const ds = (btn as HTMLElement).dataset;
            const url = ds?.url;
            const isImage = ds?.isImage === '1';
            const driverName = ds?.driverName || '';
            const accountType = ds?.accountType || '—';
            const accountName = ds?.accountName || '—';
            const paymentInstruction = ds?.paymentInstruction || '—';

            if (!url) return;

            if (titleEl) {
                titleEl.textContent = driverName
                    ? `Payment file — ${driverName}`
                    : 'Payment file';
            }
            linkEl.href = url;

            if (driverInfoEl) {
                driverInfoEl.style.display = 'block';
                if (accountTypeEl) accountTypeEl.textContent = accountType;
                if (accountNameEl) accountNameEl.textContent = accountName;
                if (paymentInstructionEl) paymentInstructionEl.textContent = paymentInstruction;
            }

            hideAll();
            if (isImage) {
                showImage(url);
            } else {
                showPdf(url);
            }

            if (modalInstance) {
                lockBodyScroll();
                modalInstance.show();
            } else {
                // Fallback when Bootstrap is not loaded: show modal manually
                lockBodyScroll();
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                modalEl.setAttribute('aria-hidden', 'false');
                document.body.classList.add('modal-open');
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.setAttribute('data-payment-preview-backdrop', '1');
                document.body.appendChild(backdrop);
                const removeFallback = (): void => {
                    unlockBodyScroll();
                    hideAll();
                    iframeEl.src = '';
                    modalEl.classList.remove('show');
                    modalEl.style.display = 'none';
                    modalEl.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                    const b = document.querySelector('[data-payment-preview-backdrop="1"]');
                    if (b) b.remove();
                };
                const dismissButtons = modalEl.querySelectorAll('[data-bs-dismiss="modal"]');
                const onceClose = (): void => {
                    removeFallback();
                    dismissButtons.forEach((el) => el.removeEventListener('click', onceClose));
                    backdrop.removeEventListener('click', onceClose);
                };
                dismissButtons.forEach((el) => el.addEventListener('click', onceClose));
                backdrop.addEventListener('click', onceClose);
            }
        });
    });

    // Clear iframe/img when modal is hidden (stop PDF playback), unlock body scroll
    modalEl.addEventListener('hidden.bs.modal', () => {
        unlockBodyScroll();
        hideAll();
        iframeEl.src = '';
    });
}

export { initPaymentFilePreview };
