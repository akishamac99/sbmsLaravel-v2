{{-- Payment screenshot picker with preview. Large phone photos are shrunk in the browser before upload. --}}
<div class="proof-upload" data-proof-upload>
    <div class="upload-drop-zone" data-drop-zone tabindex="0" role="button" aria-label="Choose payment screenshot">
        <div class="upload-drop-inner" data-drop-inner>
            <i class="fas fa-cloud-upload-alt upload-drop-icon" aria-hidden="true"></i>
            <p class="upload-drop-text">Click to choose a screenshot, or drag it here</p>
            <p class="upload-drop-hint">JPG, PNG, GIF or WEBP</p>
        </div>
        <div class="upload-preview-wrap" data-preview-wrap hidden>
            <img src="" alt="Payment proof preview" class="upload-preview-img" data-preview-img>
            <div class="upload-preview-info">
                <span class="upload-preview-name" data-file-name></span>
                <span class="upload-preview-size" data-file-size></span>
                <button type="button" class="upload-preview-remove" data-clear-upload><i class="fas fa-trash-alt"></i> Remove</button>
            </div>
        </div>
    </div>
    <input type="file" name="payment_proof" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only" data-proof-input tabindex="-1" {{ ($required ?? true) ? 'required' : '' }}>
    <div class="field-error" data-upload-error hidden></div>
</div>
