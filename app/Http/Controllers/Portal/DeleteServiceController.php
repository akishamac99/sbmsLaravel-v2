<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Services\DeleteService;
use App\Exceptions\BusinessRuleViolation;
use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** UC25 Delete Service. Confirmation prompt first, then a safe delete. */
class DeleteServiceController extends Controller
{
    public function confirm(Service $service, DeleteService $delete): View
    {
        return view('portal.delete-service', [
            'service' => $service,
            'upcoming' => $delete->upcomingBookingCount($service),
            'role' => Auth::guard('staff')->user()->role_slug,
        ]);
    }

    public function destroy(Service $service, DeleteService $delete): RedirectResponse
    {
        $role = Auth::guard('staff')->user()->role_slug;

        try {
            $delete->handle(Auth::guard('staff')->user(), $service);
        } catch (BusinessRuleViolation $e) {
            return redirect()->route("{$role}.services")->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'The service could not be deleted. Please try again.');
        }

        return redirect()->route("{$role}.services")->with('status', 'Service deleted. It can no longer be booked; past bookings are kept.');
    }
}
