export interface AvailableLoadItem {
    load_number: string;
    load_status: string;
    date_created: string | number;
}

export function populateLoadSelect(
    loadSelect: HTMLSelectElement,
    loadsInfo: HTMLElement,
    loads: AvailableLoadItem[]
): void {
    // Reset with placeholder
    loadSelect.innerHTML = '<option value="">Select a load...</option>';

    if (loads && loads.length > 0) {
        loads.forEach((load: AvailableLoadItem) => {
            const option = document.createElement('option');
            option.value = load.load_number;
            const loadStatus = load.load_status === 'tonu' ? ' (TONU)': '';
            option.textContent = `${load.load_number}${loadStatus} - ${new Date(load.date_created as any).toLocaleDateString()}`;
            loadSelect.appendChild(option);
        });
        loadsInfo.textContent = `${loads.length} available load(s) for rating`;
        loadsInfo.className = 'text-success';
    } else {
        loadsInfo.textContent = 'No available loads for rating';
        loadsInfo.className = 'text-danger';
    }

    // Always append Canceled option last
    const canceledOption = document.createElement('option');
    canceledOption.value = 'Canceled';
    canceledOption.textContent = 'Canceled';
    loadSelect.appendChild(canceledOption);
}


