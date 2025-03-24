<?php
/**
 * Template Name: Page add driver
 *
 * @package WP-rock
 * @since 4.4.0
 */

$dtiver       = new TMSDrivers();
$helperDriver = new TMSDriversHelper();
$helper       = new TMSReportsHelper();


$disabled_tabs  = 'disabled';
$driver_object  = '';
$status_publish = 'draft';

$report_object = null;
$post_id       = isset( $_GET[ 'driver' ] ) ? $_GET[ 'driver' ] : false;

if ( $post_id && is_numeric( $post_id ) ) {
	$driver_object = $dtiver->get_driver_by_id( $post_id );
	$main          = get_field_value( $driver_object, 'main' );
	$meta          = get_field_value( $driver_object, 'meta' );
	
	if ( is_array( $driver_object ) && sizeof( $driver_object ) > 0 ) {
		$disabled_tabs  = '';
		$status_publish = get_field_value( $main, 'status_post' );
	} else {
		wp_redirect( remove_query_arg( array_keys( $_GET ) ) );
		exit;
	}
	
}
$access         = true;
$full_only_view = false;


if ( $status_publish === 'draft' ) {
	$full_only_view = false;
}

get_header();

$logshow        = isset( $_COOKIE[ 'logshow' ] ) && + $_COOKIE[ 'logshow' ] !== 0 ? 'hidden-logs col-lg-1' : 'col-lg-3';
$logshowcontent = isset( $_COOKIE[ 'logshow' ] ) && + $_COOKIE[ 'logshow' ] !== 0 ? 'col-lg-11' : 'col-lg-9';

?>
    <div class="container-fluid">
        <div class="row">
            <div class="container js-section-tab">

                <div class="row">
                    <pre class="col-12 d-none">
Contact:

Driver name: (John Doe)
Driver Phone number: (000) 000-0000
Driver email: (driver@driver.com)
Home location: (city, state / example: Houston, TX)
Date of birth: (01/20/1995)
Language: (набор из English, French, Spanish, Portuguese, Russian, Ukrainian, Arabic)
MacroPoint: [switch] опционально
Trucker Tools: [switch] опционально

Team driver [Optional switch]  (Все последующее опционально, только если выбран Team driver)
Team driver name: (John Doe)
Team driver phone number: (000) 000-0000
Team driver email: (driver@driver.com)
Date of birth: (01/20/1995)
MacroPoint: [switch] опционально
Trucker Tools: [switch] опционально

Owner [Optional switch]  (Все последующее опционально, только если выбран Owner)
Owner name: John Doe
Type: (drop-down menu:
Owner phone number: (000) 000-0000
Owner email: (owner@driver.com)
Date of birth: (01/20/1995)
MacroPoint: [switch] опционально
Trucker Tools: [switch] опционально

Emergency contact: Jane Doe (Это и последующее обязательно)
Emergency number: (000) 000-0000
Relation: (drop-down menu: wife / husband / fiancé / fiancée / mother / father / son / daughter / friend / other relative)
                    </pre>
                    <pre class="col-12 d-none">
                        Vehicle:

Type: [drop-down menu: Cargo van / Sprinter van / Box truck / Pickup / Reefer / Dry van)
Make:
Model:
Vehicle year:

Pictures of vehicle: [upload box]

GVWR: (11,000 LBS) (Опционально, только если в Type выбран Box Truck или Dry van)
GVWR placard: [upload box] (Опционально, только если в Type выбран Box Truck или Dry van)

Payload: (3,500 LBS)
Dimensions: (000 / 00 / 00 '')
Dimensions pictures: [upload box]

VIN: (A0A0AAAA0AA000000)
Registration type: (drop-down menu: Vehicle registration / Bill of sale / Certificate of title)
File: [upload box]
Status: (drop-down menu: Valid / Temporary / Expired)
Expiration date: (01/20/2026)

Plates: (000 000)
Status: (drop-down menu: Valid / Temporary / Expired)
Plates pictures: [upload box]
Expiration date:  (01/20/2026)

PPE: [Switch] (Опционально) Если выбран PPE, открывается
File: [upload box]

E-Tracks: [Switch] (Опционально) Если выбран E-Tracks, открывается
File: [upload box]

Pallet Jack: [Switch] (Опционально) Если выбран Pallet Jack, открывается
File: [upload box]

Lift Gate: [Switch] (Опционально) Если выбран Lift Gate, открывается
File: [upload box]

Dolly: [Switch] (Опционально) Если выбран Dolly, открывается
File: [upload box]

Ramp: [Switch] (Опционально) Если выбран Ramp, открывается
File: [upload box]

Load bars: [Switch] (Опционально)

Printer: [Switch] (Опционально)

Sleeper: [Switch] (Опционально)
                    </pre>
                    <pre class="col-12 ">
Financial:

Account type: (drop-down menu: Business / Individual)
Account name: (text)

Payment instruction: (drop-down menu: Void check / Direct deposit form / bank statement)
File: [upload box]

W-9 classification: (drop-down menu: Business / Individual)
File: [upload box]
Address: (text)
City, state, zip: (text)

SSN: (000-00-0000) строгий формат 3 цифры - 2 цифры - 4 цифры. Обязательное поле которое
открывается только если в W-9 classification выбран вариант Individual
SSN name: (text) Обязательное поле которое открывается только если в W-9 classification выбран
вариант Individual
File: [upload box] Обязательное поле которое открывается только если в W-9 classification выбран
вариант Individual

Entity name: (text) Обязательное поле которое открывается только если в W-9 classification
выбран вариант Business
EIN: (00-0000000) строгий формат 2 цифры - 7 цифр Обязательное поле которое открывается только
если в W-9 classification выбран вариант Business
EIN form: [upload box] Обязательное поле которое открывается только если в W-9 classification
выбран вариант Business

1099-NEC [upload box]
Authorized email: (text)
                    </pre>
                    <pre class="col-12 d-none">
Documents:

Driving record: [upload box]
Record notes: (text)

Driver licence type: [drop-down menu: Regular / CDL / Enhanced]
Real ID [Switch: Yes / No]
Driver licence: [upload box]
Expiration date: (01/20/2026)

Tanker endorsement [Switch] (Опционально и доступно только если в Driver licence type выбран вариант
CDL)
Hazmat endorsement: [Switch] (Опционально и доступно только если в Driver licence type выбран
вариант CDL)

Hazmat certificate: [Switch] (Опционально]
Если выбран Hazmat certificate, открывается
File: [upload box] и
Expiration date: (01/20/2026)

TWIC [Switch] (Опционально)
Если выбран TWIC, открывается
File: [upload box] и
Expiration date: (01/20/2026)

TSA approved: [Switch] (Опционально)
Если выбран TSA, approved открывается
File: [upload box] и
Expiration date: (01/20/2026)

Legal document type: [drop-down menu: US passport / Permanent residentship / Work authorisation /
Certificate of naturalization / Enhanced driver licence Real ID / No document] после выбора
открывается upload box.
Legal document: [Upload box]
Nationality: (поле для текста)

Immigration letter: [Switch] (Опционально)
Если выбран Immigration letter, открывается
File: [upload box] и
Expiration date: (01/20/2026)

Background check: [Switch] (Опционально)
Если выбран Background check, открывается
File: [upload box]
Date: (01/15/2025)

US — Canada transition proof: [Switch] (Опционально)
Если выбран US — Canada transition proof, открывается
File: [upload box]
Date: (01/15/2025)

Change 9 training: [Switch] (Опционально)
Если выбран Change 9 training, открывается
File: [upload box]
Date: (01/15/2025)

IC agreement: [upload box]

Insured (drop-down menu: Business / Individual)

Automobile Liability
Policy number: (text)
Expiration date: (01/20/2026)
Insurer: (text)
COI: [upload box]

Motor Truck Cargo
Policy number: (text)
Expiration date: (01/20/2026)
Insurer: (text)
COI: [upload box]

Status: (drop-down menu: Additional insured / Company not listed / Cancelled / Hold)
Cancellation date: (01/20/2026) Опционально, доступно только если в Status выбрано Cancelled

Insurance declaration [upload box] Опционально

Notes: (text)
                </pre>
                </div>

                <div class="row js-logs-wrap">
					
					<?php if ( $access ): ?>

                        <div class="col-12 js-logs-content <?php echo $logshowcontent; ?>">

                            <ul class="nav nav-pills gap-2 mb-3" id="pills-tab" role="tablist">
                                <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                    <button class="nav-link w-100 <?php echo $helper->change_active_tab( 'pills-driver-contact-tab', 'show', 'drivers' ); ?> "
                                            id="pills-driver-contact-tab" data-bs-toggle="pill"
                                            data-bs-target="#pills-driver-contact" type="button" role="tab"
                                            aria-controls="pills-driver-contact" aria-selected="true">Contact
                                    </button>
                                </li>
                                <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                    <button class="nav-link w-100 <?php echo $disabled_tabs; ?> <?php echo $helper->change_active_tab( 'pills-driver-vehicle-tab', 'show', 'drivers' ); ?> "
                                            id="pills-driver-vehicle-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#pills-driver-vehicle" type="button" role="tab"
                                            aria-controls="pills-driver-vehicle"
                                            aria-selected="false">Inforamtion
                                    </button>
                                </li>

                                <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                    <button class="nav-link w-100 <?php echo $disabled_tabs;
									echo $helper->change_active_tab( 'pills-driver-finance-tab', 'show', 'drivers' ); ?> "
                                            id="pills-driver-finance-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#pills-driver-finance" type="button" role="tab"
                                            aria-controls="pills-driver-finance" aria-selected="false">Financial
                                    </button>
                                </li>

                                <li class="nav-item js-change-url-tab flex-grow-1" role="presentation">
                                    <button class="nav-link w-100 <?php echo $disabled_tabs;
									echo $helper->change_active_tab( 'pills-driver-documents-tab', 'show', 'drivers' ); ?> "
                                            id="pills-driver-documents-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#pills-driver-documents" type="button" role="tab"
                                            aria-controls="pills-driver-documents" aria-selected="false">Documents
                                    </button>
                                </li>

                            </ul>

                            <div class="tab-content" id="pills-tabContent">

                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-contact-tab', 'show', 'drivers' ); ?>"
                                     id="pills-driver-contact" role="tabpanel"
                                     aria-labelledby="pills-driver-contact-tab">
									<?php
									echo esc_html( get_template_part( TEMPLATE_PATH . 'tabs/driver', 'tab-contact', array(
										'full_view_only' => $full_only_view,
										'report_object'  => $driver_object,
										'post_id'        => $post_id
									) ) );
									?>
                                </div>

                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-vehicle-tab', 'show' ); ?>"
                                     id="pills-driver-vehicle" role="tabpanel"
                                     aria-labelledby="pills-driver-vehicle-tab">
									<?php
									echo esc_html( get_template_part( TEMPLATE_PATH . 'tabs/driver', 'tab-information', array(
										'full_view_only' => $full_only_view,
										'report_object'  => $driver_object,
										'post_id'        => $post_id
									) ) );
									?>
                                </div>

                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-finance-tab', 'show' ); ?>"
                                     id="pills-driver-finance" role="tabpanel"
                                     aria-labelledby="pills-driver-finance-tab">
									<?php
									echo esc_html( get_template_part( TEMPLATE_PATH . 'tabs/driver', 'tab-finance', array(
										'full_view_only' => $full_only_view,
										'report_object'  => $driver_object,
										'post_id'        => $post_id
									) ) );
									?>

                                </div>

                                <div class="tab-pane fade <?php echo $helper->change_active_tab( 'pills-driver-documents-tab', 'show' ); ?>"
                                     id="pills-driver-documents" role="tabpanel"
                                     aria-labelledby="pills-driver-documents-tab">


                                </div>

                            </div>
                        </div>

                        <div class="col-12 js-logs-container <?php echo $logshow; ?>">
							<?php
							if ( isset( $log_file ) && ! empty( $log_file ) ) {
								$file_url = wp_get_attachment_url( $log_file );
								if ( $file_url ) {
									?>
                                    <a class="file-btn" href="<?php echo $file_url; ?>" target="_blank"
                                       rel="noopener noreferrer">
										<?php echo $helper->get_file_icon(); ?>
                                        Open Log Archive
                                    </a>
									<?php
								}
							} else {
//								echo esc_html( get_template_part( TEMPLATE_PATH . 'report', 'logs', array(
//									'post_id' => $post_id,
//									'user_id' => get_current_user_id(),
//								) ) );
							}
							?>
                        </div>
					
					<?php else: ?>
                        <div class="col-12 col-lg-9 mt-3">
							<?php
							echo $helper->message_top( 'danger', $helper->messages_prepare( 'not-access' ) );
							?>
                        </div>
					<?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php
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
