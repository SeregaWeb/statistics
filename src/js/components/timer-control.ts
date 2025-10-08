import { printMessage } from './info-messages';

export class TimerControl {
    private modal: HTMLElement | null = null;
    private form: HTMLFormElement | null = null;
    private ajaxUrl: string;
    private currentLoadId: string | null = null;
    private currentTimer: any = null;
    private currentFlt: string | null = null;
    private currentProject: string | null = null;

    constructor(ajaxUrl: string) {
        this.ajaxUrl = ajaxUrl;
        this.init();
    }

    private init(): void {
        this.modal = document.getElementById('timerControlModal');
        this.form = this.modal?.querySelector('.js-timer-control-form') as HTMLFormElement;

        if (!this.modal || !this.form) {
            console.warn('Timer control modal not found - timer functionality will not be available');
            return;
        }

        this.setupEventListeners();
    }

    private setupEventListeners(): void {
        // Timer button click
        document.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;
            const timerBtn = target.closest('.js-timer-tracking') as HTMLButtonElement;
            
            if (timerBtn) {
                e.preventDefault();
                this.openModal(timerBtn);
            }
        });

        // Action buttons
        const startBtn = this.modal?.querySelector('.js-timer-start') as HTMLButtonElement;
        const pauseBtn = this.modal?.querySelector('.js-timer-pause') as HTMLButtonElement;
        const resumeBtn = this.modal?.querySelector('.js-timer-resume') as HTMLButtonElement;
        const stopBtn = this.modal?.querySelector('.js-timer-stop') as HTMLButtonElement;
        const updateBtn = this.modal?.querySelector('.js-timer-update') as HTMLButtonElement;

        startBtn?.addEventListener('click', () => this.handleTimerAction('start'));
        pauseBtn?.addEventListener('click', () => this.handleTimerAction('pause'));
        resumeBtn?.addEventListener('click', () => this.handleTimerAction('resume'));
        stopBtn?.addEventListener('click', () => this.handleTimerAction('stop'));
        updateBtn?.addEventListener('click', () => this.handleTimerAction('update'));

        // Comment field validation
        const commentField = this.modal?.querySelector('.js-timer-comment') as HTMLTextAreaElement;
        commentField?.addEventListener('input', () => this.validateComment());

        // Close button handler
        const closeBtn = this.modal?.querySelector('.btn-close') as HTMLButtonElement;
        closeBtn?.addEventListener('click', () => this.hideModal());

        // Close button in footer
        const footerCloseBtn = this.modal?.querySelector('.modal-footer .btn-secondary') as HTMLButtonElement;
        footerCloseBtn?.addEventListener('click', () => this.hideModal());
    }

    private async openModal(button: HTMLButtonElement): Promise<void> {
        if (!this.modal) {
            printMessage('Timer modal not available', 'danger', 3000);
            return;
        }

        this.currentLoadId = button.dataset.id || null;
        this.currentFlt = button.dataset.flt || null;
        this.currentProject = button.dataset.project || null;
        
        if (!this.currentLoadId) {
            printMessage('Load ID not found', 'danger', 3000);
            return;
        }

        // Set load ID in form
        const loadIdInput = this.form?.querySelector('.js-load-id') as HTMLInputElement;
        if (loadIdInput) {
            loadIdInput.value = this.currentLoadId;
        }

        // Load current timer status
        await this.loadTimerStatus();

        // Show modal
        this.showModal();
    }

    private showModal(): void {
        if (!this.modal) return;

        try {
            // Try Bootstrap 5 first
            if (typeof (window as any).bootstrap !== 'undefined' && (window as any).bootstrap.Modal) {
                const modalInstance = new (window as any).bootstrap.Modal(this.modal);
                modalInstance.show();
                return;
            }
        } catch (error) {
            console.warn('Bootstrap 5 Modal failed:', error);
        }

        try {
            // Try jQuery Bootstrap
            if (typeof (window as any).$ !== 'undefined' && (window as any).$.fn.modal) {
                (window as any).$(this.modal).modal('show');
                return;
            }
        } catch (error) {
            console.warn('jQuery Bootstrap Modal failed:', error);
        }

        // Fallback: show modal manually
        this.modal.style.display = 'block';
        this.modal.classList.add('show');
        this.modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        
        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'timer-modal-backdrop';
        document.body.appendChild(backdrop);

        // Close on backdrop click
        backdrop.addEventListener('click', () => this.hideModal());
        
        // Close on escape key
        const escapeHandler = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                this.hideModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
    }

    private hideModal(): void {
        if (!this.modal) return;

        try {
            // Try Bootstrap 5 first
            if (typeof (window as any).bootstrap !== 'undefined' && (window as any).bootstrap.Modal) {
                const modalInstance = (window as any).bootstrap.Modal.getInstance(this.modal);
                if (modalInstance) {
                    modalInstance.hide();
                    return;
                }
            }
        } catch (error) {
            console.warn('Bootstrap 5 Modal hide failed:', error);
        }

        try {
            // Try jQuery Bootstrap
            if (typeof (window as any).$ !== 'undefined' && (window as any).$.fn.modal) {
                (window as any).$(this.modal).modal('hide');
                return;
            }
        } catch (error) {
            console.warn('jQuery Bootstrap Modal hide failed:', error);
        }

        // Fallback: hide modal manually
        this.modal.style.display = 'none';
        this.modal.classList.remove('show');
        this.modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        
        // Remove backdrop
        const backdrop = document.getElementById('timer-modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }

    private async loadTimerStatus(): Promise<void> {
        if (!this.currentLoadId) return;

        try {
            const formData = new FormData();
            formData.append('action', 'get_timer_status');
            formData.append('load_id', this.currentLoadId);

            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                this.currentTimer = result.data.timer;
                
                // Debug logging
                if (this.currentTimer && this.currentTimer.debug) {
                    console.log('Timer Debug Info:', this.currentTimer.debug);
                    console.log('Current Timer:', this.currentTimer);
                }
                
                this.updateUI();
                await this.loadTimerHistory();
            } else {
                this.currentTimer = null;
                this.updateUI();
            }
        } catch (error) {
            printMessage(`Error loading timer status: ${error}`, 'danger', 3000);
            console.error('Error loading timer status:', error);
        }
    }

    private updateUI(): void {
        const statusDisplay = this.modal?.querySelector('.js-timer-status-display') as HTMLElement;
        const currentStatus = this.modal?.querySelector('.js-current-status') as HTMLElement;
        const startTime = this.modal?.querySelector('.js-timer-start-time') as HTMLElement;
        const duration = this.modal?.querySelector('.js-timer-duration') as HTMLElement;

        const startBtn = this.modal?.querySelector('.js-timer-start') as HTMLButtonElement;
        const pauseBtn = this.modal?.querySelector('.js-timer-pause') as HTMLButtonElement;
        const resumeBtn = this.modal?.querySelector('.js-timer-resume') as HTMLButtonElement;
        const stopBtn = this.modal?.querySelector('.js-timer-stop') as HTMLButtonElement;
        const updateBtn = this.modal?.querySelector('.js-timer-update') as HTMLButtonElement;

        // Hide all buttons first
        [startBtn, pauseBtn, resumeBtn, stopBtn, updateBtn].forEach(btn => {
            if (btn) btn.style.display = 'none';
        });

        if (this.currentTimer) {
            // Timer exists
            statusDisplay!.style.display = 'block';
            currentStatus!.textContent = this.currentTimer.status;
            startTime!.textContent = this.formatDateTime(this.currentTimer.create_timer);
            
            if (this.currentTimer.status === 'active') {
                duration!.textContent = this.calculateCurrentDuration();
                pauseBtn!.style.display = 'inline-block';
                stopBtn!.style.display = 'inline-block';
                updateBtn!.style.display = 'inline-block';
            } else if (this.currentTimer.status === 'paused') {
                duration!.textContent = this.formatDuration(this.currentTimer.duration || 0);
                resumeBtn!.style.display = 'inline-block';
                stopBtn!.style.display = 'inline-block';
                updateBtn!.style.display = 'inline-block';
            } else if (this.currentTimer.status === 'stopped') {
                duration!.textContent = this.formatDuration(this.currentTimer.duration || 0);
                startBtn!.style.display = 'inline-block';
            }
        } else {
            // No timer
            statusDisplay!.style.display = 'none';
            startBtn!.style.display = 'inline-block';
        }
    }

    private async handleTimerAction(action: string): Promise<void> {
        if (!this.currentLoadId) return;

        const commentField = this.modal?.querySelector('.js-timer-comment') as HTMLTextAreaElement;
        const comment = commentField?.value.trim() || '';

        // Validate comment for pause action only
        if (action === 'pause' && !comment) {
            this.showCommentRequired();
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', `${action}_timer`);
            formData.append('load_id', this.currentLoadId);
            formData.append('comment', comment);
            if (this.currentFlt !== null) {
                formData.append('flt', this.currentFlt);
            }
            if (this.currentProject !== null) {
                formData.append('project', this.currentProject);
            }
            
            // Debug: Log the data being sent
            console.log('Timer Control Debug - Sending data for action:', action);
            console.log('  - load_id:', this.currentLoadId);
            console.log('  - flt:', this.currentFlt);
            console.log('  - project:', this.currentProject);
            console.log('  - comment:', comment);
            console.log('  - FormData entries:', Array.from(formData.entries()));

            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                printMessage(result.data.message || `Timer ${action}ed successfully`, 'success', 3000);
                
                // Clear comment field
                if (commentField) commentField.value = '';
                
                // Reload timer status
                await this.loadTimerStatus();
                
                // Close modal if timer was stopped
                if (action === 'stop') {
                    this.hideModal();
                }
            } else {
                printMessage(result.data?.message || `Failed to ${action} timer`, 'danger', 3000);
            }
        } catch (error) {
            printMessage(`Error ${action}ing timer: ${error}`, 'danger', 3000);
            console.error(`Error ${action}ing timer:`, error);
        }
    }

    private async loadTimerHistory(): Promise<void> {
        if (!this.currentLoadId) return;

        try {
            const formData = new FormData();
            formData.append('action', 'get_timer_logs');
            formData.append('load_id', this.currentLoadId);

            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                this.displayTimerHistory(result.data.logs || []);
            }
        } catch (error) {
            console.error('Error loading timer history:', error);
        }
    }

    private displayTimerHistory(logs: any[]): void {
        const historyContainer = this.modal?.querySelector('.js-timer-history') as HTMLElement;
        
        if (!historyContainer) return;

        if (logs.length === 0) {
            historyContainer.innerHTML = '<p class="text-muted">No timer history available</p>';
            return;
        }

        const historyHTML = logs.map(log => `
            <div class="d-flex justify-content-between align-items-start border-bottom py-2">
                <div>
                    <strong>${this.capitalizeFirst(log.action)}</strong>
                    ${log.comment ? `<br><small class="text-muted">${log.comment}</small>` : ''}
                    ${log.user_name ? `<br><small class="text-info">by ${log.user_name}</small>` : ''}
                </div>
                <small class="text-muted text-right">${this.formatDateTime(log.created_at)}</small>
            </div>
        `).join('');

        historyContainer.innerHTML = historyHTML;
    }

    private validateComment(): void {
        const commentField = this.modal?.querySelector('.js-timer-comment') as HTMLTextAreaElement;
        const commentRequired = this.modal?.querySelector('.js-comment-required') as HTMLElement;
        
        if (commentField && commentRequired) {
            if (commentField.value.trim()) {
                commentRequired.style.display = 'none';
                commentField.classList.remove('is-invalid');
            } else {
                commentRequired.style.display = 'block';
                commentField.classList.add('is-invalid');
            }
        }
    }

    private showCommentRequired(): void {
        const commentField = this.modal?.querySelector('.js-timer-comment') as HTMLTextAreaElement;
        const commentRequired = this.modal?.querySelector('.js-comment-required') as HTMLElement;
        
        if (commentField && commentRequired) {
            commentRequired.style.display = 'block';
            commentField.classList.add('is-invalid');
            commentField.focus();
        }
    }

    private calculateCurrentDuration(): string {
        if (!this.currentTimer) return '0h 0m';
        
        const startTimeString = this.currentTimer.create_timer;
        
        // Server sends time in NY timezone, use debug info for current time
        const [datePart, timePart] = startTimeString.split(' ');
        const [year, month, day] = datePart.split('-').map(Number);
        const [hour, minute, second] = timePart.split(':').map(Number);
        
        // Create start time
        const startTime = new Date();
        startTime.setFullYear(year, month - 1, day);
        startTime.setHours(hour, minute, second, 0);
        
        // Use current NY time from debug info
        let currentTime;
        if (this.currentTimer.debug && this.currentTimer.debug.current_ny_time) {
            const [currentDatePart, currentTimePart] = this.currentTimer.debug.current_ny_time.split(' ');
            const [currentYear, currentMonth, currentDay] = currentDatePart.split('-').map(Number);
            const [currentHour, currentMinute, currentSecond] = currentTimePart.split(':').map(Number);
            
            currentTime = new Date();
            currentTime.setFullYear(currentYear, currentMonth - 1, currentDay);
            currentTime.setHours(currentHour, currentMinute, currentSecond, 0);
        } else {
            // Fallback to local time
            currentTime = new Date();
        }
        
        const diffMs = currentTime.getTime() - startTime.getTime();
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        
        return this.formatDuration(diffMinutes);
    }

    private formatDuration(minutes: number): string {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours}h ${mins}m`;
    }

    private formatDateTime(dateString: string): string {
        // Server sends time in format "YYYY-MM-DD HH:MM:SS" as NY time
        // Just reformat to MM/DD/YYYY, HH:MM:SS without any timezone conversion
        const [datePart, timePart] = dateString.split(' ');
        const [year, month, day] = datePart.split('-');
        const [hour, minute, second] = timePart.split(':');
        
        return `${month}/${day}/${year}, ${hour}:${minute}:${second}`;
    }

    private capitalizeFirst(str: string): string {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
}
