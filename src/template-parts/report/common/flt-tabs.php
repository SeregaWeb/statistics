<?php
/**
 * FLT Tabs Template
 * 
 * @param bool $show_flt_tabs - показывать ли табы
 * @param bool $is_flt - активен ли FLT таб
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Получаем переменные из $args
$show_flt_tabs = $args['show_flt_tabs'] ?? false;
$is_flt = $args['is_flt'] ?? false;

if ( $show_flt_tabs ) : ?>
    <!-- FLT Tabs -->
    <div class="nav nav-tabs mb-4" id="loads-tabs" role="tablist">
        <a class="nav-link <?php echo ! $is_flt ? 'active' : ''; ?>" href="<?php echo remove_query_arg( 'type' ); ?>">
            Expedite
        </a>
        <a class="nav-link <?php echo $is_flt ? 'active' : ''; ?>" href="<?php echo add_query_arg( 'type', 'flt' ); ?>">
            FTL
        </a>
    </div>
<?php endif; ?> 