interface Driver {
    id: number;
    lat: number;
    lng: number;
    status: string;
    name: string;
    phone: string;
    unit: string;
    city: string;
    state: string;
    dimensions?: string;
    payload?: string;
    vehicle_type?: string;
    capabilities?: string[];
    available_date?: string;
}

interface StatusColors {
    [key: string]: string;
}

interface StatusLabels {
    [key: string]: string;
}

class DriversMap {
    private map: any = null;
    private platform: any = null;
    private mapEvents: any = null;
    private mapUI: any = null;
    private markers: any[] = [];
    private bubbles: any[] = [];
    private markerGroup: any = null;
    private markerClickHandler: any = null;
    private ajaxUrl: string;
    private apiKey: string;
    private driverProfileUrl: string = '';
    private statusColors: StatusColors = {
        'available': '#00d200',
        'available_on': '#cefece',
        'loaded_enroute': '#cefece',
        'available_off': '#e06665',
        'banned': '#ffb261',
        'no_interview': '#d60000',
        'expired_documents': '#d60000',
        'blocked': '#d60000',
        'on_vocation': '#ffb4d3',
        'on_hold': '#b2b2b2',
        'need_update': '#f1cfcf',
        'no_updates': '#ff3939',
        'unknown': '#808080'
    };
    private statusLabels: StatusLabels = {
        'available': 'Available',
        'available_on': 'Available on',
        'available_off': 'Not available',
        'loaded_enroute': 'Loaded & Enroute',
        'banned': 'Out of service',
        'on_vocation': 'On vacation',
        'no_updates': 'No updates',
        'blocked': 'Blocked',
        'expired_documents': 'Expired documents',
        'no_interview': 'No Interview',
        'no_Interview': 'No Interview',
        'on_hold': 'On hold',
        'need_update': 'Need update',
        'unknown': 'Unknown'
    };

    constructor(ajaxUrl: string, apiKey: string) {
        this.ajaxUrl = ajaxUrl;
        this.apiKey = apiKey;
        this.init();
    }

    private init(): void {
        const modal = document.getElementById('driversMapModal');
        if (!modal) return;
        
        // Get driver profile URL from modal data attribute
        const profileUrlAttr = modal.getAttribute('data-driver-profile-url');
        if (profileUrlAttr) {
            this.driverProfileUrl = profileUrlAttr;
        }

        // Initialize map when modal is shown
        modal.addEventListener('shown.bs.modal', () => {
            this.loadHereMapsScript().then(() => {
                this.initializeMap();
            });
        });

        // Clean up when modal is hidden
        modal.addEventListener('hidden.bs.modal', () => {
            this.cleanup();
        });
    }

    private loadHereMapsScript(): Promise<void> {
        return new Promise((resolve, reject) => {
            // Check if scripts are already loaded
            if ((window as any).H && (window as any).H.service && (window as any).H.mapevents) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = `https://js.api.here.com/v3/3.1/mapsjs-core.js`;
            script.async = true;
            script.onload = () => {
                // Load mapevents module (required for click events)
                const mapeventsScript = document.createElement('script');
                mapeventsScript.type = 'text/javascript';
                mapeventsScript.src = `https://js.api.here.com/v3/3.1/mapsjs-mapevents.js`;
                mapeventsScript.async = true;
                mapeventsScript.onload = () => {
                    // Load additional modules
                    const serviceScript = document.createElement('script');
                    serviceScript.type = 'text/javascript';
                    serviceScript.src = `https://js.api.here.com/v3/3.1/mapsjs-service.js`;
                    serviceScript.async = true;
                    serviceScript.onload = () => {
                        const uiScript = document.createElement('script');
                        uiScript.type = 'text/javascript';
                        uiScript.src = `https://js.api.here.com/v3/3.1/mapsjs-ui.js`;
                        uiScript.async = true;
                        uiScript.onload = () => {
                            // Load CSS
                            const link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.type = 'text/css';
                            link.href = 'https://js.api.here.com/v3/3.1/mapsjs-ui.css';
                            document.head.appendChild(link);
                            resolve();
                        };
                        uiScript.onerror = reject;
                        document.head.appendChild(uiScript);
                    };
                    serviceScript.onerror = reject;
                    document.head.appendChild(serviceScript);
                };
                mapeventsScript.onerror = reject;
                document.head.appendChild(mapeventsScript);
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    private initializeMap(): void {
        const container = document.getElementById('driversMapContainer');
        if (!container) return;

        // Initialize platform
        this.platform = new (window as any).H.service.Platform({
            apikey: this.apiKey
        });

        // Get default map layers
        const defaultLayers = this.platform.createDefaultLayers();

        // Create map instance
        this.map = new (window as any).H.Map(
            container,
            defaultLayers.vector.normal.map,
            {
                zoom: 4,
                center: { lat: 39.8283, lng: -98.5795 }, // Center of USA
                pixelRatio: window.devicePixelRatio || 1
            }
        );

        // Add behavior
        const mapEvents = new (window as any).H.mapevents.MapEvents(this.map);
        const behavior = new (window as any).H.mapevents.Behavior(mapEvents);

        // Add UI
        const ui = (window as any).H.ui.UI.createDefault(this.map, defaultLayers);
        
        // Store mapEvents and UI for later use
        this.mapEvents = mapEvents;
        this.mapUI = ui;
        
        // Close bubbles when clicking on map (using map's addEventListener)
        this.map.addEventListener('tap', (evt: any) => {
            if (evt.target === this.map) {
                this.bubbles.forEach(bubble => {
                    ui.removeBubble(bubble);
                });
            }
        });

        // Load and display drivers
        this.loadDrivers();
    }

    private loadDrivers(): void {
        const formData = new FormData();
        formData.append('action', 'get_drivers_for_map');
        
        // Get driver IDs from modal data attribute (if available - optimization)
        const modal = document.getElementById('driversMapModal');
        let driverIds: number[] = [];
        if (modal) {
            const driverIdsAttr = modal.getAttribute('data-driver-ids');
            if (driverIdsAttr) {
                try {
                    driverIds = JSON.parse(driverIdsAttr);
                } catch (e) {
                    console.warn('Failed to parse driver IDs from data attribute:', e);
                }
            }
        }
        
        // If we have driver IDs, use them directly (optimization - skip filtering)
        if (driverIds.length > 0) {
            driverIds.forEach(id => {
                formData.append('driver_ids[]', id.toString());
            });
        } else {
            // Fallback: Get filter parameters from URL or form
            const urlParams = new URLSearchParams(window.location.search);
            const mySearch = urlParams.get('my_search') || '';
            const extendedSearch = urlParams.get('extended_search') || '';
            const radius = urlParams.get('radius') || '';
            const country = urlParams.get('country') || '';
            
            // Get capabilities - can be capabilities[] or capabilities
            const capabilities: string[] = [];
            urlParams.forEach((value, key) => {
                if (key === 'capabilities[]' || key === 'capabilities') {
                    capabilities.push(value);
                }
            });
            
            // Add filter parameters to form data
            if (mySearch) {
                formData.append('my_search', mySearch);
            }
            if (extendedSearch) {
                formData.append('extended_search', extendedSearch);
            }
            if (radius) {
                formData.append('radius', radius);
            }
            if (country) {
                formData.append('country', country);
            }
            capabilities.forEach(cap => {
                formData.append('capabilities[]', cap);
            });
        }

        fetch(this.ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.drivers) {
                this.displayDrivers(data.data.drivers);
            } else {
                console.error('Failed to load drivers:', data);
            }
        })
        .catch(error => {
            console.error('Error loading drivers:', error);
        });
    }

    private displayDrivers(drivers: Driver[]): void {
        if (!this.map || !drivers || drivers.length === 0) return;

        // Clear existing markers
        this.clearMarkers();

        // Create group for markers
        this.markerGroup = new (window as any).H.map.Group();

        // Create markers for each driver
        drivers.forEach(driver => {
            if (!driver.lat || !driver.lng) return;

            const color = this.statusColors[driver.status] || this.statusColors['unknown'];
            
            // Create custom marker icon (standard teardrop marker)
            const icon = new (window as any).H.map.Icon(
                this.createMarkerSVG(color),
                { 
                    size: { w: 34, h: 48 }, 
                    anchor: { x: 17, y: 48 }
                }
            );

            // Create marker
            const marker = new (window as any).H.map.Marker(
                { lat: driver.lat, lng: driver.lng },
                { icon: icon, data: driver }
            );

            // Create info bubble content
            const bubbleContent = this.createBubbleContent(driver);

            // Create info bubble
            const bubble = new (window as any).H.ui.InfoBubble(
                { lat: driver.lat, lng: driver.lng },
                { content: bubbleContent }
            );

            // Store bubble reference with marker
            marker.bubble = bubble;
            marker.driverData = driver;
            this.bubbles.push(bubble);

            // Add click event directly to marker
            marker.addEventListener('tap', (evt: any) => {
                evt.stopPropagation();
                this.openBubble(bubble);
            });

            this.markerGroup.addObject(marker);
            this.markers.push(marker);
        });

        // Add group to map
        this.map.addObject(this.markerGroup);

        // Fit map to show all markers
        if (drivers.length > 0) {
            this.map.getViewModel().setLookAtData({
                bounds: this.markerGroup.getBoundingBox()
            });
        }
    }

    private createMarkerSVG(color: string): string {
        // Create standard teardrop marker (like Google Maps)
        // Teardrop shape: rounded top, pointed bottom
        const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="34" height="48" viewBox="0 0 34 48">
            <!-- Shadow (simple offset) -->
            <path d="M17 2 C10.373 2 5 7.373 5 14 C5 20 17 46 17 46 C17 46 29 20 29 14 C29 7.373 23.627 2 17 2 Z" 
                  fill="#000000" 
                  opacity="0.15" 
                  transform="translate(1, 2)"/>
            <!-- Main teardrop shape -->
            <path d="M17 2 C10.373 2 5 7.373 5 14 C5 20 17 46 17 46 C17 46 29 20 29 14 C29 7.373 23.627 2 17 2 Z" 
                  fill="${color}" 
                  stroke="#ffffff" 
                  stroke-width="2"/>
            <!-- Inner circle -->
            <circle cx="17" cy="14" r="6" fill="#ffffff"/>
        </svg>`;
        return `data:image/svg+xml;charset=UTF-8,${encodeURIComponent(svg)}`;
    }

    private openBubble(bubble: any): void {
        if (!this.mapUI) return;
        
        // Close all other bubbles
        this.bubbles.forEach(b => {
            if (b !== bubble) {
                this.mapUI.removeBubble(b);
            }
        });
        // Open this bubble
        this.mapUI.addBubble(bubble);
    }

    private createBubbleContent(driver: Driver): string {
        // Get human-readable status label from mapping, fallback to formatted status
        let statusLabel = this.statusLabels[driver.status] || 
            driver.status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        
        // Add date for 'available_on' and 'loaded_enroute' statuses
        if ((driver.status === 'available_on' || driver.status === 'loaded_enroute') && driver.available_date) {
            try {
                const date = new Date(driver.available_date);
                if (!isNaN(date.getTime())) {
                    // Format date as m/d/Y g:i a (same format as PHP)
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const year = date.getFullYear();
                    const hours = date.getHours();
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    const ampm = hours >= 12 ? 'pm' : 'am';
                    const displayHours = hours % 12 || 12;
                    const formattedDate = `${month}/${day}/${year} ${displayHours}:${minutes} ${ampm}`;
                    statusLabel += ` (${formattedDate})`;
                }
            } catch (e) {
                // If date parsing fails, just use the status label without date
            }
        }
        
        const location = [driver.city, driver.state].filter(Boolean).join(', ') || 'N/A';
        const dimensions = driver.dimensions || 'N/A';
        const payload = driver.payload || 'N/A';
        const vehicleType = driver.vehicle_type || 'N/A';
        const capabilities = driver.capabilities && driver.capabilities.length > 0 
            ? driver.capabilities.join(', ') 
            : 'None';
        
        return `
            <div style="padding: 15px; min-width: 280px; max-width: 400px; font-family: Arial, sans-serif;">
                <h6 style="margin: 0 0 12px 0; font-weight: bold; font-size: 16px; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px;">
                    Unit #${driver.unit || driver.id}
                </h6>
                
                <div style="margin-bottom: 10px;">
                    ${driver.name ? `<p style="margin: 4px 0; font-size: 14px;"><strong>Name:</strong> ${this.driverProfileUrl ? `<a href="${this.driverProfileUrl}?driver=${driver.id}" target="_blank" rel="noopener noreferrer" style="color: #007bff; text-decoration: none;">${driver.name}</a>` : driver.name}</p>` : ''}
                    ${driver.phone ? `<p style="margin: 4px 0; font-size: 14px;"><strong>Phone:</strong> <a href="tel:${driver.phone}" style="color: #007bff; text-decoration: none;">${driver.phone}</a></p>` : ''}
                </div>
                
                <div style="margin-bottom: 10px; padding-top: 8px; border-top: 1px solid #f0f0f0;">
                    <p style="margin: 4px 0; font-size: 14px;"><strong>Location:</strong> ${location}</p>
                </div>
                
                <div style="margin-bottom: 10px; padding-top: 8px; border-top: 1px solid #f0f0f0;">
                    <p style="margin: 4px 0; font-size: 14px;"><strong>Dimensions:</strong> ${dimensions}</p>
                    <p style="margin: 4px 0; font-size: 14px;"><strong>Payload:</strong> ${payload}</p>
                    ${vehicleType !== 'N/A' ? `<p style="margin: 4px 0; font-size: 14px;"><strong>Vehicle Type:</strong> ${vehicleType}</p>` : ''}
                </div>
                
                <div style="margin-bottom: 8px; padding-top: 8px; border-top: 1px solid #f0f0f0;">
                    <p style="margin: 0 0 6px 0; font-size: 14px; font-weight: bold;">Additional Details:</p>
                    <p style="margin: 0; font-size: 13px; color: #555; line-height: 1.6;">${capabilities}</p>
                </div>
                
                <div style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #f0f0f0;">
                    <p style="margin: 0; font-size: 12px; color: #888;"><strong>Status:</strong> ${statusLabel}</p>
                </div>
            </div>
        `;
    }

    private clearMarkers(): void {
        if (this.map) {
            // Remove all bubbles
            if (this.bubbles.length > 0 && this.mapUI) {
                this.bubbles.forEach(bubble => {
                    this.mapUI.removeBubble(bubble);
                });
                this.bubbles = [];
            }
            
            // Remove marker group from map if it exists
            if (this.markerGroup) {
                try {
                    this.map.removeObject(this.markerGroup);
                } catch (e) {
                    // Ignore errors if group was already removed
                }
                this.markerGroup = null;
            }
            
            // Clear markers array
            this.markers = [];
        }
    }

    private cleanup(): void {
        this.clearMarkers();
        if (this.map) {
            this.map.dispose();
            this.map = null;
        }
        this.platform = null;
        this.mapEvents = null;
        this.mapUI = null;
        this.markerClickHandler = null;
    }
}

export default DriversMap;

