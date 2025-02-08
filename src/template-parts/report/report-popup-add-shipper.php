<div id="popup_add_shipper" class="popup popup-add-shipper">
    <div class="my_overlay js-popup-close"></div>
    <div class="popup__wrapper-inner js-video-container">
        <div class="popup-container">
            <button class="popup-close js-popup-close">
                <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M31.6666 10.6833L29.3166 8.33331L20 17.65L10.6833 8.33331L8.33331 10.6833L17.65 20L8.33331 29.3166L10.6833 31.6666L20 22.35L29.3166 31.6666L31.6666 29.3166L22.35 20L31.6666 10.6833Z"
                          fill="black"/>
                </svg>
            </button>
			<?php
			echo esc_html( get_template_part( 'src/template-parts/report/report', 'form-add-shipper' ) );
			?>
        </div>
    </div>
</div>