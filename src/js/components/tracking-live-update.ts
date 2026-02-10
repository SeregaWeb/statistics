/**
 * Real-time tracking page updates: poll server for counts and table rows,
 * update badges and tbody without full page reload.
 */

const POLL_INTERVAL_MS = 20000; // 20 seconds

function getCurrentParams(): Record<string, string> {
	const params: Record<string, string> = {};
	const search = window.location.search.replace( /^\?/, '' );
	if ( ! search ) {
		return params;
	}
	search.split( '&' ).forEach( ( pair ) => {
		const eq = pair.indexOf( '=' );
		if ( eq === -1 ) {
			return;
		}
		const key = pair.slice( 0, eq );
		const value = pair.slice( eq + 1 );
		try {
			params[ decodeURIComponent( key ) ] = decodeURIComponent( value );
		} catch {
			// skip malformed param
		}
	} );
	return params;
}

function getCurrentLoadIds( tbody: Element ): number[] {
	const rows = tbody.querySelectorAll( 'tr[data-load-id]' );
	return Array.from( rows ).map( ( tr ) => parseInt( tr.getAttribute( 'data-load-id' ) || '0', 10 ) ).filter( Boolean );
}

function arraysEqual( a: number[], b: number[] ): boolean {
	if ( a.length !== b.length ) {
		return false;
	}
	return a.every( ( val, i ) => val === b[ i ] );
}

function updateCounts( counts: Record<string, number> ): void {
	document.querySelectorAll( '.js-tracking-quick-status' ).forEach( ( link ) => {
		const key = link.getAttribute( 'data-status-key' );
		if ( key === null ) {
			return;
		}
		const num = counts[ key ] !== undefined ? counts[ key ] : 0;
		const badge = link.querySelector( '.js-tracking-count' );
		if ( badge && badge.textContent !== String( num ) ) {
			badge.textContent = String( num );
		}
	} );
}

function getTrackingContext(): string {
	const el = document.querySelector( '.js-table-tracking' );
	const wrapper = el?.closest( '[data-tracking-context]' );
	return ( wrapper?.getAttribute( 'data-tracking-context' ) || '' ).trim();
}

export function initTrackingLiveUpdate(): void {
	if ( document.querySelector( '[data-tracking-live-update="false"]' ) ) {
		return;
	}

	const table = document.querySelector( '.js-table-tracking' );
	const tbody = table?.querySelector( '.js-tracking-tbody' );
	const hasQuickStatus = document.querySelectorAll( '.js-tracking-quick-status' ).length > 0;

	if ( ! table || ! tbody || ! hasQuickStatus ) {
		return;
	}

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const ajaxUrl = ( window as any ).var_from_php?.ajax_url;
	if ( ! ajaxUrl ) {
		return;
	}

	let pollTimer: ReturnType<typeof setTimeout> | null = null;

	function poll(): void {
		const params = getCurrentParams();
		const formData = new FormData();
		formData.append( 'action', 'get_tracking_live_state' );
		Object.keys( params ).forEach( ( key ) => {
			formData.append( key, params[ key ] );
		} );
		const context = getTrackingContext();
		if ( context ) {
			formData.append( 'tracking_context', context );
		}

		fetch( ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} )
			.then( ( res ) => res.json() )
			.then( ( data ) => {
				if ( ! data.success || ! data.data ) {
					return;
				}
				const { counts, load_ids: newLoadIds, rows_html } = data.data;

				if ( counts && typeof counts === 'object' ) {
					updateCounts( counts );
				}

				if ( Array.isArray( newLoadIds ) && typeof rows_html === 'string' && tbody instanceof HTMLElement ) {
					const currentIds = getCurrentLoadIds( tbody );
					if ( ! arraysEqual( currentIds, newLoadIds ) ) {
						tbody.innerHTML = rows_html;
					}
				}
			} )
			.catch( () => {} );
	}

	function schedule(): void {
		if ( pollTimer ) {
			clearTimeout( pollTimer );
		}
		pollTimer = setTimeout( () => {
			poll();
			schedule();
		}, POLL_INTERVAL_MS );
	}

	// Start after a short delay so initial load is not duplicated
	pollTimer = setTimeout( () => {
		poll();
		schedule();
	}, POLL_INTERVAL_MS );
}
