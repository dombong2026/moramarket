<div class="field-block rich-editor" data-rich-editor>
  <label class="field-label" for="{{ $id }}-source">{{ $label ?? 'Content' }}</label>
  <div class="editor-toolbar" role="toolbar" aria-label="Text formatting">
    <button type="button" data-edit-command="bold" title="Bold"><strong>B</strong></button>
    <button type="button" data-edit-command="italic" title="Italic"><em>I</em></button>
    <button type="button" data-edit-command="underline" title="Underline"><u>U</u></button>
    <button type="button" data-edit-block="p" title="Paragraph">P</button>
    <button type="button" data-edit-block="h2" title="Heading 2">H2</button>
    <button type="button" data-edit-block="h3" title="Heading 3">H3</button>
    <button type="button" data-edit-block="blockquote" title="Quote">❝</button>
    <button type="button" data-edit-command="insertUnorderedList" title="Bullets">• List</button>
    <button type="button" data-edit-command="insertOrderedList" title="Numbers">1. List</button>
    <button type="button" data-edit-link title="Insert link">Link</button>
    <button type="button" data-edit-command="unlink" title="Remove link">Unlink</button>
    <button type="button" data-edit-command="undo" title="Undo">↶</button>
    <button type="button" data-edit-command="redo" title="Redo">↷</button>
    <button type="button" data-edit-command="removeFormat" title="Clear formatting">Clear</button>
    <button type="button" data-html-toggle title="Edit HTML source">HTML</button>
  </div>
  <div class="editor-canvas" contenteditable="true" role="textbox" aria-multiline="true" aria-label="{{ $label ?? 'Content' }}" data-editor-canvas>{!! $html ?? '' !!}</div>
  <textarea class="field editor-source" id="{{ $id }}-source" data-editor-source name="body_html" maxlength="100000" rows="12" hidden>{{ $html ?? '' }}</textarea>
  <p class="native-note">Text and HTML modes. HTML is sanitized on the server before publication. To see edited HTML rendered safely, save the entry. Unsupported elements and scripts are removed.</p>
</div>
