import { printMessage } from './info-messages';

async function getDriverById(useProject, driverId, ProjectsLinks) {
    try {
        // Формируем URL с параметром driver-id
        const endpoint = `${ProjectsLinks[useProject]}wp-json/wp/v2/driver-name?driver-id=${driverId}`;

        // Отправляем GET-запрос
        const response = await fetch(endpoint, {
            method: 'GET',
        });

        // Получаем ответ в формате JSON
        const data = await response.json();

        if (data.success) {
            console.log('Driver data:', data.data); // Данные водителя
            return data.data;
        }
        console.log('data', data);
        printMessage(`${data.data.message}`, 'danger', 8000);
        return false;
    } catch (error) {
        printMessage(`'Request failed' ${error}`, 'danger', 8000);
        console.error('Request failed', error);
        return false;
    }
}

// eslint-disable-next-line import/prefer-default-export
export const initGetInfoDriver = (ProjectsLinks) => {
    const btns = document.querySelectorAll('.js-fill-driver');

    if (btns) {
        btns.forEach((item) => {
            item.addEventListener('click', async (event) => {
                event.preventDefault();

                const { target } = event;
                if (!target) return;

                // Найти контейнер и input
                // @ts-ignore
                const container = target.closest('.js-container-number');
                if (!container) return;
                const input = container.querySelector('input');
                if (!input) return;

                const { value } = input;

                // Найти проект
                const useProject = document.querySelector('.js-select-current-table');
                if (!useProject) return;

                // @ts-ignore
                const valueProject = useProject.value;

                // Вызвать асинхронную функцию и дождаться её выполнения
                const driver = await getDriverById(valueProject, value, ProjectsLinks);

                // Проверить результат
                if (driver && driver.driver) {
                    const driverPhone = document.querySelector('.js-phone-driver');

                    if (driverPhone) {
                        // @ts-ignore
                        driverPhone.value = driver.phone;
                    }

                    input.value = `(${value}) ${driver.driver}`;
                    console.log('driver', `${value && driver.driver}`);
                } else {
                    console.log('Driver not found or error occurred.');
                }
            });
        });
    }
};
