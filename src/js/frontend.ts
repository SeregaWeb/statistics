/**
 * SASS
 */
import '../scss/frontend.scss';
/**
 * JavaScript
 */
import Popup from './parts/popup-window';
// eslint-disable-next-line camelcase
import {
    applyZipCodeMask,
    checboxesHelperInit,
    dragAnDropInit,
    initMoneyMask,
    quickPayMethod,
    triggerCurrentTime,
    unrequiderInit,
} from './components/input-helpers';
import {
    actionCreateReportInit,
    addDeletePinnedHandler,
    additionalContactsInit,
    addShipperPointInit,
    createDraftPosts,
    fullRemovePost,
    pinnedMessageInit,
    previewFileUpload,
    quickEditInit,
    quickEditTrackingStatus,
    removeOneFileInitial,
    sendShipperFormInit,
    timeStrictChange,
    triggerDisableBtnInit,
    updateAccountingReportInit,
    updateBillingReportInit,
    updateFilesReportInit,
    updateStatusPost,
} from './components/create-report';
import { actionCreateCompanyInit, ActionDeleteCompanyInit, ActionUpdateCompanyInit } from './components/create-company';
import { actionCreateShipperInit, ActionDeleteShipperInit, actionUpdateShipperInit } from './components/create-shipper';
import { nextTabTrigger, tabUrlUpdeter } from './components/tab-helper';
import { addSearchAction } from './components/search-action';
import { toggleBlocksInit, toggleBlocksRadio, toggleCheckboxInit } from './components/toggle-blocks-init';
import { changeTableInit } from './components/change-table';
import { initGetInfoDriver } from './components/driver-Info';
import { updateTooltip } from './components/tooltip-start';
import { toggleSidebarInit } from './components/sidebar-init';
import { autoFillAddress } from './components/auto-fill-address';
import { AuthUsersInit } from './components/auth-users';
import {
    cleanUrlByFilter,
    cleanUrlByFilterAr,
    cleanUrlByFilterDriver,
    cleanUrlByFilterPlatform,
    cleanUrlByFilterDriverSearch,
} from './components/filter-clean';
import { disabledValuesInSelectInit, showHiddenValueInit } from './components/chow-hidden-value';
import { logsInit } from './components/logs';
import { bookmarkInit } from './components/bookmark';
import { sendUpdatePerformance } from './components/performance';
import { dateMaskInit, masksAllSite, telMaskInit } from './components/tel-mask';
import { changeStopType } from './components/stop-type';
import { setStatusPaid } from './components/set-status-paid';
import { sendEmailChain } from './components/send-email-chain';
import { saveAllTracking } from './components/save-all-tracking';
import {
    createDocumentBolActions,
    createDocumentInvoice,
    createDocumentInvoiceActions,
    createDocumentSettlementSummaryActions,
} from './components/document-create-money-check';
import { driversActions, driverCoreInit } from './components/driver-core';
import { initContactsHandler } from './components/contacts/contacts-init';
import { moveDispatcher } from './components/move-dispatcher';
import { initialSearchDriver } from './components/search-driver/search-driver-core';
import { driverHoldInit } from './components/driver-hold';
import { holdSectionInit } from './components/common/hold-section';
import { initCapabilitiesFilter } from './components/capabilities-filter';
import { initQuickStatusUpdate } from './components/quick-status-update';
import './components/quick-copy';
import './components/driver-popups';
import DriverPopupForms from './components/driver-popup-forms';
import AudioHelper from './components/common/audio-helper';
import { TimerControl } from './components/timer-control';
import { TimerAnalytics } from './components/timer-analytics';

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
    const hereApi = var_from_php.here_api_key; // @ts-ignore

    // @ts-ignore
    initialSearchDriver(var_from_php);

    const useServices = {
        Odysseia: linkOdysseia,
        Endurance: linkEndurance, 
        Martlet: linkMartlet,
    };

    const popupInstance = new Popup();
    popupInstance.init();

    // Initialize Audio Helper for managing audio playback
    AudioHelper.getInstance();
    
    // Initialize Driver Popup Forms
    new DriverPopupForms(urlAjax);
    
    // Initialize Timer Control
    new TimerControl(urlAjax);
    
    // Initialize timer analytics
    new TimerAnalytics(urlAjax);

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
    sendUpdatePerformance(urlAjax);
    sendEmailChain(urlAjax);
    saveAllTracking(urlAjax);
    ActionDeleteCompanyInit(urlAjax);
    ActionDeleteShipperInit(urlAjax);
    // API request
    initGetInfoDriver(urlAjax, useServices);

    // DRIVER START
    driversActions(urlAjax);
    cleanUrlByFilterDriver();
    cleanUrlByFilterDriverSearch();
    driverHoldInit(urlAjax);
    driverCoreInit(urlAjax);
    initCapabilitiesFilter();
    initQuickStatusUpdate(urlAjax);

    // DRIVER END
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
    quickPayMethod();
    triggerCurrentTime();
    triggerDisableBtnInit();
    changeStopType();
    setStatusPaid();
    telMaskInit();
    masksAllSite();
    tabUrlUpdeter();
    timeStrictChange();
    toggleBlocksRadio();
    dateMaskInit();
    dragAnDropInit();
    unrequiderInit();
    holdSectionInit();

    createDocumentInvoice();
    createDocumentInvoiceActions(urlAjax);
    createDocumentBolActions(urlAjax);
    createDocumentSettlementSummaryActions(urlAjax);
    moveDispatcher(urlAjax);
    initContactsHandler(urlAjax);
    pinnedMessageInit(urlAjax);
    addDeletePinnedHandler(urlAjax);

    applyZipCodeMask('.js-zip-code-mask');

    // Remove preloader
    const preloaders = document.querySelectorAll('.js-preloader');
    preloaders &&
        preloaders.forEach((item) => {
            item.remove();
        });
    
    // Additional check for preloader removal (in case it appears later)
    setTimeout(() => {
        const delayedPreloaders = document.querySelectorAll('.js-preloader');
        delayedPreloaders &&
            delayedPreloaders.forEach((item) => {
                item.remove();
            });
    }, 100);
    
    // Extra check for API monitor page specifically
    if (document.body.classList.contains('page-drivers-api-monitor')) {
        setTimeout(() => {
            const apiPreloaders = document.querySelectorAll('.js-preloader');
            apiPreloaders &&
                apiPreloaders.forEach((item) => {
                    item.remove();
                });
        }, 200);
    }
}

window.document.addEventListener('DOMContentLoaded', ready);
