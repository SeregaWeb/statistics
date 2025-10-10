<?php
$TMSUsers = new TMSUsers();

$access = $TMSUsers->check_user_role_access( [
	'administrator',
	'recruiter',
	'recruiter-tl',
	'accounting',
	'moderator',
	'billing',
], true );

$access_csv_parser = $TMSUsers->check_user_role_access( [
	'administrator',
], true );

if ( $access ) {
	$settlement = new TMSGenerateDocument();
	$settlement->init(); // Initialize tables and AJAX handlers
	$available_files = $settlement->get_available_csv_files();
	?>
	<div class="settlement-csv-parser">
		<div class="generate-top">
			<h2 class="generate-title">Settlement Summary</h2>
		</div>
		
		<?php if ( $access_csv_parser ) { ?>
		<!-- Tabs -->
		<div class="csv-parser-tabs">
					<nav class="nav nav-tabs" id="csvParserTab" role="tablist">
			<a class="nav-item nav-link active" id="generate-tab" href="#generate" role="tab" aria-controls="generate" aria-selected="true">Generate Document</a>
			<a class="nav-item nav-link" id="csv-parser-tab" href="#csv-parser" role="tab" aria-controls="csv-parser" aria-selected="false">CSV Parser</a>
			<a class="nav-item nav-link" id="show-results-tab" href="#show-results" role="tab" aria-controls="show-results" aria-selected="false">Show results</a>
		</nav>
		</div>
		<?php } ?> 

		<div class="tab-content " id="csvParserTabContent">
			<!-- Generate Document Tab -->
			<div class="tab-pane pb-3 show active" id="generate" role="tabpanel" aria-labelledby="generate-tab">
				<?php if ( $settlement->_is_mpdf_exists( false ) ) { ?>
					<form class="js-generate-settlement-summary">
						<?php echo $settlement->get_template_settlement_summary( true ); ?>
						<div class="generate">
							<div class="generate-submit">
								<button class="btn btn-primary btn-orange">Generate</button>
							</div>
						</div>
					</form>
				<?php } else { ?>
					<div class="generate-top">
						<h2 class="generate-title">MPDF - not install</h2>
					</div>
				<?php } ?>
			</div>
			<?php if ( $access_csv_parser ) { ?>
			<!-- CSV Parser Tab -->
			<div class="tab-pane" id="csv-parser" role="tabpanel" aria-labelledby="csv-parser-tab">
				<div class="csv-parser-content">
					<h3>CSV Files Parser</h3>
					
					<!-- CSV Parser Sub Tabs -->
					<div class="csv-parser-sub-tabs">
								<nav class="nav nav-tabs" id="csvParserSubTab" role="tablist">
									<a class="nav-item nav-link active" id="files-tab" href="#files" role="tab" aria-controls="files" aria-selected="true">Files</a>
									<a class="nav-item nav-link" id="progress-tab" href="#progress" role="tab" aria-controls="progress" aria-selected="false">Progress</a>
									<a class="nav-item nav-link" id="results-tab" href="#results" role="tab" aria-controls="results" aria-selected="false">Results</a>
									<a class="nav-item nav-link" id="database-tab" href="#database" role="tab" aria-controls="database" aria-selected="false">Database</a>
								</nav>
					</div>

					<div class="tab-content" id="csvParserSubTabContent">
						<!-- Files Tab -->
						<div class="tab-pane show active" id="files" role="tabpanel" aria-labelledby="files-tab">
							<div class="csv-files-list">
								<h4>Available CSV Files</h4>
								<div class="table-responsive">
									<table class="table table-striped">
										<thead>
											<tr>
												<th>File Name</th>
												<th>Size</th>
												<th>Modified</th>
												<th>Progress</th>
												<th>Actions</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ($available_files as $file): ?>
											<tr data-file-path="<?php echo esc_attr($file['path']); ?>">
												<td><?php echo esc_html($file['name']); ?></td>
												<td><?php echo esc_html(size_format($file['size'])); ?></td>
												<td><?php echo esc_html(date('Y-m-d H:i:s', $file['modified'])); ?></td>
												<td>
													<div class="progress">
														<div class="progress-bar" role="progressbar" 
															style="width: <?php echo $file['progress']['percentage']; ?>%" 
															aria-valuenow="<?php echo $file['progress']['percentage']; ?>" 
															aria-valuemin="0" aria-valuemax="100">
															<?php echo $file['progress']['percentage']; ?>%
														</div>
													</div>
													<small class="text-muted">
														<?php echo $file['progress']['processed_rows']; ?>/<?php echo $file['progress']['total_rows']; ?> rows
													</small>
												</td>
												<td>
													<button class="btn btn-sm btn-primary start-parsing" 
														data-file-path="<?php echo esc_attr($file['path']); ?>"
														<?php echo $file['progress']['status'] === 'completed' ? 'disabled' : ''; ?>>
														<?php echo $file['progress']['status'] === 'completed' ? 'Completed' : 'Start'; ?>
													</button>
													<button class="btn btn-sm btn-secondary refresh-progress" 
														data-file-path="<?php echo esc_attr($file['path']); ?>">
														Refresh
													</button>
												</td>
											</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>

						<!-- Progress Tab -->
						<div class="tab-pane" id="progress" role="tabpanel" aria-labelledby="progress-tab">
							<div class="parsing-progress">
								<h4>Parsing Progress</h4>
								<div id="current-file-progress" style="display: none;">
									<div class="card">
										<div class="card-body">
											<h5 class="card-title" id="progress-file-name">Processing...</h5>
											<div class="progress mb-3">
												<div class="progress-bar progress-bar-striped progress-bar-animated" 
													id="progress-bar" role="progressbar" style="width: 0%">
													0%
												</div>
											</div>
											<div class="row">
												<div class="col-md-3">
													<div class="text-center">
														<h6 class="text-primary">Processed</h6>
														<span id="progress-processed" class="h4">0</span>
													</div>
												</div>
												<div class="col-md-3">
													<div class="text-center">
														<h6 class="text-success">Imported</h6>
														<span id="progress-imported" class="h4">0</span>
													</div>
												</div>
												<div class="col-md-3">
													<div class="text-center">
														<h6 class="text-warning">Skipped</h6>
														<span id="progress-skipped" class="h4">0</span>
													</div>
												</div>
												<div class="col-md-3">
													<div class="text-center">
														<h6 class="text-danger">Errors</h6>
														<span id="progress-errors" class="h4">0</span>
													</div>
												</div>
											</div>
											<div class="mt-3">
												<button class="btn btn-danger" id="stop-parsing">Stop Parsing</button>
											</div>
										</div>
									</div>
								</div>
								<div id="no-progress" class="text-center text-muted">
									<p>No file is currently being processed.</p>
									<p>Select a file from the Files tab to start parsing.</p>
								</div>
							</div>
						</div>

						<!-- Results Tab -->
						<div class="tab-pane" id="results" role="tabpanel" aria-labelledby="results-tab">
							<div class="parsing-results">
								<h4>Parsing Results</h4>
								<div id="results-content">
									<div class="alert alert-info">
										No parsing results yet. Start parsing a file to see results here.
									</div>
								</div>
							</div>
						</div>

						<!-- Database Tab -->
						<div class="tab-pane" id="database" role="tabpanel" aria-labelledby="database-tab">
							<div class="database-stats">
								<h4>Database Statistics</h4>
								<div class="mb-3">
									<button class="btn btn-primary" id="refresh-stats">Refresh Statistics</button>
									<button class="btn btn-danger ml-2" id="clear-data">Clear All Data</button>
								</div>
								<div id="database-content">
									<div class="text-center">
										<div class="spinner-border" role="status">
											<span class="sr-only">Loading...</span>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Show Results Tab -->
			<div class="tab-pane" id="show-results" role="tabpanel" aria-labelledby="show-results-tab">
				<div class="show-results-content">
					<h4>Show Results</h4>
					<?php
					global $wpdb;
					$table_summary = $wpdb->prefix . 'settlement_summary';
					
					// Get unique drivers from settlement summary
					$drivers = $wpdb->get_results(
						"SELECT id_driver, MAX(driver_name) AS driver_name
						 FROM {$table_summary}
						 WHERE id_driver IS NOT NULL AND id_driver <> 0
						 GROUP BY id_driver
						 ORDER BY driver_name ASC",
						ARRAY_A
					);
					
					// Current selected driver
					$selected_driver_id = isset( $_GET['id_driver'] ) ? sanitize_text_field( wp_unslash( $_GET['id_driver'] ) ) : '';
					?>
					<div class="card mb-3">
						<div class="card-body">
							<form method="get" class="form-inline">
								<label for="id_driver" class="mr-2">Driver</label>
								<select id="id_driver" name="id_driver" class="form-control mr-2" style="min-width: 280px;">
									<option value="">Select driver...</option>
									<?php if ( ! empty( $drivers ) ) { foreach ( $drivers as $d ) { $val = (string) $d['id_driver']; ?>
										<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $selected_driver_id, $val ); ?>>
											<?php echo esc_html( trim( (string) $d['driver_name'] ) !== '' ? (string) $d['driver_name'] : ( '#' . $val ) ); ?>
										</option>
									<?php } } ?>
								</select>
								<button type="submit" class="btn btn-primary">Show</button>
							</form>
						</div>
					</div>

					<?php
					if ( $selected_driver_id ) {
						// Render automatic monthly layout for selected driver
						echo $settlement->render_driver_settlement_by_month( $selected_driver_id );
					}
					?>
				</div>
			</div>
			<?php } ?>
		</div>
		
		<?php if ( $access_csv_parser ) { ?>
		<!-- Bulk PDF Generation Section -->
		<div class="bulk-pdf-generation mt-4">
			<div class="card">
				<div class="card-header">
					<h5 class="mb-0">Bulk PDF Generation</h5>
				</div>
				<div class="card-body">
					<p class="text-muted">Generate PDF documents for all drivers with proper folder structure.</p>
					<div class="bulk-generation-controls">
						<button type="button" class="btn btn-success" id="start-bulk-generation">
							<i class="fas fa-play"></i> Start Bulk Generation
						</button>
						<button type="button" class="btn btn-danger" id="stop-bulk-generation" style="display: none;">
							<i class="fas fa-stop"></i> Stop Generation
						</button>
						<button type="button" class="btn btn-warning" id="reset-bulk-generation">
							<i class="fas fa-refresh"></i> Reset Progress
						</button>
					</div>
					
					<!-- Progress Section -->
					<div id="bulk-generation-progress" class="mt-3" style="display: none;">
						<div class="progress mb-3">
							<div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
								<span class="progress-text">0%</span>
							</div>
						</div>
						<div class="generation-stats">
							<div class="row">
								<div class="col-md-3">
									<small class="text-muted">Total Drivers:</small>
									<div class="fw-bold" id="total-drivers">0</div>
								</div>
								<div class="col-md-3">
									<small class="text-muted">Completed:</small>
									<div class="fw-bold text-success" id="completed-drivers">0</div>
								</div>
								<div class="col-md-3">
									<small class="text-muted">Failed:</small>
									<div class="fw-bold text-danger" id="failed-drivers">0</div>
								</div>
								<div class="col-md-3">
									<small class="text-muted">Current:</small>
									<div class="fw-bold text-info" id="current-driver">-</div>
								</div>
							</div>
						</div>
						<div class="generation-log mt-3">
							<small class="text-muted">Generation Log:</small>
							<div id="generation-log" class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px;">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php } ?>
	</div>

	<?php if ( $access_csv_parser ) { ?>
	<script>
	// Define ajaxurl for frontend
	var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
	var settlement_nonce = '<?php echo wp_create_nonce('settlement_csv_nonce'); ?>';
	console.log('Nonce created:', settlement_nonce);
	console.log('Ajax URL:', ajaxurl);
	
	(function() {
		// Wait for jQuery to be available
		function initWhenReady() {
			if (typeof jQuery !== 'undefined') {
				jQuery(document).ready(function($) {
		// Initialize tabs manually to ensure they work
		console.log('Initializing tabs manually');
		
		// Handle main tabs
		$('#csvParserTab .nav-link').on('click', function(e) {
			e.preventDefault();
			const target = $(this).attr('href');
			
			console.log('Main tab clicked:', target);
			
			// Hide all main tab panes
			$('#csvParserTabContent .tab-pane').removeClass('show active');
			
			// Remove active class from all main tabs
			$('#csvParserTab .nav-link').removeClass('active');
			
			// Show target tab pane
			$(target).addClass('show active');
			
			// Add active class to clicked tab
			$(this).addClass('active');
			
			console.log('Tab switched to:', target);
			
			// Trigger custom event for tab change
			$(document).trigger('tabChanged', [target]);
		});
		
		// Handle sub tabs
		$('#csvParserSubTab .nav-link').on('click', function(e) {
			e.preventDefault();
			const target = $(this).attr('href');
			
			console.log('Sub tab clicked:', target);
			
			// Hide all sub tab panes
			$('#csvParserSubTabContent .tab-pane').removeClass('show active');
			
			// Remove active class from all sub tabs
			$('#csvParserSubTab .nav-link').removeClass('active');
			
			// Show target tab pane
			$(target).addClass('show active');
			
			// Add active class to clicked tab
			$(this).addClass('active');
			
			console.log('Sub tab switched to:', target);
			
			// Trigger custom event for sub tab change
			$(document).trigger('subTabChanged', [target]);
		});
		
		let currentParsing = false;
		let currentFilePath = '';
		let progressInterval = null;

		// Helper function to switch tabs
		function switchTab(tabId) {
			const tab = $('#' + tabId);
			if (tab.length) {
				tab.click();
			}
		}

		// Start parsing
		$(document).on('click', '.start-parsing', function() {
			const filePath = $(this).data('file-path');
			startParsing(filePath);
		});

		// Refresh progress
		$(document).on('click', '.refresh-progress', function() {
			const filePath = $(this).data('file-path');
			refreshFileProgress(filePath);
		});

		// Stop parsing
		$(document).on('click', '#stop-parsing', function() {
			stopParsing();
		});

		// Refresh database stats
		$(document).on('click', '#refresh-stats', function() {
			loadDatabaseStats();
		});

		// Clear all data
		$(document).on('click', '#clear-data', function() {
			if (confirm('Are you sure you want to clear all settlement data? This action cannot be undone.')) {
				$.post(ajaxurl, {
					action: 'clear_settlement_data',
					nonce: settlement_nonce
				}, function(response) {
					if (response.success) {
						alert('Data cleared successfully');
						loadDatabaseStats();
						location.reload(); // Refresh the page to update file progress
					} else {
						alert('Error: ' + response.data);
					}
				}).fail(function() {
					alert('Network error occurred');
				});
			}
		});

		// Tab change events for CSV parser sub tabs
		$(document).on('subTabChanged', function(e, target) {
			if (target === '#database') {
				loadDatabaseStats();
			}
		});

		// Tab change events for main tabs
		$(document).on('tabChanged', function(e, target) {
			if (target === '#csv-parser') {
				// Load database stats when CSV parser tab is shown
				setTimeout(function() {
					loadDatabaseStats();
				}, 100);
			}
		});

		function startParsing(filePath) {
			if (currentParsing) {
				alert('Another file is currently being processed. Please wait.');
				return;
			}

			currentParsing = true;
			currentFilePath = filePath;
			
			// Switch to progress tab
			switchTab('progress-tab');
			
			// Show progress section
			$('#current-file-progress').show();
			$('#no-progress').hide();
			$('#progress-file-name').text('Processing: ' + filePath.split('/').pop());
			
			// Reset progress
			updateProgress(0, 0, 0, 0, 0);
			
			// Start progress monitoring
			progressInterval = setInterval(function() {
				monitorProgress();
			}, 1000);
			
			// Start first batch
			processBatch(filePath, 0);
		}

		function processBatch(filePath, offset) {
			const postData = {
				action: 'parse_settlement_csv',
				file_path: filePath,
				offset: offset,
				limit: 500,
				nonce: settlement_nonce
			};
			console.log('Sending AJAX request with data:', postData);
			
			$.post(ajaxurl, postData, function(response) {
				console.log('AJAX response received:', response);
				
				if (response.success) {
					const data = response.data;
					console.log('Processing batch data:', data);
					
					// Update progress
					updateProgress(
						data.current_offset + data.processed,
						data.total_rows,
						data.imported,
						data.skipped,
						data.errors.length
					);
					
					// Add to results
					addToResults(data);
					
					// Continue if there are more rows
					if (data.has_more && currentParsing) {
						console.log('Continuing with next batch, offset:', data.current_offset + data.processed);
						setTimeout(function() {
							processBatch(filePath, data.current_offset + data.processed);
						}, 100);
					} else {
						console.log('Parsing completed or stopped');
						// Parsing completed
						stopParsing();
						showCompletionMessage(data);
					}
				} else {
					console.error('Error:', response.data);
					stopParsing();
					alert('Error: ' + response.data);
				}
			}).fail(function(xhr, status, error) {
				console.error('AJAX request failed:', {xhr: xhr, status: status, error: error});
				stopParsing();
				alert('Network error occurred: ' + error);
			});
		}

		function stopParsing() {
			currentParsing = false;
			currentFilePath = '';
			
			if (progressInterval) {
				clearInterval(progressInterval);
				progressInterval = null;
			}
			
			$('#current-file-progress').hide();
			$('#no-progress').show();
			
			// Refresh files list
			location.reload();
		}

		function updateProgress(processed, total, imported, skipped, errors) {
			const percentage = total > 0 ? Math.round((processed / total) * 100) : 0;
			
			$('#progress-bar').css('width', percentage + '%').text(percentage + '%');
			$('#progress-processed').text(processed.toLocaleString());
			$('#progress-imported').text(imported.toLocaleString());
			$('#progress-skipped').text(skipped.toLocaleString());
			$('#progress-errors').text(errors);
		}

		function monitorProgress() {
			if (!currentFilePath) return;
			
			$.post(ajaxurl, {
				action: 'get_settlement_progress',
				file_path: currentFilePath,
				nonce: settlement_nonce
			}, function(response) {
				if (response.success) {
					const progress = response.data;
					// Progress is updated by processBatch function
				}
			});
		}

		function addToResults(data) {
			const resultHtml = `
				<div class="alert alert-info">
					<strong>Batch processed:</strong> ${data.processed} rows<br>
					<strong>Imported:</strong> ${data.imported} | 
					<strong>Skipped:</strong> ${data.skipped} | 
					<strong>Errors:</strong> ${data.errors.length}<br>
					${data.errors.length > 0 ? '<strong>Error details:</strong> ' + data.errors.join(', ') : ''}
				</div>
			`;
			
			if ($('#results-content .alert-info').length === 1 && $('#results-content .alert-info').text().includes('No parsing results')) {
				$('#results-content').html(resultHtml);
			} else {
				$('#results-content').append(resultHtml);
			}
		}

		function showCompletionMessage(data) {
			const completionHtml = `
				<div class="alert alert-success">
					<h4>✅ Parsing Completed!</h4>
					<strong>Total processed:</strong> ${data.current_offset + data.processed} rows<br>
					<strong>Final stats:</strong> Imported: ${data.imported}, Skipped: ${data.skipped}, Errors: ${data.errors.length}
				</div>
			`;
			$('#results-content').append(completionHtml);
			
			// Switch to results tab
			switchTab('results-tab');
		}

		function refreshFileProgress(filePath) {
			$.post(ajaxurl, {
				action: 'get_settlement_progress',
				file_path: filePath,
				nonce: settlement_nonce
			}, function(response) {
				if (response.success) {
					const progress = response.data;
					const row = $(`tr[data-file-path="${filePath}"]`);
					
					// Update progress bar
					row.find('.progress-bar').css('width', progress.percentage + '%').text(progress.percentage + '%');
					row.find('small').text(progress.processed_rows + '/' + progress.total_rows + ' rows');
					
					// Update button
					if (progress.status === 'completed') {
						row.find('.start-parsing').text('Completed').prop('disabled', true);
					}
				}
			});
		}

		function loadDatabaseStats() {
			$('#database-content').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
			
			$.post(ajaxurl, {
				action: 'get_settlement_stats',
				nonce: settlement_nonce
			}, function(response) {
				if (response.success) {
					const stats = response.data;
					
					let html = `
						<div class="row">
							<div class="col-md-12">
								<div class="card">
									<div class="card-body text-center">
										<h3 class="text-primary">${stats.total_records.toLocaleString()}</h3>
										<p class="card-text">Total Records</p>
									</div>
								</div>
							</div>
						</div>
					`;
					
					if (stats.date_range.min_date && stats.date_range.max_date) {
						html += `
							<div class="row mt-4">
								<div class="col-md-12">
									<div class="card">
										<div class="card-body">
											<h5 class="card-title">Date Range</h5>
											<p class="card-text">From <strong>${stats.date_range.min_date}</strong> to <strong>${stats.date_range.max_date}</strong></p>
										</div>
									</div>
								</div>
							</div>
						`;
					}
					
					// Status distribution
					if (stats.status_distribution && stats.status_distribution.length > 0) {
						html += `
							<div class="row mt-4">
								<div class="col-md-12">
									<div class="card">
										<div class="card-body">
											<h5 class="card-title">Load Status Distribution</h5>
											<div class="table-responsive">
												<table class="table table-sm">
													<thead>
														<tr>
															<th>Status</th>
															<th>Count</th>
														</tr>
													</thead>
													<tbody>
						`;
						
						stats.status_distribution.forEach(function(status) {
							html += `<tr><td>${status.load_status}</td><td>${status.count.toLocaleString()}</td></tr>`;
						});
						
						html += `
													</tbody>
												</table>
											</div>
										</div>
									</div>
								</div>
							</div>
						`;
					}
					
					$('#database-content').html(html);
				} else {
					$('#database-content').html('<div class="alert alert-danger">Error loading statistics: ' + response.data + '</div>');
				}
			}).fail(function() {
				$('#database-content').html('<div class="alert alert-danger">Network error occurred while loading statistics</div>');
			});
		}
			});
		} else {
			// jQuery not available, try again in 100ms
			setTimeout(initWhenReady, 100);
		}
	}
	
	// Start initialization
	initWhenReady();
	
	// Bulk PDF Generation functionality
	let bulkGenerationActive = false;
	let currentDriverIndex = 0;
	let totalDrivers = 0;
	let driversList = [];
	
	// Wait for jQuery to be available before setting up bulk generation
	function initBulkGeneration() {
		if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
			// jQuery is available, use it
			$(document).ready(function() {
				// Start bulk generation
				$('#start-bulk-generation').on('click', function() {
					startBulkGeneration();
				});
				
					// Stop bulk generation
					$('#stop-bulk-generation').on('click', function() {
						stopBulkGeneration();
					});
					
					// Reset bulk generation progress
					$('#reset-bulk-generation').on('click', function() {
						resetBulkGeneration();
					});
			});
		} else {
			// Fallback to vanilla JavaScript if jQuery is not available
			const startBtn = document.getElementById('start-bulk-generation');
			const stopBtn = document.getElementById('stop-bulk-generation');
			
			if (startBtn) {
				startBtn.addEventListener('click', startBulkGeneration);
			}
			
			if (stopBtn) {
				stopBtn.addEventListener('click', stopBulkGeneration);
			}
			
			const resetBtn = document.getElementById('reset-bulk-generation');
			if (resetBtn) {
				resetBtn.addEventListener('click', resetBulkGeneration);
			}
		}
	}
	
	// Initialize bulk generation when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initBulkGeneration);
	} else {
		// DOM is already ready
		initBulkGeneration();
	}
	
	function startBulkGeneration() {
		// Show progress section
		if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
			// Use jQuery
			$('#bulk-generation-progress').show();
			$('#start-bulk-generation').hide();
			$('#stop-bulk-generation').show();
		} else {
			// Use vanilla JavaScript
			const progressSection = document.getElementById('bulk-generation-progress');
			const startBtn = document.getElementById('start-bulk-generation');
			const stopBtn = document.getElementById('stop-bulk-generation');
			
			if (progressSection) progressSection.style.display = 'block';
			if (startBtn) startBtn.style.display = 'none';
			if (stopBtn) stopBtn.style.display = 'inline-block';
		}
		
		// Reset progress
		updateProgress(0, 0, 0, '-');
		addLog('Starting bulk PDF generation...');
		
		// Start the process
		if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
			// Use jQuery AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'start_bulk_pdf_generation',
					nonce: settlement_nonce
				},
				success: function(response) {
					handleStartResponse(response);
				},
				error: function() {
					addLog('Network error occurred', 'error');
					stopBulkGeneration();
				}
			});
		} else {
			// Use fetch API as fallback
			const formData = new FormData();
			formData.append('action', 'start_bulk_pdf_generation');
			formData.append('nonce', settlement_nonce);
			
			fetch(ajaxurl, {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => handleStartResponse(data))
			.catch(error => {
				addLog('Network error occurred', 'error');
				stopBulkGeneration();
			});
		}
		
			function handleStartResponse(response) {
				if (response.success) {
					if (response.data.resume) {
						// Resume existing generation
						totalDrivers = response.data.total_drivers;
						driversList = response.data.progress.drivers;
						currentDriverIndex = response.data.progress.current_driver || 0;
						bulkGenerationActive = true;
						
						const completed = response.data.progress.completed_drivers || 0;
						const failed = response.data.progress.failed_drivers || 0;
						
						updateProgress(
							Math.round((completed / totalDrivers) * 100),
							completed,
							failed,
							'-'
						);
						
						addLog(`Resuming generation: ${completed}/${totalDrivers} drivers completed`);
						
						// Start processing drivers
						processNextDriver();
					} else {
						// Start new generation
						totalDrivers = response.data.total_drivers;
						driversList = response.data.progress.drivers;
						currentDriverIndex = 0;
						bulkGenerationActive = true;
						
						const completed = response.data.completed_drivers || 0;
						const remaining = response.data.remaining_drivers || totalDrivers;
						
						updateProgress(0, completed, 0, '-');
						
						if (completed > 0) {
							addLog(`Found ${totalDrivers} drivers. ${completed} already completed, ${remaining} remaining. Starting generation...`);
						} else {
							addLog(`Found ${totalDrivers} drivers. Starting generation...`);
						}
						
						// Start processing drivers
						processNextDriver();
					}
				} else {
					addLog('Error: ' + response.data, 'error');
					stopBulkGeneration();
				}
			}
	}
	
	function processNextDriver() {
		if (typeof $ === 'undefined' && typeof jQuery === 'undefined') {
			console.error('jQuery is not available');
			return;
		}
		
		if (!bulkGenerationActive || currentDriverIndex >= driversList.length) {
			// Generation completed
			addLog('Bulk generation completed!', 'success');
			stopBulkGeneration();
			return;
		}
		
		const driver = driversList[currentDriverIndex];
		const driverName = driver.driver_name || `Driver #${driver.id_driver}`;
		
		addLog(`Processing driver: ${driverName} (${currentDriverIndex + 1}/${totalDrivers})`);
		updateProgress(
			Math.round((currentDriverIndex / totalDrivers) * 100),
			currentDriverIndex,
			0,
			driverName
		);
		
		// Generate PDF for current driver
		if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
			// Use jQuery AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'generate_single_driver_pdf',
					driver_id: driver.id_driver,
					nonce: settlement_nonce
				},
				success: function(response) {
					handleDriverResponse(response, driverName);
				},
				error: function() {
					addLog(`✗ ${driverName}: Network error`, 'error');
					currentDriverIndex++;
					setTimeout(processNextDriver, 1000);
				}
			});
		} else {
			// Use fetch API as fallback
			const formData = new FormData();
			formData.append('action', 'generate_single_driver_pdf');
			formData.append('driver_id', driver.id_driver);
			formData.append('nonce', settlement_nonce);
			
			fetch(ajaxurl, {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => handleDriverResponse(data, driverName))
			.catch(error => {
				addLog(`✗ ${driverName}: Network error`, 'error');
				currentDriverIndex++;
				setTimeout(processNextDriver, 1000);
			});
		}
		
		function handleDriverResponse(response, driverName) {
			if (response.success) {
				const filesCreated = response.data.files_created ? response.data.files_created.length : 0;
				addLog(`✓ ${driverName}: ${filesCreated} files created`, 'success');
			} else {
				addLog(`✗ ${driverName}: ${response.data.message}`, 'error');
			}
			
			// Move to next driver
			currentDriverIndex++;
			
			// Add small delay to prevent overwhelming the server
			setTimeout(processNextDriver, 1000);
		}
	}
	
	function stopBulkGeneration() {
		bulkGenerationActive = false;
		
		if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
			// Use jQuery
			$('#start-bulk-generation').show();
			$('#stop-bulk-generation').hide();
		} else {
			// Use vanilla JavaScript
			const startBtn = document.getElementById('start-bulk-generation');
			const stopBtn = document.getElementById('stop-bulk-generation');
			
			if (startBtn) startBtn.style.display = 'inline-block';
			if (stopBtn) stopBtn.style.display = 'none';
		}
		
		addLog('Generation stopped by user', 'warning');
	}
	
	function resetBulkGeneration() {
		if (confirm('Are you sure you want to reset the generation progress? This will allow you to start from the beginning.')) {
			if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
				// Use jQuery AJAX
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'reset_bulk_generation_progress',
						nonce: settlement_nonce
					},
					success: function(response) {
						if (response.success) {
							addLog('Progress reset successfully', 'success');
							// Reset UI
							bulkGenerationActive = false;
							currentDriverIndex = 0;
							totalDrivers = 0;
							driversList = [];
							
							$('#start-bulk-generation').show();
							$('#stop-bulk-generation').hide();
							$('#bulk-generation-progress').hide();
							
							updateProgress(0, 0, 0, '-');
						} else {
							addLog('Error resetting progress: ' + response.data, 'error');
						}
					},
					error: function() {
						addLog('Network error occurred while resetting progress', 'error');
					}
				});
			} else {
				// Use fetch API as fallback
				const formData = new FormData();
				formData.append('action', 'reset_bulk_generation_progress');
				formData.append('nonce', settlement_nonce);
				
				fetch(ajaxurl, {
					method: 'POST',
					body: formData
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						addLog('Progress reset successfully', 'success');
						// Reset UI
						bulkGenerationActive = false;
						currentDriverIndex = 0;
						totalDrivers = 0;
						driversList = [];
						
						const startBtn = document.getElementById('start-bulk-generation');
						const stopBtn = document.getElementById('stop-bulk-generation');
						const progressSection = document.getElementById('bulk-generation-progress');
						
						if (startBtn) startBtn.style.display = 'inline-block';
						if (stopBtn) stopBtn.style.display = 'none';
						if (progressSection) progressSection.style.display = 'none';
						
						updateProgress(0, 0, 0, '-');
					} else {
						addLog('Error resetting progress: ' + data.data, 'error');
					}
				})
				.catch(error => {
					addLog('Network error occurred while resetting progress', 'error');
				});
			}
		}
	}
	
	function updateProgress(percentage, completed, failed, current) {
		if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
			// Use jQuery
			$('.progress-bar').css('width', percentage + '%').attr('aria-valuenow', percentage);
			$('.progress-text').text(percentage + '%');
			$('#total-drivers').text(totalDrivers);
			$('#completed-drivers').text(completed);
			$('#failed-drivers').text(failed);
			$('#current-driver').text(current);
		} else {
			// Use vanilla JavaScript
			const progressBar = document.querySelector('.progress-bar');
			const progressText = document.querySelector('.progress-text');
			const totalDriversEl = document.getElementById('total-drivers');
			const completedDriversEl = document.getElementById('completed-drivers');
			const failedDriversEl = document.getElementById('failed-drivers');
			const currentDriverEl = document.getElementById('current-driver');
			
			if (progressBar) {
				progressBar.style.width = percentage + '%';
				progressBar.setAttribute('aria-valuenow', percentage);
			}
			if (progressText) progressText.textContent = percentage + '%';
			if (totalDriversEl) totalDriversEl.textContent = totalDrivers;
			if (completedDriversEl) completedDriversEl.textContent = completed;
			if (failedDriversEl) failedDriversEl.textContent = failed;
			if (currentDriverEl) currentDriverEl.textContent = current;
		}
	}
	
	function addLog(message, type = 'info') {
		const timestamp = new Date().toLocaleTimeString();
		const logEntry = `[${timestamp}] ${message}`;
		
		if (typeof $ !== 'undefined' && typeof jQuery !== 'undefined') {
			// Use jQuery
			const logElement = $(`<div class="log-entry log-${type}">${logEntry}</div>`);
			$('#generation-log').append(logEntry + '\n');
			$('#generation-log').scrollTop($('#generation-log')[0].scrollHeight);
		} else {
			// Use vanilla JavaScript
			const logContainer = document.getElementById('generation-log');
			if (logContainer) {
				logContainer.textContent += logEntry + '\n';
				logContainer.scrollTop = logContainer.scrollHeight;
			} else {
				// Fallback to console if element not found
				console.log(logEntry);
			}
		}
	}
	
	})();
	</script>

	<style>
	.settlement-csv-parser {
		max-width: 1200px;
		margin: 0 auto;
		padding: 20px;
	}
	
	.csv-parser-tabs {
		margin-bottom: 20px;
	}
	
	.csv-parser-sub-tabs {
		margin-bottom: 20px;
	}
	
	.progress {
		height: 20px;
	}
	
	.card {
		margin-bottom: 20px;
	}
	
	.table th {
		border-top: none;
	}
	
	.alert {
		margin-bottom: 15px;
	}
	
	#results-content {
		max-height: 400px;
		overflow-y: auto;
	}
	
	.csv-parser-content {
		padding: 20px;
		background: #f8f9fa;
		border-radius: 5px;
	}
	
	/* Bulk PDF Generation Styles */
	.bulk-pdf-generation .card {
		border: 1px solid #dee2e6;
		border-radius: 0.375rem;
	}
	
	.bulk-pdf-generation .card-header {
		background-color: #f8f9fa;
		border-bottom: 1px solid #dee2e6;
		padding: 1rem;
	}
	
	.bulk-pdf-generation .card-body {
		padding: 1.5rem;
	}
	
	.bulk-generation-controls {
		margin-bottom: 1rem;
	}
	
	.bulk-generation-controls .btn {
		margin-right: 0.5rem;
	}
	
	.progress {
		height: 1.5rem;
		background-color: #e9ecef;
		border-radius: 0.375rem;
		overflow: hidden;
	}
	
	.progress-bar {
		background-color: #28a745;
		transition: width 0.3s ease;
		display: flex;
		align-items: center;
		justify-content: center;
		color: white;
		font-weight: bold;
		font-size: 0.875rem;
	}
	
	.generation-stats {
		background-color: #f8f9fa;
		padding: 1rem;
		border-radius: 0.375rem;
		margin-bottom: 1rem;
	}
	
	.generation-stats .col-md-3 {
		text-align: center;
	}
	
	.generation-stats small {
		display: block;
		margin-bottom: 0.25rem;
	}
	
	.generation-stats .fw-bold {
		font-size: 1.25rem;
	}
	
	#generation-log {
		background-color: #f8f9fa;
		border: 1px solid #dee2e6;
		border-radius: 0.375rem;
		padding: 1rem;
		font-family: 'Courier New', monospace;
		font-size: 0.875rem;
		line-height: 1.4;
		white-space: pre-wrap;
		word-wrap: break-word;
	}
	
	.log-entry {
		margin-bottom: 0.25rem;
	}
	
	.log-success {
		color: #28a745;
	}
	
	.log-error {
		color: #dc3545;
	}
	
	.log-warning {
		color: #ffc107;
	}
	
	.log-info {
		color: #17a2b8;
	}
	</style>
	<?php } ?>


<?php } else {
	echo '<h3 style="text-align: center; color: #000000; margin-top: 20px;">Role doesn\'t match</h3>';
} 