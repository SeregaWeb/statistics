<?php
/**
 * Template Name: Drivers API Monitor
 *
 * @package WP-rock
 * @since 1.0.0
 */

get_header();

$TMSUsers = new TMSUsers();
$helper   = new TMSReportsHelper();
// TODO: Change to check user role access
$access = true;

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3 pb-3">
                        <?php if ( ! $access ) :
                            echo $helper->message_top( 'error', 'Access only Administrator and Recruiter Team Leader have access to this page.' );
                        else:
                            // Get API stats without creating full API instance
                            $api_stats = array(
                                'total_keys' => 3, // Static count for now
                                'last_updated' => current_time('mysql')
                            );
                            
                            // Initialize API class only when needed (lazy loading)
                            $api_initialized = false;
                            ?>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">TMS Drivers API Monitor</h3>
                                </div>
                                <div class="card-body">
                                    
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h5>API Information</h5>
                                            <ul class="list-group">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Total API Keys:</span>
                                                    <span class="badge badge-primary"><?php echo $api_stats['total_keys']; ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Last Updated:</span>
                                                    <span><?php echo $api_stats['last_updated']; ?></span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h5>Available Endpoints</h5>
                                            <ul class="list-group">
                                                <li class="list-group-item">
                                                    <strong>GET</strong> <?php echo home_url('/wp-json/tms/v1/driver?email={email}'); ?>
                                                    <br><small class="text-muted">Get driver data by email address</small>
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>GET</strong> <?php echo home_url('/wp-json/tms/v1/driver?id={id}'); ?>
                                                    <br><small class="text-muted">Get driver data by driver ID</small>
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>GET</strong> <?php echo home_url('/wp-json/tms/v1/drivers?page={page}&per_page={per_page}&status={status}&search={search}'); ?>
                                                    <br><small class="text-muted">Get drivers list with pagination (default: 20 per page)</small>
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>POST</strong> <?php echo home_url('/wp-json/tms/v1/driver/update?driver_id={driver_id}'); ?>
                                                    <br><small class="text-muted">Update driver data (current_location, contact, vehicle)</small>
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>GET</strong> <?php echo home_url('/wp-json/tms/v1/driver/loads?driver_id={driver_id}&project={project}&is_flt={is_flt}&page={page}&per_page={per_page}'); ?>
                                                    <br><small class="text-muted">Get driver loads with pagination (default: 20 per page)</small>
                                                </li>
                                                <li class="list-group-item">
                                                    <strong>GET</strong> <?php echo home_url('/wp-json/tms/v1/load/{load_id}?project={project}&is_flt={is_flt}'); ?>
                                                    <br><small class="text-muted">Get specific load details by load ID</small>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-12">
                                            <h5>API Usage Instructions</h5>
                                            <div class="alert alert-info">
                                                <h6>Authentication:</h6>
                                                <p>Include the API key in the request header:</p>
                                                <code>X-API-Key: tms_api_key_2024_driver_access</code>
                                                
                                                <h6 class="mt-3">API Endpoints:</h6>
                                                
                                                <!-- Driver API Section -->
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">Driver API - Get Driver by Email or ID</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Get Driver by Email:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
curl -X GET \
  "<?php echo home_url('/wp-json/tms/v1/driver?email=driver@example.com'); ?>" \
  -H "X-API-Key: tms_api_key_2024_driver_access" \
  -H "Content-Type: application/json"
                                                        </pre>
                                                        
                                                        <p><strong>Get Driver by ID:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
curl -X GET \
  "<?php echo home_url('/wp-json/tms/v1/driver?id=1234'); ?>" \
  -H "X-API-Key: tms_api_key_2024_driver_access" \
  -H "Content-Type: application/json"
                                                        </pre>
                                                        
                                                        <p><strong>Example Response:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
{
  "success": true,
  "data": {
    "id": 123,
    "date_created": "2024-01-01 12:00:00",
    "date_updated": "2024-01-15 14:30:00",
    "user_id_added": 1,
    "status_post": "publish",
    "organized_data": {
      "current_location": {
        "zipcode": "12345",
        "city": "New York",
        "state": "NY",
        "coordinates": {"lat": "40.7128", "lng": "-74.0060"},
        "last_updated": "2024-01-15 14:30:00"
      },
      "contact": {
        "driver_phone": "+1234567890",
        "driver_email": "driver@example.com",
        "languages": {"value": "en,es", "label": "English, Spanish"},
        "preferred_distance": {"value": "otr,regional", "label": "OTR, Regional"}
      },
      "vehicle": {
        "type": {"value": "truck", "label": "Truck"},
        "make": "Freightliner",
        "model": "Cascadia",
        "year": "2020"
      },
      "documents": {
        "hazmat_certificate": {
          "has_certificate": true,
          "file_url": "http://example.com/hazmat.pdf"
        }
      },
      "statistics": {
        "rating": {
          "average_rating": 4.5,
          "total_ratings": 10,
          "all_ratings": [...]
        },
        "notifications": {
          "total_count": 3,
          "all_notifications": [...]
        }
      }
    }
  },
  "timestamp": "2024-01-15 15:00:00",
  "api_version": "1.0"
}
                                                        </pre>
                                                        
                                                        <p><strong>Test Driver API:</strong></p>
                                                        <div class="form-group mb-3">
                                                            <label for="test-email-driver">Test Email:</label>
                                                            <input type="email" class="form-control" id="test-email-driver" placeholder="Enter driver email to test" value="tdev13103@gmail.com">
                                                        </div>
                                                        <div class="form-group mb-3">
                                                            <label for="test-id-driver">Test Driver ID:</label>
                                                            <input type="number" class="form-control" id="test-id-driver" placeholder="Enter driver ID to test" value="3343">
                                                        </div>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-primary btn-sm" onclick="testDriverAPI('email')">Test by Email</button>
                                                            <button type="button" class="btn btn-success btn-sm" onclick="testDriverAPI('id')">Test by ID</button>
                                                        </div>
                                                        <div id="api-test-result-driver" class="mt-3"></div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Driver Update API Section -->
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">Driver Update API - Update Driver Data</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Request:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
curl -X POST \
  "<?php echo home_url('/wp-json/tms/v1/driver/update?driver_id=1234&user_id=1'); ?>" \
  -H "X-API-Key: tms_api_key_2024_driver_access" \
  -H "Content-Type: application/json" \
  -d '{
    "current_location": {
      "zipcode": "30263",
      "city": "Augusta",
      "state": "AL",
      "coordinates": {
        "lat": "33.37484",
        "lng": "-84.80047"
      },
      "last_updated": "2025-09-09 23:59:14"
    },
    "contact": {
      "driver_name": "Amare Tekalin",
      "driver_phone": "(404) 932-3756",
      "driver_email": "amigoakt@gmail.com",
      "home_location": "GA",
      "city": "Lawrenceville",
      "city_state_zip": "Gwinnett County, GA, 30044",
      "date_of_birth": "09/08/2025",
      "languages": "en,es,ua,ru",
      "team_driver": {
        "enabled": true,
        "name": "Serhii",
        "phone": "(240) 264-7739",
        "email": "milchenko2k16@gmail.com",
        "date_of_birth": "08/29/2026"
      },
      "preferred_distance": "otr,regional",
      "emergency_contact": {
        "name": "Tigist Habtemichael5",
        "phone": "(404) 528-8325",
        "relation": "husband"
      }
    },
    "vehicle": {
      "type": {
        "value": "cargo-van",
        "label": "Cargo van"
      },
      "make": "form",
      "model": "f-150",
      "year": "2021",
      "payload": "3000",
      "cargo_space_dimensions": "142 / 55 / 69",
      "overall_dimensions": "111 / 111 / 112",
      "vin": "3C6LRVDG6ME506993",
      "equipment": {
        "side_door": true,
        "load_bars": true,
        "printer": true,
        "sleeper": true,
        "ppe": true,
        "e_tracks": true,
        "pallet_jack": true,
        "lift_gate": true,
        "dolly": true,
        "ramp": true
      }
    }
  }'
                                                        </pre>
                                                        
                                                        <p><strong>Example Response:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
{
  "success": true,
  "message": "Driver updated successfully",
  "changed_fields": [
    {
      "field": "current_city",
      "old_value": "Newnan",
      "new_value": "Augusta"
    },
    {
      "field": "current_location",
      "old_value": "GA",
      "new_value": "AL"
    },
    {
      "field": "driver_name",
      "old_value": "John Doe",
      "new_value": "Amare Tekalin"
    },
    {
      "field": "driver_phone",
      "old_value": "(404) 123-4567",
      "new_value": "(404) 932-3756"
    }
  ],
  "log_created": true,
  "timestamp": "2024-01-15 10:30:00",
  "api_version": "1.0"
}
                                                        </pre>
                                                        
                                                        <p><strong>Test Driver Update API:</strong></p>
                                                        <div class="form-group mb-3">
                                                            <label for="test-driver-id-update">Driver ID: 3343</label>
                                                            <input type="hidden" class="form-control" id="test-driver-id-update" placeholder="Enter driver ID to update" value="3343">
                                                        </div>
                                                        <div class="form-group mb-3">
                                                            <label for="test-user-id-update">User ID:</label>
                                                            <input type="number" class="form-control" id="test-user-id-update" placeholder="Enter user ID for logging" value="1">
                                                        </div>
                                                        <div class="form-group mb-3">
                                                            <label for="test-update-data">Update Data (JSON):</label>
                                                            <textarea class="form-control" id="test-update-data" rows="10" placeholder="Enter JSON data to update">{
    "current_location": {
      "zipcode": null,
      "city": null,
      "state": null,
      "coordinates": {
        "lat": null,
        "lng": null
      },
      "last_updated": "2025-09-10 11:50:24"
    },
    "contact": {
      "driver_name": "Test Driver",
      "driver_phone": "(003) 242-3423",
      "driver_email": "tdev13103@gmail.com",
      "home_location": "NY",
      "city": "New York",
      "city_state_zip": null,
      "date_of_birth": "02/15/1989",
      "languages": "en,es,pt",
      "team_driver": {
        "enabled": true,
        "name": "Serhii",
        "phone": "(240) 264-7739",
        "email": "milchenko2k16@gmail.com",
        "date_of_birth": "09/07/2025"
      },
      "preferred_distance": "any",
      "emergency_contact": {
        "name": "Emergency",
        "phone": "(222) 222-2222",
        "relation": "wife"
      }
    },
    "vehicle": {
      "type": {
        "value": "cargo-van",
        "label": "Cargo van"
      },
      "make": "Ford",
      "model": "f-150",
      "year": "2021",
      "payload": "3480",
      "cargo_space_dimensions": "111 / 1111 / 222",
      "overall_dimensions": "111 / 111 / 13",
      "vin": "7777777777",
      "equipment": {
        "side_door": true,
        "load_bars": true,
        "printer": true,
        "sleeper": true,
        "ppe": true,
        "e_tracks": true,
        "pallet_jack": true,
        "lift_gate": false,
        "dolly": false,
        "ramp": false
      }
    }
  }</textarea>
                                                        </div>
                                                        <button type="button" class="btn btn-warning btn-sm" onclick="testDriverUpdateAPI()">Test Update</button>
                                                        <div id="api-test-result-driver-update" class="mt-3"></div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Driver Loads API Section -->
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">Driver Loads API - Get Driver Loads</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Request:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
curl -X GET \
  "<?php echo home_url('/wp-json/tms/v1/driver/loads?driver_id=3244&project=endurance&is_flt=false&page=1&per_page=20'); ?>" \
  -H "X-API-Key: tms_api_key_2024_driver_access" \
  -H "Content-Type: application/json"
                                                        </pre>
                                                        
                                                        <p><strong>Example Response:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
{
  "success": true,
  "data": {
    "loads": [
      {
        "id": 12345,
        "load_number": "LD-2024-001",
        "pick_up_date": "2024-01-15",
        "delivery_date": "2024-01-18",
        "pick_up_city": "New York",
        "delivery_city": "Los Angeles",
        "driver_rate": 2500.00,
        "second_driver_rate": 1250.00,
        "load_status": "Delivered"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total_items": 45,
      "total_pages": 3
    }
  },
  "timestamp": "2024-01-15 15:00:00",
  "api_version": "1.0"
}
                                                        </pre>
                                                        
                                                        <p><strong>Test Driver Loads API:</strong></p>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-driver-id-loads">Driver ID:</label>
                                                                    <input type="number" class="form-control" id="test-driver-id-loads" placeholder="Enter driver ID" value="3244">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-project-loads">Project:</label>
                                                                    <select class="form-control" id="test-project-loads">
                                                                        <option value="odysseia">Odysseia</option>
                                                                        <option value="martlet">Martlet</option>
                                                                        <option value="endurance" selected>Endurance</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-is-flt-loads">Is FLT:</label>
                                                                    <select class="form-control" id="test-is-flt-loads">
                                                                        <option value="false" selected>False</option>
                                                                        <option value="true">True</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-page-loads">Page:</label>
                                                                    <input type="number" class="form-control" id="test-page-loads" value="1" min="1">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-per-page-loads">Per Page:</label>
                                                                    <input type="number" class="form-control" id="test-per-page-loads" value="20" min="1" max="100">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-primary btn-sm" onclick="testDriverLoads()">Test Driver Loads API</button>
                                                        <div id="api-test-result-loads" class="mt-3"></div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Drivers List API Section -->
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">Drivers List API - Get Drivers with Pagination</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Get Drivers List:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
curl -X GET \
  "<?php echo home_url('/wp-json/tms/v1/drivers?page=1&per_page=20'); ?>" \
  -H "X-API-Key: tms_api_key_2024_driver_access" \
  -H "Content-Type: application/json"
                                                        </pre>
                                                        
                                                        <p><strong>Get Drivers with Filters:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
curl -X GET \
  "<?php echo home_url('/wp-json/tms/v1/drivers?page=1&per_page=20&status=available&search=john'); ?>" \
  -H "X-API-Key: tms_api_key_2024_driver_access" \
  -H "Content-Type: application/json"
                                                        </pre>
                                                        
                                                        <p><strong>Example Response:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">{
  "success": true,
  "data": [
    {
      "id": 3343,
      "date_created": "2025-01-01 10:00:00",
      "date_updated": "2025-01-08 15:30:00",
      "user_id_added": 1,
      "updated_zipcode": "2025-01-08 15:30:00",
      "status_post": "publish",
      "organized_data": {
        "current_location": {
          "status": {
            "value": "available",
            "label": "Available"
          },
          "date_available": "2025-01-08 15:30:00",
          "location": "New York, NY",
          "zipcode": "10001",
          "latitude": "40.7128",
          "longitude": "-74.0060",
          "country": "USA"
        },
        "contact": {
          "driver_name": "John Doe",
          "driver_email": "john@example.com",
          "driver_phone": "+1234567890",
          "emergency_contact": {
            "name": "Jane Doe",
            "phone": "+1234567891",
            "relation": {
              "value": "wife",
              "label": "Wife"
            }
          }
        },
        "vehicle": {
          "type": {
            "value": "dry-van",
            "label": "Dry Van"
          },
          "make": "Freightliner",
          "model": "Cascadia",
          "year": "2020",
          "vin": "1FUJGBDV7CLBP1234",
          "plates": "ABC123",
          "overall_dimensions": "53x8.5x9.5"
        },
        "documents": {
          "twic": {
            "has_certificate": true,
            "file_url": "https://example.com/files/twic.pdf"
          },
          "hazmat_certificate": {
            "has_certificate": true,
            "file_url": "https://example.com/files/hazmat.pdf"
          }
        },
        "statistics": {
          "rating": 4.5,
          "total_loads": 150,
          "recent_ratings": [...],
          "notifications": [...]
        }
      },
      "ratings": [...],
      "notices": [...]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total_count": 150,
    "total_pages": 8,
    "has_next_page": true,
    "has_prev_page": false
  },
  "filters": {
    "status": "available",
    "search": "john"
  },
  "timestamp": "2025-01-08 15:30:00",
  "api_version": "1.0"
}</pre>
                                                        
                                                        <p><strong>Test Drivers List API:</strong></p>
                                                        <div class="row">
                                                            <div class="col-md-3">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-page-drivers">Page:</label>
                                                                    <input type="number" class="form-control" id="test-page-drivers" placeholder="Page number" value="1" min="1">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-per-page-drivers">Per Page:</label>
                                                                    <input type="number" class="form-control" id="test-per-page-drivers" placeholder="Items per page" value="20" min="1" max="100">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-status-drivers">Status:</label>
                                                                    <select class="form-control" id="test-status-drivers">
                                                                        <option value="">All Statuses</option>
                                                                        <option value="available">Available</option>
                                                                        <option value="available_on">Available On</option>
                                                                        <option value="loaded_enroute">Loaded Enroute</option>
                                                                        <option value="no_interview">No Interview</option>
                                                                        <option value="expired_documents">Expired Documents</option>
                                                                        <option value="blocked">Blocked</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-search-drivers">Search:</label>
                                                                    <input type="text" class="form-control" id="test-search-drivers" placeholder="Search by name, phone, email">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-primary btn-sm" onclick="testDriversList()">Test Drivers List API</button>
                                                        <div id="api-test-result-drivers-list" class="mt-3"></div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Load Detail API Section -->
                                                <div class="card mb-4">
                                                    <div class="card-header">
                                                        <h5 class="mb-0">Load Detail API - Get Load Details</h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <p><strong>Request:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
curl -X GET \
  "<?php echo home_url('/wp-json/tms/v1/load/123?project=endurance&is_flt=false'); ?>" \
  -H "X-API-Key: tms_api_key_2024_driver_access" \
  -H "Content-Type: application/json"
                                                        </pre>
                                                        
                                                        <p><strong>Example Response:</strong></p>
                                                        <pre class="bg-light p-3 rounded" style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">
{
  "success": true,
  "data": {
    "id": 12345,
    "load_number": "LD-2024-001",
    "pick_up_date": "2024-01-15",
    "delivery_date": "2024-01-18",
    "pick_up_city": "New York",
    "delivery_city": "Los Angeles",
    "driver_rate": 2500.00,
    "second_driver_rate": 1250.00,
    "load_status": "Delivered",
    "attached_driver": "3244",
    "attached_second_driver": "3245",
    "meta_data": {
      "customer_name": "ABC Company",
      "commodity": "Electronics",
      "weight": "45000"
    }
  },
  "timestamp": "2024-01-15 15:00:00",
  "api_version": "1.0"
}
                                                        </pre>
                                                        
                                                        <p><strong>Test Load Detail API:</strong></p>
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-load-id-detail">Load ID:</label>
                                                                    <input type="number" class="form-control" id="test-load-id-detail" placeholder="Enter load ID" value="123">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-project-detail">Project:</label>
                                                                    <select class="form-control" id="test-project-detail">
                                                                        <option value="odysseia">Odysseia</option>
                                                                        <option value="martlet">Martlet</option>
                                                                        <option value="endurance" selected>Endurance</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <div class="form-group mb-3">
                                                                    <label for="test-is-flt-detail">Is FLT:</label>
                                                                    <select class="form-control" id="test-is-flt-detail">
                                                                        <option value="false" selected>False</option>
                                                                        <option value="true">True</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <button type="button" class="btn btn-primary btn-sm" onclick="testLoadDetail()">Test Load Detail API</button>
                                                        <div id="api-test-result-detail" class="mt-3"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    
                                </div>
                            </div>
                            
                            <script>
                            function testDriverAPI(type = 'email') {
                                const resultDiv = document.getElementById('api-test-result-driver');
                                
                                let url, identifier;
                                
                                if (type === 'email') {
                                    const email = document.getElementById('test-email-driver').value;
                                    if (!email) {
                                        resultDiv.innerHTML = '<div class="alert alert-warning">Please enter an email address</div>';
                                        return;
                                    }
                                    url = '<?php echo home_url('/wp-json/tms/v1/driver?email='); ?>' + encodeURIComponent(email);
                                    identifier = email;
                                } else {
                                    const driverId = document.getElementById('test-id-driver').value;
                                    if (!driverId) {
                                        resultDiv.innerHTML = '<div class="alert alert-warning">Please enter a driver ID</div>';
                                        return;
                                    }
                                    url = '<?php echo home_url('/wp-json/tms/v1/driver?id='); ?>' + driverId;
                                    identifier = driverId;
                                }
                                
                                resultDiv.innerHTML = '<div class="alert alert-info">Testing Driver API by ' + type + ' (' + identifier + ')...</div>';
                                
                                fetch(url, {
                                    method: 'GET',
                                    headers: {
                                        'X-API-Key': 'tms_api_key_2024_driver_access',
                                        'Content-Type': 'application/json'
                                    }
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    resultDiv.innerHTML = '<div class="alert alert-success"><h6>Driver API Response (by ' + type + '):</h6><pre style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">' + JSON.stringify(data, null, 2) + '</pre></div>';
                                })
                                .catch(error => {
                                    resultDiv.innerHTML = '<div class="alert alert-danger"><h6>API Error (by ' + type + '):</h6><pre style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">' + error.message + '</pre></div>';
                                });
                            }
                            
                            function testDriverUpdateAPI() {
                                const resultDiv = document.getElementById('api-test-result-driver-update');
                                const driverId = document.getElementById('test-driver-id-update').value;
                                const userId = document.getElementById('test-user-id-update').value;
                                const updateData = document.getElementById('test-update-data').value;
                                
                                if (!driverId) {
                                    resultDiv.innerHTML = '<div class="alert alert-warning">Please enter a driver ID</div>';
                                    return;
                                }
                                
                                if (!userId) {
                                    resultDiv.innerHTML = '<div class="alert alert-warning">Please enter a user ID</div>';
                                    return;
                                }
                                
                                if (!updateData) {
                                    resultDiv.innerHTML = '<div class="alert alert-warning">Please enter update data</div>';
                                    return;
                                }
                                
                                let jsonData;
                                try {
                                    jsonData = JSON.parse(updateData);
                                } catch (e) {
                                    resultDiv.innerHTML = '<div class="alert alert-warning">Invalid JSON format in update data</div>';
                                    return;
                                }
                                
                                resultDiv.innerHTML = '<div class="alert alert-info">Testing Driver Update API for driver ID: ' + driverId + ', user ID: ' + userId + '...</div>';
                                
                                const url = '<?php echo home_url('/wp-json/tms/v1/driver/update?driver_id='); ?>' + driverId + '&user_id=' + userId;
                                
                                fetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'X-API-Key': 'tms_api_key_2024_driver_access',
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify(jsonData)
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    resultDiv.innerHTML = '<div class="alert alert-success"><h6>Driver Update API Response:</h6><pre style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">' + JSON.stringify(data, null, 2) + '</pre></div>';
                                })
                                .catch(error => {
                                    resultDiv.innerHTML = '<div class="alert alert-danger"><h6>API Error:</h6><pre style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">' + error.message + '</pre></div>';
                                });
                            }
                            
                            function testDriverLoads() {
                                const driverId = document.getElementById('test-driver-id-loads').value;
                                const project = document.getElementById('test-project-loads').value;
                                const isFlt = document.getElementById('test-is-flt-loads').value;
                                const page = document.getElementById('test-page-loads').value;
                                const perPage = document.getElementById('test-per-page-loads').value;
                                const resultDiv = document.getElementById('api-test-result-loads');
                                
                                if (!driverId) {
                                    resultDiv.innerHTML = '<div class="alert alert-warning">Please enter a driver ID</div>';
                                    return;
                                }
                                
                                resultDiv.innerHTML = '<div class="alert alert-info">Testing Driver Loads API...</div>';
                                
                                const url = '<?php echo home_url('/wp-json/tms/v1/driver/loads'); ?>?driver_id=' + encodeURIComponent(driverId) + '&project=' + encodeURIComponent(project) + '&is_flt=' + encodeURIComponent(isFlt) + '&page=' + encodeURIComponent(page) + '&per_page=' + encodeURIComponent(perPage);
                                
                                fetch(url, {
                                    method: 'GET',
                                    headers: {
                                        'X-API-Key': 'tms_api_key_2024_driver_access',
                                        'Content-Type': 'application/json'
                                    }
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    resultDiv.innerHTML = '<div class="alert alert-success"><h6>Driver Loads API Response:</h6><pre style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">' + JSON.stringify(data, null, 2) + '</pre></div>';
                                })
                                .catch(error => {
                                    resultDiv.innerHTML = '<div class="alert alert-danger"><h6>Driver Loads API Error:</h6><pre style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">' + error.message + '</pre></div>';
                                });
                            }
                            
                            function testLoadDetail() {
                                const loadId = document.getElementById('test-load-id-detail').value;
                                const project = document.getElementById('test-project-detail').value;
                                const isFlt = document.getElementById('test-is-flt-detail').value;
                                const resultDiv = document.getElementById('api-test-result-detail');
                                
                                if (!loadId) {
                                    resultDiv.innerHTML = '<div class="alert alert-warning">Please enter a load ID</div>';
                                    return;
                                }
                                
                                resultDiv.innerHTML = '<div class="alert alert-info">Testing Load Detail API...</div>';
                                
                                const url = '<?php echo home_url('/wp-json/tms/v1/load/'); ?>' + encodeURIComponent(loadId) + '?project=' + encodeURIComponent(project) + '&is_flt=' + encodeURIComponent(isFlt);
                                
                                fetch(url, {
                                    method: 'GET',
                                    headers: {
                                        'X-API-Key': 'tms_api_key_2024_driver_access',
                                        'Content-Type': 'application/json'
                                    }
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    resultDiv.innerHTML = '<div class="alert alert-success"><h6>Load Detail API Response:</h6><pre style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">' + JSON.stringify(data, null, 2) + '</pre></div>';
                                })
                                .catch(error => {
                                    resultDiv.innerHTML = '<div class="alert alert-danger"><h6>Load Detail API Error:</h6><pre style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">' + error.message + '</pre></div>';
                                });
                            }
                            
                            function testDriversList() {
                                const page = document.getElementById('test-page-drivers').value;
                                const perPage = document.getElementById('test-per-page-drivers').value;
                                const status = document.getElementById('test-status-drivers').value;
                                const search = document.getElementById('test-search-drivers').value;
                                const resultDiv = document.getElementById('api-test-result-drivers-list');
                                
                                // Build URL with parameters
                                let url = '<?php echo home_url('/wp-json/tms/v1/drivers'); ?>?';
                                const params = new URLSearchParams();
                                
                                if (page) params.append('page', page);
                                if (perPage) params.append('per_page', perPage);
                                if (status) params.append('status', status);
                                if (search) params.append('search', search);
                                
                                url += params.toString();
                                
                                resultDiv.innerHTML = '<div class="alert alert-info">Testing Drivers List API...</div>';
                                
                                fetch(url, {
                                    method: 'GET',
                                    headers: {
                                        'X-API-Key': 'tms_api_key_2024_driver_access',
                                        'Content-Type': 'application/json'
                                    }
                                })
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error(`HTTP error! status: ${response.status}`);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    resultDiv.innerHTML = '<div class="alert alert-success"><h6>Drivers List API Response:</h6><pre style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">' + JSON.stringify(data, null, 2) + '</pre></div>';
                                })
                                .catch(error => {
                                    resultDiv.innerHTML = '<div class="alert alert-danger"><h6>Drivers List API Error:</h6><pre style="word-wrap: break-word; overflow-x: auto; white-space: pre-wrap;">' + error.message + '</pre></div>';
                                });
                            }
                            </script>
                            
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
do_action( 'wp_rock_before_page_content' );

// This is a custom template page, no need for standard WordPress loop
do_action( 'wp_rock_after_page_content' );

// Initialize API class at the end to avoid blocking page load
if ( isset($api_initialized) && !$api_initialized ) {
    $api = new TMSDriversAPI();
    $api_initialized = true;
}

get_footer();
