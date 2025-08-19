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
    }

    private handleRatingClick(button: HTMLElement): void {
        const driverId = button.getAttribute('data-driver-id');
        const driverName = button.getAttribute('data-driver-name');
        const rating = button.getAttribute('data-rating');

        if (!driverId) {
            this.showMessage('Driver ID not found', 'danger');
            return;
        }

        // Update popup content
        const nameElement = document.getElementById('driverRatingName');
        const scoreElement = document.getElementById('driverRatingScore');
        const fullPageLink = document.getElementById('driverRatingFullPage') as HTMLAnchorElement;

        if (nameElement) nameElement.textContent = driverName || 'Unknown Driver';
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

        // Load rating data
        this.loadDriverRatings(parseInt(driverId));
    }

    private handleNoticeClick(button: HTMLElement): void {
        const driverId = button.getAttribute('data-driver-id');
        const driverName = button.getAttribute('data-driver-name');
        const noticeCount = button.getAttribute('data-notice-count');

        if (!driverId) {
            this.showMessage('Driver ID not found', 'danger');
            return;
        }

        // Update popup content
        const nameElement = document.getElementById('driverNoticeName');
        const countElement = document.getElementById('driverNoticeCount');
        const fullPageLink = document.getElementById('driverNoticeFullPage') as HTMLAnchorElement;

        if (nameElement) nameElement.textContent = driverName || 'Unknown Driver';
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
                this.displayRatings(data.data, contentElement);
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
                            <span class="badge bg-${notice.status ? 'success' : 'warning'}">
                                ${notice.status ? 'Resolved' : 'Pending'}
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

// Initialize Driver Popups when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new DriverPopups();
});

export default DriverPopups;
