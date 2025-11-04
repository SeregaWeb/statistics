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

// Check if user has both team leader and expedite manager
$my_teamlead = $statistics->get_my_team_leader();
$my_expedite_manager = $statistics->get_my_expedite_manager();
$has_both_managers = ! empty( $my_teamlead ) && ! empty( $my_expedite_manager );

$dispatchers_tl = $statistics->get_dispatchers_tl( $show_only_my_office ? $office_dispatcher : null );
$expedite_managers = $statistics->get_expedite_managers( $show_only_my_office ? $office_dispatcher : null );

$dispatchers_tl = array_merge( $dispatchers_tl, $expedite_managers );

// Security check: if user tries to access manager not in their list, reset to null
$available_manager_ids = array_column( $dispatchers_tl, 'id' );
if ( $dispatcher_tl_initials ) {
	if ( ! in_array( $dispatcher_tl_initials, $available_manager_ids ) ) {
		$dispatcher_tl_initials = null;
	}
}


// If user has both managers, filter to show only their managers
if ( $has_both_managers ) {
	$my_manager_ids = array_merge( $my_teamlead, $my_expedite_manager );
	$filtered_managers = array();
	
	foreach ( $dispatchers_tl as $manager ) {
		if ( in_array( $manager['id'], $my_manager_ids ) ) {
			$filtered_managers[] = $manager;
		}
	}
	
	$dispatchers_tl = $filtered_managers;
}


$dispatchers    = $statistics->get_dispatchers( $show_only_my_office ? $office_dispatcher : null, false, true );


if ( ! $dispatcher_tl_initials ) {
	if ( ! $hide_filter ) {
		// Priority: team leader first, then expedite manager
		if ( ! empty( $my_teamlead ) && is_array( $my_teamlead ) && count( $my_teamlead ) > 0 ) {
			$dispatcher_tl_initials = $my_teamlead[ 0 ];
		} elseif ( ! empty( $my_expedite_manager ) && is_array( $my_expedite_manager ) && count( $my_expedite_manager ) > 0 ) {
			$dispatcher_tl_initials = $my_expedite_manager[ 0 ];
		}
	}
	
	// Fallback to first available manager if still no value
	if ( ! $dispatcher_tl_initials && ! empty( $dispatchers_tl ) ) {
		$dispatcher_tl_initials = $dispatchers_tl[ 0 ][ 'id' ];
	}
}

// If user has both managers, allow them to switch between them
if ( $has_both_managers ) {
	$hide_filter = true;
}


// Get team members from the selected manager/leader only
$my_team = array();
$my_team[] = $dispatcher_tl_initials; // Add the selected manager/leader for statistics calculation

// Get team from the selected manager/leader
$manager_team = get_field( 'my_team', 'user_' . $dispatcher_tl_initials );
if ( ! empty( $manager_team ) && is_array( $manager_team ) ) {
	$my_team = array_merge( $my_team, $manager_team );
}

$exclude_mg_users = get_field( 'exclude_mg_users', get_the_ID() );
if ( ! empty( $exclude_mg_users ) && is_array( $exclude_mg_users ) ) {
	$my_team = array_diff( $my_team, $exclude_mg_users );
}
// var_dump($my_team);

$dispatcher_arr = $statistics->get_dispatcher_statistics_current_month( $my_team );

// if (current_user_can('administrator')) {
// 	var_dump($my_team);
// }

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

// if (current_user_can('administrator')) {
// 	var_dump($dispatcher_stats_indexed);
// }

foreach ( $dispatchers as $dispatcher ) {
	// Show dispatcher if they are in the team
	if ( $my_team !== null && is_array( $my_team ) && in_array( $dispatcher[ 'id' ], $my_team ) ) {
		$fullname = $dispatcher[ 'fullname' ];
		$stat     = [];
		// Если данные по диспетчеру есть в $dispatcher_stats_indexed, используем их, иначе нули
		
		// if (current_user_can('administrator')) {
		// 	var_dump($fullname);
		// }

		if ( isset( $dispatcher_stats_indexed[ $fullname ] ) ) {
			$stat           = $dispatcher_stats_indexed[ $fullname ];
			$post_count     = $stat[ 'post_count' ];
			$total_profit_raw = $stat[ 'total_profit' ];
			$total_profit   = rtrim(rtrim(number_format( $total_profit_raw, 2 ), '0'), '.');
			$average_profit = rtrim(rtrim(number_format( $stat[ 'average_profit' ], 2 ), '0'), '.');
			$goal           = $stat[ 'goal' ];
			$left           = $stat[ 'goal' ] - $total_profit_raw;
			$left           = is_numeric($left) ? rtrim(rtrim(number_format($left, 2), '0'), '.') : 0;
			$compleat_color = '';
			$text_color     = '#000000';
			
			if ( $left < 0 ) {
				$left = 0;
			}
			
			if ( is_numeric( $goal ) && $goal > 0 ) {
				$profit = isset( $total_profit_raw ) ? (float) $total_profit_raw : 0.0;
				$goal_v = (float) $goal;
				$value_pr = $goal_v > 0 ? ( $profit / $goal_v ) * 100 : 0;
				
				$goal_completion = rtrim(rtrim(number_format( $value_pr, 2 ), '0'), '.');
				
				
				if ( $value_pr >= 0 && $value_pr <= 80 ) {
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