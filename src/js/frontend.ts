/**
 * SASS
 */
import '../scss/frontend.scss';
import 'bootstrap/dist/js/bootstrap.bundle';
/**
 * JavaScript
 */
import Sliders from './components/swiper-init';
import Popup from './parts/popup-window';
import { initMoneyMask } from './components/input-helpers';
import { actionCreateReportInit, previewFileUpload } from './components/create-report';
import { actionCreateCompanyInit } from './components/create-company';
import { actionCreateShipperInit } from './components/create-shipper';

function ready() {
    // @ts-ignore
    const urlAjax = var_from_php.ajax_url;
    const popupInstance = new Popup();
    popupInstance.init();

    // Mask input for money and count total
    initMoneyMask();
    // Action reports
    actionCreateReportInit(urlAjax);
    actionCreateCompanyInit(urlAjax);
    actionCreateShipperInit(urlAjax);
    previewFileUpload();
}

window.document.addEventListener('DOMContentLoaded', ready);
