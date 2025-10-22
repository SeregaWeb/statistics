/**
 * Driver Autocomplete Component
 * Handles unit number input with driver search and selection
 */
class DriverAutocomplete {
    private ajaxUrl: string;
    private searchTimeout: ReturnType<typeof setTimeout> | null = null;
    private selectedDriver: any = null;
    private searchCache: Map<string, any[]> = new Map();
    private lastSearchTerm: string = '';
    
    // Selectors for different elements
    private selectors: {
        unitInput: string;
        dropdown: string;
        attachedDriverInput: string;
        phoneInput: string;
        unitNumberNameInput: string;
        nonceInput: string;
    };

    constructor(ajaxUrl: string, selectors: {
        unitInput: string;
        dropdown: string;
        attachedDriverInput: string;
        phoneInput: string;
        unitNumberNameInput: string;
        nonceInput: string;
    }) {
        this.ajaxUrl = ajaxUrl;
        this.selectors = selectors;
        this.init();
    }

    private init(): void {
        const unitInput = document.querySelector(this.selectors.unitInput) as HTMLInputElement;
        if (!unitInput) return;

        // Event listeners
        unitInput.addEventListener('input', (e) => this.handleInput(e));
        unitInput.addEventListener('blur', () => this.handleBlur());
        unitInput.addEventListener('focus', () => this.handleFocus());
        unitInput.addEventListener('keydown', (e) => this.handleKeydown(e));

        // Click outside to close dropdown
        document.addEventListener('click', (e) => this.handleDocumentClick(e));

        // Check if there's already a selected driver on page load
        this.restoreSelectedDriver();
    }

    private handleInput(e: Event): void {
        const input = e.target as HTMLInputElement;
        const value = input.value.trim();

        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        // Clear selection if input is empty
        if (!value) {
            this.clearSelection();
            this.searchCache.clear();
            this.hideDropdown();
            return;
        }

        // Clear previous selection when user starts typing
        if (this.selectedDriver) {
            this.selectedDriver = null;
        }

        // Debounce search
        this.searchTimeout = setTimeout(() => {
            this.searchDrivers(value);
        }, 300);
    }

    private handleBlur(): void {
        setTimeout(() => {
            this.hideDropdown();
        }, 150);
    }

    private handleFocus(): void {
        const input = document.querySelector(this.selectors.unitInput) as HTMLInputElement;
        if (input) {
            const currentValue = input.value.trim();
            
            // If field is empty but we have a selected driver, show cached results for last search
            if (!currentValue && this.selectedDriver && this.lastSearchTerm && this.searchCache.has(this.lastSearchTerm)) {
                this.showDropdown(this.searchCache.get(this.lastSearchTerm)!);
            } else if (currentValue) {
                // Always check cache first
                if (this.searchCache.has(currentValue)) {
                    this.showDropdown(this.searchCache.get(currentValue)!);
                } else {
                    this.searchDrivers(currentValue);
                }
            }
        }
    }

    private handleKeydown(e: KeyboardEvent): void {
        const dropdown = document.querySelector(this.selectors.dropdown) as HTMLElement;
        const items = dropdown.querySelectorAll('.dropdown-item');
        
        if (!dropdown || items.length === 0) return;

        const currentActive = dropdown.querySelector('.dropdown-item.active');
        let activeIndex = currentActive ? Array.from(items).indexOf(currentActive) : -1;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                this.setActiveItem(items, activeIndex);
                break;
            case 'ArrowUp':
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                this.setActiveItem(items, activeIndex);
                break;
            case 'Enter':
                e.preventDefault();
                if (currentActive) {
                    this.selectDriver(currentActive as HTMLElement);
                }
                break;
            case 'Escape':
                this.hideDropdown();
                break;
        }
    }

    private handleDocumentClick(e: Event): void {
        const target = e.target as HTMLElement;
        const container = document.querySelector(this.selectors.unitInput)?.closest('.position-relative');
        
        if (container && !container.contains(target)) {
            this.hideDropdown();
        }
    }

    private async searchDrivers(unitNumber: string): Promise<void> {
        try {
            const nonceInput = document.getElementById(this.selectors.nonceInput.replace('#', '')) as HTMLInputElement;
            const nonce = nonceInput ? nonceInput.value : '';

            const formData = new FormData();
            formData.append('action', 'search_drivers_by_unit');
            formData.append('unit_number', unitNumber);
            formData.append('nonce', nonce);

            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.searchCache.set(unitNumber, data.data);
                this.lastSearchTerm = unitNumber;
                this.showDropdown(data.data);
            } else {
                this.searchCache.set(unitNumber, []);
                this.lastSearchTerm = unitNumber;
                this.hideDropdown();
            }
        } catch (error) {
            console.error('Error searching drivers:', error);
            this.hideDropdown();
        }
    }

    private showDropdown(drivers: any[]): void {
        const dropdown = document.querySelector(this.selectors.dropdown) as HTMLElement;
        if (!dropdown) return;

        if (drivers.length === 0) {
            dropdown.innerHTML = '<div class="dropdown-item text-muted">No drivers found</div>';
        } else {
            dropdown.innerHTML = drivers.map(driver => 
                `<div class="dropdown-item" data-driver='${JSON.stringify(driver)}'>
                    <strong>${driver.display_name}</strong>
                    ${driver.phone ? `<br><small class="text-muted">${driver.phone}</small>` : ''}
                </div>`
            ).join('');

            dropdown.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', () => this.selectDriver(item as HTMLElement));
            });
        }

        dropdown.style.display = 'block';
    }

    private hideDropdown(): void {
        const dropdown = document.querySelector(this.selectors.dropdown) as HTMLElement;
        if (dropdown) {
            dropdown.style.display = 'none';
        }
    }

    private setActiveItem(items: NodeListOf<Element>, index: number): void {
        items.forEach((item, i) => {
            item.classList.toggle('active', i === index);
        });
    }

    private selectDriver(item: HTMLElement): void {
        const driverData = item.getAttribute('data-driver');
        if (!driverData) return;

        try {
            const driver = JSON.parse(driverData);
            this.setSelectedDriver(driver);
            this.hideDropdown();
        } catch (error) {
            console.error('Error parsing driver data:', error);
        }
    }

    private setSelectedDriver(driver: any): void {
        this.selectedDriver = driver;

        // Update hidden fields
        const attachedDriverInput = document.querySelector(this.selectors.attachedDriverInput) as HTMLInputElement;
        if (attachedDriverInput) {
            attachedDriverInput.value = driver.driver_id;
        }

        // Clear the input field and update placeholder
        const unitInput = document.querySelector(this.selectors.unitInput) as HTMLInputElement;
        if (unitInput) {
            unitInput.value = '';
            unitInput.placeholder = `Selected: ${driver.display_name}`;
        }

        // Update phone field
        const phoneInput = document.querySelector(this.selectors.phoneInput) as HTMLInputElement;
        if (phoneInput && driver.phone) {
            phoneInput.value = driver.phone;
        }

        // Update unit_number_name field
        const unitNumberNameInput = document.querySelector(this.selectors.unitNumberNameInput) as HTMLInputElement;
        if (unitNumberNameInput) {
            unitNumberNameInput.value = driver.display_name;
        }
    }

    private clearSelection(): void {
        this.selectedDriver = null;

        // Clear hidden fields
        const attachedDriverInput = document.querySelector(this.selectors.attachedDriverInput) as HTMLInputElement;
        if (attachedDriverInput) {
            attachedDriverInput.value = '';
        }

        // Clear unit_number_name
        const unitNumberNameInput = document.querySelector(this.selectors.unitNumberNameInput) as HTMLInputElement;
        if (unitNumberNameInput) {
            unitNumberNameInput.value = '';
        }

        // Clear phone field
        const phoneInput = document.querySelector(this.selectors.phoneInput) as HTMLInputElement;
        if (phoneInput) {
            phoneInput.value = '';
        }

        // Restore original placeholder
        const unitInput = document.querySelector(this.selectors.unitInput) as HTMLInputElement;
        if (unitInput) {
            unitInput.placeholder = 'Enter unit number...';
        }
    }

    private restoreSelectedDriver(): void {
        // Check if there's already a selected driver from hidden fields
        const attachedDriverInput = document.querySelector(this.selectors.attachedDriverInput) as HTMLInputElement;
        const unitNumberNameInput = document.querySelector(this.selectors.unitNumberNameInput) as HTMLInputElement;
        const phoneInput = document.querySelector(this.selectors.phoneInput) as HTMLInputElement;

        if (attachedDriverInput && attachedDriverInput.value && 
            unitNumberNameInput && unitNumberNameInput.value &&
            phoneInput && phoneInput.value) {
            
            // Reconstruct driver object from existing data
            const driver = {
                driver_id: attachedDriverInput.value,
                display_name: unitNumberNameInput.value,
                phone: phoneInput.value,
                unit_number: attachedDriverInput.value
            };

            // Set as selected driver
            this.selectedDriver = driver;

            // Clear the input field and update placeholder
            const unitInput = document.querySelector(this.selectors.unitInput) as HTMLInputElement;
            if (unitInput) {
                unitInput.value = '';
                unitInput.placeholder = `Selected: ${driver.display_name}`;
            }
        }
    }
}

export default DriverAutocomplete;