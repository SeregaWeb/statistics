/**
 * Admin Rating Manager Component
 * Handles driver rating search and deletion for administrators
 */

export class AdminRatingManager {
    private driverIdInput: HTMLInputElement;
    private findBtn: HTMLButtonElement;
    private driverInfo: HTMLElement;
    private driverNameSpan: HTMLElement;
    private driverIdDisplay: HTMLElement;
    private ratingsContainer: HTMLElement;
    private ratingsTableBody: HTMLElement;
    private ratingsCount: HTMLElement;
    private selectAllCheckbox: HTMLInputElement;
    private deleteBtn: HTMLButtonElement;
    private messageDiv: HTMLElement;
    private currentDriverId: number | null = null;
    private ajaxUrl: string;
    private nonce: string;

    constructor() {
        this.driverIdInput = document.getElementById('admin-driver-id-input') as HTMLInputElement;
        this.findBtn = document.getElementById('admin-find-driver-btn') as HTMLButtonElement;
        this.driverInfo = document.getElementById('admin-driver-info') as HTMLElement;
        this.driverNameSpan = document.getElementById('admin-driver-name') as HTMLElement;
        this.driverIdDisplay = document.getElementById('admin-driver-id-display') as HTMLElement;
        this.ratingsContainer = document.getElementById('admin-ratings-container') as HTMLElement;
        this.ratingsTableBody = document.getElementById('admin-ratings-table-body') as HTMLElement;
        this.ratingsCount = document.getElementById('admin-ratings-count') as HTMLElement;
        this.selectAllCheckbox = document.getElementById('admin-select-all-ratings') as HTMLInputElement;
        this.deleteBtn = document.getElementById('admin-delete-ratings-btn') as HTMLButtonElement;
        this.messageDiv = document.getElementById('admin-ratings-message') as HTMLElement;

        // Get AJAX URL and nonce from data attributes or global variables
        const managerElement = document.querySelector('.admin-rating-manager');
        if (managerElement) {
            this.ajaxUrl = managerElement.getAttribute('data-ajax-url') || '';
            this.nonce = managerElement.getAttribute('data-nonce') || '';
        } else {
            // Fallback to WordPress default
            if (typeof (window as any).var_from_php !== 'undefined' && (window as any).var_from_php.ajax_url) {
                this.ajaxUrl = (window as any).var_from_php.ajax_url;
            } else {
                this.ajaxUrl = '/wp-admin/admin-ajax.php';
            }
            this.nonce = '';
        }

        if (this.driverIdInput && this.findBtn) {
            this.init();
        }
    }

    private init(): void {
        this.findBtn.addEventListener('click', () => this.handleFindDriver());
        this.driverIdInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.handleFindDriver();
            }
        });

        if (this.selectAllCheckbox) {
            this.selectAllCheckbox.addEventListener('change', () => this.handleSelectAll());
        }

        if (this.deleteBtn) {
            this.deleteBtn.addEventListener('click', () => this.handleDeleteRatings());
        }
    }

    private showMessage(text: string, type: string = 'info'): void {
        if (!this.messageDiv) return;
        
        this.messageDiv.innerHTML = `<div class="alert alert-${type}">${text}</div>`;
        setTimeout(() => {
            this.messageDiv.innerHTML = '';
        }, 5000);
    }

    private updateDeleteButton(): void {
        if (!this.deleteBtn) return;
        
        const checked = document.querySelectorAll('#admin-ratings-table-body input[type="checkbox"]:checked');
        this.deleteBtn.disabled = checked.length === 0;
    }

    private updateRowSelection(row: HTMLTableRowElement, isChecked: boolean): void {
        if (isChecked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    }

    private renderRatings(ratings: any[]): void {
        if (!this.ratingsTableBody || !this.ratingsCount) return;

        this.ratingsTableBody.innerHTML = '';
        this.ratingsCount.textContent = ratings.length.toString();

        if (ratings.length === 0) {
            this.ratingsTableBody.innerHTML = '<tr><td colspan="7" class="text-center">No ratings found</td></tr>';
            return;
        }

        ratings.forEach((rating) => {
            const row = document.createElement('tr');
            const stars = '★'.repeat(rating.reit) + '☆'.repeat(5 - rating.reit);
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'rating-checkbox';
            checkbox.value = rating.id.toString();

            checkbox.addEventListener('change', () => {
                this.updateRowSelection(row, checkbox.checked);
                this.updateDeleteButton();
            });

            // Make entire row clickable
            row.addEventListener('click', (e) => {
                // Don't toggle if clicking directly on checkbox
                if (e.target !== checkbox && (e.target as HTMLElement).tagName !== 'INPUT') {
                    checkbox.checked = !checkbox.checked;
                    this.updateRowSelection(row, checkbox.checked);
                    this.updateDeleteButton();
                }
            });

            row.innerHTML = `
                <td></td>
                <td>${rating.id}</td>
                <td>${rating.name || 'N/A'}</td>
                <td><span class="rating-stars-display">${stars}</span> (${rating.reit})</td>
                <td>${rating.order_number || 'N/A'}</td>
                <td>${rating.formatted_time || 'N/A'}</td>
                <td>${rating.message ? rating.message.substring(0, 50) + (rating.message.length > 50 ? '...' : '') : 'N/A'}</td>
            `;

            // Insert checkbox into first cell
            const firstCell = row.querySelector('td:first-child');
            if (firstCell) {
                firstCell.appendChild(checkbox);
            }

            this.ratingsTableBody.appendChild(row);
        });

        this.updateDeleteButton();
    }

    private handleFindDriver(): void {
        if (!this.driverIdInput || !this.findBtn) return;

        const driverId = this.driverIdInput.value.trim();

        if (!driverId) {
            this.showMessage('Please enter a driver ID', 'warning');
            return;
        }

        this.findBtn.disabled = true;
        this.findBtn.textContent = 'Loading...';

        const formData = new FormData();
        formData.append('action', 'admin_get_driver_ratings');
        formData.append('driver_id', driverId);
        if (this.nonce) {
            formData.append('nonce', this.nonce);
        }

        fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.findBtn.disabled = false;
            this.findBtn.textContent = 'Find';

            if (data.success) {
                this.currentDriverId = data.data.driver_id;
                if (this.driverNameSpan) {
                    this.driverNameSpan.textContent = data.data.driver_name;
                }
                if (this.driverIdDisplay) {
                    this.driverIdDisplay.textContent = data.data.driver_id.toString();
                }
                if (this.driverInfo) {
                    this.driverInfo.style.display = 'block';
                }
                if (this.ratingsContainer) {
                    this.ratingsContainer.style.display = 'block';
                }
                this.renderRatings(data.data.ratings);
                this.showMessage('Driver ratings loaded successfully', 'success');
            } else {
                this.showMessage(data.data?.message || 'Error loading ratings', 'danger');
                if (this.driverInfo) {
                    this.driverInfo.style.display = 'none';
                }
                if (this.ratingsContainer) {
                    this.ratingsContainer.style.display = 'none';
                }
            }
        })
        .catch(error => {
            this.findBtn.disabled = false;
            this.findBtn.textContent = 'Find';
            this.showMessage('Network error: ' + error.message, 'danger');
        });
    }

    private handleSelectAll(): void {
        if (!this.selectAllCheckbox) return;

        document.querySelectorAll('#admin-ratings-table-body .rating-checkbox').forEach((cb) => {
            const checkbox = cb as HTMLInputElement;
            checkbox.checked = this.selectAllCheckbox.checked;
            const row = checkbox.closest('tr');
            if (row) {
                if (this.selectAllCheckbox.checked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            }
        });
        this.updateDeleteButton();
    }

    private handleDeleteRatings(): void {
        if (!this.deleteBtn) return;

        const checked = Array.from(document.querySelectorAll('#admin-ratings-table-body input[type="checkbox"]:checked'))
            .map(cb => (cb as HTMLInputElement).value);

        if (checked.length === 0) {
            this.showMessage('Please select at least one rating to delete', 'warning');
            return;
        }

        if (!confirm(`Are you sure you want to delete ${checked.length} rating(s)? This action cannot be undone.`)) {
            return;
        }

        this.deleteBtn.disabled = true;
        this.deleteBtn.textContent = 'Deleting...';

        const formData = new FormData();
        formData.append('action', 'admin_delete_driver_ratings');
        // Append each rating ID separately so PHP receives them as array
        checked.forEach((id) => {
            formData.append('rating_ids[]', id);
        });
        if (this.nonce) {
            formData.append('nonce', this.nonce);
        }

        fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            this.deleteBtn.disabled = false;
            this.deleteBtn.textContent = 'Delete Selected';

            if (data.success) {
                this.showMessage(data.data?.message || 'Ratings deleted successfully', 'success');
                // Reload ratings
                if (this.currentDriverId) {
                    this.driverIdInput.value = this.currentDriverId.toString();
                    this.handleFindDriver();
                }
            } else {
                this.showMessage(data.data?.message || 'Error deleting ratings', 'danger');
            }
        })
        .catch(error => {
            this.deleteBtn.disabled = false;
            this.deleteBtn.textContent = 'Delete Selected';
            this.showMessage('Network error: ' + error.message, 'danger');
        });
    }
}

// Initialize when DOM is ready
export function initAdminRatingManager(): void {
    if (document.querySelector('.admin-rating-manager')) {
        new AdminRatingManager();
    }
}

