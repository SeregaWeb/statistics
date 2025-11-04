/**
 * Dark Mode Toggle TypeScript
 * Handles dark mode switching via AJAX and updates UI
 */

interface DarkModeResponse {
    success: boolean;
    data: {
        enabled: boolean;
        message: string;
    };
}

class DarkModeToggle {
    private toggleSwitch: HTMLInputElement | null = null;
    private ajaxUrl: string;

    constructor(ajaxUrl: string) {
        this.ajaxUrl = ajaxUrl;
        this.init();
    }

    private init(): void {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupEventListeners());
        } else {
            this.setupEventListeners();
        }
    }

    private setupEventListeners(): void {
        this.toggleSwitch = document.getElementById('night_mode') as HTMLInputElement;
        
        if (!this.toggleSwitch) {
            console.warn('Dark mode toggle switch not found');
            return;
        }

        // Set initial state from cookie (check if dark mode is enabled)
        const isDarkModeEnabled = document.body.classList.contains('dark-mode');
        this.toggleSwitch.checked = isDarkModeEnabled;
        
        // Initialize dynamic colors on page load
        this.updateDynamicColors(isDarkModeEnabled);
        
        // Add event listener for toggle
        this.toggleSwitch.addEventListener('change', (e: Event) => {
            const target = e.target as HTMLInputElement;
            this.handleToggle(target.checked);
        });
    }

    private handleToggle(isEnabled: boolean): void {
        // Update body class immediately for better UX
        this.updateBodyClass(isEnabled);
        
        // Send AJAX request
        this.sendAjaxRequest(isEnabled);
    }

    private updateBodyClass(isEnabled: boolean): void {
        const body = document.body;
        
        if (isEnabled) {
            body.classList.add('dark-mode');
        } else {
            body.classList.remove('dark-mode');
        }
        
        // Update colors for elements with js-change-color class
        this.updateDynamicColors(isEnabled);
    }
    
    /**
     * Update colors for elements with js-change-color class
     */
    private updateDynamicColors(isDarkMode: boolean): void {
        const elements = document.querySelectorAll('.js-change-color') as NodeListOf<HTMLElement>;
        
        elements.forEach((element) => {
            const colorLight = element.getAttribute('data-color-light');
            const colorDark = element.getAttribute('data-color-dark');
            
            if (colorLight && colorDark) {
                const newColor = isDarkMode ? colorDark : colorLight;
                element.style.setProperty('background-color', newColor, 'important');
            }
        });
    }

    private async sendAjaxRequest(isEnabled: boolean): Promise<void> {
        const formData = new FormData();
        formData.append('action', 'toggle_dark_mode');
        formData.append('enabled', isEnabled.toString());
        
        try {
            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            
            const data: DarkModeResponse = await response.json();
            
            if (data.success) {
                console.log('Dark mode toggled:', data.data.message);
            } else {
                console.error('Failed to toggle dark mode:', data.data);
                // Revert UI state on error
                this.revertToggle();
            }
        } catch (error) {
            console.error('AJAX error:', error);
            // Revert UI state on error
            this.revertToggle();
        }
    }

    private revertToggle(): void {
        if (this.toggleSwitch) {
            this.toggleSwitch.checked = !this.toggleSwitch.checked;
            const isDarkMode = this.toggleSwitch.checked;
            // Update body class and colors
            if (isDarkMode) {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
            this.updateDynamicColors(isDarkMode);
        }
    }
}

// Export for potential module usage
export default DarkModeToggle;
