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
	$table_main = $wpdb->prefix . 'drivers';
	$table_meta = $wpdb->prefix . 'drivers_meta';
	
	$query = "
		SELECT COUNT(DISTINCT m.post_id) as count 
		FROM {$table_meta} m
		INNER JOIN {$table_main} d ON m.post_id = d.id
		WHERE m.meta_key = 'mc_enabled' 
		AND m.meta_value = 'on'
		AND d.status_post = 'publish'
	";
	
	$result = $wpdb->get_var($query);
	return $result ? intval($result) : 0;
}

function get_drivers_with_dot() {
	global $wpdb;
	$table_main = $wpdb->prefix . 'drivers';
	$table_meta = $wpdb->prefix . 'drivers_meta';
	
	$query = "
		SELECT COUNT(DISTINCT m.post_id) as count 
		FROM {$table_meta} m
		INNER JOIN {$table_main} d ON m.post_id = d.id
		WHERE m.meta_key = 'dot_enabled' 
		AND m.meta_value = 'on'
		AND d.status_post = 'publish'
	";
	
	$result = $wpdb->get_var($query);
	return $result ? intval($result) : 0;
}

$user_id    = get_current_user_id();
$user_meta  = get_userdata( $user_id );
$user_roles = $user_meta->roles[ 0 ];

?>

<div class="detect-mc-page">
    <?php if ( $user_roles === 'recruiter-tl' || $user_roles === 'administrator' || $user_roles === 'recruiter' || $user_roles === 'hr_manager' ) { ?>
        
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