/**
 * TMS Notifications: badge, panel, fetch, mark read.
 * Config from PHP via window.TMSNotificationsConfig (set only when user has access).
 */

const MARK_ALL_READ_BTN_LABEL = 'Mark all read';
const CLEAR_ALL_BTN_LABEL = 'Clear all';
const LOAD_OLDER_BTN_LABEL = 'Load older';
const INTERVAL_MS = 90000;

// localStorage keys for cross-tab sync (storage event fires in other tabs only).
const LS_KEY_SOUND_MUTED = 'tms_notifications_sound_muted';
const LS_KEY_REFRESH = 'tms_notifications_refresh';

// Inline SVG for sound toggle: sound on (bell) and sound off (muted).
const SOUND_ICON_ON =
	'<svg width="18" height="18" viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path fill-rule="evenodd" d="M1185.928 1581.176c0 124.575-101.309 225.883-225.883 225.883-124.574 0-225.882-101.308-225.882-225.883h451.765ZM960.045 225.882c342.438 0 621.177 278.626 621.177 621.177v395.294c0 86.739 32.753 165.91 86.4 225.882H252.356c53.76-59.971 86.513-139.143 86.513-225.882V847.059c0-342.55 278.626-621.177 621.176-621.177Zm734.118 1016.47V847.06c0-385.694-299.294-702.268-677.647-731.294V0H903.575v115.765c-378.466 29.026-677.647 345.6-677.647 731.294v395.294c0 124.574-101.309 225.882-225.883 225.882v112.941h621.177c0 186.805 151.906 338.824 338.823 338.824 186.805 0 338.824-152.019 338.824-338.824h621.176v-112.94c-124.574 0-225.882-101.309-225.882-225.883Z"/></svg>';
const SOUND_ICON_MUTED =
	'<svg width="18" height="18" viewBox="0 0 1920 1920" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path fill-rule="evenodd" d="m1505.845 72.093-187.52 223.467c-44.16-32.64-93.333-61.013-147.2-83.947-67.84-27.626-138.773-43.84-211.093-49.386V53H853.365v109.333c-357.44 27.414-640 326.4-640 690.667v373.333c0 56.427-22.72 111.574-62.293 151.04-39.467 39.574-94.613 62.294-151.04 62.294v106.666h269.333L119.18 1725.427l81.706 68.48L1587.552 140.573l-81.707-68.48ZM1479.467 462.6C1558.293 577.587 1600 712.627 1600 853v373.333c0 117.654 95.68 213.334 213.333 213.334 29.44 0 53.334 23.893 53.334 53.333 0 29.44-23.894 53.333-53.334 53.333h-586.666c0 176.427-143.574 320-320 320-176.427 0-320-143.573-320-320V1493c0-29.44 23.893-53.333 53.333-53.333h935.04c-50.773-56.64-81.707-131.414-81.707-213.334V853c0-118.72-35.2-232.96-101.76-330.027ZM1120 1546.333H693.333c0 117.654 95.68 213.334 213.334 213.334 117.653 0 213.333-95.68 213.333-213.334Zm-213.301-1280c77.12 0 152.426 14.827 223.253 43.734 43.733 18.666 83.84 41.813 119.573 67.626L358.86 1439.667h-120c51.733-58.027 81.173-134.827 81.173-213.334V853c0-323.413 263.253-586.667 586.667-586.667Z"/></svg>';

export interface TMSNotificationItem {
	id: number;
	title?: string;
	message?: string;
	created_at?: string;
	read_at?: string | null;
}

export interface TMSNotificationsInitial {
	items: TMSNotificationItem[];
	unread_count: number;
	total_count: number;
	has_more: boolean;
}

export interface TMSNotificationsConfig {
	apiListUrl: string;
	apiReadUrl: string;
	apiReadAllUrl: string;
	apiClearAllUrl?: string;
	apiSoundMutedUrl?: string;
	restNonce: string;
	initial: TMSNotificationsInitial;
	soundUrl?: string | null;
	soundMuted?: boolean;
	soundMutedLabelMute?: string;
	soundMutedLabelUnmute?: string;
}

declare global {
	interface Window {
		TMSNotificationsConfig?: TMSNotificationsConfig;
	}
}

function escapeHtml( s: string ): string {
	return s.replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
}

function getHeaders( restNonce: string, includeJson: boolean ): Record<string, string> {
	const h: Record<string, string> = {
		Accept: 'application/json',
		'X-WP-Nonce': restNonce,
	};
	if ( includeJson ) {
		h['Content-Type'] = 'application/json';
	}
	return h;
}

export function initLiveReloadNotification(): void {
	const config = window.TMSNotificationsConfig;
	if ( ! config ) {
		return;
	}

	const toggleBtn = document.getElementById( 'tms-notifications-toggle' );
	const badgeEl = document.getElementById( 'tms-notifications-badge' );
	const panelEl = document.getElementById( 'tms-notifications-panel' );
	const listEl = document.getElementById( 'tms-notifications-list' );
	const closeBtn = document.getElementById( 'tms-notifications-close' );
	const markAllBtn = document.getElementById( 'tms-notifications-mark-all' );
	const clearAllBtn = document.getElementById( 'tms-notifications-clear-all' );
	const loadOlderBtn = document.getElementById( 'tms-notifications-load-older' );
	const soundToggleBtn = document.getElementById( 'tms-notifications-sound-toggle' );

	if ( ! toggleBtn || ! badgeEl || ! panelEl || ! listEl ) {
		return;
	}

	const { apiListUrl, apiReadUrl, apiReadAllUrl, apiClearAllUrl, apiSoundMutedUrl, restNonce, initial: initialData, soundUrl, soundMuted: initialSoundMuted, soundMutedLabelMute, soundMutedLabelUnmute } = config;

	let notificationsCache: TMSNotificationItem[] = Array.isArray( initialData.items ) ? initialData.items : [];
	let totalNotificationsCount: number = typeof initialData.total_count === 'number' ? initialData.total_count : 0;
	let currentNotificationsPage = 1;
	let hasMorePagesFromServer: boolean = initialData.has_more === true;
	let currentUnreadCount: number = typeof initialData.unread_count === 'number' ? initialData.unread_count : 0;
	let isPanelOpen = false;
	let hasFetchedFromServerOnce = false;

	// Sync mute with other tabs: prefer localStorage so all tabs stay in sync (user may have muted in another tab).
	let soundMuted: boolean = initialSoundMuted === true;
	try {
		const stored = localStorage.getItem( LS_KEY_SOUND_MUTED );
		if ( stored === '1' ) {
			soundMuted = true;
		} else if ( stored === '0' || stored === '' ) {
			soundMuted = false;
		}
	} catch ( e ) {
		// ignore
	}

	let notificationAudio: HTMLAudioElement | null = null;
	if ( typeof soundUrl === 'string' && soundUrl ) {
		try {
			notificationAudio = new Audio( soundUrl );
		} catch ( e ) {
			notificationAudio = null;
		}
	}

	// Debug: initial state (check console to verify mute + sound source).
	console.log( '[TMS Notifications] init: soundMuted=', soundMuted, 'initialSoundMuted=', initialSoundMuted, 'hasAudio=', !! notificationAudio );

	function playNotificationSound(): void {
		console.log( '[TMS Notifications] playNotificationSound called: soundMuted=', soundMuted, 'hasAudio=', !! notificationAudio );
		if ( soundMuted || ! notificationAudio ) {
			console.log( '[TMS Notifications] playNotificationSound SKIP (muted or no audio)' );
			return;
		}
		console.log( '[TMS Notifications] playNotificationSound PLAYING' );
		try {
			const cloned = notificationAudio.cloneNode( true ) as HTMLAudioElement;
			void cloned.play().catch( () => null );
		} catch ( e ) {
			// ignore
		}
	}

	function updateSoundToggleButton(): void {
		const btn = document.getElementById( 'tms-notifications-sound-toggle' );
		if ( ! btn ) return;
		const iconWrap = btn.querySelector( '.tms-notifications-sound-icon' );
		const title = soundMuted ? ( soundMutedLabelUnmute || 'Unmute sound' ) : ( soundMutedLabelMute || 'Mute sound' );
		btn.setAttribute( 'title', title );
		btn.setAttribute( 'aria-label', title );
		btn.classList.remove( 'btn-outline-danger', 'btn-outline-secondary' );
		btn.classList.add( soundMuted ? 'btn-outline-danger' : 'btn-outline-secondary' );
		if ( iconWrap ) {
			iconWrap.innerHTML = soundMuted ? SOUND_ICON_MUTED : SOUND_ICON_ON;
		}
	}

	function setBadge( unreadCount: number ): void {
		if ( unreadCount && unreadCount > 0 ) {
			badgeEl!.style.display = 'flex';
			badgeEl!.textContent = unreadCount > 99 ? '99' : String( unreadCount );
			if ( toggleBtn ) {
				toggleBtn.classList.add( 'tms-notifications-attention' );
			}
		} else {
			badgeEl!.style.display = 'none';
			badgeEl!.textContent = '';
			if ( toggleBtn ) {
				toggleBtn.classList.remove( 'tms-notifications-attention' );
			}
		}
	}

	function setMarkAllReadButtonLoading( loading: boolean ): void {
		if ( ! markAllBtn ) return;
		( markAllBtn as HTMLButtonElement ).disabled = loading;
		markAllBtn.textContent = loading ? 'Marking...' : MARK_ALL_READ_BTN_LABEL;
	}

	function updateMarkAllReadButton(): void {
		if ( ! markAllBtn ) return;
		markAllBtn.style.display = currentUnreadCount > 0 ? 'block' : 'none';
		if ( markAllBtn.style.display !== 'none' ) {
			markAllBtn.textContent = ( markAllBtn as HTMLButtonElement ).disabled ? 'Marking...' : MARK_ALL_READ_BTN_LABEL;
		}
	}

	function updateClearAllButton(): void {
		if ( ! clearAllBtn ) return;
		const hasAny = notificationsCache.length > 0 || totalNotificationsCount > 0;
		clearAllBtn.style.display = hasAny ? 'block' : 'none';
		if ( clearAllBtn.style.display !== 'none' ) {
			clearAllBtn.textContent = ( clearAllBtn as HTMLButtonElement ).disabled ? 'Clearing...' : CLEAR_ALL_BTN_LABEL;
		}
	}

	setBadge( currentUnreadCount );
	updateMarkAllReadButton();
	updateClearAllButton();

	function renderNotifications( items: TMSNotificationItem[] ): void {
		if ( ! Array.isArray( items ) || items.length === 0 ) {
			listEl!.innerHTML = '<p class="mb-0 text-muted" style="font-size: 13px;">No notifications.</p>';
			return;
		}

		const html = items
			.map( ( item ) => {
				const id = item.id;
				const title = item.title || '';
				const message = item.message || '';
				const createdAt = item.created_at || '';
				const isRead = !! item.read_at;
				return (
					'<div class="tms-notification-item" data-notification-id="' +
					id +
					'" style="padding: 6px 0; border-bottom: 1px solid #f1f1f1; cursor: pointer; ' +
					( isRead ? 'opacity:0.7;' : 'font-weight:500;' ) +
					'">' +
					'<div class="tms-notification-item__title">' +
					escapeHtml( title ) +
					'</div>' +
					( message
						? '<div class="tms-notification-item__message text-muted">' + escapeHtml( message ) + '</div>'
						: '' ) +
					( createdAt ? '<div class="tms-notification-item__date text-muted">' + escapeHtml( createdAt ) + '</div>' : '' ) +
					'</div>'
				);
			} )
			.join( '' );

		listEl!.innerHTML = html;
	}

	function setLoadOlderButtonLoading( loading: boolean ): void {
		if ( ! loadOlderBtn ) return;
		( loadOlderBtn as HTMLButtonElement ).disabled = loading;
		loadOlderBtn.textContent = loading ? 'Loading...' : LOAD_OLDER_BTN_LABEL;
	}

	function fetchNotifications( pageArg?: number, append?: boolean ): void {
		const page = pageArg ?? 1;
		append = !! append;

		const url =
			apiListUrl + ( apiListUrl.indexOf( '?' ) !== -1 ? '&' : '?' ) + 'per_page=20&page=' + page;
		fetch( url, {
			method: 'GET',
			credentials: 'same-origin',
			headers: getHeaders( restNonce, false ),
		} )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} )
			.then( ( payload: { success?: boolean; data?: TMSNotificationItem[]; unread_count?: number; total_count?: number; has_more?: boolean } ) => {
				if ( ! payload || ! payload.success ) return;

				const items = Array.isArray( payload.data ) ? payload.data : [];
				const unreadCount = typeof payload.unread_count === 'number' ? payload.unread_count : 0;
				const totalCount = typeof payload.total_count === 'number' ? payload.total_count : 0;
				const prevUnreadCount = currentUnreadCount;
				currentUnreadCount = unreadCount;
				if ( append || hasMorePagesFromServer ) {
					hasMorePagesFromServer = payload.has_more === true;
				}

				if ( append ) {
					notificationsCache = notificationsCache.concat( items );
					if ( items.length === 0 ) {
						totalNotificationsCount = notificationsCache.length;
						hasMorePagesFromServer = false;
					}
				} else {
					notificationsCache = items;
					currentNotificationsPage = 1;
				}
				if ( totalCount >= 0 ) {
					totalNotificationsCount = totalCount;
				}
				if ( page > 1 ) {
					currentNotificationsPage = page;
				}

				const willPlaySound = hasFetchedFromServerOnce && unreadCount > prevUnreadCount;
				const tabVisible = typeof document.visibilityState !== 'undefined' && document.visibilityState === 'visible';
				console.log( '[TMS Notifications] fetch result: hasFetchedOnce=', hasFetchedFromServerOnce, 'prevUnread=', prevUnreadCount, 'unreadCount=', unreadCount, 'soundMuted=', soundMuted, 'willPlaySound=', willPlaySound, 'tabVisible=', tabVisible );
				// Play sound only in the visible tab so user hears it once when multiple tabs are open.
				if ( willPlaySound && tabVisible ) {
					playNotificationSound();
				}
				if ( unreadCount > prevUnreadCount ) {
					try {
						localStorage.setItem( LS_KEY_REFRESH, Date.now().toString() );
					} catch ( err ) {
						// ignore
					}
				}
				hasFetchedFromServerOnce = true;

				setBadge( unreadCount );
				updateMarkAllReadButton();
				updateClearAllButton();
				if ( isPanelOpen ) {
					renderNotifications( notificationsCache );
					updateLoadOlderButton();
				}
				setLoadOlderButtonLoading( false );
			} )
			.catch( () => {
				setLoadOlderButtonLoading( false );
			} );
	}

	function updateLoadOlderButton(): void {
		if ( ! loadOlderBtn ) return;
		if ( hasMorePagesFromServer ) {
			loadOlderBtn.style.display = 'block';
			loadOlderBtn.textContent = ( loadOlderBtn as HTMLButtonElement ).disabled ? 'Loading...' : LOAD_OLDER_BTN_LABEL;
		} else {
			loadOlderBtn.style.display = 'none';
		}
	}

	function markRead( ids: number[] ): void {
		if ( ! ids || ! ids.length ) return;

		fetch( apiReadUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: getHeaders( restNonce, true ),
			body: JSON.stringify( { ids } ),
		} )
			.then( () => fetchNotifications() )
			.catch( () => {} );
	}

	function setClearAllButtonLoading( loading: boolean ): void {
		if ( ! clearAllBtn ) return;
		( clearAllBtn as HTMLButtonElement ).disabled = loading;
		clearAllBtn.textContent = loading ? 'Clearing...' : CLEAR_ALL_BTN_LABEL;
	}

	function markAllRead(): void {
		setMarkAllReadButtonLoading( true );
		fetch( apiReadAllUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: getHeaders( restNonce, true ),
			body: '{}',
		} )
			.then( () => fetchNotifications() )
			.catch( () => {} )
			.finally( () => setMarkAllReadButtonLoading( false ) );
	}

	function clearAll(): void {
		if ( ! apiClearAllUrl ) return;
		setClearAllButtonLoading( true );
		fetch( apiClearAllUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: getHeaders( restNonce, true ),
			body: '{}',
		} )
			.then( () => fetchNotifications() )
			.catch( () => {} )
			.finally( () => setClearAllButtonLoading( false ) );
	}

	function openPanel(): void {
		isPanelOpen = true;
		panelEl!.style.display = 'block';
		renderNotifications( notificationsCache );
		updateLoadOlderButton();
		updateMarkAllReadButton();
		updateClearAllButton();
	}

	function closePanel(): void {
		isPanelOpen = false;
		panelEl!.style.display = 'none';
	}

	toggleBtn.addEventListener( 'click', () => {
		if ( isPanelOpen ) {
			closePanel();
		} else {
			openPanel();
		}
	} );

	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', () => closePanel() );
	}

	if ( loadOlderBtn ) {
		loadOlderBtn.addEventListener( 'click', () => {
			if ( ! hasMorePagesFromServer || ( loadOlderBtn as HTMLButtonElement ).disabled ) return;
			const nextPage = currentNotificationsPage + 1;
			setLoadOlderButtonLoading( true );
			fetchNotifications( nextPage, true );
		} );
	}

	if ( markAllBtn ) {
		markAllBtn.addEventListener( 'click', () => markAllRead() );
	}

	if ( clearAllBtn && apiClearAllUrl ) {
		clearAllBtn.addEventListener( 'click', () => clearAll() );
	}

	if ( soundToggleBtn && apiSoundMutedUrl ) {
		soundToggleBtn.addEventListener( 'click', () => {
			soundMuted = ! soundMuted;
			console.log( '[TMS Notifications] mute toggled: soundMuted=', soundMuted );
			updateSoundToggleButton();
			try {
				localStorage.setItem( LS_KEY_SOUND_MUTED, soundMuted ? '1' : '0' );
			} catch ( e ) {
				// ignore
			}
			fetch( apiSoundMutedUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: getHeaders( restNonce, true ),
				body: JSON.stringify( { muted: soundMuted } ),
			} ).catch( () => {
				soundMuted = ! soundMuted;
				updateSoundToggleButton();
				try {
					localStorage.setItem( LS_KEY_SOUND_MUTED, soundMuted ? '1' : '0' );
				} catch ( err ) {
					// ignore
				}
			} );
		} );
	}

	// Cross-tab sync: when another tab changes mute or gets new notifications.
	try {
		window.addEventListener( 'storage', ( e: StorageEvent ) => {
			if ( e.key === LS_KEY_SOUND_MUTED && e.newValue !== null ) {
				const muted = e.newValue === '1';
				if ( muted !== soundMuted ) {
					soundMuted = muted;
					updateSoundToggleButton();
				}
			}
			if ( e.key === LS_KEY_REFRESH && e.newValue !== null ) {
				fetchNotifications();
			}
		} );
	} catch ( err ) {
		// ignore
	} 

	listEl.addEventListener( 'click', ( event: Event ) => {
		const item = ( event.target as HTMLElement ).closest( '.tms-notification-item' );
		if ( ! item ) return;
		const idAttr = item.getAttribute( 'data-notification-id' );
		const id = idAttr ? parseInt( idAttr, 10 ) : 0;
		if ( id > 0 ) {
			markRead( [ id ] );
		}
	} );

	fetchNotifications();
	setInterval( () => fetchNotifications(), INTERVAL_MS );
}
