// eslint-disable-next-line import/prefer-default-export
export const cleanUrlByFilter = () => {
    // @ts-ignore
    const form = document.getElementById('navbarNavDarkDropdown');

    form &&
        form.addEventListener('submit', function (event) {
            // Прекращаем отправку формы по умолчанию
            event.preventDefault();

            // Создаем объект URLSearchParams для формирования URL
            const params = new URLSearchParams();

            // Получаем значения всех полей
            // @ts-ignore
            const fmonth = this.elements.fmonth.value;
            // @ts-ignore
            const fyear = this.elements.fyear.value;
            // @ts-ignore
            const dispatcher = this.elements.dispatcher.value;
            // @ts-ignore
            const mySearch = this.elements.my_search.value;
            // @ts-ignore
            const loadStatus = this.elements.load_status.value;
            // @ts-ignore
            const source = this.elements.source.value;

            // Добавляем значения в параметры только если они не пустые
            if (fmonth) params.append('fmonth', fmonth);
            if (fyear) params.append('fyear', fyear);
            if (dispatcher) params.append('dispatcher', dispatcher);
            if (mySearch) params.append('my_search', mySearch);
            if (loadStatus) params.append('load_status', loadStatus);
            if (source) params.append('source', source);

            // Перенаправляем на URL с параметрами
            window.location.href = `?${params.toString()}`;
        });
};
