<form class="js-add-new-contact container mt-3 mb-3" method="post">
    <div class="row g-1">
        <h4 class="md-1">Add New Contact</h4>

        <div class="mb-2 js-result-search-wrap">
            <label class="form-label">Select company <span class="text-danger">*</span></label>
            <p class="form-label text-small">Enter company name or MC number</p>
            <div class="form-group position-relative js-container-search">
                <input id="input-name" type="text" required name="company_name"
                       value=""
                       placeholder="MC,DOT or Name"
                       autocomplete="off"
                       class="form-control js-search-company">
                <ul class="my-dropdown-search js-container-search-list">

                </ul>
            </div>
            <div class="result-search js-result-search">
            </div>
        </div>

        <div class="mb-2 col-md-6 col-12">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-2 col-md-5 col-12">
            <label class="form-label">Office Number </label>
            <input type="text" name="office_number" class="form-control js-tel-mask">
        </div>

        <div class="mb-2 col-md-1 col-3">
            <label class="form-label">Ext</label>
            <input type="text" name="direct_ext" class="form-control">
        </div>

        <div class="mb-2 col-md-6 col-9">
            <label class="form-label">Direct Number</label>
            <input type="text" name="direct_number" class="form-control js-tel-mask">
        </div>

        <div class="mb-2 col-md-6 col-12">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <h5 class="mt-3">Support Contact (optional)</h5>


        <div class="mb-2 col-md-4 col-12">
            <label class="form-label">Support Contact</label>
            <input type="text" name="support_contact" class="form-control">
        </div>

        <div class="mb-2 col-md-3 col-9">
            <label class="form-label">Support Phone</label>
            <input type="text" name="support_phone" class="form-control js-tel-mask">
        </div>

        <div class="mb-2 col-md-1 col-3">
            <label class="form-label">Ext</label>
            <input type="text" name="support_ext" class="form-control">
        </div>

        <div class="mb-2 col-md-4 col-12">
            <label class="form-label">Support Email</label>
            <input type="email" name="support_email" class="form-control">
        </div>

        <h5 class="mt-3">Additional Contacts (optional)</h5>

        <div class="js-additional-contacts col-12"></div>

        <div class="mt-2">
            <button type="button" class="btn btn-outline-primary btn-sm js-add-contact-btn">+ Add Additional Contact
            </button>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-success">
                <span class="active-state">Save Contact</span>
                <span class="disabled-state">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Creating...
                </span>
            </button>
        </div>
    </div>
</form>