<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/** Re-checks the Owner's password before an account is permanently deleted. */
class ConfirmDeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) Auth::guard('staff')->user()?->isOwner();
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password:staff'],
            'confirm_dependencies' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['password.current_password' => 'The password is incorrect.'];
    }
}
