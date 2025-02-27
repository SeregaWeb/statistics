<?php

$company  = new TMSReportsCompany();
$reports  = new TMSReports();
$helper   = new TMSReportsHelper();
$TMSUsers = new TMSUsers();


// tab 1
$value_contact_name      = '';
$value_contact_phone     = '';
$value_contact_email     = '';
$value_company_name      = '';
$template_select_company = '';
$set_up_platform_val     = '';
$set_up_val              = '';
$value_contact_phone_ext = '';

$report_object             = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;
$post_id                   = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;
$post_status               = 'draft';
$read_only                 = false;
$additional_contacts_isset = false;

$full_view_only = false;

if ( $report_object ) {
	
	$full_view_only = get_field_value( $args, 'full_view_only' );
	
	$values = $report_object;
	$meta   = get_field_value( $values, 'meta' );
	$main   = get_field_value( $values, 'main' );
	
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
		$id_customer              = get_field_value( $meta, 'customer_id' );
		$current_company          = $company->get_company_by_id( $id_customer );
		$value_contact_name       = get_field_value( $meta, 'contact_name' );
		$value_contact_phone      = get_field_value( $meta, 'contact_phone' );
		$value_contact_phone_ext  = get_field_value( $meta, 'contact_phone_ext' );
		$value_contact_email      = get_field_value( $meta, 'contact_email' );
		$additional_contacts_json = get_field_value( $meta, 'additional_contacts' );
		$post_status              = get_field_value( $main, 'status_post' );
		if ( $current_company ) {
			if ( $additional_contacts_json ) {
				$additional_contacts = json_decode( $additional_contacts_json, ARRAY_A );
				if ( ! empty( $additional_contacts ) ) {
					$additional_contacts_isset = true;
				}
			}
			
			$current_array_company = $current_company[ 0 ];
			
			
			$value_company_name = $current_array_company->company_name;
			
			$contact = $current_array_company->contact_first_name . ' ' . $current_array_company->contact_last_name;
			$phone   = $current_array_company->phone_number;
			$email   = $current_array_company->email;
			$name    = $current_array_company->company_name;
			$address = $current_array_company->address1 . ', ' . $company->get_label_by_key( $current_array_company->state ) . ' ' . $current_array_company->zip_code . ', ' . $current_array_company->country;
			$dot     = $current_array_company->dot_number;
			$mc      = $current_array_company->mc_number;
			
			$template_select_company = $company->print_list_customers( $name, $address, $mc, $dot, $contact, $phone, $email, $current_array_company->id );;
		}
	}
	$read_only = $TMSUsers->check_read_only( $post_status );
}

?>

<h3 class="display-6 mb-4">Customer</h3>
<form class="<?php echo ! $full_view_only ? 'js-create-not-publish-report' : ''; ?>">
	
	<?php if ( $read_only ): ?>
        <input type="hidden" name="read_only" value="true">
	<?php endif; ?>

    <div class="container">
        <div class="row">
            <div class="col-12 col-md-8 pl-0 pr-0 pr-md-3 js-result-search-wrap">
				
				<?php if ( ! $read_only ): ?>

                    <figure>
                        <blockquote class="blockquote">
                            <p>Enter your customer's Motor Carrier # or name<span
                                        class="required-star text-danger">*</span></p>
                        </blockquote>
                        <figcaption class="blockquote-footer">
                            Find your customer in our database or <a
                                    class="js-open-popup-activator link-primary"
                                    href="#popup_add_company">click here</a> to add a new
                            customer.
                        </figcaption>
                    </figure>

                    <div class="form-group position-relative js-container-search">
                        <input id="input-name" type="text" required name="company_name"
                               value="<?php echo $value_company_name; ?>"
                               placeholder="MC,DOT or Name"
                               autocomplete="off"
                               class="form-control js-search-company">
                        <ul class="my-dropdown-search js-container-search-list">

                        </ul>
                    </div>
				<?php endif; ?>
                <div class="result-search js-result-search">
					<?php echo $template_select_company; ?>
                </div>

            </div>
            <div class="col-12 col-md-4 p-0 pl-md-5 mt-3 mt-md-0">
                <h4>Contact</h4>

                <div class="form-group mt-3">
                    <label for="contact-input-firstname" class="form-label">Contact Name
                        <span class="required-star text-danger">*</span></label>
					<?php if ( ! $read_only ): ?>
                        <input id="contact-input-firstname" required type="text"
                               name="contact_name"
                               value="<?php echo $value_contact_name; ?>" placeholder="Name"
                               class="form-control"/>
					<?php else: ?>
                        <p><strong><?php echo $value_contact_name; ?></strong></p>
					<?php endif; ?>
                </div>

                <div class="form-group mt-3">
                    <label for="contact-input-phone" class="form-label">Phone Number
                        <span class="required-star text-danger">*</span></label>
					
					<?php if ( ! $read_only ): ?>
                        <div class="d-flex gap-1">
                            <input id="contact-input-phone" required type="tel"
                                   name="contact_phone"
                                   value="<?php echo $value_contact_phone; ?>"
                                   placeholder="Phone" class="form-control js-tel-mask"/>

                            <input id="contact-input-phone_ext" style="max-width: 120px;" type="number"
                                   name="contact_phone_ext"
                                   value="<?php echo $value_contact_phone_ext; ?>"
                                   placeholder="ext." class="form-control"/>
                        </div>
					<?php else: ?>
                        <p><strong><?php echo $value_contact_phone; ?></strong>
                            ext:<strong><?php echo $value_contact_phone_ext ?></strong></p>
					<?php endif; ?>
                </div>

                <div class="form-group mt-3">
                    <label for="contact-input-email" class="form-label">Email <span
                                class="required-star text-danger">*</span></label>
					<?php if ( ! $read_only ): ?>
                        <input id="contact-input-email"
                               value="<?php echo $value_contact_email; ?>" type="email"
                               required name="contact_email" placeholder="Email"
                               class="form-control">
					
					<?php else: ?>
                        <p><strong><?php echo $value_contact_email; ?></strong></p>
					<?php endif; ?>
                </div>
				
				<?php if ( ! $read_only ): ?>
                    <button class="btn mt-3 js-toggle btn-outline-secondary <?php echo ( $additional_contacts_isset )
						? 'active' : ''; ?>" data-block-toggle="js-additional-contact">Add
                        additional contacts
                    </button>
				<?php endif; ?>
            </div>

            <div class="col-12 col-lg-8">
                <div class="additional-contacts js-additional-contact row <?php echo ( $additional_contacts_isset ) ? ''
					: 'd-none'; ?>">
                    <div class="col-12 p-1">
                        <h4>Additional Contact</h4>
                    </div>
					
					<?php if ( ! $read_only ): ?>

                        <div class="form-group mt-3 col-12 col-md-3 p-1">
                            <label for="contact-input-firstname" class="form-label">Contact Name</label>
                            <input id="contact-input-firstname" type="text"
                                   name="additional_form_contact_name"
                                   placeholder="Name"
                                   class="form-control">
                        </div>

                        <div class="form-group mt-3 col-12 col-md-3 p-1">
                            <label for="contact-input-phone" class="form-label ">Phone Number</label>
                            <input id="contact-input-phone" type="tel"
                                   name="additional_form_contact_phone"
                                   placeholder="Phone" class="form-control js-tel-mask">
                        </div>

                        <div class="form-group mt-3 col-12 col-md-2 p-1">
                            <label for="contact-input-phone" class="form-label ">Ext</label>
                            <input id="contact-input-ext" type="tel"
                                   name="additional_form_contact_phone_ext"
                                   placeholder="Ext" class="form-control">
                        </div>

                        <div class="form-group mt-3 col-12 col-md-4 p-1">
                            <label for="contact-input-email" class="form-label">Email</label>
                            <input id="contact-input-email"
                                   type="email"
                                   name="additional_form_contact_email" placeholder="Email"
                                   class="form-control">
                        </div>

                        <div class="d-flex justify-content-start gap-2 mt-3 p-1">
                            <button class="btn btn-primary js-add-additional-contact">
                                Add
                            </button>
                            <button class="btn btn-primary d-none js-edit-additional-contact">
                                End edit
                            </button>
                        </div>

                        <div class="col-12">
                            <hr class="mt-2 mb-0">
                        </div>
					<?php endif; ?>
                    <div class="js-additional-contact-wrap">
						
						<?php
						if ( isset( $additional_contacts ) && is_array( $additional_contacts ) ):
							foreach ( $additional_contacts as $contact_val ):
								?>
								<?php if ( is_array( $contact_val ) ): ?>
                                <div class="additional-card js-additional-card">
									
									<?php if ( ! $read_only ): ?>
										
										<?php
										$fields = [
											'name'  => [
												'name_attr' => 'additional_contact_name[]',
												'class'     => 'js-additional-field-name'
											],
											'phone' => [
												'name_attr' => 'additional_contact_phone[]',
												'class'     => 'js-additional-field-phone'
											],
											'ext'   => [
												'name_attr' => 'additional_contact_phone_ext[]',
												'class'     => 'js-additional-field-ext'
											],
											'email' => [
												'name_attr' => 'additional_contact_email[]',
												'class'     => 'js-additional-field-email'
											]
										];
										
										foreach ( $fields as $key => $attrs ) {
											$value = isset( $contact_val[ $key ] ) && ! empty( $contact_val[ $key ] )
												? $contact_val[ $key ] : 'unset';
											echo '<input type="text" name="' . esc_attr( $attrs[ 'name_attr' ] ) . '" readonly value="' . esc_attr( $value ) . '" class="form-control ' . esc_attr( $attrs[ 'class' ] ) . '">';
										}
										?>

                                        <button class="additional-card__edit js-edit-contact">
											<?php echo $reports->get_icon_edit(); ?>
                                        </button>
                                        <button class="additional-card__remove js-remove-contact">
											<?php echo $reports->get_close_icon(); ?>
                                        </button>
									<?php else: ?>
                                        <ul class="w-100 m-0">
                                            <li>
                                                <strong><?php echo $contact_val[ 'name' ]; ?></strong>
                                            </li>
                                            <li>
                                                <strong><?php echo $contact_val[ 'phone' ]; ?></strong>
                                            </li>
                                            <li>
                                                <strong><?php echo $contact_val[ 'ext' ]; ?></strong>
                                            </li>
                                            <li>
                                                <strong><?php echo $contact_val[ 'email' ]; ?></strong>
                                            </li>
                                        </ul>
									<?php endif; ?>
                                </div>
							<?php endif; ?>
							
							<?php endforeach;
						endif; ?>
                    </div>
                </div>

            </div>

            <div class="col-12 pl-0 mt-3" role="presentation">
                <div class="justify-content-start gap-2">
                    <a type="button" href="<?php echo home_url(); ?>"
                       class="btn btn-dark">Cancel</a>
					
					<?php if ( $full_view_only || $read_only ): ?>
                        <button type="button" data-tab-id="pills-load-tab"
                                class="btn btn-primary js-next-tab">Next
                        </button>
					<?php else: ?>
                        <button type="submit" class="btn btn-primary js-submit-and-next-tab"
                                data-tab-id="pills-load-tab">
							<?php echo ( ! isset( $post_id ) || ! is_numeric( $post_id ) ) ? 'Create' : 'Next'; ?>
                        </button>
					<?php endif; ?>
                </div>
            </div>
        </div>
    </div>
	
	<?php if ( isset( $post_id ) && is_numeric( $post_id ) ): ?>
        <input type="hidden" name="post_id" class="js-post-id" value="<?php echo $post_id; ?>"/>
        <input type="hidden" name="current_post_status" class="js-post-status" value="<?php echo $post_status; ?>"/>
	<?php endif; ?>

</form>
