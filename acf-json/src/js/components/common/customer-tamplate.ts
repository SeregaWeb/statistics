// eslint-disable-next-line import/prefer-default-export
export function generateCustomerTemplate({ id, name, address, mc, dot, contact, phone, email }) {
    const dotTmpl = dot ? `<span><strong>#DOT:</strong> ${dot}</span>` : '';

    const mcTmpl = mc ? `<span><strong>#MC:</strong> ${mc}</span>` : '';

    return `
        <ul class="result-search-el">
            <li class="name">${name ?? ''}</li>
            <li class="address">${address ?? ''}</li>
            <li>${mcTmpl}</li>
            <li>${dotTmpl}</li>
            <li>${contact ?? ''}</li>
            <li>${phone ?? ''}</li>
            <li>${email ?? ''}</li>
        </ul>
        <input type="hidden" name="customer_id" value="${id ?? ''}">
    `;
}
