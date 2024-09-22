/**
 * SASS
 */
import '../scss/frontend.scss';
/**
 * JavaScript
 */
import Sliders from './components/swiper-init';
import Popup from './parts/popup-window';
import { initMoneyMask } from './components/input-helpers';
import {
    actionCreateReportInit,
    additionalContactsInit,
    addShipperPointInit,
    createDraftPosts,
    previewFileUpload,
    removeOneFileInitial,
    sendShipperFormInit,
    updateFilesReportInit,
    updateStatusPost,
} from './components/create-report';
import { actionCreateCompanyInit } from './components/create-company';
import { actionCreateShipperInit } from './components/create-shipper';
import { nextTabTrigger } from './components/tab-helper';
import { addSearchAction } from './components/search-action';
import { toggleBlocksInit } from './components/toggle-blocks-init';
import { changeTableInit } from './components/change-table';
import { initGetInfoDriver } from './components/driver-Info';

function ready() {
    // @ts-ignore
    const urlAjax = var_from_php.ajax_url;
    // @ts-ignore
    const linkOdysseia = var_from_php.link_web_service_odysseia;
    // @ts-ignore
    const linkEndurance = var_from_php.link_web_service_endurance;
    // @ts-ignore
    const linkMartlet = var_from_php.link_web_service_martlet;

    const useServices = {
        Odysseia: linkOdysseia,
        Endurance: linkEndurance,
        Martlet: linkMartlet,
    };

    const popupInstance = new Popup();
    popupInstance.init();

    // Ajax Actions
    actionCreateReportInit(urlAjax);
    createDraftPosts(urlAjax);
    updateFilesReportInit(urlAjax);
    actionCreateCompanyInit(urlAjax);
    actionCreateShipperInit(urlAjax);
    sendShipperFormInit(urlAjax);
    addSearchAction(urlAjax);
    updateStatusPost(urlAjax);
    removeOneFileInitial(urlAjax);
    changeTableInit(urlAjax);

    // API request
    initGetInfoDriver(useServices);

    additionalContactsInit();
    toggleBlocksInit();
    addShipperPointInit();
    // Helpers
    initMoneyMask(); // Money helper
    previewFileUpload(); // File preview
    nextTabTrigger(); // Buttons trigger next bootstrap tab
}

window.document.addEventListener('DOMContentLoaded', ready);
