// eslint-disable-next-line import/prefer-default-export
export const bookmarkInit = (ajaxUrl) => {
    const buttons = document.querySelectorAll('.js-btn-bookmark');
    if (!buttons) return;

    buttons &&
        buttons.forEach((item) => {
            item.addEventListener('click', async (event) => {
                
                const button = event.target;
                // @ts-ignore
                const postId = button.dataset.id;
                // @ts-ignore
                const isFlt = button.dataset.flt === '1';

                try {
                    const response = await fetch(ajaxUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'toggle_bookmark',
                            post_id: postId,
                            is_flt: isFlt ? '1' : '0',
                        }),
                    });
                    const result = await response.json();

                    if (result.success) {
                        // @ts-ignore
                        button.classList.toggle('active', result.is_bookmarked);
                    } else {
                        // eslint-disable-next-line no-alert
                        alert(result.message || 'Something went wrong!');
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        });
};
