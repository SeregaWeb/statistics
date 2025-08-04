<?php
/**
 * Template Name: Page Driver Backershot
 */
get_header();

// Функция для получения списка файлов из директории
function get_txt_files_list() {
	$files_list = array();
	$directory = get_template_directory() . '/backershot/'; // Путь к директории с файлами
	
	// Проверяем существование директории
	if (file_exists($directory)) {
		// Получаем список файлов в директории
		$files = scandir($directory);
		
		// Отфильтровываем только txt файлы
		foreach ($files as $file) {
			if (pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
				$files_list[] = $file;
			}
		}
	}
	
	return $files_list;
}

// Создание HTML выпадающего списка
function generate_files_dropdown() {
	$files = get_txt_files_list();
	
	if (empty($files)) {
		echo '<p class="no-files">Files not found.</p>';
		return;
	}
    
    $selected = isset($_POST['selected_file']) && !empty($_POST['selected_file']) ? $_POST['selected_file'] : '';
	
	echo '<div class="file-selector">';
	echo '<form class="txt-files d-flex gap-2 align-items-center" method="post">';
	echo '<div class="d-flex gap-2 align-items-center">';
	echo '<select id="txt-files-select" name="selected_file" class="form-select">';
	echo '<option value="">Select file</option>';
	foreach ($files as $file) {
        $selected_options = esc_html($file) === $selected ? 'selected' : '';
		echo '<option '.$selected_options.' value="' . esc_attr($file) . '">' . esc_html($file) . '</option>';
	}
	echo '</select>';
	echo '</div>';
	echo '<button type="submit" class="btn btn-primary" name="view_report">Select</button>';
	echo '</form>';
	echo '</div>';
}

function get_drivers_with_mc() {
	global $wpdb;
	$table_meta = $wpdb->prefix . 'drivers_meta';
	
	$query = $wpdb->prepare(
		"SELECT COUNT(DISTINCT post_id) as count 
		FROM {$table_meta} 
		WHERE meta_key = 'mc_enabled' AND meta_value = 'on'"
	);
	
	$result = $wpdb->get_var($query);
	return $result ? intval($result) : 0;
}

function get_drivers_with_dot() {
	global $wpdb;
	$table_meta = $wpdb->prefix . 'drivers_meta';
	
	$query = $wpdb->prepare(
		"SELECT COUNT(DISTINCT post_id) as count 
		FROM {$table_meta} 
		WHERE meta_key = 'dot_enabled' AND meta_value = 'on'"
	);
	
	$result = $wpdb->get_var($query);
	return $result ? intval($result) : 0;
}

$user_id    = get_current_user_id();
$user_meta  = get_userdata( $user_id );
$user_roles = $user_meta->roles[ 0 ];

?>
<style>
.detect-mc-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: white;
    min-height: 100vh;
}

.page-title {
    text-align: center;
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 40px;
    letter-spacing: -0.5px;
}

.stats-section {
    text-align: center;
    margin-bottom: 20px;
}

.stats-label {
    font-size: 1.2rem;
    color: #666;
    font-weight: 400;
}

.stats-counters {
    display: flex;
    justify-content: center;
    gap: 20px;
    align-items: center;
}

.counter {
    font-size: 16px;
    font-weight: 700;
    padding: 10px 20px;
    border-radius: 8px;
    background: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.counter.dot {
    color: #f69d18;
}

.counter.mc {
    color: #b92727;
}

.file-selector {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px;
    margin-bottom: 40px;
    flex-wrap: wrap;
}

.select-wrapper {
    position: relative;
    min-width: 200px;
}

.file-select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    background: white;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease;
}

.file-select:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
}


.btn-select:hover {
    background: #5a4fd8;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(108, 92, 231, 0.3);
}

.logs-container {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 30px;
    margin-top: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    border: 1px solid #e9ecef;
}

.logs-content {
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 0.9rem;
    line-height: 1.6;
    color: #333;
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 600px;
    overflow-y: auto;
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e1e5e9;
}

.logs-content .dot-highlight {
    color: #f69d18;
    font-weight: bold;
}

.logs-content .mc-highlight {
    color: #b92727;
    font-weight: bold;
}

.no-files {
    text-align: center;
    color: #666;
    font-style: italic;
    margin: 20px 0;
}

.error-message {
    text-align: center;
    color: #dc3545;
    background: #f8d7da;
    padding: 15px;
    border-radius: 8px;
    margin: 20px 0;
}

@media (max-width: 768px) {
    .detect-mc-page {
        padding: 20px 15px;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .stats-counters {
        flex-direction: column;
        gap: 20px;
    }
    
    .file-selector {
        flex-direction: column;
        align-items: stretch;
    }
    
    .select-wrapper {
        min-width: auto;
    }
}
</style>

<div class="detect-mc-page">
    <?php if ( $user_roles === 'recruiter-team-lead' || $user_roles === 'administrator' || $user_roles === 'recruiter' ) { ?>
        
        <h1 class="page-title">Detect MC Logs</h1>
        
        <div class="stats-section">
            <p class="stats-label">Total driver with</p>
            <div class="stats-counters">
                <span class="counter dot">DOT: <?php echo get_drivers_with_dot(); ?></span>
                <span class="counter mc">MC: <?php echo get_drivers_with_mc(); ?></span>
            </div>
        </div>
        
        <?php generate_files_dropdown(); ?>
        
        <?php if (isset($_POST['view_report'])) { ?>
            <div class="logs-container">
                <?php
                if (!empty($_POST['selected_file'])) {
                    $selected_file = sanitize_text_field($_POST['selected_file']);
                    $directory = get_template_directory() . '/backershot/';
                    $file_path = $directory . $selected_file;
                    
                    if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) === 'txt') {
                        $file_content = file_get_contents($file_path);
                        
                        if ($file_content !== false) {
                            $lines = explode("\n", $file_content);
                            $output = '';
                            
                            foreach ($lines as $line) {
                                // Подсветка DOT и MC
                                if (strpos($line, 'DOT:') !== false) {
                                    $line = preg_replace('/(DOT:\d+)/', '<span class="dot-highlight">$1</span>', $line);
                                }
                                if (strpos($line, 'MC:') !== false) {
                                    $line = preg_replace('/(MC:\s*\d+)/', '<span class="mc-highlight">$1</span>', $line);
                                }
                                
                                $output .= $line . "\n";
                            }
                            
                            echo '<div class="logs-content">' . $output . '</div>';
                        } else {
                            echo '<div class="error-message">Error reading file.</div>';
                        }
                    } else {
                        echo '<div class="error-message">File not found.</div>';
                    }
                } else {
                    echo '<div class="error-message">Please select a file.</div>';
                }
                ?>
            </div>
        <?php } ?>
        
    <?php } else { ?>
        <div class="error-message">
            <h3>Access Denied</h3>
            <p>Your role doesn't have permission to view this page.</p>
        </div>
    <?php } ?>
</div>

<?php get_footer(); ?> 