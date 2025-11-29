/**
 * Drivers Rate Statistics Page JavaScript
 * Handles tab switching and footer updates
 */

export function initDriversRate(): void {
    const performanceTab = document.getElementById('dr-performance-tab');
    const ratingsTab = document.getElementById('dr-ratings-tab');
    const footerInfoText = document.getElementById('dr-footer-info-text');
    const footerCountText = document.getElementById('dr-footer-count-text');
    
    if (!performanceTab || !ratingsTab || !footerInfoText || !footerCountText) {
        return;
    }
    
    // Get data from data attributes
    const performanceInfo = performanceTab.getAttribute('data-info') || 'Sorted by delivered loads count, then by total profit';
    const performanceCount = performanceTab.getAttribute('data-count') || '0 drivers';
    
    const ratingsInfo = ratingsTab.getAttribute('data-info') || 'Sorted by rating count (desc), then by average rating (desc)';
    const ratingsCount = ratingsTab.getAttribute('data-count') || '0 drivers';
    
    function updateFooter(isPerformance: boolean): void {
        if (footerInfoText && footerCountText) {
            if (isPerformance) {
                footerInfoText.textContent = performanceInfo;
                footerCountText.textContent = performanceCount;
            } else {
                footerInfoText.textContent = ratingsInfo;
                footerCountText.textContent = ratingsCount;
            }
        }
    }
    
    if (performanceTab) {
        performanceTab.addEventListener('shown.bs.tab', () => {
            updateFooter(true);
        });
    }
    
    if (ratingsTab) {
        ratingsTab.addEventListener('shown.bs.tab', () => {
            updateFooter(false);
        });
    }
}

