<?php
/**
 * Fix Driver Data Admin Page
 * Finds and fixes records where unit_number_name is filled but attached_driver is missing
 */

if (!defined('ABSPATH')) {
    exit;
}

class FixDriverDataAdmin {
    
    private $tables = [
        'wp_reportsmeta_flt_endurance',
        'wp_reportsmeta_flt_martlet', 
        'wp_reportsmeta_flt_odysseia',
        'wp_reportsmeta_endurance',
        'wp_reportsmeta_martlet',
        'wp_reportsmeta_odysseia'
    ];
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_fix_driver_data_search', array($this, 'ajax_search_records'));
        add_action('wp_ajax_fix_driver_data_fix', array($this, 'ajax_fix_records'));
        add_action('wp_ajax_fix_driver_data_search_second', array($this, 'ajax_search_second_records'));
        add_action('wp_ajax_fix_driver_data_fix_second', array($this, 'ajax_fix_second_records'));
    }
    
    public function add_admin_menu() {
        add_management_page(
            'Fix Driver Data',
            'Fix Driver Data',
            'manage_options',
            'fix-driver-data',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Fix Driver Data</h1>
            <p>This tool finds records where driver data is missing and fixes it automatically.</p>
            
            <!-- Tabs -->
            <h2 class="nav-tab-wrapper">
                <a href="#first-driver-tab" class="nav-tab nav-tab-active" data-tab="first-driver">First Driver</a>
                <a href="#second-driver-tab" class="nav-tab" data-tab="second-driver">Second Driver</a>
            </h2>
            
            <!-- First Driver Tab -->
            <div id="first-driver-tab" class="tab-content active">
                <p>Finds records where <code>unit_number_name</code> is filled but <code>attached_driver</code> is missing.</p>
                <div id="fix-driver-data-results"></div>
            </div>
            
            <!-- Second Driver Tab -->
            <div id="second-driver-tab" class="tab-content">
                <p>Finds records where <code>second_unit_number_name</code> is filled but <code>attached_second_driver</code> is missing.</p>
                <div id="fix-second-driver-data-results"></div>
            </div>
            
            <div id="pagination-controls" style="display: none;">
                <button type="button" id="prev-page" class="button">Previous</button>
                <span id="page-info">Page 1 of 1</span>
                <button type="button" id="next-page" class="button">Next</button>
                <span style="margin-left: 20px;">Records per page: 
                    <select id="records-per-page">
                        <option value="50">50</option>
                        <option value="100" selected>100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select>
                </span>
            </div>
            
            <button type="button" id="search-records" class="button button-primary">Search First Driver Records</button>
            <button type="button" id="search-second-records" class="button button-primary" style="display: none;">Search Second Driver Records</button>
            <button type="button" id="fix-records" class="button button-secondary" style="display: none;">Fix Selected Records</button>
            <button type="button" id="fix-second-records" class="button button-secondary" style="display: none;">Fix Selected Second Driver Records</button>
            
            <div id="loading" style="display: none;">
                <p>Loading...</p>
            </div>
        </div>
        
        <style>
            .tab-content {
                display: none;
            }
            .tab-content.active {
                display: block;
            }
            .record-item {
                border: 1px solid #ddd;
                margin: 10px 0;
                padding: 15px;
                background: #f9f9f9;
            }
            .record-item.selected {
                background: #e7f3ff;
                border-color: #0073aa;
            }
            .record-info {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .record-details {
                flex: 1;
            }
            .record-actions {
                margin-left: 20px;
            }
            .checkbox-wrapper {
                margin-right: 10px;
            }
            .summary {
                background: #fff;
                border: 1px solid #ddd;
                padding: 15px;
                margin: 20px 0;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            let foundRecords = [];
            let foundSecondRecords = [];
            let currentPage = 1;
            let totalPages = 1;
            let recordsPerPage = 100;
            let totalRecords = 0;
            let currentTab = 'first-driver';
            
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                const tab = $(this).data('tab');
                switchTab(tab);
            });
            
            function switchTab(tab) {
                currentTab = tab;
                
                // Update tab appearance
                $('.nav-tab').removeClass('nav-tab-active');
                $('.nav-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');
                
                // Show/hide tab content
                $('.tab-content').removeClass('active');
                $('#' + tab + '-tab').addClass('active');
                
                // Show/hide buttons
                if (tab === 'first-driver') {
                    $('#search-records').show();
                    $('#search-second-records').hide();
                    $('#fix-records').show();
                    $('#fix-second-records').hide();
                } else {
                    $('#search-records').hide();
                    $('#search-second-records').show();
                    $('#fix-records').hide();
                    $('#fix-second-records').show();
                }
                
                // Clear results when switching tabs
                $('#fix-driver-data-results').empty();
                $('#fix-second-driver-data-results').empty();
                $('#pagination-controls').hide();
            }
            
            function loadPage(page) {
                $('#loading').show();
                $('#fix-driver-data-results').empty();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fix_driver_data_search',
                        page: page,
                        per_page: recordsPerPage,
                        nonce: '<?php echo wp_create_nonce('fix_driver_data_nonce'); ?>'
                    },
                    success: function(response) {
                        $('#loading').hide();
                        if (response.success) {
                            foundRecords = response.data.records;
                            totalRecords = response.data.total;
                            totalPages = Math.ceil(totalRecords / recordsPerPage);
                            currentPage = page;
                            displayResults(response.data);
                            updatePaginationControls();
                        } else {
                            $('#fix-driver-data-results').html('<div class="error"><p>Error: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#loading').hide();
                        $('#fix-driver-data-results').html('<div class="error"><p>AJAX Error</p></div>');
                    }
                });
            }
            
            function loadSecondPage(page) {
                $('#loading').show();
                $('#fix-second-driver-data-results').empty();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'fix_driver_data_search_second',
                        page: page,
                        per_page: recordsPerPage,
                        nonce: '<?php echo wp_create_nonce('fix_driver_data_nonce'); ?>'
                    },
                    success: function(response) {
                        $('#loading').hide();
                        if (response.success) {
                            foundSecondRecords = response.data.records;
                            totalRecords = response.data.total;
                            totalPages = Math.ceil(totalRecords / recordsPerPage);
                            currentPage = page;
                            displaySecondResults(response.data);
                            updatePaginationControls();
                        } else {
                            $('#fix-second-driver-data-results').html('<div class="error"><p>Error: ' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $('#loading').hide();
                        $('#fix-second-driver-data-results').html('<div class="error"><p>AJAX Error</p></div>');
                    }
                });
            }
            
            $('#search-records').on('click', function() {
                currentPage = 1;
                loadPage(1);
            });
            
            $('#search-second-records').on('click', function() {
                currentPage = 1;
                loadSecondPage(1);
            });
            
            $('#prev-page').on('click', function() {
                if (currentPage > 1) {
                    loadPage(currentPage - 1);
                }
            });
            
            $('#next-page').on('click', function() {
                if (currentPage < totalPages) {
                    loadPage(currentPage + 1);
                }
            });
            
            $('#records-per-page').on('change', function() {
                recordsPerPage = parseInt($(this).val());
                currentPage = 1;
                loadPage(1);
            });
            
            function updatePaginationControls() {
                $('#page-info').text('Page ' + currentPage + ' of ' + totalPages + ' (Total: ' + totalRecords + ' records)');
                $('#prev-page').prop('disabled', currentPage <= 1);
                $('#next-page').prop('disabled', currentPage >= totalPages);
                $('#pagination-controls').show();
            }
            
            $('#fix-records').on('click', function() {
                console.log('Fix button clicked');
                console.log('Found records:', foundRecords);
                
                // Debug: check all checkboxes
                $('.record-checkbox').each(function() {
                    console.log('Checkbox:', $(this).val(), 'checked:', $(this).is(':checked'));
                });
                
                const selectedRecords = foundRecords.filter(record => {
                    // Find checkbox by value instead of ID
                    const checkbox = $('.record-checkbox[value="' + record.table + '|' + record.post_id + '"]');
                    const isChecked = checkbox.is(':checked');
                    console.log('Record:', record.table, record.post_id, 'checkbox found:', checkbox.length > 0, 'checked:', isChecked);
                    return isChecked;
                });
                
                console.log('Selected records:', selectedRecords);
                
                if (selectedRecords.length === 0) {
                    alert('Please select at least one record to fix.');
                    return;
                }
                
                // Show detailed confirmation
                let confirmMessage = 'Are you sure you want to fix ' + selectedRecords.length + ' selected records?\n\n';
                confirmMessage += 'This will extract driver IDs from unit_number_name and update attached_driver field.\n\n';
                confirmMessage += 'Example: "(2000) Robert Smith" → attached_driver = "2000"\n\n';
                confirmMessage += 'Selected records:\n';
                selectedRecords.slice(0, 5).forEach(record => {
                    confirmMessage += '- ' + record.table + ' (ID: ' + record.post_id + ') → ' + record.unit_number_name + '\n';
                });
                if (selectedRecords.length > 5) {
                    confirmMessage += '- ... and ' + (selectedRecords.length - 5) + ' more\n';
                }
                
                if (!confirm(confirmMessage)) {
                    return;
                }
                
                $('#loading').show();
                $('#loading').html('<p>Fixing ' + selectedRecords.length + ' records...</p><div id="fix-progress"></div>');
                
                // Process records in batches to avoid timeout
                const batchSize = 10;
                let processed = 0;
                let fixed = 0;
                let errors = [];
                
                function processBatch() {
                    const batch = selectedRecords.slice(processed, processed + batchSize);
                    if (batch.length === 0) {
                        // All done
                        $('#loading').hide();
                        let resultMessage = 'Completed!\n\n';
                        resultMessage += 'Fixed: ' + fixed + ' records\n';
                        if (errors.length > 0) {
                            resultMessage += 'Errors: ' + errors.length + '\n';
                            resultMessage += 'First few errors:\n' + errors.slice(0, 3).join('\n');
                        }
                        alert(resultMessage);
                        $('#search-records').click(); // Refresh results
                        return;
                    }
                    
                    // Update progress
                    $('#fix-progress').html('Processing ' + (processed + 1) + '-' + Math.min(processed + batchSize, selectedRecords.length) + ' of ' + selectedRecords.length + ' records...');
                    
                        // Convert records to string format for PHP
                        const recordStrings = batch.map(record => record.table + '|' + record.post_id);
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'fix_driver_data_fix',
                                records: recordStrings,
                                nonce: '<?php echo wp_create_nonce('fix_driver_data_nonce'); ?>'
                            },
                        success: function(response) {
                            if (response.success) {
                                fixed += response.data.fixed;
                                if (response.data.errors && response.data.errors.length > 0) {
                                    errors = errors.concat(response.data.errors);
                                }
                            } else {
                                errors.push('Batch error: ' + response.data);
                            }
                            
                            processed += batch.length;
                            setTimeout(processBatch, 100); // Small delay between batches
                        },
                        error: function() {
                            errors.push('AJAX error for batch ' + (processed + 1) + '-' + (processed + batch.length));
                            processed += batch.length;
                            setTimeout(processBatch, 100);
                        }
                    });
                }
                
                processBatch();
            });
            
            $('#fix-second-records').on('click', function() {
                console.log('Fix second driver button clicked');
                console.log('Found second records:', foundSecondRecords);
                
                const selectedRecords = foundSecondRecords.filter(record => {
                    const checkbox = $('.second-record-checkbox[value="' + record.table + '|' + record.post_id + '"]');
                    const isChecked = checkbox.is(':checked');
                    console.log('Second Record:', record.table, record.post_id, 'checkbox found:', checkbox.length > 0, 'checked:', isChecked);
                    return isChecked;
                });
                
                console.log('Selected second records:', selectedRecords);
                
                if (selectedRecords.length === 0) {
                    alert('Please select at least one record to fix.');
                    return;
                }
                
                // Show detailed confirmation
                let confirmMessage = 'Are you sure you want to fix ' + selectedRecords.length + ' selected second driver records?\n\n';
                confirmMessage += 'This will extract driver IDs from second_unit_number_name and update attached_second_driver field.\n\n';
                confirmMessage += 'Example: "(2000) Robert Smith" → attached_second_driver = "2000"\n\n';
                confirmMessage += 'Selected records:\n';
                selectedRecords.slice(0, 5).forEach(record => {
                    confirmMessage += '- ' + record.table + ' (ID: ' + record.post_id + ') → ' + record.second_unit_number_name + '\n';
                });
                if (selectedRecords.length > 5) {
                    confirmMessage += '- ... and ' + (selectedRecords.length - 5) + ' more\n';
                }
                
                if (!confirm(confirmMessage)) {
                    return;
                }
                
                $('#loading').show();
                $('#loading').html('<p>Fixing ' + selectedRecords.length + ' second driver records...</p><div id="fix-progress"></div>');
                
                // Process records in batches to avoid timeout
                const batchSize = 10;
                let processed = 0;
                let fixed = 0;
                let errors = [];
                
                function processSecondBatch() {
                    const batch = selectedRecords.slice(processed, processed + batchSize);
                    if (batch.length === 0) {
                        // All done
                        $('#loading').hide();
                        let resultMessage = 'Completed!\n\n';
                        resultMessage += 'Fixed: ' + fixed + ' second driver records\n';
                        if (errors.length > 0) {
                            resultMessage += 'Errors: ' + errors.length + '\n';
                            resultMessage += 'First few errors:\n' + errors.slice(0, 3).join('\n');
                        }
                        alert(resultMessage);
                        $('#search-second-records').click(); // Refresh results
                        return;
                    }
                    
                    // Update progress
                    $('#fix-progress').html('Processing ' + (processed + 1) + '-' + Math.min(processed + batchSize, selectedRecords.length) + ' of ' + selectedRecords.length + ' records...');
                    
                    // Convert records to string format for PHP
                    const recordStrings = batch.map(record => record.table + '|' + record.post_id);
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'fix_driver_data_fix_second',
                            records: recordStrings,
                            nonce: '<?php echo wp_create_nonce('fix_driver_data_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                fixed += response.data.fixed;
                                if (response.data.errors && response.data.errors.length > 0) {
                                    errors = errors.concat(response.data.errors);
                                }
                            } else {
                                errors.push('Batch error: ' + response.data);
                            }
                            
                            processed += batch.length;
                            setTimeout(processSecondBatch, 100); // Small delay between batches
                        },
                        error: function() {
                            errors.push('AJAX error for batch ' + (processed + 1) + '-' + (processed + batch.length));
                            processed += batch.length;
                            setTimeout(processSecondBatch, 100);
                        }
                    });
                }
                
                processSecondBatch();
            });
            
            function displayResults(data) {
                let html = '<div class="summary">';
                html += '<h3>Found ' + data.total + ' records with missing attached_driver</h3>';
                html += '<p>Tables: ' + data.tables.join(', ') + '</p>';
                html += '<p>Showing page ' + currentPage + ' of ' + totalPages + '</p>';
                html += '</div>';
                
                if (data.records.length > 0) {
                    html += '<div style="margin: 20px 0;">';
                    html += '<label><input type="checkbox" id="select-all"> Select All on This Page</label>';
                    html += '</div>';
                    
                    data.records.forEach(function(record) {
                        html += '<div class="record-item" id="record-' + record.table + '-' + record.post_id + '">';
                        html += '<div class="record-info">';
                        html += '<div class="checkbox-wrapper">';
                        html += '<input type="checkbox" class="record-checkbox" value="' + record.table + '|' + record.post_id + '">';
                        html += '</div>';
                        html += '<div class="record-details">';
                        html += '<strong>Table:</strong> ' + record.table + '<br>';
                        html += '<strong>Post ID:</strong> ' + record.post_id + '<br>';
                        html += '<strong>Unit Number Name:</strong> ' + record.unit_number_name + '<br>';
                        html += '<strong>Attached Driver:</strong> ' + (record.attached_driver || '<em>Missing</em>') + '<br>';
                        html += '<strong>TBD:</strong> ' + (record.tbd ? 'Yes' : 'No');
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    });
                    
                    $('#fix-records').show();
                } else {
                    html += '<p>No records found that need fixing.</p>';
                }
                
                $('#fix-driver-data-results').html(html);
                
                // Handle select all
                $('#select-all').on('change', function() {
                    $('.record-checkbox').prop('checked', this.checked);
                    $('.record-item').toggleClass('selected', this.checked);
                });
                
                // Handle individual checkboxes
                $('.record-checkbox').on('change', function() {
                    $(this).closest('.record-item').toggleClass('selected', this.checked);
                });
            }
            
            function displaySecondResults(data) {
                let html = '<div class="summary">';
                html += '<h3>Found ' + data.total + ' records with missing attached_second_driver</h3>';
                html += '<p>Tables: ' + data.tables.join(', ') + '</p>';
                html += '<p>Showing page ' + currentPage + ' of ' + totalPages + '</p>';
                html += '</div>';
                
                if (data.records.length > 0) {
                    html += '<div style="margin: 20px 0;">';
                    html += '<label><input type="checkbox" id="select-all-second"> Select All on This Page</label>';
                    html += '</div>';
                    
                    data.records.forEach(function(record) {
                        html += '<div class="record-item" id="second-record-' + record.table + '-' + record.post_id + '">';
                        html += '<div class="record-info">';
                        html += '<div class="checkbox-wrapper">';
                        html += '<input type="checkbox" class="second-record-checkbox" value="' + record.table + '|' + record.post_id + '">';
                        html += '</div>';
                        html += '<div class="record-details">';
                        html += '<strong>Table:</strong> ' + record.table + '<br>';
                        html += '<strong>Post ID:</strong> ' + record.post_id + '<br>';
                        html += '<strong>Second Unit Number Name:</strong> ' + record.second_unit_number_name + '<br>';
                        html += '<strong>Attached Second Driver:</strong> ' + (record.attached_second_driver || '<em>Missing</em>');
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    });
                    
                    $('#fix-second-records').show();
                } else {
                    html += '<p>No records found that need fixing.</p>';
                }
                
                $('#fix-second-driver-data-results').html(html);
                
                // Handle select all for second driver
                $('#select-all-second').on('change', function() {
                    $('.second-record-checkbox').prop('checked', this.checked);
                    $('.record-item').toggleClass('selected', this.checked);
                });
                
                // Handle individual checkboxes for second driver
                $('.second-record-checkbox').on('change', function() {
                    $(this).closest('.record-item').toggleClass('selected', this.checked);
                });
            }
        });
        </script>
        <?php
    }
    
    public function ajax_search_records() {
        check_ajax_referer('fix_driver_data_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 100);
        $offset = ($page - 1) * $per_page;
        
        global $wpdb;
        $records = [];
        $tables_found = [];
        $total_count = 0;
        
        // First, get total count
        foreach ($this->tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$table_exists) {
                continue;
            }
            
            $tables_found[] = $table;
            
            $count_query = "
                SELECT COUNT(*) as count
                FROM (
                    SELECT post_id
                    FROM $table 
                    WHERE meta_key IN ('unit_number_name', 'attached_driver', 'tbd')
                    GROUP BY post_id
                    HAVING 
                        MAX(CASE WHEN meta_key = 'unit_number_name' THEN meta_value END) IS NOT NULL 
                        AND MAX(CASE WHEN meta_key = 'unit_number_name' THEN meta_value END) != '' 
                        AND MAX(CASE WHEN meta_key = 'unit_number_name' THEN meta_value END) != 'TBD'
                        AND (MAX(CASE WHEN meta_key = 'attached_driver' THEN meta_value END) IS NULL 
                             OR MAX(CASE WHEN meta_key = 'attached_driver' THEN meta_value END) = '')
                ) as subquery
            ";
            
            $count_result = $wpdb->get_var($count_query);
            $total_count += intval($count_result);
        }
        
        // Then get records for current page
        $records_collected = 0;
        $records_needed = $per_page;
        $skip_records = $offset;
        
        foreach ($this->tables as $table) {
            if ($records_needed <= 0) break;
            
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$table_exists) {
                continue;
            }
            
            // Calculate how many records to skip and take for this table
            $table_offset = max(0, $skip_records - $records_collected);
            $table_limit = $records_needed;
            
            $query = "
                SELECT 
                    post_id,
                    MAX(CASE WHEN meta_key = 'unit_number_name' THEN meta_value END) as unit_number_name,
                    MAX(CASE WHEN meta_key = 'attached_driver' THEN meta_value END) as attached_driver,
                    MAX(CASE WHEN meta_key = 'tbd' THEN meta_value END) as tbd
                FROM $table 
                WHERE meta_key IN ('unit_number_name', 'attached_driver', 'tbd')
                GROUP BY post_id
                HAVING 
                    unit_number_name IS NOT NULL 
                    AND unit_number_name != '' 
                    AND unit_number_name != 'TBD'
                    AND (attached_driver IS NULL OR attached_driver = '')
                ORDER BY post_id DESC
                LIMIT $table_offset, $table_limit
            ";
            
            $results = $wpdb->get_results($query);
            
            foreach ($results as $result) {
                if ($records_collected < $skip_records) {
                    $records_collected++;
                    continue;
                }
                
                if (count($records) >= $records_needed) {
                    break 2; // Break out of both loops
                }
                
                $records[] = [
                    'table' => $table,
                    'post_id' => $result->post_id,
                    'unit_number_name' => $result->unit_number_name,
                    'attached_driver' => $result->attached_driver,
                    'tbd' => $result->tbd
                ];
                
                $records_collected++;
            }
        }
        
        wp_send_json_success([
            'records' => $records,
            'total' => $total_count,
            'tables' => $tables_found,
            'page' => $page,
            'per_page' => $per_page
        ]);
    }
    
    public function ajax_fix_records() {
        check_ajax_referer('fix_driver_data_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $records = $_POST['records'] ?? [];
        if (empty($records)) {
            wp_send_json_error('No records provided');
        }
        
        global $wpdb;
        $fixed = 0;
        $errors = [];
        $details = [];
        
        foreach ($records as $record) {
            list($table, $post_id) = explode('|', $record);
            
            // Get the unit_number_name to extract driver ID
            $unit_number_name = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_value FROM $table WHERE post_id = %d AND meta_key = 'unit_number_name'",
                $post_id
            ));
            
            if (!$unit_number_name) {
                $errors[] = "No unit_number_name found for post_id $post_id in table $table";
                continue;
            }
            
            // Clean the unit_number_name (remove newlines and extra spaces)
            $clean_unit_number_name = trim(preg_replace('/\s+/', ' ', $unit_number_name));
            
            // Debug logging
            error_log("DEBUG: Original unit_number_name: " . json_encode($unit_number_name));
            error_log("DEBUG: Clean unit_number_name: " . json_encode($clean_unit_number_name));
            error_log("DEBUG: Regex test result: " . (preg_match('/(\d+)/', $clean_unit_number_name, $matches) ? 'MATCH' : 'NO MATCH'));
            
            // Extract driver ID from unit_number_name (find first number in the string)
            if (preg_match('/(\d+)/', $clean_unit_number_name, $matches)) {
                $driver_id = $matches[1];
                error_log("DEBUG: Extracted driver_id: " . $driver_id);
                
                // Check if attached_driver already exists
                $existing_records = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table WHERE post_id = %d AND meta_key = 'attached_driver'",
                    $post_id
                ));
                
                if (!empty($existing_records)) {
                    // Update the first record
                    $first_record = $existing_records[0];
                    $result = $wpdb->update(
                        $table,
                        ['meta_value' => $driver_id],
                        ['id' => $first_record->id]
                    );
                    
                    // Delete all other duplicate records
                    if (count($existing_records) > 1) {
                        $other_ids = array_slice(array_column($existing_records, 'id'), 1);
                        foreach ($other_ids as $id) {
                            $wpdb->delete($table, ['id' => $id]);
                        }
                    }
                    
                    if ($result !== false) {
                        $fixed++;
                        $details[] = "Updated: $table (ID: $post_id) '$unit_number_name' → attached_driver = '$driver_id'";
                    } else {
                        $errors[] = "Failed to update post_id $post_id in table $table: " . $wpdb->last_error;
                    }
                } else {
                    // Insert new record
                    $result = $wpdb->insert(
                        $table,
                        [
                            'post_id' => $post_id,
                            'meta_key' => 'attached_driver',
                            'meta_value' => $driver_id
                        ]
                    );
                    
                    if ($result !== false) {
                        $fixed++;
                        $details[] = "Inserted: $table (ID: $post_id) '$unit_number_name' → attached_driver = '$driver_id'";
                    } else {
                        $errors[] = "Failed to insert post_id $post_id in table $table: " . $wpdb->last_error;
                    }
                }
            } else {
                $errors[] = "Could not extract driver ID from unit_number_name '$clean_unit_number_name' for post_id $post_id in table $table";
            }
        }
        
        // Always return success with details, even if there are some errors
        wp_send_json_success([
            'fixed' => $fixed,
            'errors' => $errors,
            'details' => $details
        ]);
    }
    
    public function ajax_search_second_records() {
        check_ajax_referer('fix_driver_data_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 100);
        $offset = ($page - 1) * $per_page;
        
        global $wpdb;
        $records = [];
        $tables_found = [];
        $total_count = 0;
        
        // First, get total count for second driver
        foreach ($this->tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$table_exists) {
                continue;
            }
            
            $tables_found[] = $table;
            
            $count_query = "
                SELECT COUNT(*) as count
                FROM (
                    SELECT post_id
                    FROM $table 
                    WHERE meta_key IN ('second_unit_number_name', 'attached_second_driver')
                    GROUP BY post_id
                    HAVING 
                        MAX(CASE WHEN meta_key = 'second_unit_number_name' THEN meta_value END) IS NOT NULL 
                        AND MAX(CASE WHEN meta_key = 'second_unit_number_name' THEN meta_value END) != '' 
                        AND (MAX(CASE WHEN meta_key = 'attached_second_driver' THEN meta_value END) IS NULL 
                             OR MAX(CASE WHEN meta_key = 'attached_second_driver' THEN meta_value END) = '')
                ) as subquery
            ";
            
            $count_result = $wpdb->get_var($count_query);
            $total_count += intval($count_result);
        }
        
        // Then get records for current page
        $records_collected = 0;
        $records_needed = $per_page;
        $skip_records = $offset;
        
        foreach ($this->tables as $table) {
            if ($records_needed <= 0) break;
            
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$table_exists) {
                continue;
            }
            
            // Calculate how many records to skip and take for this table
            $table_offset = max(0, $skip_records - $records_collected);
            $table_limit = $records_needed;
            
            $query = "
                SELECT 
                    post_id,
                    MAX(CASE WHEN meta_key = 'second_unit_number_name' THEN meta_value END) as second_unit_number_name,
                    MAX(CASE WHEN meta_key = 'attached_second_driver' THEN meta_value END) as attached_second_driver
                FROM $table 
                WHERE meta_key IN ('second_unit_number_name', 'attached_second_driver')
                GROUP BY post_id
                HAVING 
                    second_unit_number_name IS NOT NULL 
                    AND second_unit_number_name != '' 
                    AND (attached_second_driver IS NULL OR attached_second_driver = '')
                ORDER BY post_id DESC
                LIMIT $table_offset, $table_limit
            ";
            
            $results = $wpdb->get_results($query);
            
            foreach ($results as $result) {
                if ($records_collected < $skip_records) {
                    $records_collected++;
                    continue;
                }
                
                if (count($records) >= $records_needed) {
                    break 2; // Break out of both loops
                }
                
                $records[] = [
                    'table' => $table,
                    'post_id' => $result->post_id,
                    'second_unit_number_name' => $result->second_unit_number_name,
                    'attached_second_driver' => $result->attached_second_driver
                ];
                
                $records_collected++;
            }
        }
        
        wp_send_json_success([
            'records' => $records,
            'total' => $total_count,
            'tables' => $tables_found,
            'page' => $page,
            'per_page' => $per_page
        ]);
    }
    
    public function ajax_fix_second_records() {
        check_ajax_referer('fix_driver_data_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $records = $_POST['records'] ?? [];
        if (empty($records)) {
            wp_send_json_error('No records provided');
        }
        
        global $wpdb;
        $fixed = 0;
        $errors = [];
        $details = [];
        
        foreach ($records as $record) {
            list($table, $post_id) = explode('|', $record);
            
            // Get the second_unit_number_name to extract driver ID
            $second_unit_number_name = $wpdb->get_var($wpdb->prepare(
                "SELECT meta_value FROM $table WHERE post_id = %d AND meta_key = 'second_unit_number_name'",
                $post_id
            ));
            
            if (!$second_unit_number_name) {
                $errors[] = "No second_unit_number_name found for post_id $post_id in table $table";
                continue;
            }
            
            // Clean the second_unit_number_name (remove newlines and extra spaces)
            $clean_second_unit_number_name = trim(preg_replace('/\s+/', ' ', $second_unit_number_name));
            
            // Debug logging
            error_log("DEBUG SECOND: Original second_unit_number_name: " . json_encode($second_unit_number_name));
            error_log("DEBUG SECOND: Clean second_unit_number_name: " . json_encode($clean_second_unit_number_name));
            error_log("DEBUG SECOND: Regex test result: " . (preg_match('/(\d+)/', $clean_second_unit_number_name, $matches) ? 'MATCH' : 'NO MATCH'));
            
            // Extract driver ID from second_unit_number_name (find first number in the string)
            if (preg_match('/(\d+)/', $clean_second_unit_number_name, $matches)) {
                $driver_id = $matches[1];
                error_log("DEBUG SECOND: Extracted driver_id: " . $driver_id);
                
                // Check if attached_second_driver already exists
                $existing_records = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table WHERE post_id = %d AND meta_key = 'attached_second_driver'",
                    $post_id
                ));
                
                if (!empty($existing_records)) {
                    // Update the first record
                    $first_record = $existing_records[0];
                    $result = $wpdb->update(
                        $table,
                        ['meta_value' => $driver_id],
                        ['id' => $first_record->id]
                    );
                    
                    // Delete all other duplicate records
                    if (count($existing_records) > 1) {
                        $other_ids = array_slice(array_column($existing_records, 'id'), 1);
                        foreach ($other_ids as $id) {
                            $wpdb->delete($table, ['id' => $id]);
                        }
                    }
                    
                    if ($result !== false) {
                        $fixed++;
                        $details[] = "Updated: $table (ID: $post_id) '$second_unit_number_name' → attached_second_driver = '$driver_id'";
                    } else {
                        $errors[] = "Failed to update post_id $post_id in table $table: " . $wpdb->last_error;
                    }
                } else {
                    // Insert new record
                    $result = $wpdb->insert(
                        $table,
                        [
                            'post_id' => $post_id,
                            'meta_key' => 'attached_second_driver',
                            'meta_value' => $driver_id
                        ]
                    );
                    
                    if ($result !== false) {
                        $fixed++;
                        $details[] = "Inserted: $table (ID: $post_id) '$second_unit_number_name' → attached_second_driver = '$driver_id'";
                    } else {
                        $errors[] = "Failed to insert post_id $post_id in table $table: " . $wpdb->last_error;
                    }
                }
            } else {
                $errors[] = "Could not extract driver ID from second_unit_number_name '$clean_second_unit_number_name' for post_id $post_id in table $table";
            }
        }
        
        // Always return success with details, even if there are some errors
        wp_send_json_success([
            'fixed' => $fixed,
            'errors' => $errors,
            'details' => $details
        ]);
    }
}

// Initialize the admin page
new FixDriverDataAdmin();
