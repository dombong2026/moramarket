<x-ui.modal-frame id="mm-editor-link" aria-labelledby="mm-link-heading">
  <div class="modal-header">
    <h2 id="mm-link-heading">Insert Link</h2>
    <button type="button" class="modal-close" data-close-modal aria-label="Close link editor">
      @include('partials.icon', ['name'=>'close'])
    </button>
  </div>
  <form id="mm-editor-link-form">
    <div class="modal-body">
      <label class="field-label" for="mm-link-url">Link URL</label>
      <input id="mm-link-url" type="text" class="field" placeholder="https://example.com" autocomplete="url" required>
      <p class="native-note">Enter HTTPS, HTTP or a local path beginning with /.</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" data-close-modal>Cancel</button>
      <button type="submit" class="btn btn-primary">Insert Link</button>
    </div>
  </form>
</x-ui.modal-frame>
