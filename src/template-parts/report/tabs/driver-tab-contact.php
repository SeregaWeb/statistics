<?php

$full_only_view = get_field_value( $args, 'full_view_only' );
$report_object  = get_field_value( $args, 'report_object' );
$post_id        = get_field_value( $args, 'post_id' );

?>

<div class="container mt-4">
    <h2 class="mb-3">Owner & Drivers Information</h2>
    <form>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Owner Name*</label>
                <input required type="text" class="form-control"
                       value="">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Owner Phone*</label>
                <input required type="tel" class="form-control"
                       value="">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Owner Email*</label>
                <input required type="email" class="form-control"
                       value="">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Main Contact*</label>
                <input required type="tel" class="form-control"
                       value="">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Home State*</label>
                <input required type="text" class="form-control"
                       value="">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Home City*</label>
                <input required type="text" class="form-control" value="">
            </div>
        </div>

        <h4 class="mt-4">First Driver</h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">First Driver Name*</label>
                <input required type="text" class="form-control"
                       value="">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">First Driver Phone*</label>
                <input required type="tel" class="form-control"
                       value="">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">First Driver Email*</label>
                <input required type="email" class="form-control"
                       value="">
            </div>
        </div>

        <h4 class="mt-4">Second Driver</h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Second Driver Name</label>
                <input type="text" class="form-control" value="">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Second Driver Phone</label>
                <input type="tel" class="form-control" value="">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Second Driver Email</label>
                <input type="email" class="form-control"
                       value="">
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Submit</button>
    </form>
</div>