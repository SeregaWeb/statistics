<?php
/**
 * Template Name: Page contacts
 *
 * @package WP-rock
 * @since 4.4.0
 */

get_header();

$reports     = new TMSReports();
$TMSUsers    = new TMSUsers();
$TMSContacts = new TMSContacts();
$company     = new TMSReportsCompany();

$dispatchers         = $reports->get_dispatchers();
$dispatcher_initials = get_field_value( $_GET, 'dispatcher' );
$search              = get_field_value( $_GET, 'my_search' );

$show_filter = $TMSUsers->check_user_role_access( array(
	'administrator',
), true );

$args = array();

if ( $show_filter ) {
	$args[ 'dispatcher' ] = $dispatcher_initials;
}

if ( $search ) {
	$args[ 'search' ] = $search;
}

$access = $TMSUsers->check_user_role_access( array(
	'dispatcher',
	'dispatcher-tl',
	'administrator',
), true );

$data          = $TMSContacts->get_all_contacts( $args );
$results       = get_field_value( $data, 'data' );
$total_pages   = get_field_value( $data[ 'pagination' ], 'total_pages' );
$current_pages = get_field_value( $data[ 'pagination' ], 'current_page' );

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container">
                <div class="row">
                    <div class="col-12 pt-3">
						
						<?php if ( $access ) { ?>
                            <div class="d-flex justify-content-between ">
                                <div class="d-flex justify-content-start gap-4 align-items-center mb-4">
                                    <h2 class="">Contacts book</h2>
                                    <button class="btn btn-outline-primary js-open-popup-activator"
                                            data-href="#popup_contacts">
                                        Add a contact
                                    </button>
                                </div>
								<?php if ( $show_filter ): ?>
                                    <form class="flex-column align-items-end justify-content-end gap-1"
                                          id="navbarNavDarkDropdown">
                                        <div class="d-flex gap-1">
                                            <select class="form-select w-auto" name="dispatcher"
                                                    aria-label=".form-select-sm example">
                                                <option value="">Dispatcher</option>
												<?php if ( is_array( $dispatchers ) ): ?>
													<?php foreach ( $dispatchers as $dispatcher ): ?>
                                                        <option value="<?php echo $dispatcher[ 'id' ]; ?>" <?php echo strval( $dispatcher_initials ) === strval( $dispatcher[ 'id' ] )
															? 'selected' : ''; ?> >
															<?php echo $dispatcher[ 'fullname' ]; ?>
                                                        </option>
													<?php endforeach; ?>
												<?php endif; ?>
                                            </select>
                                            <!---->
                                            <!--                                        <input class="form-control w-auto" type="search" name="my_search"-->
                                            <!--                                               placeholder="Search"-->
                                            <!--                                               value="-->
											<?php //echo $search; ?><!--" aria-label="Search">-->
                                            <button class="btn btn-outline-dark" type="submit">Search</button>
											<?php if ( ! empty( $_GET ) ): ?>
                                                <a class="btn btn-outline-danger"
                                                   href="<?php echo get_the_permalink(); ?>">Reset</a>
											<?php endif; ?>
                                        </div>
                                    </form>
								<?php endif; ?>
                            </div>
                            <table class="table mb-5 w-100">
                                <thead>
                                <tr>
                                    <th>Company name</th>
                                    <th>Address</th>
                                    <th>Name</th>
                                    <th>Office number</th>
                                    <th>Direct number</th>
                                    <th>Email</th>
                                    <th>Number of loads</th>
                                    <th>Profit</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
								<?php if ( $results ):
									$statistics_for_contact = array();
									$ids_for_statistics = array();
									foreach ( $results as $result ):
										$ids_for_statistics[] = $result[ 'main_id' ];
									endforeach;
									
									$statistics_for_contact = $reports->get_profit_by_preset( $ids_for_statistics );
									
									foreach ( $results as $item ):
										$stat = isset( $statistics_for_contact[ 'brocker_' . $item[ 'main_id' ] ] )
											? $statistics_for_contact[ 'brocker_' . $item[ 'main_id' ] ]
											: array( 'total_posts' => 0, 'total_profit' => 0 );
										$value_company_name = $item[ 'company_name' ];
										$contact            = $item[ 'contact_first_name' ] . ' ' . $item[ 'contact_last_name' ];
										$phone              = $item[ 'phone_number' ];
										$email              = $item[ 'email' ];
										$name               = $item[ 'company_name' ];
										$address            = $item[ 'address1' ] . ', ' . $company->get_label_by_key( $item[ 'state' ] ) . ' ' . $item[ 'zip_code' ] . ', ' . $item[ 'country' ];
										$dot                = $item[ 'dot_number' ];
										$mc                 = $item[ 'mc_number' ];
										
										$template_select_company = $company->print_list_customers( $item[ 'company_id' ], $name, $address, $mc, $dot, $contact, $phone, $email );
										
										?>
                                        <tr>

                                            <td class="js-popup-edit-content d-none">
                                                <div class="row g-1">
                                                    <h4 class="md-1">Edit contact</h4>
                                                    <input type="hidden" name="main_id"
                                                           value="<?= esc_attr( $item[ 'main_id' ] ); ?>">
                                                    <div class="mb-2 js-result-search-wrap">
                                                        <label class="form-label">Select company <span
                                                                    class="text-danger">*</span></label>
                                                        <p class="form-label text-small">Enter company name or MC
                                                            number</p>
                                                        <div class="form-group position-relative js-container-search">
                                                            <input id="input-name" type="text" required
                                                                   name="company_name"
                                                                   value="<?= esc_attr( $item[ 'company_name' ] ?? '' ) ?>"
                                                                   placeholder="MC,DOT or Name"
                                                                   autocomplete="off"
                                                                   class="form-control js-search-company">
                                                            <ul class="my-dropdown-search js-container-search-list"></ul>
                                                        </div>
                                                        <div class="result-search js-result-search">
															<?php echo $template_select_company; ?>
                                                        </div>
                                                    </div>

                                                    <div class="mb-2 col-md-6 col-12">
                                                        <label class="form-label">Name <span
                                                                    class="text-danger">*</span></label>
                                                        <input type="text" name="name" class="form-control"
                                                               value="<?= esc_attr( $item[ 'name' ] ?? '' ) ?>"
                                                               required>
                                                    </div>

                                                    <div class="mb-2 col-md-5 col-12">
                                                        <label class="form-label">Office Number</label>
                                                        <input type="text" name="office_number"
                                                               class="form-control js-tel-mask"
                                                               value="<?= esc_attr( $item[ 'office_number' ] ?? '' ) ?>">
                                                    </div>

                                                    <div class="mb-2 col-md-1 col-3">
                                                        <label class="form-label">Ext</label>
                                                        <input type="text" name="direct_ext"
                                                               class="form-control "
                                                               value="<?= esc_attr( $item[ 'direct_ext' ] ?? '' ) ?>">
                                                    </div>

                                                    <div class="mb-2 col-md-6 col-9">
                                                        <label class="form-label">Direct Number</label>
                                                        <input type="text" name="direct_number"
                                                               class="form-control js-tel-mask"
                                                               value="<?= esc_attr( $item[ 'direct_number' ] ?? '' ) ?>">
                                                    </div>

                                                    <div class="mb-2 col-md-6 col-12">
                                                        <label class="form-label">Email <span
                                                                    class="text-danger">*</span></label>
                                                        <input type="email" name="email" class="form-control"
                                                               value="<?= esc_attr( $item[ 'direct_email' ] ?? '' ) ?>"
                                                               required>
                                                    </div>

                                                    <h5 class="mt-3">Support Contact (optional)</h5>

                                                    <div class="mb-2 col-md-4 col-12">
                                                        <label class="form-label">Support Contact</label>
                                                        <input type="text" name="support_contact" class="form-control"
                                                               value="<?= esc_attr( $item[ 'support_contact' ] ?? '' ) ?>">
                                                    </div>

                                                    <div class="mb-2 col-md-3 col-9">
                                                        <label class="form-label">Support Phone</label>
                                                        <input type="text" name="support_phone"
                                                               class="form-control js-tel-mask"
                                                               value="<?= esc_attr( $item[ 'support_phone' ] ?? '' ) ?>">
                                                    </div>

                                                    <div class="mb-2 col-md-1 col-3">
                                                        <label class="form-label">Ext</label>
                                                        <input type="text" name="support_ext" class="form-control"
                                                               value="<?= esc_attr( $item[ 'support_ext' ] ?? '' ) ?>">
                                                    </div>

                                                    <div class="mb-2 col-md-4 col-12">
                                                        <label class="form-label">Support Email</label>
                                                        <input type="email" name="support_email" class="form-control"
                                                               value="<?= esc_attr( $item[ 'support_email' ] ?? '' ) ?>">
                                                    </div>

                                                    <h5 class="mt-3">Additional Contacts (optional)</h5>

                                                    <div class="js-additional-contacts col-12">
														<?php if ( ! empty( $item[ 'additional_contacts' ] ) && is_array( $item[ 'additional_contacts' ] ) ): ?>
															<?php foreach ( $item[ 'additional_contacts' ] as $contact ): ?>
                                                                <div class="additional-contact row g-1 mt-2">
                                                                    <div class="col">
                                                                        <input type="text"
                                                                               name="additional_contact_name[]"
                                                                               class="form-control"
                                                                               placeholder="Name"
                                                                               value="<?= esc_attr( $contact[ 'contact_name' ] ) ?>">
                                                                    </div>
                                                                    <div class="col">
                                                                        <input type="text"
                                                                               name="additional_contact_phone[]"
                                                                               class="form-control  js-tel-mask"
                                                                               placeholder="Phone"
                                                                               value="<?= esc_attr( $contact[ 'contact_phone' ] ) ?>">
                                                                    </div>
                                                                    <div class="col-1">
                                                                        <input type="text"
                                                                               name="additional_contact_ext[]"
                                                                               class="form-control"
                                                                               placeholder="ext"
                                                                               value="<?= esc_attr( $contact[ 'contact_ext' ] ) ?>">
                                                                    </div>
                                                                    <div class="col">
                                                                        <input type="email"
                                                                               name="additional_contact_email[]"
                                                                               class="form-control"
                                                                               placeholder="Email"
                                                                               value="<?= esc_attr( $contact[ 'contact_email' ] ) ?>">
                                                                    </div>
                                                                    <div class="col-md-1 d-flex align-items-center">
                                                                        <button type="button"
                                                                                class="btn btn-outline-danger btn-sm js-remove-contact"
                                                                                title="Remove">
                                                                            &times;
                                                                        </button>
                                                                    </div>
                                                                </div>
															<?php endforeach; ?>
														<?php endif; ?>
                                                    </div>

                                                    <div class="mt-2">
                                                        <button type="button"
                                                                class="btn btn-outline-primary btn-sm js-add-contact-btn">
                                                            +
                                                            Add Additional Contact
                                                        </button>
                                                    </div>

                                                    <div class="mt-3">
                                                        <button type="submit" class="btn btn-success">
                                                            <span class="active-state">Save Contact</span>
                                                            <span class="disabled-state">
                                                            <span class="spinner-border spinner-border-sm" role="status"
                                                                  aria-hidden="true"></span>
                                                            Creating...
                                                        </span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <input type="hidden" name="id"
                                                       value="<?php echo esc_attr( $item[ 'id' ] ); ?>">
												<?php echo esc_html( $item[ 'company_name' ] ); ?>
                                            </td>
                                            <td><?php echo esc_html( $item[ 'city' ] ); ?>,
												<?php echo esc_html( $item[ 'state' ] ); ?>
												<?php echo esc_html( $item[ 'zip_code' ] ); ?></td>
                                            <td>
												<?php echo $item[ 'name' ]; ?>
                                            </td>
                                            <td><?php echo $item[ 'office_number' ]; ?></td>
                                            <td><?php echo $item[ 'direct_number' ]; ?></td>
                                            <td><?php echo $item[ 'direct_email' ]; ?></td>
                                            <td><?php echo $stat[ 'total_posts' ]; ?></td>
                                            <td><?php echo esc_html( '$' . $reports->format_currency( $stat[ 'total_profit' ] ) ); ?></td>
                                            <td class="d-flex justify-content-end gap-1">
                                                <button class="btn btn-outline-primary btn-sm js-open-popup-edit">Edit
                                                </button>

                                                <button class="btn btn-danger btn-sm js-remove-contact"
                                                        data-value="<?php echo $item[ 'main_id' ] ?>">Remove
                                                </button>
                                            </td>
                                        </tr>
									<?php endforeach;
								endif; ?>
                                </tbody>
                            </table>
							
							<?php
							
							
							echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'pagination', array(
								'total_pages'  => $total_pages,
								'current_page' => $current_pages,
							) ) );
							
						} else {
							echo $reports->message_top( 'error', "You don't have access to contacts" );
						} ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php

echo esc_html( get_template_part( TEMPLATE_PATH . 'popups/report', 'popup-edit-contact' ) );

do_action( 'wp_rock_before_page_content' );

if ( have_posts() ) :
	// Start the loop.
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;

do_action( 'wp_rock_after_page_content' );

get_footer();
