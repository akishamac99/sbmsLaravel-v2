<?php

namespace App\Http\Controllers\Portal;

use App\Actions\ServiceCategories\DeleteServiceCategory;
use App\Actions\ServiceCategories\SaveServiceCategory;
use App\Exceptions\BusinessRuleViolation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ServiceCategoryRequest;
use App\Models\ServiceCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** UC54 Manage Service Categories. */
class ServiceCategoryController extends Controller
{
    public function index(): View
    {
        return view('portal.service-categories', [
            'categories' => ServiceCategory::withCount('services')->orderBy('category_name')->get(),
            'role' => Auth::guard('staff')->user()->role_slug,
            'canEdit' => Auth::guard('staff')->user()->isOwner() || \App\Models\Permission::allows('Manager', 'services', 'edit'),
        ]);
    }

    public function store(ServiceCategoryRequest $request, SaveServiceCategory $save): RedirectResponse
    {
        $save->handle(Auth::guard('staff')->user(), $request->validated());

        return back()->with('status', 'Service category added.');
    }

    public function update(ServiceCategoryRequest $request, ServiceCategory $category, SaveServiceCategory $save): RedirectResponse
    {
        $save->handle(Auth::guard('staff')->user(), $request->validated(), $category);

        return back()->with('status', 'Service category updated.');
    }

    public function destroy(ServiceCategory $category, DeleteServiceCategory $delete): RedirectResponse
    {
        try {
            $delete->handle(Auth::guard('staff')->user(), $category);
        } catch (BusinessRuleViolation $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Service category deleted.');
    }
}
