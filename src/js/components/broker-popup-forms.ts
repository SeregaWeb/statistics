import { printMessage } from './info-messages';

/**
 * Broker Popup Forms Handler
 * Handles forms for adding notices in broker popups
 */

class BrokerPopupForms {
    private ajaxUrl: string;
    private currentBrokerId: string | null = null;

    constructor(ajaxUrl: string) {
        this.ajaxUrl = ajaxUrl;
        this.init();
    }

    private init(): void {
        this.handleNoticeForm();
        this.listenForPopupOpen();
    }

    /**
     * Listen for popup open events to capture broker ID
     */
    private listenForPopupOpen(): void {
        // Listen for when popups are opened
        document.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;
            
            // Check if it's a broker notice button
            if (target.closest('[data-broker-id]')) {
                const button = target.closest('[data-broker-id]') as HTMLElement;
                const brokerId = button.getAttribute('data-broker-id');
                if (brokerId) {
                    this.currentBrokerId = brokerId;
                }
            }
        });
    }

    /**
     * Handle notice form submission
     */
    private handleNoticeForm(): void {
        // Use event delegation to handle form submission for dynamically loaded forms
        document.addEventListener('submit', (e) => {
            const target = e.target as HTMLFormElement;
            if (target && target.id === 'brokerNoticeForm') {
                e.preventDefault();
                
                // Always prioritize the hidden field in the form (most reliable)
                const brokerIdField = document.getElementById('brokerNoticeId') as HTMLInputElement;
                let brokerId = brokerIdField ? brokerIdField.value : null;
                
                // Fallback to other methods if hidden field is empty
                if (!brokerId || brokerId.trim() === '') {
                    // Check if broker_id is already in the form (for modal on broker-single page)
                    const existingBrokerId = target.querySelector('input[name="broker_id"]') as HTMLInputElement;
                    brokerId = existingBrokerId ? existingBrokerId.value : null;
                    
                    // If still no broker_id, try to get it from popup or current context
                    if (!brokerId) {
                        brokerId = this.currentBrokerId || this.getBrokerIdFromPopup('brokerNoticeName');
                        
                        // Set the broker ID in the hidden field if it exists
                        if (brokerIdField && brokerId) {
                            brokerIdField.value = brokerId;
                        }
                    }
                }
                
                console.log('Submitting broker notice form with broker ID:', brokerId);
                
                if (brokerId && brokerId.trim() !== '') {
                    const formData = new FormData(target);
                    
                    // Ensure broker_id is always set correctly
                    formData.set('broker_id', brokerId);
                    
                    // Add action for AJAX
                    formData.set('action', 'add_broker_notice');
                    
                    this.submitForm(formData, 'notice');
                } else {
                    printMessage('No broker ID found!', 'danger', 3000);
                    return;
                }
            }
        });
    }

    /**
     * Get broker ID from popup element
     */
    private getBrokerIdFromPopup(elementId: string): string | null {
        const element = document.getElementById(elementId);
        if (element) {
            return element.getAttribute('data-broker-id');
        }
        return null;
    }
    
    /**
     * Add new notice to the table on broker single page
     */
    private addNoticeToTable(noticeData: any): void {
        if (!noticeData) {
            return;
        }
        
        // Get current user name from the notice data or use default
        const userName = noticeData.name || 'Current User';
        const noticeDate = noticeData.date || Math.floor(Date.now() / 1000);
        const noticeMessage = noticeData.message || '';
        const noticeLoadNumber = noticeData.load_number || '';
        
        // Find the notifications card body container (more specific selector)
        const notificationsCard = document.querySelector('#pills-notes .card-body');
        if (!notificationsCard) {
            return;
        }
        
        // Find the notices table
        let tableBody = notificationsCard.querySelector('tbody');
        
        // If table doesn't exist yet, create it
        if (!tableBody) {
            // Remove "No notifications yet" message if exists
            const emptyMessage = notificationsCard.querySelector('.text-muted');
            if (emptyMessage) {
                emptyMessage.remove();
            }
            
            // Create table structure
            const tableContainer = document.createElement('div');
            tableContainer.className = 'mt-3';
            tableContainer.innerHTML = `
                <h6>Recent notices</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th width="20%">Name</th>
                                <th width="20%">Load Number</th>
                                <th width="60%">Message</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="text-center mt-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="brokerNoticesLoadMore" data-step="5">Load more</button>
                </div>
            `;
            
            // Insert after the count div
            const countDiv = notificationsCard.querySelector('.mb-3');
            if (countDiv && countDiv.nextSibling) {
                notificationsCard.insertBefore(tableContainer, countDiv.nextSibling);
            } else {
                notificationsCard.appendChild(tableContainer);
            }
            
            tableBody = notificationsCard.querySelector('tbody');
            if (!tableBody) {
                return;
            }
        }
        
        // Update total count
        const countElement = notificationsCard.querySelector('.display-4.text-info');
        if (countElement) {
            const currentCount = parseInt(countElement.textContent || '0', 10);
            countElement.textContent = (currentCount + 1).toString();
        }
        
        // Format date (same format as PHP: m/d/Y g:i a)
        const dateObj = new Date(noticeDate * 1000);
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        const year = dateObj.getFullYear();
        const hours = dateObj.getHours();
        const minutes = String(dateObj.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'pm' : 'am';
        const displayHours = hours % 12 || 12;
        const formattedDate = `${month}/${day}/${year} ${displayHours}:${minutes} ${ampm}`;
        
        // Create new table row
        const newRow = document.createElement('tr');
        newRow.className = 'js-broker-notice-row';
        newRow.innerHTML = `
            <td>
                <div>${this.escapeHtml(userName)}</div>
                <small class="text-muted">${formattedDate}</small>
            </td>
            <td>${noticeLoadNumber ? this.escapeHtml(noticeLoadNumber) : '-'}</td>
            <td>${this.escapeHtml(noticeMessage)}</td>
        `;
        
        // Insert at the beginning of the table
        const firstRow = tableBody.querySelector('tr');
        if (firstRow) {
            tableBody.insertBefore(newRow, firstRow);
        } else {
            tableBody.appendChild(newRow);
        }
        
        // Show "Load more" button if there are more than 5 notices now
        const allRows = tableBody.querySelectorAll('.js-broker-notice-row');
        const loadMoreButton = document.getElementById('brokerNoticesLoadMore');
        if (loadMoreButton) {
            if (allRows.length > 5) {
                loadMoreButton.classList.remove('d-none');
            }
        }
        
        // Hide rows beyond the first 5
        allRows.forEach((row, index) => {
            if (index >= 5) {
                (row as HTMLElement).style.display = 'none';
            }
        });
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    private escapeHtml(text: string): string {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Submit form via AJAX
     */
    private async submitForm(formData: FormData, type: string): Promise<void> {
        try {
            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                printMessage('Notice added successfully', 'success', 3000);
                
                // Reset form
                const form = document.getElementById('brokerNoticeForm') as HTMLFormElement;
                if (form) {
                    form.reset();
                }
                
                // Check if we're in a modal context (page-broker-single.php) or popup context (table)
                const isInModal = document.getElementById('brokerNoticeModal') !== null;
                const isInPopup = document.getElementById('broker-notice-popup') !== null;
                
                if (isInModal) {
                    // Close Bootstrap modal
                    const modalElement = document.getElementById('brokerNoticeModal');
                    if (modalElement) {
                        const modal = (window as any).bootstrap?.Modal?.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                        } else {
                            // Fallback: remove modal classes
                            modalElement.classList.remove('show');
                            document.body.classList.remove('modal-open');
                            const backdrop = document.querySelector('.modal-backdrop');
                            if (backdrop) {
                                backdrop.remove();
                            }
                        }
                    }
                    
                    // Add new notice to the table without page reload
                    if (data.data && data.data.notice) {
                        this.addNoticeToTable(data.data.notice);
                    }
                } else if (isInPopup) {
                    // Reload notices in popup
                    const brokerIdField = document.getElementById('brokerNoticeId') as HTMLInputElement;
                    if (brokerIdField && brokerIdField.value) {
                        // Dispatch event to reload notices
                        document.dispatchEvent(new CustomEvent('tms:broker-notice-added', {
                            detail: { brokerId: brokerIdField.value }
                        }));
                    }
                }
            } else {
                printMessage(data.data?.message || 'Failed to add notice', 'danger', 3000);
            }
        } catch (error) {
            console.error('AJAX error:', error);
            printMessage('Error submitting form', 'danger', 3000);
        }
    }
}

export default BrokerPopupForms;

