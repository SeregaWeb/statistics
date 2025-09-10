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
			<?php } ?>
		</div>
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
	</style>
	<?php } ?>


<?php } else {
	echo '<h3 style="text-align: center; color: #000000; margin-top: 20px;">Role doesn\'t match</h3>';
} 