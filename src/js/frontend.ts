/**
 * SASS
 */
import '../scss/frontend.scss';
import '../scss/dark-mode.scss';
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
import { disabledValuesInSelectInit, showHiddenValueInit, legalDocumentExpirationInit } from './components/chow-hidden-value';
import { logsInit, modalLogsInit } from './components/logs';
import { bookmarkInit } from './components/bookmark';
import { sendUpdatePerformance } from './components/performance';
import { dateMaskInit, masksAllSite, telMaskInit } from './components/tel-mask';
import { changeStopType } from './components/stop-type';
import { setStatusPaid } from './components/set-status-paid';
import { sendEmailChain } from './components/send-email-chain';
import { saveAllTracking } from './components/save-all-tracking';
import { initAutoSubmitForm } from './components/auto-submit-form';
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
import { initEtaPopups } from './components/eta-popup';
import { initEtaTimers, updateEtaTimer } from './components/eta-timer';
import './components/quick-copy';
import './components/driver-popups';
import DriverPopupForms from './components/driver-popup-forms';
import BrokerPopups from './components/broker-popups';
import BrokerPopupForms from './components/broker-popup-forms';
import DriverAutocomplete from './components/driver-autocomplete';
import AudioHelper from './components/common/audio-helper';
import { TimerControl } from './components/timer-control';
import { TimerAnalytics } from './components/timer-analytics';
import DarkModeToggle from './components/dark-mode-toggle';
import DriversMap from './components/drivers-map'; 

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
    const driverPopupForms = new DriverPopupForms(urlAjax);
    
    // Initialize Broker Popup Forms
    const brokerPopupForms = new BrokerPopupForms(urlAjax);
    
    // Initialize Broker Popups
    // @ts-ignore
    const singlePageBrokerUrl = var_from_php?.single_page_broker || '';
    const brokerPopups = new BrokerPopups(urlAjax, singlePageBrokerUrl);
    
    // Initialize Driver Autocomplete
    new DriverAutocomplete(urlAjax, {
        unitInput: '.js-unit-number-input',
        dropdown: '.js-driver-dropdown',
        attachedDriverInput: 'input[name="attached_driver"]',
        phoneInput: '.js-phone-driver',
        unitNumberNameInput: 'input[name="unit_number_name"]',
        nonceInput: '#driver-search-nonce',
        driverValueInput: '.js-driver-value'
    });
    
    // Initialize Second Driver Autocomplete
    new DriverAutocomplete(urlAjax, {
        unitInput: '.js-second-unit-number-input',
        dropdown: '.js-second-driver-dropdown',
        attachedDriverInput: 'input[name="attached_second_driver"]',
        phoneInput: '.js-second-phone-driver',
        unitNumberNameInput: 'input[name="second_unit_number_name"]',
        nonceInput: '#second-driver-search-nonce',
    });

    // Initialize Third Driver Autocomplete
    new DriverAutocomplete(urlAjax, {
        unitInput: '.js-third-unit-number-input',
        dropdown: '.js-third-driver-dropdown',
        attachedDriverInput: 'input[name="attached_third_driver"]',
        phoneInput: '.js-third-phone-driver',
        unitNumberNameInput: 'input[name="third_unit_number_name"]',
        nonceInput: '#third-driver-search-nonce',
    });

    // Add driver validation
    initDriverValidation();
    
    // Load driver statistics if on driver page
    if (document.getElementById('driver-statistics-container')) {
        const driverIdInput = document.querySelector('input[name="driver_id"]') as HTMLInputElement;
        if (driverIdInput && driverIdInput.value) {
            driverPopupForms.loadDriverStatistics(parseInt(driverIdInput.value));
        }
    }
    
    // Initialize Timer Control
    new TimerControl(urlAjax);
    
    // Initialize timer analytics
    new TimerAnalytics(urlAjax);
    
    // Initialize dark mode toggle
    new DarkModeToggle(urlAjax);

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
    modalLogsInit(urlAjax);
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
    initEtaPopups();
    initEtaTimers();
    
    // Initialize Drivers Map
    if (hereApi) {
        new DriversMap(urlAjax, hereApi);
    }

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
    legalDocumentExpirationInit();
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

    // Initialize auto-submit forms for statistics pages
    // These forms will automatically submit when select values change
    initAutoSubmitForm('.js-auto-submit-form');

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

function initDriverValidation(): void {
    // Track driver selection state
    let firstDriverSelected = false;
    let secondDriverSelected = false;
    let thirdDriverSelected = false;

    // Initialize state on page load
    function initializeDriverState() {
        const firstDriverInput = document.querySelector('input[name="attached_driver"]') as HTMLInputElement;
        const secondDriverInput = document.querySelector('input[name="attached_second_driver"]') as HTMLInputElement;
        const thirdDriverInput = document.querySelector('input[name="attached_third_driver"]') as HTMLInputElement;
        
        // Check if drivers are already selected on page load
        if (firstDriverInput && firstDriverInput.value) {
            firstDriverSelected = true;
            console.log('First driver already selected on load:', firstDriverInput.value);
        }
        
        if (secondDriverInput && secondDriverInput.value) {
            secondDriverSelected = true;
            console.log('Second driver already selected on load:', secondDriverInput.value);
        }
        
        if (thirdDriverInput && thirdDriverInput.value) {
            thirdDriverSelected = true;
            console.log('Third driver already selected on load:', thirdDriverInput.value);
        }
    }

    // Initialize state
    initializeDriverState();

    // Also check if autocomplete components are working
    setTimeout(() => {
        const firstUnitInput = document.querySelector('.js-unit-number-input') as HTMLInputElement;
        const secondUnitInput = document.querySelector('.js-second-unit-number-input') as HTMLInputElement;
        const thirdUnitInput = document.querySelector('.js-third-unit-number-input') as HTMLInputElement;
        
        console.log('Autocomplete components check:', {
            firstUnitInputExists: !!firstUnitInput,
            secondUnitInputExists: !!secondUnitInput,
            thirdUnitInputExists: !!thirdUnitInput,
            firstDriverSelected,
            secondDriverSelected,
            thirdDriverSelected
        });
    }, 1000);

    // Listen for driver selection changes
    document.addEventListener('driverSelectionChanged', (e: any) => {
        const { hasSelectedDriver, selectors } = e.detail;
        
        console.log('Driver selection changed:', { hasSelectedDriver, selectors });
        
        // Check which driver was changed
        if (selectors.unitInput === '.js-unit-number-input') {
            firstDriverSelected = hasSelectedDriver;
            console.log('First driver selected:', firstDriverSelected);
        } else if (selectors.unitInput === '.js-second-unit-number-input') {
            secondDriverSelected = hasSelectedDriver;
            console.log('Second driver selected:', secondDriverSelected);
        } else if (selectors.unitInput === '.js-third-unit-number-input') {
            thirdDriverSelected = hasSelectedDriver;
            console.log('Third driver selected:', thirdDriverSelected);
        }

        // Validate form submission
        validateDriverSelection();
    });

    // Validate on form submission
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            if (!validateDriverSelection()) {
                e.preventDefault();
                return false;
            }
        });
    });

    function validateDriverSelection(): boolean {
        // Check if TBD is checked
        const tbdCheckbox = document.querySelector('.js-tbd') as HTMLInputElement;
        const isTbdMode = tbdCheckbox && tbdCheckbox.checked;

        console.log('Validating driver selection:', { isTbdMode, firstDriverSelected, secondDriverSelected });

        // If TBD mode is active, skip validation for first driver only
        // Second driver should still be validated even in TBD mode

        // Check if any driver fields have values but no selected driver
        const firstDriverInput = document.querySelector('input[name="attached_driver"]') as HTMLInputElement;
        const secondDriverInput = document.querySelector('input[name="attached_second_driver"]') as HTMLInputElement;
        const thirdDriverInput = document.querySelector('input[name="attached_third_driver"]') as HTMLInputElement;
        
        const firstUnitInput = document.querySelector('.js-unit-number-input') as HTMLInputElement;
        const secondUnitInput = document.querySelector('.js-second-unit-number-input') as HTMLInputElement;
        const thirdUnitInput = document.querySelector('.js-third-unit-number-input') as HTMLInputElement;

        console.log('Driver inputs:', {
            firstDriverValue: firstDriverInput?.value,
            secondDriverValue: secondDriverInput?.value,
            thirdDriverValue: thirdDriverInput?.value
        });

        let isValid = true;
        let errorMessage = '';

        // Check first driver - skip if TBD mode is active
        if (!isTbdMode && firstDriverInput && firstDriverInput.value && !firstDriverSelected) {
            isValid = false;
            errorMessage = 'Please select a valid driver for the first driver field.';
            console.log('First driver validation failed');
        }

        // Check second driver - only if the second driver section is visible and has values
        const secondDriverSection = document.querySelector('.js-second-driver');
        const isSecondDriverVisible = secondDriverSection && !secondDriverSection.classList.contains('d-none');
        const secondDriverInputExists = document.querySelector('.js-second-unit-number-input');
        
        console.log('Second driver checks:', {
            secondDriverInputExists: !!secondDriverInputExists,
            isSecondDriverVisible,
            secondDriverValue: secondDriverInput?.value,
            secondDriverSelected
        });
        
        // Only validate second driver if the section exists, is visible, and has values
        if (secondDriverInputExists && isSecondDriverVisible && secondDriverInput && secondDriverInput.value && !secondDriverSelected) {
            isValid = false;
            errorMessage = 'Please select a valid driver for the second driver field.';
            console.log('Second driver validation failed');
        }

        // Check third driver - only if the third driver section is visible and has values
        const thirdDriverSection = document.querySelector('.js-third-driver');
        const isThirdDriverVisible = thirdDriverSection && !thirdDriverSection.classList.contains('d-none');
        const thirdDriverInputExists = document.querySelector('.js-third-unit-number-input');
        
        console.log('Third driver checks:', {
            thirdDriverInputExists: !!thirdDriverInputExists,
            isThirdDriverVisible,
            thirdDriverValue: thirdDriverInput?.value,
            thirdDriverSelected
        });
        
        // Only validate third driver if the section exists, is visible, and has values
        if (thirdDriverInputExists && isThirdDriverVisible && thirdDriverInput && thirdDriverInput.value && !thirdDriverSelected) {
            isValid = false;
            errorMessage = 'Please select a valid driver for the third driver field.';
            console.log('Third driver validation failed');
        }

        console.log('Validation result:', { isValid, errorMessage });

        // Show error if validation failed
        if (!isValid) {
            alert(errorMessage);
            
            // Focus on the problematic field
            if (firstDriverInput && firstDriverInput.value && !firstDriverSelected) {
                firstUnitInput?.focus();
            } else if (isSecondDriverVisible && secondDriverInput && secondDriverInput.value && !secondDriverSelected) {
                secondUnitInput?.focus();
            }
        }

        return isValid;
    }
}

window.document.addEventListener('DOMContentLoaded', ready);
