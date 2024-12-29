/**
 * SASS
 */
import '../scss/frontend.scss';
/**
 * JavaScript
 */
import Popup from './parts/popup-window';
// eslint-disable-next-line camelcase
import { checboxesHelperInit, initMoneyMask, quick_pay_method, trigger_current_time } from './components/input-helpers';
import {
    actionCreateReportInit,
    additionalContactsInit,
    addShipperPointInit,
    createDraftPosts,
    fullRemovePost,
    previewFileUpload,
    quickEditInit,
    quickEditTrackingStatus,
    removeOneFileInitial,
    sendShipperFormInit,
    triggerDisableBtnInit,
    updateAccountingReportInit,
    updateBillingReportInit,
    updateFilesReportInit,
    updateStatusPost,
} from './components/create-report';
import { actionCreateCompanyInit, ActionUpdateCompanyInit } from './components/create-company';
import { actionCreateShipperInit, actionUpdateShipperInit } from './components/create-shipper';
import { nextTabTrigger } from './components/tab-helper';
import { addSearchAction } from './components/search-action';
import { toggleBlocksInit, toggleCheckboxInit } from './components/toggle-blocks-init';
import { changeTableInit } from './components/change-table';
import { initGetInfoDriver } from './components/driver-Info';
import { updateTooltip } from './components/tooltip-start';
import { toggleSidebarInit } from './components/sidebar-init';
import { autoFillAddress } from './components/auto-fill-address';
import { AuthUsersInit } from './components/auth-users';
import { cleanUrlByFilter, cleanUrlByFilterAr, cleanUrlByFilterPlatform } from './components/filter-clean';
import { disabledValuesInSelectInit, showHiddenValueInit } from './components/chow-hidden-value';
import { logsInit } from './components/logs';
import { bookmarkInit } from './components/bookmark';

function ready() {
    console.log('ready');
    // @ts-ignore
    const urlAjax = var_from_php.ajax_url;
    // @ts-ignore
    const linkOdysseia = var_from_php.link_web_service_odysseia;
    // @ts-ignore
    const linkEndurance = var_from_php.link_web_service_endurance;
    // @ts-ignore
    const linkMartlet = var_from_php.link_web_service_martlet;
    // @ts-ignore
    const hereApi = var_from_php.here_api_key;

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
    ActionUpdateCompanyInit(urlAjax);
    actionCreateShipperInit(urlAjax);
    actionUpdateShipperInit(urlAjax);
    sendShipperFormInit(urlAjax);
    addSearchAction(urlAjax);
    updateStatusPost(urlAjax);
    removeOneFileInitial(urlAjax);
    changeTableInit(urlAjax);
    AuthUsersInit(urlAjax);
    updateBillingReportInit(urlAjax);
    updateAccountingReportInit(urlAjax);
    fullRemovePost(urlAjax);
    quickEditInit(urlAjax, '.js-quick-edit', 'quick_update_post');
    quickEditInit(urlAjax, '.js-quick-edit-ar', 'quick_update_post_ar');
    bookmarkInit(urlAjax);
    logsInit(urlAjax);
    quickEditTrackingStatus(urlAjax);
    // API request
    initGetInfoDriver(urlAjax, useServices);

    additionalContactsInit();
    addShipperPointInit();
    // Helpers
    initMoneyMask(); // Money helper
    previewFileUpload(); // File preview
    nextTabTrigger(); // Buttons trigger next bootstrap tab
    updateTooltip();
    toggleSidebarInit();
    autoFillAddress(hereApi);
    cleanUrlByFilter();
    cleanUrlByFilterAr();
    cleanUrlByFilterPlatform();
    showHiddenValueInit();
    checboxesHelperInit();
    toggleBlocksInit();
    toggleCheckboxInit();
    disabledValuesInSelectInit();
    quick_pay_method();
    trigger_current_time();
    triggerDisableBtnInit();

    const preloaders = document.querySelectorAll('.js-preloader');
    preloaders &&
        preloaders.forEach((item) => {
            item.remove();
        });
}

window.document.addEventListener('DOMContentLoaded', ready);
