import { printMessage } from '../info-messages';

export async function getLocationDataByZipCodeHero(zipCode, apiKey, CountrySearch = 'USA') {
    console.log('CountrySearch', CountrySearch);
    
    // Формируем параметр in в зависимости от переданной страны
    let countryParam;
    if (CountrySearch === 'USA' || CountrySearch === 'CAN' || CountrySearch === 'MEX') {
        // Если передана конкретная страна, ищем только по ней
        countryParam = `countryCode%3A${CountrySearch}`;
    } else {
        // По умолчанию ищем по всем 3 странам
        countryParam = 'countryCode%3AUSA%2CCAN%2CMEX';
    }
    
    const endpoint = `https://geocode.search.hereapi.com/v1/geocode?q=${zipCode}&in=${countryParam}&apiKey=${apiKey}`;

    try {
        const response = await fetch(endpoint);
        const data = await response.json();

        if (data.items && data.items.length > 0) {
            const location = data.items[0];
            const city = location.address.city || '';
            const state = location.address.stateCode || '';
            const country = location.address.countryCode || '';
            const lat = location.position.lat || '';
            const lng = location.position.lng || '';

            return {
                city,
                state,
                country,
                lat,
                lng,
            };
        }

        return null;
    } catch (error) {
        console.error('Error fetching data from Here API:', error);
        printMessage(`Error fetching data from Here API: ${error}`, 'danger', 8000);
        return null;
    }
}

export const autoFillAddressHero = async (zipCode, apiKey, countrySearch = 'USA') => {
    let result: any = false;
    if (zipCode) {
        try {
            const locationData = await getLocationDataByZipCodeHero(zipCode, apiKey, countrySearch);
            if (locationData) {
                console.log(locationData);
                console.log('City:', locationData.city);
                console.log('State:', locationData.state);
                console.log('Country:', locationData.country);
                result = locationData;
                printMessage(`Autofill completed successfully, check City State Country`, 'success', 8000);
            } else {
                printMessage(`Location data not found.`, 'danger', 8000);
            }
        } catch (error) {
            printMessage(`Error fetching location data: ${error}`, 'danger', 8000);
        }
    }

    return result;
};

/**
 * Copy all driver phone numbers from the page to clipboard
 */
export const copyDriverPhones = () => {
    // Collect all phone numbers from the page
    const phoneElements = document.querySelectorAll('.driver-phone[data-phone]');
    const phoneNumbers: string[] = [];
    
    phoneElements.forEach((element) => {
        const phone = element.getAttribute('data-phone');
        if (phone && phone.trim() !== '') {
            phoneNumbers.push(phone.trim());
        }
    });
    
    if (phoneNumbers.length === 0) {
        printMessage('No phone numbers found on this page.', 'warning', 3000);
        return;
    }
    
    // Join phone numbers with newlines
    const phoneList = phoneNumbers.join('\n');
    
    // Copy to clipboard
    navigator.clipboard.writeText(phoneList).then(() => {
        // Show success message
        const copyBtn = document.getElementById('copy-driver-phones-btn');
        if (copyBtn) {
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = 'Copied!';
            copyBtn.classList.remove('btn-outline-primary');
            copyBtn.classList.add('btn-success');
            
            // Reset button after 2 seconds
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
                copyBtn.classList.remove('btn-success');
                copyBtn.classList.add('btn-outline-primary');
            }, 2000);
        }
        
        printMessage(`Successfully copied ${phoneNumbers.length} phone numbers to clipboard.`, 'success', 3000);
        
    }).catch((err) => {
        console.error('Failed to copy phone numbers: ', err);
        printMessage('Failed to copy phone numbers to clipboard.', 'danger', 5000);
    });
};

/**
 * Initialize copy driver phones functionality
 */
export const initCopyDriverPhones = () => {
    const copyBtn = document.getElementById('copy-driver-phones-btn');
    
    if (copyBtn) {
        copyBtn.addEventListener('click', copyDriverPhones);
    }
};

/**
 * Copy all driver phone numbers from the page to clipboard
 */
export const copyDriverEmails = () => {
    // Collect all phone numbers from the page
    const emailElements = document.querySelectorAll('.driver-email[data-email]');
    const emails: string[] = [];
    
    emailElements.forEach((element) => {
        const email = element.getAttribute('data-email');
        if (email && email.trim() !== '') {
            emails.push(email.trim());
        }
    });
    
    if (emails.length === 0) {
        printMessage('No emails found on this page.', 'warning', 3000);
        return;
    }
    
    // Join phone numbers with newlines
    const emailList = emails.join('\n');
    
    // Copy to clipboard
    navigator.clipboard.writeText(emailList).then(() => {
        // Show success message
        const copyBtn = document.getElementById('copy-driver-emails-btn');
        if (copyBtn) {
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = 'Copied!';
            copyBtn.classList.remove('btn-outline-primary');
            copyBtn.classList.add('btn-success');
            
            // Reset button after 2 seconds
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
                copyBtn.classList.remove('btn-success');
                copyBtn.classList.add('btn-outline-primary');
            }, 2000);
        }
        
        printMessage(`Successfully copied ${emails.length} emails to clipboard.`, 'success', 3000);
        
    }).catch((err) => {
        console.error('Failed to copy emails: ', err);
        printMessage('Failed to copy emails to clipboard.', 'danger', 5000);
    });
};

/**
 * Initialize copy driver emails functionality
 */
export const initCopyDriverEmails = () => {
    const copyBtn = document.getElementById('copy-driver-emails-btn');
    
    if (copyBtn) {
        copyBtn.addEventListener('click', copyDriverEmails);
    }
};
