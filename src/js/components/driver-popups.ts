/**
 * Driver Popups Component
 * Handles rating and notice popups for drivers using existing popup system
 */

interface DriverRating {
    id: number;
    name: string;
    time: number;
    reit: number;
    message: string;
    order_number: string;
}

interface DriverNotice {
    id: number;
    name: string;
    date: number;
    message: string;
    status: number;
}

import { populateLoadSelect } from '../utils/load-select';

class DriverPopups {
    private addNewLoadUrl: string = '';
    private popupSystem: any = null;

    constructor() {
        this.init();
    }

    private init(): void {
        // Get add new load URL from global variable
        this.addNewLoadUrl = (window as any).var_from_php?.add_new_load_url || '/add-new-load/';
        
        // Add event listeners
        this.addEventListeners();
        
        // Import existing popup system
        this.importPopupSystem();

        // Server-side hides initial rows; no client init needed
    }

    private async importPopupSystem(): Promise<void> {
        try {
            // Wait a bit for the page to load completely
            await new Promise(resolve => setTimeout(resolve, 100));
            
            const { default: Popup } = await import('../parts/popup-window.js');
            this.popupSystem = new Popup();
            console.log('Popup system initialized successfully');
        } catch (error) {
            console.warn('Popup system not available:', error);
        }
    }

    private addEventListeners(): void {
        // Rating button clicks
        document.addEventListener('click', (e: Event) => {
            const target = e.target as HTMLElement;
            
            if (target.classList.contains('js-driver-rating-btn')) {
                e.preventDefault();
                this.handleRatingClick(target);
            }
        });

        // Notice button clicks
        document.addEventListener('click', (e: Event) => {
            const target = e.target as HTMLElement;
            if (target.classList.contains('js-driver-notice-btn')) {
                e.preventDefault();
                this.handleNoticeClick(target);
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

        // Load more ratings on driver single page
        document.addEventListener('click', (e: Event) => {
            const target = e.target as HTMLElement;
            if (target && target.id === 'ratingsLoadMore') {
                e.preventDefault();
                this.revealMoreRatings(target as HTMLButtonElement);
            }
            if (target && target.id === 'noticesLoadMore') {
                e.preventDefault();
                this.revealMoreNotices(target as HTMLButtonElement);
            }
        });
    }

    private handleRatingClick(button: HTMLElement): void {
        const driverId = button.getAttribute('data-driver-id');
        const driverName = button.getAttribute('data-driver-name');
        const rating = button.getAttribute('data-rating');

        console.log('Rating button clicked:', button);
        console.log('Driver ID from button:', driverId);
        console.log('Driver name from button:', driverName);
        console.log('Rating from button:', rating);

        if (!driverId) {
            this.showMessage('Driver ID not found', 'danger');
            return;
        }

        // Update popup content
        const nameElement = document.getElementById('driverRatingName');
        const scoreElement = document.getElementById('driverRatingScore');
        const fullPageLink = document.getElementById('driverRatingFullPage') as HTMLAnchorElement;

        if (nameElement) {
            nameElement.textContent = driverName || 'Unknown Driver';
            nameElement.setAttribute('data-driver-id', driverId);
            console.log('Setting driver ID for ratings:', driverId);
            console.log('Element after setting:', nameElement);
        }
        if (scoreElement) scoreElement.textContent = rating || '0';
        if (fullPageLink) {
            fullPageLink.href = `${this.addNewLoadUrl}?driver=${driverId}&tab=pills-driver-stats-tab`;
        }

        // Clear previous content and show loading
        const contentElement = document.getElementById('driverRatingContent');
        if (contentElement) {
            contentElement.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
        }

        // Show popup using existing system
        this.openPopup('#driver-rating-popup');

        // Notify forms module to reset rating UI state
        try {
            document.dispatchEvent(new CustomEvent('tms:rating-popup-open'));
        } catch (e) {
            // no-op
        }

        // Load rating data
        this.loadDriverRatings(parseInt(driverId));
    }

    // Reveal more notices in chunks
    private revealMoreNotices(button: HTMLButtonElement): void {
        const stepAttr = button.getAttribute('data-step');
        const step = stepAttr ? parseInt(stepAttr, 10) : 5;
        const rows = Array.from(document.querySelectorAll<HTMLTableRowElement>('.js-notice-row'));
        if (rows.length === 0) {
            button.classList.add('d-none');
            return;
        }

        const isRowVisible = (row: HTMLTableRowElement) => row.style.display !== 'none';
        const visibleCount = rows.reduce((acc, r) => acc + (isRowVisible(r) ? 1 : 0), 0);
        const nextVisible = Math.min(visibleCount + step, rows.length);

        for (let i = visibleCount; i < nextVisible; i++) {
            rows[i].style.display = '';
        }

        if (nextVisible >= rows.length) {
            button.classList.add('d-none');
        } else {
            button.classList.remove('d-none');
        }
    }

    private handleNoticeClick(button: HTMLElement): void {
        const driverId = button.getAttribute('data-driver-id');
        const driverName = button.getAttribute('data-driver-name');
        const noticeCount = button.getAttribute('data-notice-count');

        console.log('Notice button clicked:', button);
        console.log('Driver ID from button:', driverId);
        console.log('Driver name from button:', driverName);
        console.log('Notice count from button:', noticeCount);

        if (!driverId) {
            this.showMessage('Driver ID not found', 'danger');
            return;
        }

        // Update popup content
        const nameElement = document.getElementById('driverNoticeName');
        const countElement = document.getElementById('driverNoticeCount');
        const fullPageLink = document.getElementById('driverNoticeFullPage') as HTMLAnchorElement;
        const driverIdField = document.getElementById('noticeDriverId') as HTMLInputElement;

        if (nameElement) {
            nameElement.textContent = driverName || 'Unknown Driver';
            nameElement.setAttribute('data-driver-id', driverId);
            console.log('Setting driver ID for notices:', driverId);
            console.log('Element after setting:', nameElement);
        }
        
        // Update hidden driver_id field in the form
        if (driverIdField) {
            driverIdField.value = driverId;
            console.log('Updated noticeDriverId field:', driverId);
        }
        
        if (countElement) countElement.textContent = noticeCount || '0';
        if (fullPageLink) {
            fullPageLink.href = `${this.addNewLoadUrl}?driver=${driverId}&tab=pills-driver-stats-tab`;
        }

        // Clear previous content and show loading
        const contentElement = document.getElementById('driverNoticeContent');
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
        const noticeForm = document.getElementById('noticeForm') as HTMLFormElement;
        if (noticeForm) {
            // Reset all fields except driver_id
            const messageField = noticeForm.querySelector('textarea[name="message"]') as HTMLTextAreaElement;
            if (messageField) {
                messageField.value = '';
            }
        }

        // Dispatch event to update currentDriverId in DriverPopupForms
        try {
            document.dispatchEvent(new CustomEvent('tms:notice-popup-open', {
                detail: { driverId: driverId }
            }));
        } catch (e) {
            console.warn('Failed to dispatch notice popup open event:', e);
        }

        // Show popup using existing system
        this.openPopup('#driver-notice-popup');

        // Load notice data
        this.loadDriverNotices(parseInt(driverId));
    }

    private openPopup(selector: string): void {
        console.log('Opening popup:', selector, 'Popup system available:', !!this.popupSystem);
        
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



    private async loadDriverRatings(driverId: number): Promise<void> {
        const contentElement = document.getElementById('driverRatingContent');
        if (!contentElement) return;

        try {
            const formData = new FormData();
            formData.append('action', 'get_driver_ratings');
            formData.append('driver_id', driverId.toString());

            const response = await fetch((window as any).var_from_php?.ajax_url || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // data.data now contains both ratings and available_loads
                this.displayRatings(data.data.ratings, contentElement);
                
                // Update available loads in the form
                this.updateAvailableLoads(data.data.available_loads);
            } else {
                contentElement.innerHTML = '<div class="alert alert-danger">Failed to load ratings</div>';
            }
        } catch (error) {
            console.error('Error loading ratings:', error);
            contentElement.innerHTML = '<div class="alert alert-danger">Error loading ratings</div>';
        }
    }

    private async loadDriverNotices(driverId: number): Promise<void> {
        const contentElement = document.getElementById('driverNoticeContent');
        if (!contentElement) return;

        try {
            const formData = new FormData();
            formData.append('action', 'get_driver_notices');
            formData.append('driver_id', driverId.toString());

            const response = await fetch((window as any).var_from_php?.ajax_url || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.displayNotices(data.data, contentElement);
            } else {
                contentElement.innerHTML = '<div class="alert alert-danger">Failed to load notices</div>';
            }
        } catch (error) {
            console.error('Error loading notices:', error);
            contentElement.innerHTML = '<div class="alert alert-danger">Error loading notices</div>';
        }
    }

    private displayRatings(ratings: DriverRating[], container: HTMLElement): void {
        if (!ratings || ratings.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No ratings found for this driver.</div>';
            return;
        }

        const ratingsHtml = ratings.map(rating => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong>${rating.name}</strong>
                            ${rating.order_number ? `<small class="text-muted">Order: ${rating.order_number}</small>` : ''}
                        </div>
                        <div class="text-end">
                            <span class="badge bg-${this.getRatingColor(rating.reit)}">${rating.reit}/5</span>
                            <small class="text-muted d-block">${this.formatDate(rating.time)}</small>
                        </div>
                    </div>
                    ${rating.message ? `<p class="mb-0">${rating.message}</p>` : ''}
                </div>
            </div>
        `).join('');

        container.innerHTML = ratingsHtml;

        // After rendering ratings inside popup we do not paginate here
    }

    // Helper for driver single page table: reveals 5 more rows on each click
    private revealMoreRatings(button: HTMLButtonElement): void {
        const stepAttr = button.getAttribute('data-step');
        const step = stepAttr ? parseInt(stepAttr, 10) : 5;
        const rows = Array.from(document.querySelectorAll<HTMLTableRowElement>('.js-rating-row'));
        if (rows.length === 0) {
            button.classList.add('d-none');
            return;
        }

        // Determine how many rows are currently visible
        const isRowVisible = (row: HTMLTableRowElement) => row.style.display !== 'none';
        let visibleCount = rows.reduce((acc, r) => acc + (isRowVisible(r) ? 1 : 0), 0);

        // On first run, if nothing hidden logic prepared yet, hide rows beyond first step
        if (visibleCount === 0 || visibleCount === rows.length) {
            rows.forEach((r, idx) => {
                r.style.display = idx < step ? '' : 'none';
            });
            visibleCount = Math.min(step, rows.length);
        } else {
            // Reveal next chunk
            const nextVisible = Math.min(visibleCount + step, rows.length);
            for (let i = visibleCount; i < nextVisible; i++) {
                rows[i].style.display = '';
            }
            visibleCount = nextVisible;
        }

        // Toggle button visibility
        if (visibleCount >= rows.length) {
            button.classList.add('d-none');
        } else {
            button.classList.remove('d-none');
        }
    }

    // setupDriverPageRatingsPagination removed; handled by PHP to avoid flash

    private updateAvailableLoads(availableLoads: any[]): void {
        const loadSelect = document.getElementById('loadNumber') as HTMLSelectElement;
        const loadsInfo = document.getElementById('loadsInfo') as HTMLElement;
        if (!loadSelect || !loadsInfo) return;
        populateLoadSelect(loadSelect, loadsInfo, availableLoads);
    }

    private displayNotices(notices: DriverNotice[], container: HTMLElement): void {
        if (!notices || notices.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No notices found for this driver.</div>';
            return;
        }

        const noticesHtml = notices.map(notice => `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong>${notice.name}</strong>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-${+notice.status === 1 ? 'success' : 'warning'}">
                                ${+notice.status === 1 ? 'Resolved' : 'Pending'}
                            </span>
                            <small class="text-muted d-block">${this.formatDate(notice.date)}</small>
                        </div>
                    </div>
                    ${notice.message ? `<p class="mb-0">${notice.message}</p>` : ''}
                </div>
            </div>
        `).join('');

        container.innerHTML = noticesHtml;
    }

    private getRatingColor(rating: number): string {
        if (rating >= 4) return 'success';
        if (rating >= 3) return 'warning';
        return 'danger';
    }

    private formatDate(timestamp: number): string {
        const date = new Date(timestamp * 1000);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }

    private showMessage(message: string, type: 'success' | 'danger' | 'warning'): void {
        if (typeof (window as any).printMessage === 'function') {
            (window as any).printMessage(message, type, 3000);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }
}

// Create global instance
let driverPopupsInstance: DriverPopups | null = null;

// Initialize Driver Popups when DOM is ready
driverPopupsInstance = new DriverPopups();

// Export both class and instance
export default DriverPopups;
export { driverPopupsInstance };
