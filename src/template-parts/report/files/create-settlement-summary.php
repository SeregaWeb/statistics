<?php
$TMSUsers = new TMSUsers();

$access = $TMSUsers->check_user_role_access( [
	'administrator',
	'recruiter',
	'recruiter-tl',
	'accounting',
	'moderator',
	'billing',
], true );

if ( $access ) {
	$settlement = new TMSGenerateDocument();
	if ( $settlement->_is_mpdf_exists( false ) ) {
		?>
        <form class="js-generate-settlement-summary">
            <div class="generate-top">
                <h2 class="generate-title">Create Settlement Summary</h2>
            </div>
			
			<?php
			echo $settlement->get_template_settlement_summary( true );
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