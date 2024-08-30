import Swiper, { EffectFade, Navigation } from 'swiper';

/**
 * Initialization of Swiper Slider.
 *
 * @param {string} selector - selector name.
 */
function initSwiper(selector) {
    if (!selector) {
        return false;
    }

    return new Swiper(selector, {
        spaceBetween: 24,
        slidesPerView: 1,
        effect: 'fade',
        fadeEffect: {
            crossFade: true,
        },
        modules: [Navigation, EffectFade],
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
    });
}

const Sliders = { initSwiper };
export default Sliders;
