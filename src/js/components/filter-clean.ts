// eslint-disable-next-line import/prefer-default-export
export const cleanUrlByFilter = () => {
    const form = document.getElementById('navbarNavDarkDropdown') as HTMLFormElement | null;

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const params = new URLSearchParams();

            // Получаем значения всех полей безопасно
            const fmonth = form.elements.namedItem('fmonth') as HTMLInputElement | null;
            const fyear = form.elements.namedItem('fyear') as HTMLInputElement | null;
            const dispatcher = form.elements.namedItem('dispatcher') as HTMLInputElement | null;
            const mySearch = form.elements.namedItem('my_search') as HTMLInputElement | null;
            const loadStatus = form.elements.namedItem('load_status') as HTMLInputElement | null;
            const source = form.elements.namedItem('source') as HTMLInputElement | null;
            const factoring = form.elements.namedItem('factoring') as HTMLInputElement | null;
            const invoice = form.elements.namedItem('invoice') as HTMLInputElement | null;

            // Проверяем и добавляем параметры только если элементы существуют и не пусты
            if (fmonth?.value) params.append('fmonth', fmonth.value);
            if (fyear?.value) params.append('fyear', fyear.value);
            if (dispatcher?.value) params.append('dispatcher', dispatcher.value);
            if (mySearch?.value) params.append('my_search', mySearch.value);
            if (loadStatus?.value) params.append('load_status', loadStatus.value);
            if (source?.value) params.append('source', source.value);
            if (factoring?.value) params.append('factoring', factoring.value);
            if (invoice?.value) params.append('invoice', invoice.value);

            // Перенаправляем на URL с параметрами
            window.location.href = `?${params.toString()}`;
        });
    }
};
