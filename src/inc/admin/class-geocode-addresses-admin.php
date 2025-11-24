<?php
/**
 * Geocode Addresses Admin Page
 * Provides interface for bulk geocoding shipper and company addresses
 */

if (!defined('ABSPATH')) {
    exit;
}

class GeocodeAddressesAdmin {
    
    private $geocode_class;
    
    public function __construct() {
        $this->geocode_class = new TMSGeocodeAddresses();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_geocode_add_columns', array($this, 'ajax_add_columns'));
        add_action('wp_ajax_geocode_shippers', array($this, 'ajax_geocode_shippers'));
        add_action('wp_ajax_geocode_companies', array($this, 'ajax_geocode_companies'));
        add_action('wp_ajax_geocode_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_geocode_get_failed', array($this, 'ajax_get_failed'));
        add_action('wp_ajax_geocode_clear_failed', array($this, 'ajax_clear_failed'));
        add_action('wp_ajax_geocode_remove_failed', array($this, 'ajax_remove_failed'));
    }
    
    public function add_admin_menu() {
        add_management_page(
            'Geocode Addresses',
            'Geocode Addresses',
            'manage_options',
            'geocode-addresses',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        // Add columns if they don't exist
        $this->geocode_class->add_lat_lon_columns();
        
        $stats = $this->geocode_class->get_geocoding_stats();
        ?>
        <div class="wrap">
            <h1>Geocode Addresses</h1>
            <p>This tool will geocode addresses for shippers and companies using HERE Maps API.</p>
            <div class="notice notice-info" style="margin-top: 15px;">
                <p><strong>Automatic Processing:</strong> A cron job runs once per day (at 2:00 AM) to automatically process new addresses and retry failed ones. Failed addresses are automatically skipped and added to the failed list below. You can also manually process addresses using the buttons below.</p>
                <?php
                $next_run = wp_next_scheduled('tms_geocode_addresses_cron');
                if ($next_run) {
                    $next_run_formatted = date('Y-m-d H:i:s', $next_run);
                    $time_until = human_time_diff(time(), $next_run);
                    echo '<p><strong>Next cron run:</strong> ' . esc_html($next_run_formatted) . ' (in ' . $time_until . ')</p>';
                } else {
                    echo '<p style="color: orange;"><strong>Warning:</strong> Cron job is not scheduled. It will be scheduled automatically on the next page load.</p>';
                }
                ?>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Statistics</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Records Needing Geocoding</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Shippers</strong></td>
                            <td id="shipper-count"><?php echo esc_html($stats['shippers']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Companies</strong></td>
                            <td id="company-count"><?php echo esc_html($stats['companies']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Geocode Shippers</h2>
                <p>Geocode addresses for shippers that don't have coordinates.</p>
                <p>
                    <label>
                        <input type="number" id="shipper-limit" value="100" min="1" max="200" style="width: 80px;">
                        records per batch
                    </label>
                </p>
                <p>
                    <button type="button" class="button button-primary" id="geocode-shippers-btn">
                        Start Geocoding Shippers
                    </button>
                    <span id="shipper-status" style="margin-left: 10px;"></span>
                </p>
                <div id="shipper-progress" style="margin-top: 10px;"></div>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Geocode Companies</h2>
                <p>Geocode addresses for companies that don't have coordinates.</p>
                <p>
                    <label>
                        <input type="number" id="company-limit" value="100" min="1" max="200" style="width: 80px;">
                        records per batch
                    </label>
                </p>
                <p>
                    <button type="button" class="button button-primary" id="geocode-companies-btn">
                        Start Geocoding Companies
                    </button>
                    <span id="company-status" style="margin-left: 10px;"></span>
                </p>
                <div id="company-progress" style="margin-top: 10px;"></div>
            </div>
            
            <div class="card" style="max-width: 1200px; margin-top: 20px;">
                <h2>Failed Addresses</h2>
                <p>Addresses that could not be geocoded. These addresses are automatically skipped during geocoding.</p>
                <p>
                    <button type="button" class="button" id="refresh-failed-btn">Refresh List</button>
                    <button type="button" class="button button-secondary" id="clear-shippers-failed-btn" style="margin-left: 10px;">
                        Clear Shippers Failed List
                    </button>
                    <button type="button" class="button button-secondary" id="clear-companies-failed-btn" style="margin-left: 10px;">
                        Clear Companies Failed List
                    </button>
                </p>
                <div id="failed-addresses" style="margin-top: 20px;">
                    <p>Loading...</p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var geocodingInProgress = false;
            
            function updateStats() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'geocode_get_stats'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#shipper-count').text(response.data.shippers);
                            $('#company-count').text(response.data.companies);
                        }
                    }
                });
            }
            
            function geocodeShippers() {
                if (geocodingInProgress) return;
                
                geocodingInProgress = true;
                var limit = parseInt($('#shipper-limit').val()) || 100;
                var btn = $('#geocode-shippers-btn');
                var status = $('#shipper-status');
                var progress = $('#shipper-progress');
                
                btn.prop('disabled', true);
                status.html('<span style="color: orange;">Processing...</span>');
                progress.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'geocode_shippers',
                        limit: limit
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var html = '<strong>Batch Complete:</strong><br>';
                            html += 'Total: ' + data.total + '<br>';
                            html += 'Success: <span style="color: green;">' + data.success + '</span><br>';
                            html += 'Failed: <span style="color: red;">' + data.failed + '</span><br>';
                            
                            if (data.errors && data.errors.length > 0) {
                                html += '<details style="margin-top: 10px;"><summary>Errors (' + data.errors.length + ')</summary>';
                                html += '<ul style="max-height: 200px; overflow-y: auto;">';
                                data.errors.forEach(function(error) {
                                    html += '<li>' + error + '</li>';
                                });
                                html += '</ul></details>';
                            }
                            
                            progress.html(html);
                            status.html('<span style="color: green;">Complete</span>');
                            
                            updateStats();
                            
                            if (data.total > 0 && data.success > 0) {
                                // Auto-continue if there are more records
                                setTimeout(function() {
                                    if (confirm('Process next batch?')) {
                                        geocodeShippers();
                                    } else {
                                        btn.prop('disabled', false);
                                        geocodingInProgress = false;
                                    }
                                }, 1000);
                            } else {
                                btn.prop('disabled', false);
                                geocodingInProgress = false;
                            }
                        } else {
                            status.html('<span style="color: red;">Error: ' + response.data + '</span>');
                            btn.prop('disabled', false);
                            geocodingInProgress = false;
                        }
                    },
                    error: function() {
                        status.html('<span style="color: red;">AJAX Error</span>');
                        btn.prop('disabled', false);
                        geocodingInProgress = false;
                    }
                });
            }
            
            function geocodeCompanies() {
                if (geocodingInProgress) return;
                
                geocodingInProgress = true;
                var limit = parseInt($('#company-limit').val()) || 100;
                var btn = $('#geocode-companies-btn');
                var status = $('#company-status');
                var progress = $('#company-progress');
                
                btn.prop('disabled', true);
                status.html('<span style="color: orange;">Processing...</span>');
                progress.html('');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'geocode_companies',
                        limit: limit
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var html = '<strong>Batch Complete:</strong><br>';
                            html += 'Total: ' + data.total + '<br>';
                            html += 'Success: <span style="color: green;">' + data.success + '</span><br>';
                            html += 'Failed: <span style="color: red;">' + data.failed + '</span><br>';
                            
                            if (data.errors && data.errors.length > 0) {
                                html += '<details style="margin-top: 10px;"><summary>Errors (' + data.errors.length + ')</summary>';
                                html += '<ul style="max-height: 200px; overflow-y: auto;">';
                                data.errors.forEach(function(error) {
                                    html += '<li>' + error + '</li>';
                                });
                                html += '</ul></details>';
                            }
                            
                            progress.html(html);
                            status.html('<span style="color: green;">Complete</span>');
                            
                            updateStats();
                            
                            if (data.total > 0 && data.success > 0) {
                                // Auto-continue if there are more records
                                setTimeout(function() {
                                    if (confirm('Process next batch?')) {
                                        geocodeCompanies();
                                    } else {
                                        btn.prop('disabled', false);
                                        geocodingInProgress = false;
                                    }
                                }, 1000);
                            } else {
                                btn.prop('disabled', false);
                                geocodingInProgress = false;
                            }
                        } else {
                            status.html('<span style="color: red;">Error: ' + response.data + '</span>');
                            btn.prop('disabled', false);
                            geocodingInProgress = false;
                        }
                    },
                    error: function() {
                        status.html('<span style="color: red;">AJAX Error</span>');
                        btn.prop('disabled', false);
                        geocodingInProgress = false;
                    }
                });
            }
            
            $('#geocode-shippers-btn').on('click', geocodeShippers);
            $('#geocode-companies-btn').on('click', geocodeCompanies);
            
            function loadFailedAddresses() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'geocode_get_failed'
                    },
                    success: function(response) {
                        if (response.success) {
                            var data = response.data;
                            var html = '';
                            
                            // Shippers failed
                            html += '<h3>Shippers (' + (data.shippers ? Object.keys(data.shippers).length : 0) + ')</h3>';
                            if (data.shippers && Object.keys(data.shippers).length > 0) {
                                html += '<table class="widefat" style="margin-top: 10px;">';
                                html += '<thead><tr><th>ID</th><th>Address</th><th>Error</th><th>Date Added</th><th>Action</th></tr></thead>';
                                html += '<tbody>';
                                $.each(data.shippers, function(id, item) {
                                    html += '<tr>';
                                    html += '<td><strong>' + id + '</strong></td>';
                                    html += '<td>' + (item.address || 'N/A') + '</td>';
                                    html += '<td><span style="color: red;">' + (item.error || 'N/A') + '</span></td>';
                                    html += '<td>' + (item.date || 'N/A') + '</td>';
                                    html += '<td><button type="button" class="button button-small remove-failed-btn" data-type="shippers" data-id="' + id + '">Remove</button></td>';
                                    html += '</tr>';
                                });
                                html += '</tbody></table>';
                            } else {
                                html += '<p style="color: green;">No failed shipper addresses.</p>';
                            }
                            
                            html += '<h3 style="margin-top: 30px;">Companies (' + (data.companies ? Object.keys(data.companies).length : 0) + ')</h3>';
                            if (data.companies && Object.keys(data.companies).length > 0) {
                                html += '<table class="widefat" style="margin-top: 10px;">';
                                html += '<thead><tr><th>ID</th><th>Address</th><th>Error</th><th>Date Added</th><th>Action</th></tr></thead>';
                                html += '<tbody>';
                                $.each(data.companies, function(id, item) {
                                    html += '<tr>';
                                    html += '<td><strong>' + id + '</strong></td>';
                                    html += '<td>' + (item.address || 'N/A') + '</td>';
                                    html += '<td><span style="color: red;">' + (item.error || 'N/A') + '</span></td>';
                                    html += '<td>' + (item.date || 'N/A') + '</td>';
                                    html += '<td><button type="button" class="button button-small remove-failed-btn" data-type="companies" data-id="' + id + '">Remove</button></td>';
                                    html += '</tr>';
                                });
                                html += '</tbody></table>';
                            } else {
                                html += '<p style="color: green;">No failed company addresses.</p>';
                            }
                            
                            $('#failed-addresses').html(html);
                        } else {
                            $('#failed-addresses').html('<p style="color: red;">Error loading failed addresses.</p>');
                        }
                    },
                    error: function() {
                        $('#failed-addresses').html('<p style="color: red;">AJAX Error loading failed addresses.</p>');
                    }
                });
            }
            
            function clearFailedAddresses(type) {
                if (!confirm('Are you sure you want to clear all failed ' + type + ' addresses? This will allow them to be geocoded again.')) {
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'geocode_clear_failed',
                        type: type
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Failed ' + type + ' addresses cleared successfully.');
                            loadFailedAddresses();
                            updateStats();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('AJAX Error clearing failed addresses.');
                    }
                });
            }
            
            function removeFailedAddress(type, id) {
                if (!confirm('Remove this address from the failed list? It will be geocoded again on the next batch.')) {
                    return;
                }
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'geocode_remove_failed',
                        type: type,
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            loadFailedAddresses();
                            updateStats();
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('AJAX Error removing failed address.');
                    }
                });
            }
            
            // Use event delegation for dynamically added buttons
            $(document).on('click', '.remove-failed-btn', function() {
                var type = $(this).data('type');
                var id = $(this).data('id');
                removeFailedAddress(type, id);
            });
            
            $('#refresh-failed-btn').on('click', loadFailedAddresses);
            $('#clear-shippers-failed-btn').on('click', function() {
                clearFailedAddresses('shippers');
            });
            $('#clear-companies-failed-btn').on('click', function() {
                clearFailedAddresses('companies');
            });
            
            // Load failed addresses on page load
            loadFailedAddresses();
            
            // Auto-refresh stats every 30 seconds
            setInterval(updateStats, 30000);
        });
        </script>
        <?php
    }
    
    public function ajax_add_columns() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $this->geocode_class->add_lat_lon_columns();
        wp_send_json_success('Columns added successfully');
    }
    
    public function ajax_geocode_shippers() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $stats = $this->geocode_class->geocode_shippers($limit);
        wp_send_json_success($stats);
    }
    
    public function ajax_geocode_companies() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 50;
        $stats = $this->geocode_class->geocode_companies($limit);
        wp_send_json_success($stats);
    }
    
    public function ajax_get_stats() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $stats = $this->geocode_class->get_geocoding_stats();
        wp_send_json_success($stats);
    }
    
    public function ajax_get_failed() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $shippers = $this->geocode_class->get_failed_addresses('shippers');
        $companies = $this->geocode_class->get_failed_addresses('companies');
        
        wp_send_json_success(array(
            'shippers' => $shippers,
            'companies' => $companies
        ));
    }
    
    public function ajax_clear_failed() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        
        if (!in_array($type, array('shippers', 'companies'))) {
            wp_send_json_error('Invalid type');
        }
        
        $this->geocode_class->clear_failed_addresses($type);
        wp_send_json_success('Failed addresses cleared');
    }
    
    public function ajax_remove_failed() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!in_array($type, array('shippers', 'companies'))) {
            wp_send_json_error('Invalid type');
        }
        
        if ($id <= 0) {
            wp_send_json_error('Invalid ID');
        }
        
        $this->geocode_class->remove_failed_address($type, $id);
        wp_send_json_success('Failed address removed');
    }
    
}

// Initialize admin page
if (is_admin()) {
    new GeocodeAddressesAdmin();
}

