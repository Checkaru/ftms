<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Coordinator\OrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Organization::class);

        $organizations = Organization::withCount('placements')
            ->orderBy('name')
            ->paginate(15);

        return view('coordinator.organizations.index', compact('organizations'));
    }

    public function create(): View
    {
        $this->authorize('create', Organization::class);

        return view('coordinator.organizations.create', ['organization' => new Organization()]);
    }

    public function store(OrganizationRequest $request): RedirectResponse
    {
        $this->authorize('create', Organization::class);

        Organization::create($request->validated());

        return redirect()->route('coordinator.organizations.index')
            ->with('success', 'تمت إضافة المؤسسة.');
    }

    public function edit(Organization $organization): View
    {
        $this->authorize('update', $organization);

        return view('coordinator.organizations.edit', compact('organization'));
    }

    public function update(OrganizationRequest $request, Organization $organization): RedirectResponse
    {
        $this->authorize('update', $organization);

        $organization->update($request->validated());

        return redirect()->route('coordinator.organizations.index')
            ->with('success', 'تم تحديث المؤسسة.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->authorize('delete', $organization);

        // organization_id on placements is restrictOnDelete — block instead of erroring.
        if ($organization->placements()->exists()) {
            return redirect()->route('coordinator.organizations.index')
                ->with('error', 'لا يمكن حذف مؤسسة مرتبطة بتنسيبات. عطّلها بدلاً من ذلك.');
        }

        $organization->delete();

        return redirect()->route('coordinator.organizations.index')
            ->with('success', 'تم حذف المؤسسة.');
    }
}
