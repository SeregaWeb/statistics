/**
 * Timer Analytics Component
 * Handles timer analytics display and export functionality
 */

export class TimerAnalytics {
    private ajaxUrl: string;
    private currentLogsOffset: number = 0;
    private logsPerPage: number = 20;
    private hasMoreLogs: boolean = true;

    constructor(ajaxUrl: string) {
        this.ajaxUrl = ajaxUrl;
        this.init();
    }

    private init(): void {
        this.setupEventListeners();
    }

    private setupEventListeners(): void {
        // Form submission
        const form = document.getElementById('analyticsFilters') as HTMLFormElement;
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.loadAnalytics();
            });
        }

        // Export button
        const exportBtn = document.getElementById('exportAnalytics');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => {
                this.exportAnalytics();
            });
        }

        // Load timer logs button
        const loadLogsBtn = document.getElementById('loadTimerLogs');
        if (loadLogsBtn) {
            loadLogsBtn.addEventListener('click', () => {
                this.loadTimerLogs();
            });
        }

        // Load more logs button
        const loadMoreLogsBtn = document.getElementById('loadMoreLogs');
        if (loadMoreLogsBtn) {
            loadMoreLogsBtn.addEventListener('click', () => {
                this.loadMoreTimerLogs();
            });
        }

    }

    private async loadAnalytics(): Promise<void> {
        const form = document.getElementById('analyticsFilters') as HTMLFormElement;
        if (!form) return;

        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Add form data to params
        for (const [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value.toString());
            }
        }

        this.showLoading(true);
        this.hideResults();
        
        // Reset pagination state when loading new analytics
        this.resetLogsPagination();
        
        // Clear timer logs table when loading new analytics
        this.clearTimerLogsTable();

        try {
            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString() + '&action=get_timer_analytics'
            });

            const data = await response.json();

            if (data.success) {
                this.displayAnalytics(data.data);
            } else {
                this.showError(data.data?.message || 'Failed to load analytics');
            }
        } catch (error) {
            console.error('Error loading analytics:', error);
            this.showError('Network error occurred');
        } finally {
            this.showLoading(false);
        }
    }

    private async exportAnalytics(): Promise<void> {
        const form = document.getElementById('analyticsFilters') as HTMLFormElement;
        if (!form) return;

        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Add form data to params
        for (const [key, value] of formData.entries()) {
            if (value) {
                params.append(key, value.toString());
            }
        }

        try {
            console.log('Exporting analytics with params:', params.toString());
            
            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString() + '&action=export_timer_analytics'
            });

            const data = await response.json();
            console.log('Export response:', data);

            if (data.success) {
                this.downloadFile(data.data.file_url);
            } else {
                this.showError(data.data?.message || 'Failed to export analytics');
            }
        } catch (error) {
            console.error('Error exporting analytics:', error);
            this.showError('Network error occurred');
        }
    }

    private displayAnalytics(data: any): void {
        this.displayOverallStats(data.analytics);
        this.displayZoneDistribution(data.analytics);
        this.displayUserAnalytics(data.user_analytics);
        this.showResults();
    }

    private displayOverallStats(analytics: any): void {
        const container = document.getElementById('overallStats');
        if (!container) return;

        container.innerHTML = `
            <div class="col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Timer Uses</h5>
                        <h3>${analytics.total_timer_uses || 0}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Updates</h5>
                        <h3>${analytics.total_updates || 0}</h3>
                    </div>
                </div>
            </div>
        `;
    }

    private displayZoneDistribution(analytics: any): void {
        const container = document.getElementById('zoneDistribution');
        if (!container) return;

        const total = (analytics.yellow_zone_count || 0) + 
                     (analytics.red_zone_count || 0) + (analytics.black_zone_count || 0);

        if (total === 0) {
            container.innerHTML = '<p class="text-muted">No timer data available</p>';
            return;
        }

        container.innerHTML = `
            <div class="mb-2">
                <div class="d-flex justify-content-between">
                    <span>Yellow Zone (1-2h)</span>
                    <span>${analytics.yellow_zone_count || 0} (${analytics.yellow_zone_percentage || 0}%)</span>
                </div>
                <div class="progress mb-2">
                    <div class="progress-bar bg-warning" style="width: ${analytics.yellow_zone_percentage || 0}%"></div>
                </div>
            </div>
            <div class="mb-2">
                <div class="d-flex justify-content-between">
                    <span>Red Zone (2-4h)</span>
                    <span>${analytics.red_zone_count || 0} (${analytics.red_zone_percentage || 0}%)</span>
                </div>
                <div class="progress mb-2">
                    <div class="progress-bar bg-danger" style="width: ${analytics.red_zone_percentage || 0}%"></div>
                </div>
            </div>
            <div class="mb-2">
                <div class="d-flex justify-content-between">
                    <span>Black Zone (4h+)</span>
                    <span>${analytics.black_zone_count || 0} (${analytics.black_zone_percentage || 0}%)</span>
                </div>
                <div class="progress mb-2">
                    <div class="progress-bar bg-dark" style="width: ${analytics.black_zone_percentage || 0}%"></div>
                </div>
            </div>
        `;
    }

    private displayTimerUsage(analytics: any): void {
        const container = document.getElementById('timerUsage');
        if (!container) return;

        const total = analytics.total_loads || 0;
        const withoutTimer = analytics.loads_without_timer || 0;
        const withOneTimer = analytics.loads_with_one_timer || 0;
        const withMultipleTimers = analytics.loads_with_multiple_timers || 0;

        if (total === 0) {
            container.innerHTML = '<p class="text-muted">No data available</p>';
            return;
        }

        container.innerHTML = `
            <div class="mb-2">
                <div class="d-flex justify-content-between">
                    <span>Without Timer</span>
                    <span>${withoutTimer} (${Math.round(withoutTimer / total * 100)}%)</span>
                </div>
                <div class="progress mb-2">
                    <div class="progress-bar bg-secondary" style="width: ${Math.round(withoutTimer / total * 100)}%"></div>
                </div>
            </div>
            <div class="mb-2">
                <div class="d-flex justify-content-between">
                    <span>With One Timer</span>
                    <span>${withOneTimer} (${Math.round(withOneTimer / total * 100)}%)</span>
                </div>
                <div class="progress mb-2">
                    <div class="progress-bar bg-info" style="width: ${Math.round(withOneTimer / total * 100)}%"></div>
                </div>
            </div>
            <div class="mb-2">
                <div class="d-flex justify-content-between">
                    <span>With Multiple Timers</span>
                    <span>${withMultipleTimers} (${Math.round(withMultipleTimers / total * 100)}%)</span>
                </div>
                <div class="progress mb-2">
                    <div class="progress-bar bg-primary" style="width: ${Math.round(withMultipleTimers / total * 100)}%"></div>
                </div>
            </div>
        `;
    }

    private displayUserAnalytics(userAnalytics: any[]): void {
        const tbody = document.querySelector('#userAnalyticsTable tbody');
        if (!tbody) return;

        if (!userAnalytics || userAnalytics.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No user data available</td></tr>';
            return;
        }

        tbody.innerHTML = userAnalytics.map(user => `
            <tr>
                <td>${user.user_name || `User ${user.id_user}`}</td>
                <td>${user.total_timer_uses || 0}</td>
                <td>${user.total_updates || 0}</td>
                <td><span class="badge bg-warning">${user.yellow_zone_count || 0}</span></td>
                <td><span class="badge bg-danger">${user.red_zone_count || 0}</span></td>
                <td><span class="badge bg-dark">${user.black_zone_count || 0}</span></td>
            </tr>
        `).join('');
    }

    private getUserName(userId: number): string {
        // Try to get user name from the select options
        const userSelect = document.getElementById('analyticsUser') as HTMLSelectElement;
        if (userSelect) {
            const option = userSelect.querySelector(`option[value="${userId}"]`);
            if (option) {
                return option.textContent || `User ${userId}`;
            }
        }
        return `User ${userId}`;
    }

    private formatDuration(minutes: number): string {
        if (minutes < 60) {
            return `${Math.round(minutes)}m`;
        } else {
            const hours = Math.floor(minutes / 60);
            const mins = Math.round(minutes % 60);
            return `${hours}h ${mins}m`;
        }
    }

    private showLoading(show: boolean): void {
        const loading = document.getElementById('analyticsLoading');
        if (loading) {
            loading.style.display = show ? 'block' : 'none';
        }
    }

    private showResults(): void {
        const results = document.getElementById('analyticsResults');
        if (results) {
            results.style.display = 'block';
        }
    }

    private hideResults(): void {
        const results = document.getElementById('analyticsResults');
        if (results) {
            results.style.display = 'none';
        }
    }

    private showError(message: string): void {
        // You can implement a notification system here
        alert('Error: ' + message);
    }

    private downloadFile(fileUrl: string): void {
        console.log('Downloading file from URL:', fileUrl);
        
        // Extract filename from URL for download attribute
        const filename = fileUrl.split('/').pop() || 'export.xlsx';
        
        // Create download link
        const link = document.createElement('a');
        link.href = fileUrl;
        link.download = filename;
        link.target = '_blank'; // Open in new tab as fallback
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    private async loadTimerLogs(): Promise<void> {
        // Reset pagination state
        this.resetLogsPagination();
        
        const form = document.getElementById('analyticsFilters') as HTMLFormElement;
        if (!form) return;

        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Add form data to params, but exclude 'action' field to avoid conflict
        for (const [key, value] of formData.entries()) {
            if (value && key !== 'action') {
                params.append(key, value.toString());
            }
        }

        // Add timer action filter separately (if selected)
        const actionSelect = form.querySelector('#analyticsAction') as HTMLSelectElement;
        if (actionSelect && actionSelect.value) {
            params.append('timer_action', actionSelect.value);
        }

        // Add pagination parameters
        params.append('offset', this.currentLogsOffset.toString());
        params.append('limit', this.logsPerPage.toString());

        // Add AJAX action (this is the WordPress AJAX action, not the timer action)
        params.append('action', 'get_timer_logs_analytics');

        try {
            this.showLoading(true);
            
            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString()
            });

            const data = await response.json();

            if (data.success) {
                console.log('Timer logs response:', data.data);
                this.displayTimerLogs(data.data.logs, true); // true = replace existing logs
                this.updateLoadMoreButton(data.data.logs.length, data.data.total_logs);
            } else {
                this.showError(data.data.message || 'Failed to load timer logs');
            }
        } catch (error) {
            console.error('Error loading timer logs:', error);
            this.showError('Network error occurred');
        } finally {
            this.showLoading(false);
        }
    }

    private displayTimerLogs(logs: any[], replace: boolean = false): void {
        const tbody = document.querySelector('#timerLogsTable tbody');
        if (!tbody) return;

        // Clear existing rows if replacing
        if (replace) {
            tbody.innerHTML = '';
        }

        if (logs.length === 0 && replace) {
            const row = document.createElement('tr');
            row.innerHTML = '<td colspan="7" class="text-center">No timer logs found</td>';
            tbody.appendChild(row);
            return;
        }

        // Add log rows
        logs.forEach(log => {
            const row = document.createElement('tr');
            
            // Format action with badge
            const actionBadge = this.getActionBadge(log.action);
            
            // Format load type
            const loadType = (log.flt === 1 || log.flt === '1') ? 'FLT' : 'Regular';
            
            row.innerHTML = `
                <td>${log.formatted_time}</td>
                <td>${log.user_name}</td>
                <td>${log.load_id}</td>
                <td>${actionBadge}</td>
                <td>${log.comment || '-'}</td>
                <td>${log.project}</td>
                <td>${loadType}</td>
            `;
            
            tbody.appendChild(row);
        });
    }

    private getActionBadge(action: string): string {
        const badges: { [key: string]: string } = {
            'start': '<span class="badge bg-success">Start</span>',
            'pause': '<span class="badge bg-warning">Pause</span>',
            'resume': '<span class="badge bg-info">Resume</span>',
            'stop': '<span class="badge bg-danger">Stop</span>',
            'update': '<span class="badge bg-primary">Update</span>'
        };
        
        return badges[action] || `<span class="badge bg-secondary">${action}</span>`;
    }

    private clearTimerLogsTable(): void {
        const tbody = document.querySelector('#timerLogsTable tbody');
        if (tbody) {
            tbody.innerHTML = '';
        }
    }

    private resetLogsPagination(): void {
        // Reset pagination state
        this.currentLogsOffset = 0;
        this.hasMoreLogs = true;
        
        // Hide Load More button
        const loadMoreBtn = document.getElementById('loadMoreLogs') as HTMLButtonElement;
        if (loadMoreBtn) {
            loadMoreBtn.style.display = 'none';
            loadMoreBtn.textContent = 'Load More Logs';
        }
        
        console.log('Timer Analytics: Reset logs pagination state');
    }

    private async loadMoreTimerLogs(): Promise<void> {
        if (!this.hasMoreLogs) return;

        this.currentLogsOffset += this.logsPerPage;
        
        const form = document.getElementById('analyticsFilters') as HTMLFormElement;
        if (!form) return;

        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        // Add form data to params, but exclude 'action' field to avoid conflict
        for (const [key, value] of formData.entries()) {
            if (value && key !== 'action') {
                params.append(key, value.toString());
            }
        }

        // Add timer action filter separately (if selected)
        const actionSelect = form.querySelector('#analyticsAction') as HTMLSelectElement;
        if (actionSelect && actionSelect.value) {
            params.append('timer_action', actionSelect.value);
        }

        // Add pagination parameters
        params.append('offset', this.currentLogsOffset.toString());
        params.append('limit', this.logsPerPage.toString());

        // Add AJAX action
        params.append('action', 'get_timer_logs_analytics');

        try {
            const loadMoreBtn = document.getElementById('loadMoreLogs') as HTMLButtonElement;
            if (loadMoreBtn) {
                loadMoreBtn.disabled = true;
                loadMoreBtn.textContent = 'Loading...';
            }
            
            const response = await fetch(this.ajaxUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString()
            });

            const data = await response.json();

            if (data.success) {
                this.displayTimerLogs(data.data.logs, false); // false = append to existing logs
                this.updateLoadMoreButton(data.data.logs.length, data.data.total_logs);
            } else {
                this.showError(data.data.message || 'Failed to load more timer logs');
            }
        } catch (error) {
            console.error('Error loading more timer logs:', error);
            this.showError('Network error occurred');
        } finally {
            const loadMoreBtn = document.getElementById('loadMoreLogs') as HTMLButtonElement;
            if (loadMoreBtn) {
                loadMoreBtn.disabled = false;
                // Don't reset text here - let updateLoadMoreButton handle it
            }
        }
    }

    private updateLoadMoreButton(loadedCount: number, totalLogs: number): void {
        const loadMoreBtn = document.getElementById('loadMoreLogs') as HTMLButtonElement;
        if (!loadMoreBtn) return;

        // Calculate total displayed logs (including previous loads)
        const totalDisplayed = this.currentLogsOffset + loadedCount;
        
        console.log('updateLoadMoreButton:', {
            loadedCount,
            totalLogs,
            currentLogsOffset: this.currentLogsOffset,
            totalDisplayed,
            logsPerPage: this.logsPerPage,
            hasMoreLogs: this.hasMoreLogs
        });
        
        if (loadedCount < this.logsPerPage || totalDisplayed >= totalLogs) {
            // No more logs to load
            this.hasMoreLogs = false;
            loadMoreBtn.style.display = 'none';
            console.log('Hiding Load More button - no more logs');
        } else {
            // More logs available
            this.hasMoreLogs = true;
            loadMoreBtn.style.display = 'inline-block';
            loadMoreBtn.textContent = `Load More Logs (${totalDisplayed}/${totalLogs})`;
            console.log('Showing Load More button with count:', `${totalDisplayed}/${totalLogs}`);
        }
    }

}
 