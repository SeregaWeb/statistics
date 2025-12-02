<div class="admin-rating-manager mt-4" 
     data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
     data-nonce="<?php echo wp_create_nonce('admin_rating_action'); ?>">
     <div class="card">
          <div class="card-header">
          <h5 class="mb-0">Driver Rating Manager</h5>
          </div>
          <div class="card-body">
          <div class="mb-3">
               <label for="admin-driver-id-input" class="form-label">Driver ID</label>
               <div class="input-group">
                    <input type="number" class="form-control" id="admin-driver-id-input" placeholder="Enter driver ID">
                    <button class="btn btn-primary" type="button" id="admin-find-driver-btn">Find</button>
               </div>
          </div>
          
          <div id="admin-driver-info" style="display: none;" class="mb-3">
               <div class="alert alert-info">
                    <strong>Driver:</strong> <span id="admin-driver-name"></span> (ID: <span id="admin-driver-id-display"></span>)
               </div>
          </div>
          
          <div id="admin-ratings-container" style="display: none;">
               <h6 class="mb-3">Ratings (<span id="admin-ratings-count">0</span>)</h6>
               <div class="table-responsive">
                    <table class="table table-sm table-striped">
                         <thead>
                              <tr>
                              <th width="40">
                                   <input type="checkbox" id="admin-select-all-ratings">
                              </th>
                              <th>ID</th>
                              <th>Rater</th>
                              <th>Rating</th>
                              <th>Load #</th>
                              <th>Date</th>
                              <th>Message</th>
                              </tr>
                         </thead>
                         <tbody id="admin-ratings-table-body">
                         </tbody>
                    </table>
               </div>
               <div class="mt-3">
                    <button class="btn btn-danger" id="admin-delete-ratings-btn" disabled>
                         Delete Selected
                    </button>
               </div>
          </div>
          
          <div id="admin-ratings-message" class="mt-3"></div>
          </div>
     </div>
</div>