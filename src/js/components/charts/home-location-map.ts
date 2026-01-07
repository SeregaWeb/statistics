/**
 * Home Location Map Component
 * Handles Leaflet map initialization for displaying US states with driver counts
 */

interface StateMapData {
	[name: string]: {
		name: string;
		count: number;
	};
}

interface StateMarker {
	state: string;
	name: string;
	count: number;
	lat: number;
	lng: number;
}

// Leaflet is loaded globally
declare global {
	interface Window {
		L: any;
	}
}

class HomeLocationMap {
	private map: any = null;
	private mapInitialized: boolean = false;
	private stateMapData: StateMapData;
	private maxCount: number;
	private stateMarkersData: StateMarker[];
	private geojsonSource: string;
	private mapContainer: HTMLElement | null = null;

	// State name to abbreviation mapping
	private stateNameToAbbr: { [key: string]: string } = {
		'Alabama': 'AL', 'Alaska': 'AK', 'Arizona': 'AZ', 'Arkansas': 'AR', 'California': 'CA',
		'Colorado': 'CO', 'Connecticut': 'CT', 'Delaware': 'DE', 'Florida': 'FL', 'Georgia': 'GA',
		'Hawaii': 'HI', 'Idaho': 'ID', 'Illinois': 'IL', 'Indiana': 'IN', 'Iowa': 'IA',
		'Kansas': 'KS', 'Kentucky': 'KY', 'Louisiana': 'LA', 'Maine': 'ME', 'Maryland': 'MD',
		'Massachusetts': 'MA', 'Michigan': 'MI', 'Minnesota': 'MN', 'Mississippi': 'MS', 'Missouri': 'MO',
		'Montana': 'MT', 'Nebraska': 'NE', 'Nevada': 'NV', 'New Hampshire': 'NH', 'New Jersey': 'NJ',
		'New Mexico': 'NM', 'New York': 'NY', 'North Carolina': 'NC', 'North Dakota': 'ND', 'Ohio': 'OH',
		'Oklahoma': 'OK', 'Oregon': 'OR', 'Pennsylvania': 'PA', 'Rhode Island': 'RI', 'South Carolina': 'SC',
		'South Dakota': 'SD', 'Tennessee': 'TN', 'Texas': 'TX', 'Utah': 'UT', 'Vermont': 'VT',
		'Virginia': 'VA', 'Washington': 'WA', 'West Virginia': 'WV', 'Wisconsin': 'WI', 'Wyoming': 'WY',
		'District of Columbia': 'DC'
	};

	constructor(
		stateMapData: StateMapData,
		maxCount: number,
		stateMarkersData: StateMarker[],
		geojsonSource: string
	) {
		this.stateMapData = stateMapData;
		this.maxCount = maxCount;
		this.stateMarkersData = stateMarkersData;
		this.geojsonSource = geojsonSource;
		this.init();
	}

	private init(): void {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', () => this.initMap());
		} else {
			this.initMap();
		}

		// Also try to initialize when tab becomes visible (for tab switching)
		const observer = new MutationObserver(() => {
			if (!this.mapInitialized && this.isContainerVisible()) {
				this.initMap();
			}
		});

		const chartContainer = document.querySelector('.chart-container[data-chart="home-location"]');
		if (chartContainer) {
			observer.observe(chartContainer, {
				attributes: true,
				attributeFilter: ['style']
			});
		}
	}

	private isContainerVisible(): boolean {
		if (!this.mapContainer) {
			this.mapContainer = document.getElementById('usaStatesMap');
		}
		if (!this.mapContainer) {
			return false;
		}
		return this.mapContainer.offsetParent !== null ||
			this.mapContainer.style.display !== 'none' ||
			window.getComputedStyle(this.mapContainer).display !== 'none';
	}

	private initMap(): void {
		if (this.mapInitialized || this.map !== null) {
			return;
		}

		this.mapContainer = document.getElementById('usaStatesMap');
		if (!this.mapContainer) {
			return;
		}

		if (!this.isContainerVisible()) {
			setTimeout(() => this.initMap(), 100);
			return;
		}

		// Initialize map centered on USA
		this.map = window.L.map('usaStatesMap').setView([39.8283, -98.5795], 8);
		this.mapInitialized = true;

		// Add base map layers
		this.setupBaseLayers();
		
		// Load and display states
		this.loadStatesGeoJSON();
		
		// Add state markers
		this.addStateMarkers();

		// Store map in global scope for future use
		(window as any).usaStatesMap = this.map;
	}

	private setupBaseLayers(): void {
		const L = window.L;
		// Use CartoDB Positron as base - it shows roads clearly and works well with overlays
		const cartoLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
			subdomains: 'abcd',
			maxZoom: 19
		}).addTo(this.map);

		// Alternative: OpenStreetMap standard (shows all details including roads)
		const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{s}/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
			maxZoom: 19
		});

		// Alternative: OpenStreetMap with roads emphasized
		const roadsLayer = L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
			attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
			maxZoom: 19
		});

		// Add layer control to switch between map styles
		const baseMaps = {
			"Light Style (Recommended)": cartoLayer,
			"OpenStreetMap (Detailed)": osmLayer,
			"Roads Emphasized": roadsLayer
		};
		L.control.layers(baseMaps).addTo(this.map);
	}

	private getColorForCount(count: number): string {
		if (count === 0) return '#f0f0f0';

		const intensity = count / this.maxCount;
		if (intensity < 0.2) return '#c6e2ff';
		if (intensity < 0.4) return '#7db8e8';
		if (intensity < 0.6) return '#4a9cd6';
		if (intensity < 0.8) return '#2e7bb8';
		return '#1a5a9a';
	}

	private getStateAbbr(properties: any): string {
		let abbr = properties.STUSPS || properties.STATE_ABBR || properties.abbr || properties.state || properties.STATE || '';

		if (abbr && abbr.length <= 2) {
			return abbr.toUpperCase();
		}

		const fullName = properties.NAME || properties.name || properties.STATE_NAME || abbr;
		if (fullName && this.stateNameToAbbr[fullName]) {
			return this.stateNameToAbbr[fullName];
		}

		if (fullName) {
			for (const [name, abbrValue] of Object.entries(this.stateNameToAbbr)) {
				if (name.toLowerCase() === fullName.toLowerCase()) {
					return abbrValue;
				}
			}
		}

		return '';
	}

	private isUSState(feature: any): boolean {
		const stateAbbr = this.getStateAbbr(feature.properties);
		if (stateAbbr && this.stateMapData[stateAbbr]) {
			return true;
		}

		const stateName = feature.properties.NAME || feature.properties.name || '';
		if (stateName && this.stateNameToAbbr[stateName]) {
			return true;
		}

		if (stateAbbr && Object.values(this.stateNameToAbbr).includes(stateAbbr)) {
			return true;
		}

		return false;
	}

	private updateInfoPanel(stateName: string, count: number): void {
		let infoPanel = document.getElementById('mapInfoPanel');
		if (!infoPanel) {
			if (!this.mapContainer) return;
			infoPanel = document.createElement('div');
			infoPanel.id = 'mapInfoPanel';
			infoPanel.className = 'home-location-map-info-panel';
			this.mapContainer.appendChild(infoPanel);
		}

		if (stateName && count > 0) {
			infoPanel.innerHTML = '<strong>' + stateName + '</strong><br>Drivers: ' + count;
			infoPanel.style.display = 'block';
		} else {
			infoPanel.style.display = 'none';
		}
	}

	private loadStatesGeoJSON(): void {
		fetch(this.geojsonSource)
			.then(response => {
				if (!response.ok) {
					throw new Error('Failed to load states GeoJSON');
				}
				return response.json();
			})
			.then(geojson => {
				this.renderStatesLayer(geojson);
			})
			.catch(error => {
				console.error('Error loading states GeoJSON:', error);
				this.showErrorMessage('Map Ready - State boundaries loading...');
			});
	}

	private renderStatesLayer(geojson: any): void {
		const L = window.L;
		const styleState = (feature: any) => {
			const isUS = this.isUSState(feature);

			if (!isUS) {
				return {
					fillColor: '#ffffff',
					weight: 0,
					opacity: 0,
					color: 'transparent',
					dashArray: '',
					fillOpacity: 0
				};
			}

			const stateAbbr = this.getStateAbbr(feature.properties);
			const stateData = this.stateMapData[stateAbbr] || { count: 0, name: feature.properties.NAME || stateAbbr };

			return {
				fillColor: this.getColorForCount(stateData.count),
				weight: 1.5,
				opacity: 0.8,
				color: '#fff',
				dashArray: '',
				fillOpacity: 0.25
			};
		};

		const highlightFeature = (e: any) => {
			const layer = e.target;
			layer.setStyle({
				weight: 3,
				color: '#333',
				dashArray: '',
				fillOpacity: 0.5
			});

			if (!L.Browser.ie && !L.Browser.opera && !L.Browser.edge) {
				layer.bringToFront();
			}

			const stateAbbr = this.getStateAbbr(layer.feature.properties);
			const stateData = this.stateMapData[stateAbbr] || { count: 0, name: layer.feature.properties.NAME || stateAbbr };
			this.updateInfoPanel(stateData.name, stateData.count);
		};

		const resetHighlight = (e: any) => {
			geojsonLayer.resetStyle(e.target);
			this.updateInfoPanel('', 0);
		};

		const zoomToFeature = (e: any) => {
			this.map.fitBounds(e.target.getBounds());
			const stateAbbr = this.getStateAbbr(e.target.feature.properties);
			const stateData = this.stateMapData[stateAbbr] || { count: 0, name: e.target.feature.properties.NAME || stateAbbr };
			this.updateInfoPanel(stateData.name, stateData.count);
		};

		const geojsonLayer = L.geoJSON(geojson, {
			style: styleState,
			onEachFeature: (feature: any, layer: any) => {
				const isUS = this.isUSState(feature);

				if (isUS) {
					const stateAbbr = this.getStateAbbr(feature.properties);
					const stateData = this.stateMapData[stateAbbr] || { count: 0, name: feature.properties.NAME || stateAbbr };

					layer.bindPopup(
						'<strong>' + stateData.name + '</strong><br>' +
						'Drivers: ' + stateData.count
					);

					layer.on({
						mouseover: highlightFeature,
						mouseout: resetHighlight,
						click: zoomToFeature
					});
				}
			}
		});

		geojsonLayer.addTo(this.map);
		this.map.getPane('overlayPane').style.zIndex = 400;
		this.map.setView([39.8283, -98.5795], 5);
	}

	private addStateMarkers(): void {
		if (!this.stateMarkersData || this.stateMarkersData.length === 0) {
			return;
		}

		const L = window.L;
		this.stateMarkersData.forEach((stateMarker: StateMarker) => {
			if (stateMarker.lat && stateMarker.lng && stateMarker.count > 0) {
				const baseSize = 40;
				const maxSize = 80;
				const size = Math.min(
					baseSize + (stateMarker.count / this.maxCount) * (maxSize - baseSize),
					maxSize
				);

				const stateIcon = L.divIcon({
					className: 'state-driver-marker',
					html: '<div class="state-driver-marker-content" style="width: ' + size + 'px; height: ' + size + 'px; font-size: ' + (size * 0.35) + 'px;">' + stateMarker.count + '</div>',
					iconSize: [size, size],
					iconAnchor: [size / 2, size / 2]
				});

				const marker = L.marker([stateMarker.lat, stateMarker.lng], { icon: stateIcon });

				const popupContent = '<div class="state-marker-popup">' +
					'<strong>' + stateMarker.name + '</strong><br>' +
					'<span class="state-driver-count">' + stateMarker.count + '</span> ' +
					(stateMarker.count === 1 ? 'driver' : 'drivers') +
					'</div>';

				marker.bindPopup(popupContent);
				marker.addTo(this.map);
			}
		});
	}

	private showErrorMessage(message: string): void {
		if (!this.mapContainer) return;

		const infoDiv = document.createElement('div');
		infoDiv.className = 'home-location-map-error-message';
		infoDiv.innerHTML = '<strong>Map Ready</strong><br>' + message + '<br><small>Markers can be added below.</small>';
		this.mapContainer.appendChild(infoDiv);

		setTimeout(() => {
			if (infoDiv.parentNode) {
				infoDiv.parentNode.removeChild(infoDiv);
			}
		}, 5000);
	}
}

// Initialize map when data is available
export function initHomeLocationMap(
	stateMapData: StateMapData,
	maxCount: number,
	stateMarkersData: StateMarker[],
	geojsonSource: string
): void {
	// Wait for Leaflet to be available
	const checkLeaflet = () => {
		if (typeof (window as any).L !== 'undefined') {
			new HomeLocationMap(stateMapData, maxCount, stateMarkersData, geojsonSource);
		} else {
			// Retry after a short delay if Leaflet is not yet loaded
			setTimeout(checkLeaflet, 100);
		}
	};
	
	checkLeaflet();
}

