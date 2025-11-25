/**
 * Rating Reminder Modal
 * Shows a reminder modal for unrated loads once per 30 minutes
 */

export function initRatingReminderModal(): void {
	const modalElement = document.getElementById('ratingReminderModal');
	if (!modalElement) {
		return;
	}

	// Get user ID from data attribute
	const userId = modalElement.getAttribute('data-user-id');
	if (!userId) {
		return;
	}

	// Check if modal was shown in the last 30 minutes
	const storageKey = `rating_reminder_shown_${userId}`;
	const lastShownTimestamp = localStorage.getItem(storageKey);
	const now = Date.now();
	const thirtyMinutes = 30 * 60 * 1000; // 30 minutes in milliseconds

	// Show modal if not shown in the last 30 minutes
	let shouldShow = true;
	if (lastShownTimestamp) {
		const timeSinceLastShown = now - parseInt(lastShownTimestamp, 10);
		if (timeSinceLastShown < thirtyMinutes) {
			shouldShow = false;
		}
	}

	if (!shouldShow) {
		return;
	}

	// DOM is already loaded when this function is called from ready()
	// Save timestamp only when user closes the modal, not when showing it
	const saveCloseTimestamp = (): void => {
		localStorage.setItem(storageKey, Date.now().toString());
	};

	// Try Bootstrap 5
	// @ts-ignore
	if (typeof window.bootstrap !== 'undefined' && window.bootstrap.Modal) {
		// @ts-ignore
		const modal = new window.bootstrap.Modal(modalElement);
		modal.show();

		// Save timestamp only when modal is hidden (when user closes it)
		modalElement.addEventListener('hidden.bs.modal', saveCloseTimestamp);
	} else {
		// Fallback: show modal manually
		modalElement.classList.add('show');
		modalElement.style.display = 'block';
		modalElement.setAttribute('aria-hidden', 'false');

		// Close handler - save timestamp when user closes
		const closeHandler = (): void => {
			modalElement.classList.remove('show');
			modalElement.style.display = 'none';
			modalElement.setAttribute('aria-hidden', 'true');
			saveCloseTimestamp();
		};

		// Close on button click
		const closeBtn = modalElement.querySelector('[data-bs-dismiss="modal"]');
		if (closeBtn) {
			closeBtn.addEventListener('click', closeHandler);
		}
	}
}

