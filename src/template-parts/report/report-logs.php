<?php
$helper = new TMSReportsHelper();
?>

<div class="w-100">
    <h4 class="mb-2 log-title-container">
        <span>Logs</span>
        <button class="js-hide-logs">
			<?php echo $helper->get_icon_logs(); ?>
            <span class="log-title-container__small">Logs</span>
        </button>
    </h4>
    <div class="log">
        <div class="log-card administrator">
            <span class="log-card__role">administrator</span>
            <div class="log-card__top">
                <p class="log-card__name">Sergey Milchenko

                </p>
                <p class="log-card__time">5/20/2024 20:00</p>
            </div>
            <div class="log-card__message">
                Change <span>booked rate</span> old $200 new $225.50
            </div>
        </div>

        <div class="log-card accounting">
            <span class="log-card__role">accounting</span>
            <div class="log-card__top">
                <p class="log-card__name">Sergey Milchenko

                </p>
                <p class="log-card__time">5/20/2024</p>
            </div>
            <div class="log-card__message">
                Change <span>booked rate</span> old $200 new $225.50
            </div>
        </div>

        <div class="log-card recruiter">
            <span class="log-card__role">recruiter</span>
            <div class="log-card__top">
                <p class="log-card__name">Sergey Milchenko

                </p>
                <p class="log-card__time">5/20/2024</p>
            </div>
            <div class="log-card__message">
                Change <span>booked rate</span> old $200 new $225.50
            </div>
        </div>

        <div class="log-card billing">
            <span class="log-card__role">billing</span>
            <div class="log-card__top">
                <p class="log-card__name">Sergey Milchenko

                </p>
                <p class="log-card__time">5/20/2024</p>
            </div>
            <div class="log-card__message">
                Change <span>booked rate</span> old $200 new $225.50
            </div>
        </div>

        <div class="log-card tracking">
            <span class="log-card__role">tracking</span>
            <div class="log-card__top">
                <p class="log-card__name">Sergey Milchenko

                </p>
                <p class="log-card__time">5/20/2024</p>
            </div>
            <div class="log-card__message">
                Change <span>booked rate</span> old $200 new $225.50
            </div>
        </div>

        <div class="log-card dispatcher-tl">
            <span class="log-card__role">dispatcher-tl</span>
            <div class="log-card__top">
                <p class="log-card__name">Sergey Milchenko

                </p>
                <p class="log-card__time">5/20/2024</p>
            </div>
            <div class="log-card__message">
                Change <span>booked rate</span> old $200 new $225.50
            </div>
        </div>

        <div class="log-card dispatcher">
            <span class="log-card__role">dispatcher</span>
            <div class="log-card__top">
                <p class="log-card__name">Sergey Milchenko

                </p>
                <p class="log-card__time">5/20/2024</p>
            </div>
            <div class="log-card__message">
                Change <span>booked rate</span> old $200 new $225.50
            </div>
        </div>
    </div>
    <div class="mb-3 d-flex align-items-end gap-1 log-message">
        <div class="w-100">
            <label for="exampleFormControlTextarea1" class="form-label">Your message</label>
            <textarea class="form-control" id="exampleFormControlTextarea1" ></textarea>
        </div>
        <button class="btn btn-primary">Send</button>
    </div>
</div>