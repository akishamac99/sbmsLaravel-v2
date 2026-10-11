<?php

namespace App\Http\Controllers;

use App\Models\MessengerContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// "Connect Messenger" / "Disconnect" for whoever is logged in (a customer or a staff member).
class MessengerLinkController extends Controller
{
    public function connect(): RedirectResponse
    {
        [$type, $id] = $this->identity();

        $code = 'SBMS-'.Str::upper(Str::random(6));

        MessengerContact::updateOrCreate(
            ['user_type' => $type, 'userID' => $id],
            ['link_code' => $code, 'link_code_expires_at' => now()->addMinutes(30)]
        );

        return back()->with('messenger_code', $code);
    }

    public function disconnect(): RedirectResponse
    {
        [$type, $id] = $this->identity();

        MessengerContact::forUser($type, $id)->update([
            'psid' => null,
            'linked_at' => null,
            'link_code' => null,
            'link_code_expires_at' => null,
        ]);

        return back()->with('status', 'Messenger disconnected.');
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function identity(): array
    {
        if ($customerId = Auth::guard('customer')->id()) {
            return ['customer', (int) $customerId];
        }

        return ['staff', (int) Auth::guard('staff')->id()];
    }
}
