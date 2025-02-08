<div class="pagination">
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
	
	$big = 999999999; // need an unlikely integer
	
	echo paginate_links( array(
		'base'      => str_replace( 999999999, '%#%', esc_url( add_query_arg( 'paged', 999999999 ) ) ),
		// База теперь просто '%#%'
		'format'    => '?paged=%#%',
		// Явно указываем формат '?paged='
		'total'     => $args[ 'total_pages' ],
		// Обязательно передаем общее количество страниц
		'current'   => $args[ 'current_page' ],
		// Обязательно передаем текущую страницу
		'prev_text' => __( 'Prev' ),
		'next_text' => __( 'Next' ),
		//	'rewrite'   => false, // Отключаем перезапись URL
		//	'add_args'  => false
		'add_args'  => array(),
		// Добавляем дополнительные параметры (если есть)
		'type'      => 'plain',
	) );
	
	//echo paginate_links( $pagination_args );
	?>
</div>
