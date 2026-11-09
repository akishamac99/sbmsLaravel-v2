@extends('layouts.portal')
@section('title', 'Delete Account')
@push('head') @include('partials.a11y-styles') @endpush

@section('content')
    <div class="page-header"><h1 id="confirm-title">Delete account</h1></div>

    <section class="card confirm-panel" aria-labelledby="confirm-title">
        <div class="card-body">

            <p>You are about to <strong>permanently delete</strong> the {{ $accountLabel }} of <strong>{{ $accountName }}</strong>. This cannot be undone.</p>

            @if ($counts && ($counts['bookings'] + $counts['feedback']) > 0)
                <div role="alert" class="alert-inline">
                    <p><strong>This account has existing records.</strong> Deleting it may affect booking records.</p>
                    <ul>
                        <li>{{ $counts['active_bookings'] }} active booking(s)</li>
                        <li>{{ $counts['bookings'] }} booking(s) in total will lose their assigned staff member</li>
                        <li>{{ $counts['feedback'] }} feedback record(s) about this person will be deleted</li>
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $action }}" novalidate>
                @csrf
                @method('DELETE')

                @if ($counts && ($counts['bookings'] + $counts['feedback']) > 0)
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="confirm_dependencies" value="1" required aria-describedby="dep-help">
                            I understand and want to continue
                        </label>
                        <p id="dep-help" class="sr-only">Required because this account has existing records.</p>
                    </div>
                @endif

                <div class="form-group">
                    <label for="password">Enter your password to confirm</label>
                    <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password"
                           aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @if($errors->has('password')) aria-describedby="password-error" @endif>
                    @error('password')<p id="password-error" class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="form-actions">
                    <a class="btn-ghost" href="{{ route('owner.accounts.archived') }}" autofocus>Cancel</a>
                    <button type="submit" class="btn-primary">Delete account</button>
                </div>
            </form>
        </div>
    </section>
@endsection
