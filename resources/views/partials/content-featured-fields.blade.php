<div class="form-grid">
  <div>
    <label class="field-label" for="{{ $id }}-featured-type">Featured media</label>
    <select id="{{ $id }}-featured-type" name="featured_type" class="field">
      @foreach(['none'=>'None','image'=>'Image','video'=>'Uploaded video','youtube'=>'YouTube','color'=>'Solid color'] as $kind=>$label)
      <option value="{{ $kind }}" @selected(($entry->featured_type ?? 'none')===$kind)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div>
    <label class="field-label" for="{{ $id }}-featured-media">Media Library file</label>
    <select class="field" name="featured_media_id" id="{{ $id }}-featured-media">
      <option value="">Choose an uploaded image or video</option>
      @foreach($mediaAssets as $asset)
      <option value="{{ $asset->id }}" @selected(($entry->featured_media_id ?? null)===$asset->id)>{{ $asset->original_name }} ({{ str_starts_with($asset->mime_type,'video/')?'Video':'Image' }})</option>
      @endforeach
    </select>
  </div>
  <div>
    <label for="{{ $id }}-youtube" class="field-label">YouTube URL or video ID</label>
    <input class="field" id="{{ $id }}-youtube" type="text" name="featured_youtube_id" maxlength="250" value="{{ $entry->featured_youtube_id ?? '' }}" placeholder="https://www.youtube.com/watch?v=...">
  </div>
  <div>
    <label for="{{ $id }}-color" class="field-label">Featured color</label>
    <input class="field" id="{{ $id }}-color" name="featured_color" type="color" value="{{ $entry->featured_color ?? '#f97316' }}">
  </div>
</div>
