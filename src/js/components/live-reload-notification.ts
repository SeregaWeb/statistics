/**
 * TMS Notifications: badge, panel, fetch, mark read.
 * Config from PHP via window.TMSNotificationsConfig (set only when user has access).
 */

const MARK_ALL_READ_BTN_LABEL = 'Mark all read';
const LOAD_OLDER_BTN_LABEL = 'Load older';
const INTERVAL_MS = 90000;

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
	restNonce: string;
	initial: TMSNotificationsInitial;
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
	const loadOlderBtn = document.getElementById( 'tms-notifications-load-older' );

	if ( ! toggleBtn || ! badgeEl || ! panelEl || ! listEl ) {
		return;
	}

	const { apiListUrl, apiReadUrl, apiReadAllUrl, restNonce, initial: initialData } = config;

	let notificationsCache: TMSNotificationItem[] = Array.isArray( initialData.items ) ? initialData.items : [];
	let totalNotificationsCount: number = typeof initialData.total_count === 'number' ? initialData.total_count : 0;
	let currentNotificationsPage = 1;
	let hasMorePagesFromServer: boolean = initialData.has_more === true;
	let currentUnreadCount: number = typeof initialData.unread_count === 'number' ? initialData.unread_count : 0;
	let isPanelOpen = false;

	function setBadge( unreadCount: number ): void {
		if ( unreadCount && unreadCount > 0 ) {
			badgeEl!.style.display = 'flex';
			badgeEl!.textContent = unreadCount > 99 ? '99' : String( unreadCount );
		} else {
			badgeEl!.style.display = 'none';
			badgeEl!.textContent = '';
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
 
	setBadge( currentUnreadCount );
	updateMarkAllReadButton();

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
					'<div style="font-size: 13px;">' +
					escapeHtml( title ) +
					'</div>' +
					( message
						? '<div style="font-size: 12px; color:#555; white-space:pre-line;">' + escapeHtml( message ) + '</div>'
						: '' ) +
					( createdAt ? '<div style="font-size: 11px; color:#999; margin-top:2px;">' + escapeHtml( createdAt ) + '</div>' : '' ) +
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

				setBadge( unreadCount );
				updateMarkAllReadButton();
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

	function openPanel(): void {
		isPanelOpen = true;
		panelEl!.style.display = 'block';
		renderNotifications( notificationsCache );
		updateLoadOlderButton();
		updateMarkAllReadButton();
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
