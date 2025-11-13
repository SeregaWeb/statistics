<?php
$position = get_field_value( $args, 'position' );

if ( $position === 'left' ) {
	$position = 'justify-content-start';
} else {
	$position = 'justify-content-end';
}
?>
<div class="pagination <?php echo $position; ?>">
	<?php
	
	// Выводим ссылки на страницы
	//$pagination_args = array(
	//	'base'         => add_query_arg( 'paged', '%#%' ), // Используем add_query_arg()
	//	'format'       => '',
	//	'total'   => $args['total_pages'],
	//	'current' => $args['current_page'],
	//	'prev_text' => __('Prev'),
	//	'next_text' => __('Next'),
	//	'rewrite'   => false,
	//	'add_args'     => false
	//);
	
	//	$big = 999999999; // need an unlikely integer
	//
	//	echo paginate_links( array(
	//		'base'      => str_replace( 999999999, '%#%', esc_url( add_query_arg( 'paged', 999999999 ) ) ),
	//		// База теперь просто '%#%'
	//		'format'    => '?paged=%#%',
	//		// Явно указываем формат '?paged='
	//		'total'     => $args[ 'total_pages' ],
	//		// Обязательно передаем общее количество страниц
	//		'current'   => $args[ 'current_page' ],
	//		// Обязательно передаем текущую страницу
	//		'prev_text' => __( 'Prev' ),
	//		'next_text' => __( 'Next' ),
	//		//	'rewrite'   => false, // Отключаем перезапись URL
	//		//	'add_args'  => false
	//		'add_args'  => array(),
	//		// Добавляем дополнительные параметры (если есть)
	//		'type'      => 'plain',
	//	) );
	
	
	// Собираем все GET-параметры, кроме 'paged'
	$add_args = $_GET;
	if ( isset( $add_args[ 'paged' ] ) ) {
		unset( $add_args[ 'paged' ] );
	}
	
	// Получаем URL для первой страницы и отсекаем от него строку запроса
	$base_url = strtok( get_pagenum_link( 1 ), '?' );
	
	// Добавляем в базовый URL параметр 'paged' с плейсхолдером
	$base = add_query_arg( 'paged', '%#%', $base_url );
	// var_dump($base); // http://localhost:8888/tms-statistics/tracking/?paged=%#%
	
	echo paginate_links( array(
		'base'      => esc_url( $base ),
		'format'    => '',
		'total'     => $args[ 'total_pages' ],
		'current'   => $args[ 'current_page' ],
		'prev_text' => __( 'Prev' ),
		'next_text' => __( 'Next' ),
		'add_args'  => $add_args,
		'type'      => 'plain',
	) );
	?>
</div>
