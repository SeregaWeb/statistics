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
            const office = form.elements.namedItem('office') as HTMLInputElement | null;
            const type = form.elements.namedItem('type') as HTMLInputElement | null;

            // Проверяем и добавляем параметры только если элементы существуют и не пусты
            if (office?.value) params.append('office', office.value);
            if (fmonth?.value) params.append('fmonth', fmonth.value);
            if (fyear?.value) params.append('fyear', fyear.value);
            if (dispatcher?.value) params.append('dispatcher', dispatcher.value);
            if (mySearch?.value) params.append('my_search', mySearch.value);
            if (loadStatus?.value) params.append('load_status', loadStatus.value);
            if (source?.value) params.append('source', source.value);
            if (factoring?.value) params.append('factoring', factoring.value);
            if (invoice?.value) params.append('invoice', invoice.value);
            if (type?.value) params.append('type', type.value);

            // Перенаправляем на URL с параметрами
            window.location.href = `?${params.toString()}`;
        });
    }
};

export const cleanUrlByFilterAr = () => {
    const form = document.getElementById('navbarNavDarkDropdownAr') as HTMLFormElement | null;

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const params = new URLSearchParams();

            const mySearch = form.elements.namedItem('my_search') as HTMLInputElement | null;
            const status = form.elements.namedItem('status') as HTMLInputElement | null;
            const type = form.elements.namedItem('type') as HTMLInputElement | null;

            // Проверяем и добавляем параметры только если элементы существуют и не пусты
            if (mySearch?.value) params.append('my_search', mySearch.value);
            if (status?.value) params.append('status', status.value);
            if (type?.value) params.append('type', type.value);

            // Перенаправляем на URL с параметрами
            window.location.href = `?${params.toString()}`;
        });
    }
};

export const cleanUrlByFilterPlatform = () => {
    const form = document.getElementById('navbarNavDarkDropdownPlatform') as HTMLFormElement | null;

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const params = new URLSearchParams();

            const mySearch = form.elements.namedItem('my_search') as HTMLInputElement | null;
            const status = form.elements.namedItem('platform') as HTMLInputElement | null;
            const factoringStatus = form.elements.namedItem('factoring_status') as HTMLInputElement | null;
            const setupStatus = form.elements.namedItem('setup_status') as HTMLInputElement | null;
            const companyStatus = form.elements.namedItem('company_status') as HTMLInputElement | null;

            // Проверяем и добавляем параметры только если элементы существуют и не пусты
            if (mySearch?.value) params.append('my_search', mySearch.value);
            if (status?.value) params.append('platform', status.value);
            if (factoringStatus?.value) params.append('factoring_status', factoringStatus.value);
            if (setupStatus?.value) params.append('setup_status', setupStatus.value);
            if (companyStatus?.value) params.append('company_status', companyStatus.value);

            // Перенаправляем на URL с параметрами
            window.location.href = `?${params.toString()}`;
        });
    }
};

export const cleanUrlByFilterDriver = () => {
    const form = document.getElementById('navbarNavDriver') as HTMLFormElement | null;

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const params = new URLSearchParams();

            // Получаем значения всех полей безопасно
            const fmonth = form.elements.namedItem('fmonth') as HTMLInputElement | null;
            const fyear = form.elements.namedItem('fyear') as HTMLInputElement | null;
            const recruiter = form.elements.namedItem('recruiter') as HTMLInputElement | null;
            const mySearch = form.elements.namedItem('my_search') as HTMLInputElement | null;
            const source = form.elements.namedItem('source') as HTMLInputElement | null;
            const additional = form.elements.namedItem('additional') as HTMLInputElement | null;

            // Проверяем и добавляем параметры только если элементы существуют и не пусты
            if (recruiter?.value) params.append('recruiter', recruiter.value);
            if (fmonth?.value) params.append('fmonth', fmonth.value);
            if (fyear?.value) params.append('fyear', fyear.value);
            if (additional?.value) params.append('additional', additional.value);
            if (mySearch?.value) params.append('my_search', mySearch.value);
            if (source?.value) params.append('source', source.value);

            // Перенаправляем на URL с параметрами
            window.location.href = `?${params.toString()}`;
        });
    }
};

export const cleanUrlByFilterDriverSearch = () => {
    const form = document.getElementById('navbarNavDriverSearch') as HTMLFormElement | null;

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const params = new URLSearchParams();

            // Получаем значения всех полей безопасно
            const country = form.elements.namedItem('country') as HTMLInputElement | null;
            const radius = form.elements.namedItem('radius') as HTMLInputElement | null;
            const mySearch = form.elements.namedItem('my_search') as HTMLInputElement | null;
            const extendedSearch = form.elements.namedItem('extended_search') as HTMLInputElement | null;

            // Проверяем и добавляем параметры только если элементы существуют и не пусты
            if (mySearch?.value) params.append('my_search', mySearch.value);
            if (country?.value) params.append('country', country.value);
            if (radius?.value) params.append('radius', radius.value);
            if (extendedSearch?.value) params.append('extended_search', extendedSearch.value); 

            // Перенаправляем на URL с параметрами
            window.location.href = `?${params.toString()}`;
        });
    }
};
