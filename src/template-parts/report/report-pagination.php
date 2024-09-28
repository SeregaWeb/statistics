<!--<nav aria-label="Page navigation example">-->
<!--	<ul class="pagination">-->
<!--		<li class="page-item"><a class="page-link" href="#">Previous</a></li>-->
<!--		<li class="page-item"><a class="page-link" href="#">1</a></li>-->
<!--		<li class="page-item"><a class="page-link" href="#">2</a></li>-->
<!--		<li class="page-item"><a class="page-link" href="#">3</a></li>-->
<!--		<li class="page-item"><a class="page-link" href="#">Next</a></li>-->
<!--	</ul>-->
<!--</nav>-->

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
