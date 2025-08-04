<?php


$statistics = new TMSStatistics();
$TMSUsers   = new TMSUsers();

$dispatcher_tl_initials = get_field_value( $_GET, 'team-lead' );

$hide_filter = $TMSUsers->check_user_role_access( array(
	'dispatcher',
) );

$show_only_my_office = $TMSUsers->check_user_role_access( array(
	'dispatcher',
	'recruiter',
), true );

$office_dispatcher = get_field( 'work_location', 'user_' . get_current_user_id() );

$dispatchers_tl = $statistics->get_dispatchers_tl( $show_only_my_office ? $office_dispatcher : null );
$dispatchers    = $statistics->get_dispatchers( $show_only_my_office ? $office_dispatcher : null );


if ( ! $dispatcher_tl_initials ) {
	$dispatcher_tl_initials = $dispatchers_tl[ 0 ][ 'id' ];
}

if ( ! $hide_filter ) {
	$my_teamlead = $statistics->get_my_team_leader();
	if ( ! empty( $my_teamlead ) && is_array( $my_teamlead ) && count( $my_teamlead ) > 0 ) {
		$dispatcher_tl_initials = $my_teamlead[ 0 ];
	}
}

$my_team        = get_field( 'my_team', 'user_' . $dispatcher_tl_initials );
$my_team[]      = $dispatcher_tl_initials;
$dispatcher_arr = $statistics->get_dispatcher_statistics_current_month( $my_team );

$dispatcher_stats_indexed = [];
if ( is_array( $dispatcher_arr ) && ! empty( $dispatcher_arr ) ) {
	foreach ( $dispatcher_arr as $dispatcher_stat ) {
		$dispatcher_stats_indexed[ $dispatcher_stat[ 'dispatcher_initials' ] ] = $dispatcher_stat;
	}
}
$total_team_load     = 0;
$total_team_profit   = 0;
$total_team_goals    = 0;
$total_team_average  = 0;
$total_team_left     = 0;
$total_team_complete = 0;

if ( $hide_filter ):
	?>
    <form class="w-100">
        <div class="d-flex gap-1">
            <input type="hidden" name="active_state" value="goal">
            <select class="form-select w-auto" name="team-lead"
                    aria-label=".form-select-sm example">
				<?php if ( is_array( $dispatchers_tl ) ): ?>
					<?php foreach ( $dispatchers_tl as $dispatcher ): ?>
                        <option value="<?php echo $dispatcher[ 'id' ]; ?>" <?php echo strval( $dispatcher_tl_initials ) === strval( $dispatcher[ 'id' ] )
							? 'selected' : ''; ?> >
							<?php echo $dispatcher[ 'fullname' ]; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif; ?>
            </select>
            <button class="btn btn-primary" type="submit">Filter</button>
        </div>
    </form>
<?php
endif;
// HTML код для отображения таблицы
echo '<table class="table-stat" border="1" cellpadding="5" cellspacing="0">';
echo '<tr class="text-center">';
echo '<th class="text-left">Dispatcher</th>';
echo '<th>Loads</th>';
echo '<th>Profit</th>';
echo '<th>Goal</th>';
echo '<th>Left</th>';
echo '<th>Average Profit</th>';
echo '<th>Completed</th>';
echo '</tr>';

// Проходим по массиву диспетчеров, чтобы гарантировать вывод всех диспетчеров
foreach ( $dispatchers as $dispatcher ) {
	if ( $my_team !== null && is_array( $my_team ) && in_array( $dispatcher[ 'id' ], $my_team ) ) {
		$fullname = $dispatcher[ 'fullname' ];
		$stat     = [];
		// Если данные по диспетчеру есть в $dispatcher_stats_indexed, используем их, иначе нули
		
		if ( isset( $dispatcher_stats_indexed[ $fullname ] ) ) {
			$stat           = $dispatcher_stats_indexed[ $fullname ];
			$post_count     = $stat[ 'post_count' ];
			$total_profit   = number_format( $stat[ 'total_profit' ], 2 );
			$average_profit = number_format( $stat[ 'average_profit' ], 2 );
			$goal           = $stat[ 'goal' ];
			$left           = $stat[ 'goal' ] - $stat[ 'total_profit' ];
			
			$compleat_color = '';
			$text_color     = '#000000';
			
			if ( $left < 0 ) {
				$left = 0;
			}
			
			if ( is_numeric( $goal ) && $goal > 0 ) {
				$value_pr = ( $stat[ 'total_profit' ] / + $goal ) * 100;
				
				$goal_completion = number_format( $value_pr, 2 );
				
				
				if ( $value_pr > 0 && $value_pr <= 80 ) {
					$compleat_color = '#ff0000';
					$text_color     = '#ffffff';
				} else if ( $value_pr > 80 && $value_pr <= 90 ) {
					$compleat_color = '#ff5858 ';
					$text_color     = '#ffffff';
				} else if ( $value_pr > 90 && $value_pr <= 99.99 ) {
					$compleat_color = '#ff8989';
					$text_color     = '#ffffff';
				} else {
					$compleat_color = '#b2d963';
				}
				
			} else {
				$goal_completion = 'N/A'; // Если цель равна 0
			}
		} else {
			// Если данных по диспетчеру нет, выставляем 0 для всех полей
			$post_count      = 0;
			$compleat_color  = '#ff0000';
			$text_color      = '#ffffff';
			$total_profit    = number_format( 0, 2 );
			$average_profit  = number_format( 0, 2 );
			$goal            = 0;
			$left            = 0;
			$goal            = get_field( 'monthly_goal', 'user_' . $dispatcher[ 'id' ] );
			$goal_completion = 0;
		}
		
		if ( isset( $stat ) && ! empty( $stat ) ) {
			$total_team_load   += is_numeric( $post_count ) ? $post_count : 0;
			$total_team_profit += is_numeric( $stat[ 'total_profit' ] ) ? floatval( $stat[ 'total_profit' ] ) : 0;
			$total_team_goals  += is_numeric( $stat[ 'goal' ] ) ? $stat[ 'goal' ] : 0;
			
		}
		
		
		
		// Вывод строки таблицы для текущего диспетчера
		echo '<tr class="text-center">';
		echo '<td class="text-left">' . $fullname . '</td>';
		echo '<td>' . $post_count . '</td>';
		echo '<td>$' . $total_profit . '</td>';
		echo '<td>$' . $goal . '</td>';
		echo '<td style="background-color:' . $compleat_color . '; color: ' . $text_color . ';">$' . $left . '</td>';
		echo '<td>$' . $average_profit . '</td>';
		echo '<td>' . $goal_completion . '%</td>';
		echo '</tr>';
	}
}

echo '</table>';


if ( $total_team_profit > 0 && $total_team_load > 0 && $total_team_goals > 0 ) :
	
	$total_team_average  += $total_team_profit / $total_team_load;
	$total_team_complete = ( + $total_team_profit / + $total_team_goals ) * 100;
	$total_team_complete = number_format( $total_team_complete, 2 );

	$total_team_left = $total_team_goals - $total_team_profit;

	if ( $total_team_left < 0 ) {
		$total_team_left = 0;
	}
	
	echo '<h2>Total team</h2>';
	
	echo '<table class="table-stat total" border="1" cellpadding="5" cellspacing="0">';
	echo '<tr class="text-center">';
	echo '<th>Loads</th>';
	echo '<th>Profit</th>';
	echo '<th>Goal</th>';
	echo '<th>Left</th>';
	echo '<th>Average Profit</th>';
	echo '<th>Completed</th>';
	echo '</tr>';
	echo '<tr class="text-center">';
	echo '<td>' . $total_team_load . '</td>';
	echo '<td>$' . number_format( $total_team_profit, 2 ) . '</td>';
	echo '<td>$' . number_format( $total_team_goals, 2 ) . '</td>';
	echo '<td>$' . number_format( $total_team_left, 2 ) . '</td>';
	echo '<td>$' . number_format( $total_team_average, 2 ) . '</td>';
	echo '<td>' . number_format( $total_team_complete, 2 ) . '%</td>';
	echo '</tr>';
	echo '</table>';

endif;