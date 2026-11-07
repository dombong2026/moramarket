<dialog class="modal" id="mm-global-confirm" aria-labelledby="mm-confirm-heading">
  <div class="modal-header">
    <h2 id="mm-confirm-heading">Confirm action</h2>
    <button type="button" class="modal-close" data-close-modal aria-label="Close confirmation">@include('partials.icon', ['name'=>'close'])</button>
  </div>
  <div class="modal-body"><p data-confirm-message>Are you sure you want to continue?</p></div>
  <div class="modal-footer">
    <button type="button" class="btn btn-outline" data-close-modal>Cancel</button>
    <button type="button" class="btn btn-danger" data-confirm-approve>Confirm</button>
  </div>
</dialog>
