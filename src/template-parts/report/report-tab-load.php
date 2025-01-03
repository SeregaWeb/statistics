<?php
$helper        = new TMSReportsHelper();
$TMSUsers      = new TMSUsers();

$states        = $helper->get_states();
$statuses      = $helper->get_statuses();
$sources       = $helper->get_sources();
$instructions  = $helper->get_instructions();
$types         = $helper->get_load_types();
$dispatchers   = $helper->get_dispatchers();
$post_id       = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;
$report_object = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;

$read_only = false;
$full_view_only = get_field_value($args, 'full_view_only');
$post_status               = 'draft';

$date_booked         = '';
$dispatcher_initials = '';
$reference_number    = '';
$unit_number_name    = '';
$booked_rate         = '';
$driver_rate         = '';
$driver_phone        = '';
$profit              = '';
$pick_up_date        = '';
$load_status         = '';
$instructions_val    = array();
$source_val          = '';
$load_type           = '';
$commodity           = '';
$weight              = '';
$notes               = '';
$proof_of_delivery   = '';
$modifi_price = '';
$factoring_status = '';
$tbd = false;

$processing_fees = 0;
$type_pay_method = '';
$percent_quick_pay = 0;


if ( $report_object ) {
	$values = $report_object;
    $meta = get_field_value($values, 'meta');
    $main = get_field_value($values, 'main');
	if ( is_array( $meta ) && sizeof( $meta ) > 0 ) {
		$date_booked         = get_field_value($main, 'date_booked');
		if (empty($date_booked) || $date_booked = '0000-00-00 00:00:00') {
			$date_booked_formatted = date('Y-m-d');
		} else {
			$date_booked_formatted = date('Y-m-d', strtotime($date_booked));
		}
  
		$pick_up_date        = get_field_value($main, 'pick_up_date');
		$pick_up_date_formatted = date('Y-m-d', strtotime($pick_up_date));
        
        $delivery_date        = get_field_value($main, 'delivery_date');
		$delivery_date_formatted = date('Y-m-d', strtotime($delivery_date));
		
        $dispatcher_initials = get_field_value($meta, 'dispatcher_initials');
		$reference_number    = get_field_value($meta, 'reference_number');
		$unit_number_name    = get_field_value($meta, 'unit_number_name');
		$booked_rate         = get_field_value($meta, 'booked_rate');
		$driver_rate         = get_field_value($meta, 'driver_rate');
		$driver_phone        = get_field_value($meta, 'driver_phone');
		$profit              = get_field_value($meta, 'profit');
		$load_status         = get_field_value($meta, 'load_status');
        $instructions_str    = str_replace(' ', '',get_field_value($meta, 'instructions'));
        $instructions_val    = explode(',', $instructions_str);
		$source_val          = get_field_value($meta, 'source');
		$load_type           = get_field_value($meta, 'load_type');
		$commodity           = get_field_value($meta, 'commodity');
		$weight              = get_field_value($meta, 'weight');
		$notes               = get_field_value($meta, 'notes');
        $modifi_price        = get_field_value($meta, 'booked_rate_modify');
		$proof_of_delivery   = get_field_value($meta, 'proof_of_delivery');
		
		$processing_fees = get_field_value($meta, 'processing_fees');
		$type_pay_method = get_field_value($meta, 'type_pay');
		$percent_quick_pay = get_field_value($meta, 'percent_quick_pay');
		$processing = get_field_value($meta, 'processing');
		$tbd = get_field_value($meta, 'tbd');
  
		$post_status         = get_field_value( $main, 'status_post' );
		
		$factoring_status = get_field_value($meta, 'factoring_status');
        
        if ($driver_rate && $profit && !$booked_rate) {
            $booked_rate = '0.00';
            
            if (!$modifi_price) {
                $modifi_price = '0.00';
            }
        }
	}
}

if (!is_numeric($proof_of_delivery)) {
    $disable_status = 'disabled';
}

$read_only = $TMSUsers->check_read_only($post_status);
?>
<form class="form-group <?php echo !$full_view_only ? 'js-add-new-report' : ''; ?>" >
	
	<?php if ( $read_only ): ?>
        <input type="hidden" name="read_only" value="true">
	<?php endif; ?>
 
	<?php if ( isset( $post_id ) && is_numeric( $post_id ) ): ?>
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
	<?php endif; ?>
    
    <?php if ($post_status): ?>
        <input type="hidden" name="post_status" value="<?php echo $post_status; ?>">
    <?php endif; ?>
    
    <div class="row">
        <h3 class="display-6 mb-4">Load info</h3>

        <div class="col-12">
            <p class="h5">Load</p>
        </div>

        <div class="mb-2 col-12 col-xl-4">
            <label for="reference_number" class="form-label">Reference Number</label>
            <?php if (!$read_only): ?>
            <input type="text" name="reference_number" value="<?php echo $reference_number; ?>" class="form-control"
                   required>
            <?php else: ?>
                <p class="m-0"><strong><?php echo $reference_number ?></strong></p>
            <?php endif; ?>
        </div>
        

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="booked_rate" class="form-label">Booked Rate</label>
          
            <?php if ($full_view_only || $factoring_status === 'charge-back'):
                ?>
                <p class="m-0"><strong>$<?php echo $modifi_price ?></strong></p>
            <?php else: ?>
            
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" value="<?php echo $booked_rate; ?>" name="booked_rate"
                       class="form-control js-money js-all-value" required>
                
                <?php if ($type_pay_method === 'quick-pay' && $processing === 'direct'):
                    echo $helper->get_warning_icon();
                endif; ?>
            </div>
            
                <?php
                if ($modifi_price && ($percent_quick_pay > 0 || $processing_fees > 0)):
                        if (!$percent_quick_pay || !is_numeric($percent_quick_pay)) {
	                        $percent_quick_pay = 0;
                        }
	                
                        if (!$processing_fees || !is_numeric($processing_fees)) {
	                        $processing_fees = 0;
                        }
                        
                        ?>
                        <?php if ($processing === 'direct'):
                            echo '<strong class="mt-1 d-block js-update-mod-price">$'.$modifi_price.' <span class="text-small"> price including quick pay  '.$percent_quick_pay.'% and processing fees $'.$processing_fees.'</span></strong>';
                        endif; ?>
                <?php endif;?>

                <input type="hidden" class="js-old_value_booked_rate" name="old_value_booked_rate" value="<?php echo $booked_rate; ?>">
            
                <input type="hidden" class="js-processing_fees"  name="processing_fees" value="<?php echo $processing_fees; ?>">
                <input type="hidden" class="js-type_pay"  name="type_pay" value="<?php echo $type_pay_method; ?>">
                <input type="hidden" class="js-percent_quick_pay" name="percent_quick_pay" value="<?php echo $percent_quick_pay; ?>">
                <input type="hidden" class="js-processing" name="processing" value="<?php echo $processing; ?>">
            
            <?php endif; ?>
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="load_status" class="form-label">Load Status</label>
	        
	        <?php if ($full_view_only): ?>
                <p class="m-0"><strong><?php echo $statuses[$load_status]; ?></strong></p>
	        <?php else: ?>
            
            <select name="load_status" class="form-control form-select" required>
	            <?php if (!$read_only): ?>
                <option value="">Select status</option>
                <?php endif; ?>
                
				<?php if ( is_array( $statuses ) ): ?>
					<?php foreach ( $statuses as $key => $status ): ?>
                        <option <?php echo $load_status === $key ? 'selected' : ''; echo isset($disable_status) && $key === 'delivered' ? 'disabled' : ''; ?> value="<?php echo $key; ?>">
							<?php echo $status; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>
            <?php endif; ?>

            <input type="hidden" name="old_load_status" value="<?php echo $load_status; ?>">
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="load_type" class="form-label">Load Type</label>
            <?php if(!$read_only): ?>
            <select name="load_type" class="form-control form-select" required>
                <option value="">Load type</option>
				<?php if ( is_array( $types ) ): ?>
					<?php foreach ( $types as $key => $type ): ?>
                        <option <?php echo $load_type === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
							<?php echo $type; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>

            <?php else: ?>
            <p class="m-0"><strong><?php echo $load_type; ?></strong></p>
	        <?php endif; ?>
        </div>

        <div class="col-12 mt-5">
            <p class="h5">Dispatcher</p>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="dispatcher_initials" class="form-label">Dispatcher Initials</label>
	        
	        <?php if ($full_view_only):
		        $user_name = $helper->get_user_full_name_by_id($dispatcher_initials);
                ?>
                <p class="m-0"><strong><?php echo $user_name['full_name']; ?></strong></p>
	        <?php else:
            
            if ( current_user_can('dispatcher') || current_user_can('dispatcher-tl') ) {
	            if (!$dispatcher_initials) {
                    $dispatcher_initials = get_current_user_id();
		            $user_name = $helper->get_user_full_name_by_id($dispatcher_initials);
		            
                    ?>
                    <input type="hidden" name="dispatcher_initials" value="<?php echo $dispatcher_initials; ?>" required>
                    <p class="text-primary"><?php echo $user_name['full_name']; ?></p>
                    <?php
                } else {
		            $user_name = $helper->get_user_full_name_by_id($dispatcher_initials);
                    ?>
                    <input type="hidden" name="dispatcher_initials" value="<?php echo $dispatcher_initials; ?>" required>
                    <p class="text-primary"><?php echo $user_name['full_name']; ?></p>
                    <?php
	            }
            } else {  ?>
                <select name="dispatcher_initials" class="form-control form-select" required>
                    <option value="">Select dispatcher</option>
                    <?php if (is_array($dispatchers)): ?>
                        <?php foreach ($dispatchers as $dispatcher):  ?>
                                <option value="<?php echo $dispatcher['id']; ?>" <?php echo strval($dispatcher_initials) === strval($dispatcher['id']) ? 'selected' : ''; ?> >
                                    <?php echo $dispatcher['fullname']; ?>
                                </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            <?php } ?>
            
            <?php endif; ?>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="date_booked" class="form-label">Date Booked</label>
            <?php if(!$read_only): ?>
                <input type="date" name="date_booked" value="<?php echo $date_booked_formatted; ?>" class="form-control" required>
            <?php else: ?>
                <p class="m-0"><strong><?php echo $date_booked_formatted; ?></strong></p>
                <input type="hidden" name="date_booked" value="<?php echo $date_booked; ?>">
	        <?php endif; ?>
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="source" class="form-label">Source</label>
	        <?php if(!$read_only): ?>
            <select name="source" class="form-control form-select" required>
                <option value="">Select source</option>
				<?php if ( is_array( $sources ) ): ?>
					<?php foreach ( $sources as $key => $source ): ?>
                        <option <?php echo $source_val === $key ? 'selected' : ''; ?> value="<?php echo $key; ?>">
							<?php echo $source; ?>
                        </option>
					<?php endforeach; ?>
				<?php endif ?>
            </select>

            <?php else: ?>
            <p class="m-0"><strong><?php echo $source_val; ?></strong></p>
	        <?php endif; ?>
        </div>

        <div class="col-12 mt-5">
            <p class="h5">Driver</p>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <div class="form-check form-switch">
                <input class="form-check-input js-tbd" <?php echo $tbd ? 'checked' : ''; ?> type="checkbox" name="tbd" id="tbd">
                <label class="form-check-label" for="tbd">TBD</label>

                <?php if($tbd): ?>
                    <input type="hidden" name="old_tbd" value="1">
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12"></div>
	    
	    <?php if ($full_view_only): ?>
            <div class="mb-2 col-12 col-md-6 col-xl-4">
                <label for="unit_number_name" class="form-label">Unit Number & Name</label>
                <p class="m-0"><strong><?php echo $unit_number_name; ?></strong></p>
            </div>

            <div class="mb-2 col-12 col-md-6 col-xl-4">
                <label for="driver_rate" class="form-label">Driver Rate</label>
                <p class="m-0"><strong>$<?php echo $driver_rate; ?></strong></p>
            </div>

            <div class="col-12"></div>
        
            <div class="mb-2 col-12 col-md-6 col-xl-4">
                <label for="driver_phone" class="form-label">Driver phone</label>
                <p class="m-0"><strong><?php echo $driver_phone; ?></strong></p>
            </div>
          

            <div class="col-12 col-md-6 col-xl-4">
                <label for="profit" class="form-label">Profit</label>
                <p class="m-0"><strong>$<?php echo $profit; ?></strong></p>
            </div>

            <div class="col-12"></div>

            <div class="mb-2 col-12 col-md-6 col-xl-4">
                <label for="pick_up_date" class="form-label">Pick Up Date</label>
                <p class="m-0"><strong><?php echo $pick_up_date_formatted; ?></strong></p>
            </div>
        
            <div class="mb-2 col-12 col-md-6 col-xl-4">
                <label for="pick_up_date" class="form-label">Delivery Date</label>
                <p class="m-0"><strong><?php echo $delivery_date_formatted; ?></strong></p>
            </div>
	    <?php else: ?>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="unit_number_name" class="form-label">Unit Number & Name</label>
            <div class="d-flex gap-1 js-container-number">
            <input type="text" name="unit_number_name" <?php echo $tbd ? 'readonly' : ''; ?> data-value="<?php echo $unit_number_name; ?>" value="<?php echo $unit_number_name; ?>" class="form-control" required>
            <button class="btn btn-primary js-fill-driver">Fill</button>
            </div>
            <input type="hidden" name="old_unit_number_name" value="<?php echo $unit_number_name; ?>" >
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="driver_rate" class="form-label">Driver Rate</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" name="driver_rate" <?php echo $tbd ? 'readonly' : ''; ?> data-value="<?php echo $driver_rate; ?>" value="<?php echo $tbd ? 0 : $driver_rate; ?>"
                       class="form-control js-money js-driver-value" required>

                <input type="hidden" class="js-old_value_driver_rate" name="old_value_driver_rate" value="<?php echo $driver_rate; ?>">
            </div>
        </div>

        <div class="col-12"></div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="driver_phone" class="form-label ">Driver phone</label>
            <input type="text" data-value="<?php echo $driver_phone; ?>" <?php echo $tbd ? 'readonly' : ''; ?> name="driver_phone" value="<?php echo $driver_phone; ?>" class="form-control js-tel-mask js-phone-driver" required>
            <input type="hidden" name="old_driver_phone" value="<?php echo $driver_phone; ?>">
        </div>

        <div class="col-12 col-md-6 col-xl-4">
            <label for="profit" class="form-label">Profit</label>
            <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="text" name="profit" data-value="<?php echo $profit; ?>" value="<?php echo $tbd ? 0 : $profit; ?>" readonly
                       class="form-control js-money js-money-total" required>
            </div>
        </div>

        <div class="col-12"></div>


        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="pick_up_date" class="form-label">Pick Up Date</label>
            <input type="date" name="pick_up_date" value="<?php echo $pick_up_date_formatted; ?>" class="form-control" required>
            <input type="hidden" name="old_pick_up_date" value="<?php echo $pick_up_date_formatted; ?>">
        </div>
        
        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="pick_up_date" class="form-label">Delivery Date</label>
            <input type="date" name="delivery_date" value="<?php echo $delivery_date_formatted; ?>" class="form-control" required>
            <input type="hidden" name="old_delivery_date" value="<?php echo $delivery_date_formatted; ?>" class="form-control" required>
            
        </div>

        <?php endif; ?>
        
        <div class="col-12">
            <label class="form-label mt-3">Instructions</label>
        </div>
	    
	    <?php if ($full_view_only): ?>
            <div class="col-12 col-xl-8 d-flex flex-wrap ">
			    <?php
			    if ( is_array( $instructions ) ): ?>
				    <?php foreach ( $instructions as $key => $instruction ):
					    $checked = array_search($key, $instructions_val);
					    ?>
                        <div class="form-check form-switch p-0 col-12 col-md-6 col-xl-4">
                            <input disabled class="form-check-input disabled ml-0" <?php echo is_numeric($checked) ? 'checked': ''; ?> name="instructions[]" value="<?php echo $key; ?>"
                                   type="checkbox" id="flexSwitchCheckDefault_<?php echo $key; ?>">
                            <label class="form-check-label ml-2"
                                   for="flexSwitchCheckDefault_<?php echo $key; ?>"><?php echo $instruction; ?>
                            </label>
                        </div>
				    <?php endforeach; ?>
			    <?php endif ?>
            </div>

            <div class="col-12 mt-5">
                <p class="h5">Additional information about the cargo</p>
            </div>

            <div class="mb-2 col-12 col-md-6 col-xl-4">
                <label for="commodity" class="form-label">Commodity</label>
                <p class="m-0"><strong><?php echo $commodity; ?></strong></p>
            </div>

            <div class="mb-2 col-12 col-md-6 col-xl-4">
                <label for="weight" class="form-label">Weight (lbs)</label>
                <p class="m-0"><strong><?php echo $weight; ?></strong></p>
            </div>

            <div class="mb-5 col-12 col-xl-8">
                <label for="notes" class="form-label">Notes</label>
                <p class="m-0"><strong><?php echo $notes; ?></strong></p>
            </div>
	    <?php else: ?>
        
        <div class="col-12 col-xl-8 d-flex flex-wrap ">
			<?php
            if ( is_array( $instructions ) ): ?>
				<?php foreach ( $instructions as $key => $instruction ):
                    $checked = array_search($key, $instructions_val);
                    ?>
                    <div class="form-check form-switch p-0 col-12 col-md-6 col-xl-4">
                        <input class="form-check-input ml-0" <?php echo is_numeric($checked) ? 'checked': ''; ?> name="instructions[]" value="<?php echo $key; ?>"
                               type="checkbox" id="flexSwitchCheckDefault_<?php echo $key; ?>">
                        <label class="form-check-label ml-2"
                               for="flexSwitchCheckDefault_<?php echo $key; ?>"><?php echo $instruction; ?>
                        </label>
                    </div>
				<?php endforeach; ?>

                <input type="hidden" name="old_instructions" value="<?php echo $instructions_str; ?>">
			<?php endif ?>
        </div>

        <div class="col-12 mt-5">
            <p class="h5">Additional information about the cargo</p>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="commodity" class="form-label">Commodity</label>
            <input type="text" value="<?php echo $commodity; ?>" name="commodity" class="form-control" required>
        </div>

        <div class="mb-2 col-12 col-md-6 col-xl-4">
            <label for="weight" class="form-label">Weight (lbs)</label>
            <input type="number" step="0.1" name="weight" value="<?php echo $weight; ?>" class="form-control" required>
            <input type="hidden" name="old_weight" value="<?php echo $weight; ?>">
        </div>

        <div class="mb-5 col-12 col-xl-8">
            <label for="notes" class="form-label">Notes</label>
            <textarea name="notes" class="form-control"><?php echo $notes; ?></textarea>
        </div>
	    
	    <?php endif; ?>
        

        <div class="col-12" role="presentation">
            <div class="justify-content-start gap-2">
                <button type="button" data-tab-id="pills-customer-tab"
                        class="btn btn-dark js-next-tab">Previous
                </button>
                <?php if ($full_view_only): ?>
                    <button type="button" data-tab-id="pills-trip-tab"
                            class="btn btn-primary js-next-tab">Next
                    </button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary js-submit-and-next-tab" data-tab-id="pills-trip-tab">Next
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

</form>
