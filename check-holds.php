<?php
// Подключаем WordPress
require_once('../../../wp-load.php');

echo "<h1>Driver Holds Status Check</h1>";

global $wpdb;
$table_name = $wpdb->prefix . 'driver_hold_status';
$current_time = current_time('mysql');

echo "<p><strong>Current time:</strong> {$current_time}</p>";

// Проверяем все холды
$all_holds = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY update_date ASC");
echo "<p><strong>Total holds:</strong> " . count($all_holds) . "</p>";

if (count($all_holds) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Driver ID</th><th>Dispatcher ID</th><th>Status</th><th>Expires</th><th>Minutes Left</th><th>Expired?</th></tr>";
    
    foreach ($all_holds as $hold) {
        $time_diff = strtotime($hold->update_date) - strtotime($current_time);
        $minutes_left = $time_diff / 60;
        $is_expired = $minutes_left < 0;
        $color = $is_expired ? 'red' : ($minutes_left < 5 ? 'orange' : 'green');
        
        echo "<tr style='color: {$color};'>";
        echo "<td>{$hold->id}</td>";
        echo "<td>{$hold->driver_id}</td>";
        echo "<td>{$hold->dispatcher_id}</td>";
        echo "<td>{$hold->driver_status}</td>";
        echo "<td>{$hold->update_date}</td>";
        echo "<td>" . round($minutes_left, 1) . "</td>";
        echo "<td>" . ($is_expired ? 'YES' : 'NO') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No holds found in database.</p>";
}

// Проверяем истекшие холды
$expired_holds = $wpdb->get_results("
    SELECT * FROM {$table_name}
    WHERE update_date < %s
", $current_time);

echo "<h2>Expired Holds ({$current_time}):</h2>";
echo "<p><strong>Count:</strong> " . count($expired_holds) . "</p>";

if (count($expired_holds) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Driver ID</th><th>Status</th><th>Expires</th></tr>";
    
    foreach ($expired_holds as $hold) {
        echo "<tr style='color: red;'>";
        echo "<td>{$hold->id}</td>";
        echo "<td>{$hold->driver_id}</td>";
        echo "<td>{$hold->driver_status}</td>";
        echo "<td>{$hold->update_date}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Кнопка для ручного запуска cron
if (isset($_POST['run_cron'])) {
    echo "<h2>Running Cron...</h2>";
    
    $drivers = new TMSDrivers();
    $result = $drivers->cron_delete_expired_holds();
    
    echo "<p><strong>Result:</strong> {$result} holds processed</p>";
    
    // Обновляем данные
    $all_holds = $wpdb->get_results("SELECT * FROM {$table_name}");
    echo "<p><strong>Total holds after cron:</strong> " . count($all_holds) . "</p>";
    
    echo "<script>setTimeout(function(){ location.reload(); }, 2000);</script>";
}

echo "<form method='post' style='margin: 20px 0;'>";
echo "<input type='submit' name='run_cron' value='Run Cron Manually' style='padding: 10px; font-size: 16px; background: #0073aa; color: white; border: none; cursor: pointer;'>";
echo "</form>";

echo "<p><a href='check-holds.php'>Refresh Page</a></p>";
?> 