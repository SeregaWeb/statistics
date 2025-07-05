<?php

$user_id    = get_current_user_id();
$user_meta  = get_userdata( $user_id );
$user_roles = $user_meta->roles[ 0 ];

if ( $user_roles === 'administrator' || $user_roles === 'accounting' || $user_roles === 'billing' ) {
	$bol = new TMSGenerateDocument();
	if ( $bol->_is_mpdf_exists( false ) ) {
		?>
        <form class="js-generate-invoice">
            <div class="generate-top">
                <h2 class="generate-title">Create invoice</h2>
            </div>
			
			<?php
			echo $bol->get_template_invoice( true );
			?>
            <div class="generate">
                <div class="generate-submit">
                    <button class="btn btn-primary btn-orange">Generate</button>
                </div>
            </div>
        </form>
	<?php } else { ?>
        <div class="generate-top">
            <h2 class="generate-title">MPDF - not install</h2>
        </div>
	<?php }
} else {
	echo '<h3 style="text-align: center; color: #000000; margin-top: 20px;">Role doesn\'t match</h3>';
}