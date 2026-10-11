<span class="pw-field">
    <input class="form-control" id="{{ $id }}" type="password" name="{{ $name }}" autocomplete="{{ $autocomplete }}" required @if (! empty($minlength)) minlength="{{ $minlength }}" @endif>
    <button type="button" class="pw-toggle" data-pw-toggle="{{ $id }}" aria-label="Show password"><i class="fas fa-eye"></i></button>
</span>
