/**
 * ETA Timer Component
 * Displays countdown timer with color coding based on remaining time
 * 
 * Color scheme:
 * - >30 min: green
 * - 30-15 min: yellow
 * - 15-0 min: red
 * - 0+ min: black
 * 
 * Performance optimizations:
 * - Single global interval for all timers (instead of one per timer)
 * - Debounced MutationObserver to reduce re-initialization overhead
 * - Smart initialization (only new elements)
 */

interface EtaTimerData {
    loadId: string;
    etaType: string;
    etaDatetime: string;
    timezone: string;
    isFlt: string;
    loadStatus: string;
}

class EtaTimer {
    private element: HTMLElement;
    private data: EtaTimerData;
    private isStopped: boolean = false;
    private cachedEtaTimestamp: number | null = null;

    constructor(element: HTMLElement) {
        this.element = element;
        this.data = {
            loadId: element.getAttribute('data-load-id') || '',
            etaType: element.getAttribute('data-eta-type') || '',
            etaDatetime: element.getAttribute('data-eta-datetime') || '',
            timezone: element.getAttribute('data-timezone') || '',
            isFlt: element.getAttribute('data-is-flt') || '0',
            loadStatus: element.getAttribute('data-load-status') || ''
        };

        // Check if load is closed (delivered, tonu, cancelled)
        if (this.isLoadClosed()) {
            this.isStopped = true;
            this.displayStopped();
            return;
        }

        // Check if we have required data
        if (!this.data.etaDatetime) {
            this.displayError();
            return;
        }
        
        // Initial update (global interval will handle subsequent updates)
        this.updateTimer();
    }

    private isLoadClosed(): boolean {
        const closedStatuses = ['delivered', 'tonu', 'cancelled'];
        return closedStatuses.includes(this.data.loadStatus);
    }

    /**
     * Update timer display (called by global interval)
     * Uses cached timestamps when possible for better performance
     */
    public updateTimer(): void {
        if (this.isStopped || !this.data.etaDatetime) {
            return;
        }

        // Check if load is closed (status might have changed)
        if (this.isLoadClosed()) {
            this.stop();
            return;
        }

        // Parse timezone from string like "PDT (UTC-7)" or "CDT (UTC-5)"
        const timezoneInfo = this.parseTimezone(this.data.timezone);
        if (!timezoneInfo) {
            // If timezone parsing fails, treat as local time
            this.updateTimerAsLocal();
            return;
        }

        // Logic: ETA is set in the destination timezone (e.g., EDT UTC-4, PDT UTC-7)
        // Timer should count down in the destination timezone, not NY time
        // Example: If ETA is set to 8:00 EDT, timer counts to 8:00 EDT (not 8:00 NY time)
        
        const etaDateStr = this.data.etaDatetime;
        
        // Only recalculate ETA timestamp if datetime changed
        const currentEtaDatetime = this.element.getAttribute('data-eta-datetime');
        if (currentEtaDatetime !== this.data.etaDatetime) {
            this.data.etaDatetime = currentEtaDatetime || '';
            this.cachedEtaTimestamp = null; // Invalidate cache
        }
        
        // Get timezone offset in hours (e.g., -4 for EDT, -7 for PDT, -5 for CDT)
        const destinationOffsetHours = timezoneInfo.offset;
        
        // Cache ETA timestamp calculation (only recalculate if ETA changed)
        if (this.cachedEtaTimestamp === null) {
            // Parse ETA datetime components (already in destination timezone)
            const [datePart, timePart] = etaDateStr.split(' ');
            const [year, month, day] = datePart.split('-').map(Number);
            const [hours, minutes, seconds] = timePart.split(':').map(Number);
            
            // Create ETA date in destination timezone
            // We'll create a "virtual" timestamp by treating destination time as UTC
            // This allows us to compare times in the same timezone
            this.cachedEtaTimestamp = Date.UTC(year, month - 1, day, hours, minutes, seconds || 0);
        }
        
        // Get current time in destination timezone
        // offset is negative (e.g., -4 for EDT UTC-4), meaning destination is offset hours behind UTC
        // If UTC is 12:00 and offset is -4, destination time is 12:00 + (-4) = 08:00
        const nowUtc = new Date();
        const nowUtcTimestamp = nowUtc.getTime();
        
        // Convert UTC to destination timezone
        // Add offset to UTC to get destination time (offset is negative, so this subtracts hours)
        const nowDestinationTimestamp = nowUtcTimestamp + (destinationOffsetHours * 60 * 60 * 1000);
        
        // Create comparable timestamp for current destination time
        // Extract date components from destination time
        const nowDestinationDate = new Date(nowDestinationTimestamp);
        const nowDestinationYear = nowDestinationDate.getUTCFullYear();
        const nowDestinationMonth = nowDestinationDate.getUTCMonth();
        const nowDestinationDay = nowDestinationDate.getUTCDate();
        const nowDestinationHour = nowDestinationDate.getUTCHours();
        const nowDestinationMinute = nowDestinationDate.getUTCMinutes();
        const nowDestinationSecond = nowDestinationDate.getUTCSeconds();
        
        // Create "virtual" UTC timestamp using destination time components
        const nowDestinationComparable = Date.UTC(
            nowDestinationYear,
            nowDestinationMonth,
            nowDestinationDay,
            nowDestinationHour,
            nowDestinationMinute,
            nowDestinationSecond
        );
        
        // Calculate difference in milliseconds
        const diffMs = this.cachedEtaTimestamp - nowDestinationComparable;
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        const diffSeconds = Math.floor((diffMs % (1000 * 60)) / 1000);

        // Update display
        this.updateDisplay(diffMinutes, diffSeconds);
    }
    

    private updateTimerAsLocal(): void {
        // Fallback: treat eta_datetime as local browser time
        const etaDate = new Date(this.data.etaDatetime.replace(' ', 'T'));
        if (isNaN(etaDate.getTime())) {
            this.displayError();
            return;
        }

        const now = new Date();
        const diffMs = etaDate.getTime() - now.getTime();
        const diffMinutes = Math.floor(diffMs / (1000 * 60));
        const diffSeconds = Math.floor((diffMs % (1000 * 60)) / 1000);

        this.updateDisplay(diffMinutes, diffSeconds);
    }

    private parseTimezone(timezoneString: string): { offset: number } | null {
        // Parse strings like "PDT (UTC-7)", "EDT (UTC-4)", etc.
        const match = timezoneString.match(/\(UTC([+-]\d+)\)/);
        if (match) {
            return { offset: parseInt(match[1], 10) };
        }
        return null;
    }

    private updateDisplay(minutes: number, seconds: number): void {
        const textElement = this.element.querySelector('.js-eta-timer-text');
        if (!textElement) {
            return;
        }

        // Format time display with hours:minutes:seconds
        let displayText: string;
        let timerClass: string; // CSS class for timer color state
        let buttonClass: string;

        if (minutes < 0) {
            // Past due (0+ min)
            const absMinutes = Math.abs(minutes);
            const absSeconds = Math.abs(seconds);
            const hours = Math.floor(absMinutes / 60);
            const mins = absMinutes % 60;
            if (hours > 0) {
                displayText = `-${hours}:${mins.toString().padStart(2, '0')}:${absSeconds.toString().padStart(2, '0')}`;
            } else {
                displayText = `-${mins}:${absSeconds.toString().padStart(2, '0')}`;
            }
            timerClass = 'eta-timer-black';
            buttonClass = 'btn-dark'; // black button
        } else if (minutes === 0 && seconds <= 0) {
            // Exactly 0
            displayText = '0:00:00';
            timerClass = 'eta-timer-black';
            buttonClass = 'btn-dark'; // black button
        } else {
            // Calculate hours and remaining minutes
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            
            // Format as hours:minutes:seconds
            if (hours > 0) {
                displayText = `${hours}:${mins.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            } else {
                displayText = `${mins}:${seconds.toString().padStart(2, '0')}`;
            }
            
            // Set color class based on remaining time
            if (minutes < 15) {
                // 15-0 min: red
                timerClass = 'eta-timer-red';
                buttonClass = 'btn-danger'; // red button
            } else if (minutes < 30) {
                // 30-15 min: yellow
                timerClass = 'eta-timer-yellow';
                buttonClass = 'btn-warning'; // yellow button
            } else {
                // >30 min: green
                timerClass = 'eta-timer-green';
                buttonClass = 'btn-success'; // green button
            }
        }

        textElement.textContent = displayText;
        
        // Remove all timer color classes and add the current one
        this.element.classList.remove('eta-timer-green', 'eta-timer-yellow', 'eta-timer-red', 'eta-timer-black');
        this.element.classList.add(timerClass);
        
        // Update button color
        this.updateButtonColor(buttonClass);
    }
    
    /**
     * Update the ETA button color based on remaining time
     */
    private updateButtonColor(buttonClass: string): void {
        // Find the button in the same container as the timer
        const container = this.element.closest('.d-flex.flex-column');
        if (!container) {
            return;
        }
        
        const button = container.querySelector('.js-open-popup-activator') as HTMLButtonElement;
        if (!button) {
            return;
        }
        
        // Remove all color classes
        button.classList.remove('btn-success', 'btn-warning', 'btn-danger', 'btn-dark', 'btn-outline-primary');
        
        // Add the new color class
        button.classList.add(buttonClass);
    }

    private displayStopped(): void {
        const textElement = this.element.querySelector('.js-eta-timer-text');
        if (textElement) {
            textElement.textContent = 'Stopped';
        }
        
        // Remove all timer color classes
        this.element.classList.remove('eta-timer-green', 'eta-timer-yellow', 'eta-timer-red', 'eta-timer-black');
        
        // Reset button to default state when stopped
        this.updateButtonColor('btn-outline-primary');
    }

    private displayError(): void {
        const textElement = this.element.querySelector('.js-eta-timer-text');
        if (textElement) {
            textElement.textContent = 'Error';
            (textElement as HTMLElement).style.color = '#dc3545'; // red
        }
        
        // Keep button in default state on error
        // Don't change button color on error to avoid confusion
    }

    public updateEta(newEtaDatetime: string): void {
        this.data.etaDatetime = newEtaDatetime;
        this.element.setAttribute('data-eta-datetime', newEtaDatetime);
        this.cachedEtaTimestamp = null; // Invalidate cache
        this.isStopped = false;
        this.updateTimer();
    }

    public stop(): void {
        this.isStopped = true;
        this.displayStopped();
    }

    public destroy(): void {
        // Cleanup handled by global manager
        this.isStopped = true;
    }
}

// Store active timers
const activeTimers = new Map<HTMLElement, EtaTimer>();

// Global interval for all timers (single interval instead of one per timer)
let globalIntervalId: number | null = null;
const UPDATE_INTERVAL = 1000; // 1 second

/**
 * Start global interval to update all timers
 */
const startGlobalInterval = (): void => {
    if (globalIntervalId !== null) {
        return; // Already running
    }
    
    globalIntervalId = window.setInterval(() => {
        // Update all timers (each timer calculates time in its own timezone)
        activeTimers.forEach((timer) => {
            timer.updateTimer();
        });
    }, UPDATE_INTERVAL);
};

/**
 * Stop global interval
 */
const stopGlobalInterval = (): void => {
    if (globalIntervalId !== null) {
        clearInterval(globalIntervalId);
        globalIntervalId = null;
    }
};

/**
 * Initialize all ETA timers on the page
 * Only initializes new elements (optimized for performance)
 */
export const initEtaTimers = (): void => {
    const timerElements = document.querySelectorAll('.js-eta-timer');
    
    timerElements.forEach((element) => {
        const htmlElement = element as HTMLElement;
        
        // Skip if already initialized
        if (activeTimers.has(htmlElement)) {
            return;
        }
        
        // Skip if element is not in DOM (cleanup will handle it)
        if (!document.contains(htmlElement)) {
            return;
        }
        
        const etaDatetime = htmlElement.getAttribute('data-eta-datetime');
        if (etaDatetime) {
            const timer = new EtaTimer(htmlElement);
            activeTimers.set(htmlElement, timer);
        }
    });
    
    // Start global interval if we have timers
    if (activeTimers.size > 0 && globalIntervalId === null) {
        startGlobalInterval();
    } else if (activeTimers.size === 0 && globalIntervalId !== null) {
        // Stop interval if no timers
        stopGlobalInterval();
    }
};

/**
 * Update ETA timer after ETA is saved
 */
export const updateEtaTimer = (loadId: string, etaType: string, newEtaDatetime: string): void => {
    // Find timer element by load ID and type
    const timerElement = document.querySelector(
        `.js-eta-timer[data-load-id="${loadId}"][data-eta-type="${etaType}"]`
    ) as HTMLElement;

    if (!timerElement) {
        // Timer element doesn't exist yet, wait a bit and try again (max 5 attempts)
        let attempts = 0;
        const maxAttempts = 5;
        const checkInterval = setInterval(() => {
            attempts++;
            const element = document.querySelector(
                `.js-eta-timer[data-load-id="${loadId}"][data-eta-type="${etaType}"]`
            ) as HTMLElement;
            
            if (element) {
                clearInterval(checkInterval);
                updateEtaTimer(loadId, etaType, newEtaDatetime);
            } else if (attempts >= maxAttempts) {
                clearInterval(checkInterval);
                // Timer element not found - silently fail (element may be removed or not yet created)
            }
        }, 200);
        return;
    }

    // Update the data attribute
    timerElement.setAttribute('data-eta-datetime', newEtaDatetime);

    if (activeTimers.has(timerElement)) {
        const timer = activeTimers.get(timerElement);
        if (timer) {
            timer.updateEta(newEtaDatetime);
        }
    } else {
        // Create new timer if element exists but timer wasn't initialized
        const timer = new EtaTimer(timerElement);
        activeTimers.set(timerElement, timer);
    }
};

/**
 * Stop ETA timer when load is closed
 */
export const stopEtaTimer = (loadId: string, etaType: string): void => {
    const timerElement = document.querySelector(
        `.js-eta-timer[data-load-id="${loadId}"][data-eta-type="${etaType}"]`
    ) as HTMLElement;

    if (timerElement && activeTimers.has(timerElement)) {
        const timer = activeTimers.get(timerElement);
        if (timer) {
            timer.stop();
        }
    }
};

/**
 * Cleanup timers when elements are removed
 */
export const cleanupEtaTimers = (): void => {
    activeTimers.forEach((timer, element) => {
        if (!document.contains(element)) {
            timer.destroy();
            activeTimers.delete(element);
        }
    });
    
    // Stop global interval if no timers left
    if (activeTimers.size === 0 && globalIntervalId !== null) {
        stopGlobalInterval();
    }
};

// Debounce function for MutationObserver
const debounce = <T extends (...args: any[]) => void>(
    func: T,
    wait: number
): ((...args: Parameters<T>) => void) => {
    let timeout: number | null = null;
    return (...args: Parameters<T>) => {
        if (timeout !== null) {
            clearTimeout(timeout);
        }
        timeout = window.setTimeout(() => func(...args), wait);
    };
};

// Debounced initialization (only runs after DOM changes stop for 300ms)
const debouncedInit = debounce(() => {
    cleanupEtaTimers();
    initEtaTimers();
}, 300);

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        initEtaTimers();
    });
} else {
    initEtaTimers();
}

// Re-initialize when new content is loaded (for dynamic content)
// Using debounce to avoid excessive re-initialization
const observer = new MutationObserver((mutations) => {
    // Only process if there are actual .js-eta-timer elements added/removed
    const hasRelevantChanges = mutations.some((mutation) => {
        if (mutation.type !== 'childList') {
            return false;
        }
        
        // Check if any added/removed nodes are timer elements or contain them
        const addedNodes = Array.from(mutation.addedNodes);
        const removedNodes = Array.from(mutation.removedNodes);
        
        const hasTimerElements = (nodes: Node[]): boolean => {
            return nodes.some((node) => {
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return false;
                }
                const element = node as HTMLElement;
                return element.classList?.contains('js-eta-timer') ||
                       element.querySelector?.('.js-eta-timer') !== null;
            });
        };
        
        return hasTimerElements(addedNodes) || hasTimerElements(removedNodes);
    });
    
    if (hasRelevantChanges) {
        debouncedInit();
    }
});

observer.observe(document.body, {
    childList: true,
    subtree: true
});

