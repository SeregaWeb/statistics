<div class="pagination">
<?php
// Выводим ссылки на страницы
$pagination_args = array(
	'base'    => add_query_arg( 'paged', '%#%' ),
	'format'  => '',
	'total'   => $args['total_pages'],
	'current' => $args['current_page'],
	'prev_text' => __('Prev'),
	'next_text' => __('Next'),
);

echo paginate_links( $pagination_args );
?>
</div>
