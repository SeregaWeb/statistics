/**
 * Quick Copy functionality for driver phone numbers
 * Copies phone numbers from driver table based on status
 */

interface DriverRow {
    status: string;
    phone: string;
}

class QuickCopy {
    private tableSelector: string = '.table tbody tr';
    private statusColumnSelector: string = '.driver-status';
    private phoneColumnSelector: string = '.js-phone-driver';

    constructor() {
        this.init();
    }

    private init(): void {
        // Add event listeners to quick copy buttons
        document.addEventListener('click', (e: Event) => {
            const target = e.target as HTMLElement;
            if (target.classList.contains('js-quick-copy')) {
                e.preventDefault();
                this.handleQuickCopy(target);
            }
        });
    }

    private handleQuickCopy(button: HTMLElement): void {
        const status = button.getAttribute('data-status');
        if (!status) {
            this.showMessage('Status not found', 'danger');
            return;
        }

        console.log('status', status);
        const phones = this.getPhonesByStatus(status);
        console.log('phones', phones);
        if (phones.length === 0) {
            this.showMessage(`No drivers found with status: ${status}`, 'warning');
            return;
        }

        this.copyToClipboard(phones.join(', '));
        this.showMessage(`Copied ${phones.length} phone numbers for ${status} drivers`, 'success');
    }

    private getPhonesByStatus(targetStatus: string): string[] {
        const rows = document.querySelectorAll(this.tableSelector) as NodeListOf<HTMLElement>;
        console.log('rows', rows);
        const phones: string[] = [];

        rows.forEach((row: HTMLElement) => {
            const driverData = this.extractDriverData(row);
            console.log('driverData', driverData);
            if (driverData && this.matchesStatus(driverData.status, targetStatus)) {
                if (driverData.phone && !phones.includes(driverData.phone)) {
                    phones.push(driverData.phone);
                }
            }
        });

        return phones;
    }

    private extractDriverData(row: HTMLElement): DriverRow | null {
        try {
            // Find status element (first column)
            const statusElement = row.querySelector(this.statusColumnSelector) as HTMLElement;
            if (!statusElement) return null;

            console.log('statusElement', statusElement);
            // Find phone element directly by class
            const phoneElement = row.querySelector(this.phoneColumnSelector) as HTMLElement;
            if (!phoneElement) return null;
            console.log('phoneElement', phoneElement);

            console.log(statusElement.textContent?.trim(), phoneElement.textContent?.trim());
            const status = this.normalizeStatus(statusElement.textContent?.trim() || '');
            const phone = phoneElement.textContent?.trim() || '';

            // Skip if phone is masked (***-***-****)
            if (phone.includes('***')) {
                return null;
            }

            return { status, phone };
        } catch (error) {
            console.error('Error extracting driver data:', error);
            return null;
        }
    }

    private normalizeStatus(status: string): string {
        // Normalize status text to match our expected values
        const statusMap: { [key: string]: string } = {
            'available': 'available',
            'on hold': 'available', // on_hold counts as available
            'available on': 'available_on',
            'available_on': 'available_on',
            'loaded & enroute': 'available_on',
            'not available': 'not_available',
            'no updates': 'not_available',
            'blocked': 'not_available',
            'banned': 'not_available',
            'expired documents': 'not_available',
            'expired_documents': 'not_available',
            'need set status': 'not_available' 
        };

        const normalized = status.toLowerCase();
        return statusMap[normalized] || 'not_available';
    }

    private matchesStatus(driverStatus: string, targetStatus: string): boolean {
        if (targetStatus === 'all') {
            return true; // Include all drivers except blocked/banned
        }

        if (targetStatus === 'available') {
            return driverStatus === 'available';
        }

        if (targetStatus === 'available_on') {
            return driverStatus === 'available_on';
        }

        if (targetStatus === 'not_available') {
            return driverStatus === 'not_available';
        }

        return false;
    }

    private async copyToClipboard(text: string): Promise<void> {
        try {
            await navigator.clipboard.writeText(text);
        } catch (error) {
            // Fallback for older browsers
            this.fallbackCopyToClipboard(text);
        }
    }

    private fallbackCopyToClipboard(text: string): void {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
        } catch (error) {
            console.error('Fallback copy failed:', error);
        }

        document.body.removeChild(textArea);
    }

    private showMessage(message: string, type: 'success' | 'danger' | 'warning'): void {
        // Use existing printMessage function if available
        if (typeof (window as any).printMessage === 'function') {
            (window as any).printMessage(message, type, 3000);
        } else {
            // Fallback message display
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }
}

// Initialize Quick Copy when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new QuickCopy();
});

export default QuickCopy;
