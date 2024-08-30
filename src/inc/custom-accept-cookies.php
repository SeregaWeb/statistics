<?php
/**
 * Custom accept cookie functionality
 *
 * @package WP-rock/custom-accept-cookie
 */

add_action(
    'wp_rock_after_site_page_tag',
    function () {
        global $global_options;
        $text = get_field_value( $global_options, 'cookie_text' );
        $btn  = get_field_value( $global_options, 'cookie_button' );

        if ( empty( $text ) || empty( $btn ) ) {
            return '';
        }
        ?>
        <style>
            .accept-cookie-box {
                z-index: 2000;
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                padding: 20px 0;
                transform: translateY(100%);
                background: #4C5157;
                mix-blend-mode: normal;
            }

            .accept-cookie-box__icon {
                margin-right: 16px;
                min-width: 34px;
            }

            @media screen and (max-width: 816px) {
                .accept-cookie-box__icon {
                    min-width: 24px;
                }
            }

            @media screen and (max-width: 479px) {
                .accept-cookie-box {
                    padding-right: 0;
                }
            }

            .accept-cookie-box.opened {
                transform: translateY(0);
            }

            .accept-cookie-box__inner {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            @media screen and (max-width: 816px) {
                .accept-cookie-box__inner {
                    flex-wrap: wrap;
                    align-items: flex-start;
                    justify-content: flex-start;
                }
            }

            .accept-cookie-box__text {
                font-family: 'Nexa' , sans-serif;
                font-style: normal;
                font-weight: 400;
                font-size: 15px;
                line-height: 17px;
                color: #FFFFFF;
                display: flex;
                align-items: center;
                margin-right: 30px;
                position: relative;
            }

            @media screen and (max-width: 768px) {
                .accept-cookie-box__text {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                    margin: 0;
                    margin-bottom: 10px;

                }
            }

            @media screen and (max-width: 479px) {
                .accept-cookie-box__text {
                    font-size: 13px;
                }
            }


            .accept-cookie-box__close-btn {
                position: absolute;
                right: 10px;
                top: 5px;
                z-index: 20;
                background: none;
                border: none;
                padding: 0;
                display: none;
            }

            @media screen and (max-width: 768px ) {
                .accept-cookie-box__close-btn {
                    display: block;
                }
            }

            .accept-cookie-box__close-btn svg {
                pointer-events: none;
                width: 15px;
                height: 15px;
                fill: #ffffff;
            }

            body.accept-cookie-box-is-opened .chat-icon {
                bottom: 85px;
            }
        </style>

        <div class="accept-cookie-box js-accept-cookie-box">
            <div class="container">
                <div class="accept-cookie-box__inner">
                    <div class="accept-cookie-box__text">
                        <button class="accept-cookie-box__close-btn js-close-accept-cookie-box-btn"
                                data-role="close-accept-cookie-box">
                            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                 viewBox="0 0 492 492" style="enable-background:new 0 0 492 492;" xml:space="preserve">
                <g>
                    <g>
                        <path d="M300.188,246L484.14,62.04c5.06-5.064,7.852-11.82,7.86-19.024c0-7.208-2.792-13.972-7.86-19.028L468.02,7.872
                            c-5.068-5.076-11.824-7.856-19.036-7.856c-7.2,0-13.956,2.78-19.024,7.856L246.008,191.82L62.048,7.872
                            c-5.06-5.076-11.82-7.856-19.028-7.856c-7.2,0-13.96,2.78-19.02,7.856L7.872,23.988c-10.496,10.496-10.496,27.568,0,38.052
                            L191.828,246L7.872,429.952c-5.064,5.072-7.852,11.828-7.852,19.032c0,7.204,2.788,13.96,7.852,19.028l16.124,16.116
                            c5.06,5.072,11.824,7.856,19.02,7.856c7.208,0,13.968-2.784,19.028-7.856l183.96-183.952l183.952,183.952
                            c5.068,5.072,11.824,7.856,19.024,7.856h0.008c7.204,0,13.96-2.784,19.028-7.856l16.12-16.116
                            c5.06-5.064,7.852-11.824,7.852-19.028c0-7.204-2.792-13.96-7.852-19.028L300.188,246z"/>
                    </g>
                </g>
                </svg>
                        </button>
                        <svg  class="accept-cookie-box__icon" width="35" height="34" viewBox="0 0 35 34" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M34 3.11619V5.23238H18.3402L15.5186 9.46475H0V3.11619C0 1.94742 0.94742 1 2.11619 1H31.8838C33.0526 1 34 1.94742 34 3.11619Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9.2403 28.5104H2.11619C0.94742 28.5104 0 27.563 0 26.3942V5.23242" stroke="white" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M34.0001 5.23242V26.3942C34.0001 27.563 33.0526 28.5104 31.8839 28.5104H24.7598" stroke="white" stroke-width="2" stroke-linejoin="round"/>
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M22.3865 22.0465C22.3865 22.008 22.396 21.9721 22.3979 21.9341C22.147 22.0001 21.8886 22.0465 21.617 22.0465C19.917 22.0465 18.5389 20.6684 18.5389 18.9684C17.2772 18.9684 16.2554 17.9551 16.2343 16.6985C11.9187 17.0859 8.53516 20.7081 8.53516 25.1246C8.53516 29.7995 12.325 33.5893 16.9999 33.5893C21.6748 33.5893 25.4647 29.7995 25.4647 25.1246C25.4647 24.8218 25.4473 24.5232 25.4163 24.2287C25.1881 24.3041 24.9486 24.3551 24.6951 24.3551C23.4201 24.355 22.3865 23.3215 22.3865 22.0465Z" stroke="#26AF61" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <?php
                        echo esc_html( $text );
                        ?>
                    </div>
                    <button class="accept-cookie-box__accept-btn btn btn-white bg-pink js-accept-cookie-btn"
                            data-role="accept-cookie">
                        <?php
                        echo esc_html( $btn );
                        ?>
                    </button>
                </div>
            </div>
        </div>

        <script>

            window.addEventListener("load", (event) => {
                const ACCEPT_COOKIE_BOX = document.querySelector('.js-accept-cookie-box');
                const CHAT_ICON = document.querySelector('.chat-icon');
                const ACCEPT_COOKIE_CHECK = localStorage.getItem('accept-cookie');

                if (!ACCEPT_COOKIE_CHECK || +ACCEPT_COOKIE_CHECK !== 1) {
                    (ACCEPT_COOKIE_BOX) && ACCEPT_COOKIE_BOX.classList.add('opened');
                    document.body.classList.add('accept-cookie-box-is-opened');
                }

                document.body.addEventListener('click', function (event) {
                    const ROLE = event.target.dataset.role;
                    if (!ROLE) return;

                    switch (ROLE) {
                        case 'accept-cookie':
                            localStorage.setItem('accept-cookie', 1);
                            (ACCEPT_COOKIE_BOX) && ACCEPT_COOKIE_BOX.classList.remove('opened');
                            document.body.classList.remove('accept-cookie-box-is-opened');
                            break;
                        case 'close-accept-cookie-box':
                            (ACCEPT_COOKIE_BOX) && ACCEPT_COOKIE_BOX.classList.remove('opened');
                            document.body.classList.remove('accept-cookie-box-is-opened');
                            break;
                    }
                });
            });

        </script>
        <?php
    }
);
