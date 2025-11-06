/**
 * Broker Popups Component
 * Handles notice popups for brokers using existing popup system
 */

interface BrokerNotice {
    id: number;
    name: string;
    date: number;
    message: string;
    load_number: string;
    status: number;
}

class BrokerPopups {
    private addNewBrokerUrl: string = '';
    private ajaxUrl: string = '';
    private popupSystem: any = null;

    constructor(ajaxUrl: string, addNewBrokerUrl: string = '') {
        this.ajaxUrl = ajaxUrl;
        this.addNewBrokerUrl = addNewBrokerUrl;
        this.init();
    }

    private init(): void {
        // Add event listeners
        this.addEventListeners();
        
        // Import existing popup system
        this.importPopupSystem();
    }

    private async importPopupSystem(): Promise<void> {
        try {
            // Wait a bit for the page to load completely
            await new Promise(resolve => setTimeout(resolve, 100));
            
            const { default: Popup } = await import('../parts/popup-window.js');
            this.popupSystem = new Popup();
            console.log('Popup system initialized successfully for brokers');
        } catch (error) {
            console.warn('Popup system not available:', error);
        }
    }

    private addEventListeners(): void {
        // Notice button clicks
        document.addEventListener('click', (e: Event) => {
            const target = e.target as HTMLElement;
            // Check if clicked element or its parent has the class
            const button = target.closest('.js-broker-notice-btn') as HTMLElement;
            if (button) {
                e.preventDefault();
                this.handleNoticeClick(button);
            }
            // Load more notices button
            if (target && target.id === 'brokerNoticesLoadMore') {
                e.preventDefault();
                this.revealMoreNotices(target as HTMLButtonElement);
            }
        });

        // Close popup clicks (use existing system)
        document.addEventListener('click', (e: Event) => {
            const target = e.target as HTMLElement;
            if (target.classList.contains('js-popup-close')) {
                e.preventDefault();
                if (this.popupSystem) {
                    this.popupSystem.forceCloseAllPopup();
                }
            }
        });

        // ESC key to close popup (use existing system)
        document.addEventListener('keydown', (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                if (this.popupSystem) {
                    this.popupSystem.forceCloseAllPopup();
                }
            }
        });
        
        // Listen for broker notice added event to reload notices
        document.addEventListener('tms:broker-notice-added', (e: any) => {
            if (e.detail && e.detail.brokerId) {
                this.loadBrokerNotices(parseInt(e.detail.brokerId));
            }
        });
    }

    private handleNoticeClick(button: HTMLElement): void {
        const brokerId = button.getAttribute('data-broker-id');
        const brokerName = button.getAttribute('data-broker-name');
        const noticeCount = button.getAttribute('data-notice-count');

        console.log('Broker notice button clicked:', button);
        console.log('Broker ID from button:', brokerId);
        console.log('Broker name from button:', brokerName);
        console.log('Notice count from button:', noticeCount);

        if (!brokerId) {
            this.showMessage('Broker ID not found', 'danger');
            return;
        }

        // Update popup content
        const nameElement = document.getElementById('brokerNoticeName');
        const countElement = document.getElementById('brokerNoticeCount');
        const fullPageLink = document.getElementById('brokerNoticeFullPage') as HTMLAnchorElement;
        const brokerIdField = document.getElementById('brokerNoticeId') as HTMLInputElement;

        if (nameElement) {
            nameElement.textContent = brokerName || 'Unknown Broker';
            nameElement.setAttribute('data-broker-id', brokerId);
        }
        
        // Update hidden broker_id field in the form
        if (brokerIdField) {
            brokerIdField.value = brokerId;
        }
        
        if (countElement) countElement.textContent = noticeCount || '0';
        if (fullPageLink && this.addNewBrokerUrl) {
            fullPageLink.href = `${this.addNewBrokerUrl}?broker_id=${brokerId}`;
        }

        // Clear previous content and show loading
        const contentElement = document.getElementById('brokerNoticeContent');
        if (contentElement) {
            contentElement.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
        }
        
        // Clear the form to reset previous values
        const noticeForm = document.getElementById('brokerNoticeForm') as HTMLFormElement;
        if (noticeForm) {
            const messageField = noticeForm.querySelector('textarea[name="message"]') as HTMLTextAreaElement;
            const loadNumberField = noticeForm.querySelector('input[name="load_number"]') as HTMLInputElement;
            if (messageField) {
                messageField.value = '';
            }
            if (loadNumberField) {
                loadNumberField.value = '';
            }
        }

        // Show popup using existing system
        this.openPopup('#broker-notice-popup');

        // Load notice data
        this.loadBrokerNotices(parseInt(brokerId));
    }

    private openPopup(selector: string): void {
        // Always use manual opening to avoid fadeOut conflicts
        const popupElement = document.querySelector(selector) as HTMLElement;
        if (popupElement) {
            // Reset any inline styles that might interfere
            popupElement.style.opacity = '';
            popupElement.style.display = '';
            
            popupElement.classList.add('popup-active');
            document.body.classList.add('popup-opened');
            document.documentElement.classList.add('popup-opened');
        }
    }

    private async loadBrokerNotices(brokerId: number): Promise<void> {
        try {
            const formData = new FormData();
            formData.append('action', 'get_broker_notices');
            formData.append('broker_id', brokerId.toString());

            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.displayBrokerNotices(data.data || []);
            } else {
                this.displayBrokerNotices([]);
            }
        } catch (error) {
            console.error('Error loading broker notices:', error);
            this.showMessage('Failed to load notices', 'danger');
            const contentElement = document.getElementById('brokerNoticeContent');
            if (contentElement) {
                contentElement.innerHTML = '<p class="text-danger">Error loading notices</p>';
            }
        }
    }

    private displayBrokerNotices(notices: BrokerNotice[]): void {
        const contentElement = document.getElementById('brokerNoticeContent');
        if (!contentElement) return;

        if (notices.length === 0) {
            contentElement.innerHTML = '<p class="text-muted">No notices found</p>';
            return;
        }

        let html = '<div class="list-group">';
        
        notices.forEach((notice) => {
            const date = new Date(notice.date * 1000).toLocaleString();
            const loadNumberDisplay = notice.load_number ? ` (Load: ${notice.load_number})` : '';
            
            html += `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${this.escapeHtml(notice.name)}${loadNumberDisplay}</h6>
                        <small>${date}</small>
                    </div>
                    <p class="mb-1">${this.escapeHtml(notice.message)}</p>
                </div>
            `;
        });
        
        html += '</div>';
        contentElement.innerHTML = html;
    }

    private escapeHtml(text: string): string {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Reveal more notices in chunks
    private revealMoreNotices(button: HTMLButtonElement): void {
        const stepAttr = button.getAttribute('data-step');
        const step = stepAttr ? parseInt(stepAttr, 10) : 5;
        const rows = Array.from(document.querySelectorAll<HTMLTableRowElement>('.js-broker-notice-row'));
        
        let revealedCount = 0;
        rows.forEach((row) => {
            if (row.style.display === 'none') {
                row.style.display = '';
                revealedCount++;
                if (revealedCount >= step) {
                    return;
                }
            }
        });
        
        // Hide button if all rows are visible
        const hiddenRows = rows.filter(row => row.style.display === 'none');
        if (hiddenRows.length === 0) {
            button.classList.add('d-none');
        } else {
            button.classList.remove('d-none');
        }
    }

    private showMessage(message: string, type: string = 'info'): void {
        // Use existing message system if available
        if ((window as any).printMessage) {
            (window as any).printMessage(message, type, 3000);
        } else {
            console.log(`[${type}] ${message}`);
        }
    }
}

export default BrokerPopups;

