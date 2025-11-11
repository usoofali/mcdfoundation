<?php

namespace App\Services;

use App\Models\Dependent;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DependentService
{
    /**
     * Get all dependents for a specific member.
     */
    public function getDependentsForMember(Member $member): Collection
    {
        return $member->dependents()->orderBy('relationship')->orderBy('name')->get();
    }

    /**
     * Get paginated dependents for a specific member.
     */
    public function getPaginatedDependentsForMember(Member $member, int $perPage = 15): LengthAwarePaginator
    {
        return $member->dependents()
            ->orderBy('relationship')
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a new dependent for a member.
     */
    public function createDependent(Member $member, array $data): Dependent
    {
        $data['member_id'] = $member->id;

        // Handle document upload if provided
        if (isset($data['document']) && $data['document']) {
            $data['document_path'] = $data['document']->store('dependent-documents', 'public');
            unset($data['document']);
        }

        return Dependent::create($data);
    }

    /**
     * Update an existing dependent.
     */
    public function updateDependent(Dependent $dependent, array $data): bool
    {
        // Handle document upload if provided
        if (isset($data['document']) && $data['document']) {
            // Delete old document if exists
            if ($dependent->document_path) {
                Storage::disk('public')->delete($dependent->document_path);
            }

            $data['document_path'] = $data['document']->store('dependent-documents', 'public');
            unset($data['document']);
        }

        return $dependent->update($data);
    }

    /**
     * Delete a dependent.
     */
    public function deleteDependent(Dependent $dependent): ?bool
    {
        // Delete associated document if exists
        if ($dependent->document_path) {
            Storage::disk('public')->delete($dependent->document_path);
        }

        return $dependent->delete();
    }

    /**
     * Get dependent statistics for a member.
     */
    public function getDependentStats(Member $member): array
    {
        $dependents = $member->dependents;

        return [
            'total' => $dependents->count(),
            'eligible' => $dependents->where('eligible', true)->count(),
            'children' => $dependents->where('relationship', 'child')->count(),
            'spouses' => $dependents->where('relationship', 'spouse')->count(),
            'parents' => $dependents->where('relationship', 'parent')->count(),
            'others' => $dependents->where('relationship', 'other')->count(),
        ];
    }

    /**
     * Recalculate eligibility for all dependents of a member.
     */
    public function recalculateEligibilityForMember(Member $member): void
    {
        $member->dependents->each(function (Dependent $dependent) {
            $dependent->eligible = $dependent->calculateEligibility();
            $dependent->save();
        });
    }

    /**
     * Get all eligible dependents across all members.
     */
    public function getAllEligibleDependents(): Collection
    {
        return Dependent::eligible()
            ->with('member')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get dependents by relationship type.
     */
    public function getDependentsByRelationship(string $relationship): Collection
    {
        return Dependent::byRelationship($relationship)
            ->with('member')
            ->orderBy('name')
            ->get();
    }

    /**
     * Search dependents by name.
     */
    public function searchDependents(string $search): Collection
    {
        return Dependent::where('name', 'like', "%{$search}%")
            ->with('member')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get dependents with overdue documents (if document tracking is implemented).
     */
    public function getDependentsWithMissingDocuments(): Collection
    {
        return Dependent::whereNull('document_path')
            ->where('relationship', 'child') // Children typically need birth certificates
            ->with('member')
            ->get();
    }

    /**
     * Validate dependent data.
     */
    public function validateDependentData(array $data, ?Dependent $dependent = null): array
    {
        $rules = [
            'name' => 'required|string|max:150',
            'nin' => [
                'required',
                'string',
                'size:11',
                'regex:/^[0-9]{11}$/',
                Rule::unique('dependents', 'nin')
                    ->whereNull('deleted_at')
                    ->ignore($dependent?->id),
            ],
            'date_of_birth' => 'required|date|before:today',
            'relationship' => 'required|in:spouse,child,parent,sibling,other',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'notes' => 'nullable|string|max:1000',
        ];

        return validator($data, $rules)->validate();
    }
}
