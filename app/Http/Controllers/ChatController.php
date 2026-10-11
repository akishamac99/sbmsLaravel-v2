<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

// The chat bubble. A conversation is one customer and one staff member.
class ChatController extends Controller
{
    public function contacts(): JsonResponse
    {
        [$type, $me] = $this->me();
        [$mine, $other] = $type === 'customer' ? ['customerID', 'staffID'] : ['staffID', 'customerID'];

        // People I already talked to, plus people from my bookings.
        $ids = ChatMessage::where($mine, $me)->distinct()->pluck($other)
            ->merge(Booking::where($mine, $me)->whereNotNull($other)->distinct()->pluck($other))
            ->unique()->values();

        $people = $type === 'customer'
            ? Staff::whereIn('staffID', $ids)->where('is_archived', 0)->where('is_active', 1)->get()->keyBy('staffID')
            : Customer::whereIn('customerID', $ids)->get()->keyBy('customerID');

        $lastIds = ChatMessage::where($mine, $me)
            ->select($other)->selectRaw('max(id) as last_id')->groupBy($other)
            ->pluck('last_id', $other);
        $last = ChatMessage::whereIn('id', $lastIds->values())->get()->keyBy($other);

        $unread = ChatMessage::where($mine, $me)->where('sender_type', '!=', $type)->whereNull('read_at')
            ->select($other)->selectRaw('count(*) as n')->groupBy($other)
            ->pluck('n', $other);

        $rows = $ids->map(function ($id) use ($people, $last, $unread, $type) {
            $person = $people->get($id);

            if (! $person) {
                return null;
            }

            $message = $last->get($id);

            return [
                'id' => (int) $id,
                'name' => $person->full_name,
                'sub' => $type === 'customer' ? $person->role : null,
                'last' => $message ? ($message->sender_type === $type ? 'You: ' : '').Str::limit($message->body, 40) : null,
                'at' => $message?->created_at?->toIso8601String(),
                'unread' => (int) $unread->get($id, 0),
            ];
        })->filter()->sortByDesc(fn ($row) => $row['at'] ?? '')->values();

        return response()->json(['contacts' => $rows]);
    }

    public function messages(Request $request): JsonResponse
    {
        [$type, $me] = $this->me();
        $with = (int) $request->query('with');
        $after = (int) $request->query('after', 0);

        abort_unless($this->allowed($type, $me, $with), 403);

        $thread = $this->thread($type, $me, $with);

        $messages = $after > 0
            ? (clone $thread)->where('id', '>', $after)->orderBy('id')->limit(100)->get()
            : (clone $thread)->orderByDesc('id')->limit(100)->get()->reverse()->values();

        // Whatever they sent me is now read.
        (clone $thread)->where('sender_type', '!=', $type)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json([
            'messages' => $messages->map(fn ($m) => $this->present($m, $type))->values(),
        ]);
    }

    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'with' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:1000'],
        ]);

        [$type, $me] = $this->me();
        $with = (int) $data['with'];

        abort_unless($this->allowed($type, $me, $with), 403);

        $message = ChatMessage::create([
            'customerID' => $type === 'customer' ? $me : $with,
            'staffID' => $type === 'staff' ? $me : $with,
            'sender_type' => $type,
            'body' => $data['body'],
            'created_at' => now(),
        ]);

        return response()->json(['message' => $this->present($message, $type)]);
    }

    public function unread(): JsonResponse
    {
        [$type, $me] = $this->me();
        $mine = $type === 'customer' ? 'customerID' : 'staffID';

        $count = ChatMessage::where($mine, $me)->where('sender_type', '!=', $type)->whereNull('read_at')->count();

        return response()->json(['unread' => $count]);
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function me(): array
    {
        if ($id = Auth::guard('customer')->id()) {
            return ['customer', (int) $id];
        }

        return ['staff', (int) Auth::guard('staff')->id()];
    }

    private function thread(string $type, int $me, int $with)
    {
        return $type === 'customer'
            ? ChatMessage::where('customerID', $me)->where('staffID', $with)
            : ChatMessage::where('staffID', $me)->where('customerID', $with);
    }

    // A customer can start a chat only with staff on one of their bookings; staff can reply once a chat or booking exists.
    private function allowed(string $type, int $me, int $with): bool
    {
        if ($with < 1) {
            return false;
        }

        [$customerId, $staffId] = $type === 'customer' ? [$me, $with] : [$with, $me];

        if (! Customer::whereKey($customerId)->exists() || ! Staff::whereKey($staffId)->where('is_archived', 0)->exists()) {
            return false;
        }

        return ChatMessage::where('customerID', $customerId)->where('staffID', $staffId)->exists()
            || Booking::where('customerID', $customerId)->where('staffID', $staffId)->exists();
    }

    private function present(ChatMessage $message, string $type): array
    {
        return [
            'id' => $message->id,
            'mine' => $message->sender_type === $type,
            'body' => $message->body,
            'at' => $message->created_at?->toIso8601String(),
        ];
    }
}
