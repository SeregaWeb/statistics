export function printMessage(message = '', typeMessage = 'primary', hideTime = 3000) {
    const container = document.querySelector('.js-show-info-message');

    if (!container || message === '') return;
    const timestamp = Math.floor(Date.now() / 1000);
    console.log(timestamp);

    const template = `
		<div class="toast show align-items-center mt-1 text-white bg-${typeMessage} border-0 js-stamp-${timestamp}" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body flex-grow-1">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white p-2 me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
	`;

    container.innerHTML += template;

    setTimeout(() => {
        console.log('timestamp', timestamp);
        const messages = document.querySelectorAll(`.js-stamp-${timestamp}`);

        messages &&
            messages.forEach((item) => {
                item.remove();
            });
    }, hideTime);
}
