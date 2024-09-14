<?php

$company = new TMSReportsCompany();
$reports = new TMSReports();
$helper  = new TMSReportsHelper();

// tab 1
$value_contact_name      = '';
$value_contact_phone     = '';
$value_contact_email     = '';
$value_company_name      = '';
$template_select_company = '';
$set_up_platform_val     = '';
$set_up_val              = '';

$set_up_platform = $helper->get_set_up_platform();
$set_up          = $helper->get_set_up();

$report_object = ! empty( $args[ 'report_object' ] ) ? $args[ 'report_object' ] : null;
$post_id       = ! empty( $args[ 'post_id' ] ) ? $args[ 'post_id' ] : null;

$additional_contacts_isset = false;

if ( $report_object ) {
	$values = $report_object;
	
	if ( is_array( $values ) && sizeof( $values ) > 0 ) {
		$id_customer     = $values[ 0 ]->customer_id;
		$current_company = $company->get_company_by_id( $id_customer );
		
		$value_contact_name    = $values[ 0 ]->contact_name;
		$value_contact_phone   = $values[ 0 ]->contact_phone;
		$value_contact_email   = $values[ 0 ]->contact_email;
		$set_up_val            = $values[ 0 ]->set_up;
		$set_up_platform_val   = $values[ 0 ]->set_up_platform;
		$additional_contacts_json = $values[ 0 ]->additional_contacts;
  
		if ( $current_company ) {
            if ($additional_contacts_json) {
	            $additional_contacts = json_decode($additional_contacts_json, ARRAY_A);
                if (!empty($additional_contacts)) {
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
}
?>

<h3 class="display-6 mb-4">Customer</h3>
<form class="js-create-not-publish-report">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-8 p-0 js-result-search-wrap">
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
                           class="form-control js-search-company">
                    <ul class="my-dropdown-search js-container-search-list">

                    </ul>
                </div>
                <div class="result-search js-result-search">
					<?php echo $template_select_company; ?>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mt-3">
                        <label for="set_up" class="form-label">Set up<span
                                    class="required-star text-danger">*</span></label>
                        <select name="set_up" class="form-control form-select" required>
                            <option value="">Select set up</option>
							<?php if ( is_array( $set_up ) ): ?>
								<?php foreach ( $set_up as $key => $val ): ?>
                                    <option <?php echo $set_up_val === $key ? 'selected' : ''; ?>
                                            value="<?php echo $key; ?>">
										<?php echo $val; ?>
                                    </option>
								<?php endforeach; ?>
							<?php endif ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6 mt-3">
                        <label for="set_up_platform" class="form-label">Set up platform<span
                                    class="required-star text-danger">*</span></label>
                        <select name="set_up_platform" class="form-control form-select" required>
                            <option value="">Select platform</option>
							<?php if ( is_array( $set_up_platform ) ): ?>
								<?php foreach ( $set_up_platform as $key => $val ): ?>
                                    <option <?php echo $set_up_platform_val === $key ? 'selected' : ''; ?>
                                            value="<?php echo $key; ?>">
										<?php echo $val; ?>
                                    </option>
								<?php endforeach; ?>
							<?php endif ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 p-0 pl-md-5 mt-3 mt-md-0">
                <h4>Contact</h4>

                <div class="form-group mt-3">
                    <label for="contact-input-firstname" class="form-label">Contact Name
                        <span class="required-star text-danger">*</span></label>
                    <input id="contact-input-firstname" required type="text"
                           name="contact_name"
                           value="<?php echo $value_contact_name; ?>" placeholder="Name"
                           class="form-control">
                </div>

                <div class="form-group mt-3">
                    <label for="contact-input-phone" class="form-label">Phone Number
                        <span class="required-star text-danger">*</span></label>
                    <input id="contact-input-phone" required type="tel"
                           name="contact_phone"
                           value="<?php echo $value_contact_phone; ?>"
                           placeholder="Phone" class="form-control">
                </div>

                <div class="form-group mt-3">
                    <label for="contact-input-email" class="form-label">Email <span
                                class="required-star text-danger">*</span></label>
                    <input id="contact-input-email"
                           value="<?php echo $value_contact_email; ?>" type="email"
                           required name="contact_email" placeholder="Email"
                           class="form-control">
                </div>

                <button class="btn mt-3 js-toggle btn-outline-secondary <?php echo ($additional_contacts_isset) ? 'active' : '';  ?>" data-block-toggle="js-additional-contact">Add
                    additional contacts
                </button>
            </div>

            <div class="col-12 col-lg-8">
                <div class="additional-contacts js-additional-contact row <?php echo ($additional_contacts_isset) ? '' : 'd-none';  ?>">
                    <div class="col-12">
                        <h4>Additional Contact</h4>
                    </div>
                    <div class="form-group mt-3 col-12 col-md-4">
                        <label for="contact-input-firstname" class="form-label">Contact Name</label>
                        <input id="contact-input-firstname" type="text"
                               name="additional_form_contact_name"
                               placeholder="Name"
                               class="form-control">
                    </div>

                    <div class="form-group mt-3 col-12 col-md-4">
                        <label for="contact-input-phone" class="form-label">Phone Number</label>
                        <input id="contact-input-phone" type="tel"
                               name="additional_form_contact_phone"
                               placeholder="Phone" class="form-control">
                    </div>

                    <div class="form-group mt-3 col-12 col-md-4">
                        <label for="contact-input-email" class="form-label">Email</label>
                        <input id="contact-input-email"
                               type="email"
                               name="additional_form_contact_email" placeholder="Email"
                               class="form-control">
                    </div>
                    <div class="d-flex justify-content-start gap-2 mt-3">
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

                    <div class="js-additional-contact-wrap">
                        
                        <?php
                        if (isset($additional_contacts) && is_array($additional_contacts)):
                            foreach ($additional_contacts as $contact_val):
                        ?>

                                <div class="additional-card js-additional-card">
                                    <input type="text" name="additional_contact_name[]" readonly value="<?php echo $contact_val['name']; ?>"
                                           class="form-control js-additional-field-name">
                                    <input type="text" name="additional_contact_phone[]" readonly value="<?php echo $contact_val['phone']; ?>"
                                           class="form-control js-additional-field-phone">
                                    <input type="text" name="additional_contact_email[]" readonly
                                           value="<?php echo $contact_val['email']; ?>" class="form-control js-additional-field-email">
                                    <button class="additional-card__edit js-edit-contact">
			                            <?php echo $reports->get_icon_edit(); ?>
                                    </button>
                                    <button class="additional-card__remove js-remove-contact">
			                            <?php echo $reports->get_close_icon(); ?>
                                    </button>
                                </div>
                        
                        <?php endforeach;
                        endif; ?>
                    </div>
                </div>

            </div>

            <div class="col-12 pl-0 mt-3" role="presentation">
                <div class="justify-content-start gap-2">
                    <a type="button" href="<?php echo home_url(); ?>"
                       class="btn btn-dark">Cancel</a>
                    <button type="submit" class="btn btn-primary js-submit-and-next-tab"
                            data-tab-id="pills-load-tab">Next
                    </button>
                </div>
            </div>
        </div>
    </div>
	<?php if ( isset( $post_id ) && is_numeric( $post_id ) ): ?>
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
	<?php endif; ?>
</form>