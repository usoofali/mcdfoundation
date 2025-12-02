<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Program;
use App\Models\ProgramEnrollment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProgramService
{
    /**
     * Check if a member is eligible to enroll in a program.
     */
    public function isEligible(Member $member, Program $program): bool
    {
        // 1. Program must be active
        if (!$program->is_active) {
            return false;
        }

        // 2. Member must be active
        if ($member->status !== 'active') {
            return false;
        }

        // 3. Member must not already be enrolled
        if ($this->isAlreadyEnrolled($member, $program)) {
            return false;
        }

        // 4. Program must have available capacity
        if ($program->is_at_capacity) {
            return false;
        }

        // 5. Check minimum contributions requirement
        if (isset($program->eligibility_rules['min_contributions'])) {
            $required = $program->eligibility_rules['min_contributions'];
            $contributions = $member->contributions()
                ->where('status', 'paid')
                ->count();

            if ($contributions < $required) {
                return false;
            }
        }

        // 6. Check minimum age requirement
        if (isset($program->eligibility_rules['min_age'])) {
            $age = $member->age;
            if ($age < $program->eligibility_rules['min_age']) {
                return false;
            }
        }

        // 7. Check maximum age requirement
        if (isset($program->eligibility_rules['max_age'])) {
            $age = $member->age;
            if ($age > $program->eligibility_rules['max_age']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get detailed eligibility reasons for a member and program.
     */
    public function getEligibilityReasons(Member $member, Program $program): array
    {
        $reasons = [];

        if (!$program->is_active) {
            $reasons[] = 'Program is not active.';
        }

        if ($member->status !== 'active') {
            $reasons[] = 'Member is not active.';
        }

        if ($this->isAlreadyEnrolled($member, $program)) {
            $reasons[] = 'Member is already enrolled in this program.';
        }

        if ($program->is_at_capacity) {
            $reasons[] = 'Program has reached maximum capacity.';
        }

        if (isset($program->eligibility_rules['min_contributions'])) {
            $required = $program->eligibility_rules['min_contributions'];
            $contributions = $member->contributions()
                ->where('status', 'paid')
                ->count();

            if ($contributions < $required) {
                $reasons[] = "Member has {$contributions} paid contributions; {$required} required.";
            }
        }

        if (isset($program->eligibility_rules['min_age'])) {
            $age = $member->age;
            $min = $program->eligibility_rules['min_age'];
            if ($age < $min) {
                $reasons[] = "Member is {$age} years old; minimum age is {$min}.";
            }
        }

        if (isset($program->eligibility_rules['max_age'])) {
            $age = $member->age;
            $max = $program->eligibility_rules['max_age'];
            if ($age > $max) {
                $reasons[] = "Member is {$age} years old; maximum age is {$max}.";
            }
        }

        return $reasons;
    }

    /**
     * Comprehensive eligibility check with detailed information.
     */
    public function checkEnrollmentEligibility(Member $member, Program $program): array
    {
        $eligible = $this->isEligible($member, $program);
        $reasons = $this->getEligibilityReasons($member, $program);

        return [
            'eligible' => $eligible,
            'reasons' => $reasons,
            'member' => $member,
            'program' => $program,
            'available_slots' => $program->available_slots,
            'enrolled_count' => $program->enrolled_count,
        ];
    }

    /**
     * Enroll a member in a program.
     */
    public function enroll(Member $member, Program $program, ?string $remarks = null): ProgramEnrollment
    {
        if (!$this->isEligible($member, $program)) {
            $reasons = implode(' ', $this->getEligibilityReasons($member, $program));
            throw new \Exception("Member cannot be enrolled: {$reasons}");
        }

        return DB::transaction(function () use ($member, $program, $remarks) {
            return ProgramEnrollment::create([
                'member_id' => $member->id,
                'program_id' => $program->id,
                'enrolled_at' => now()->toDateString(),
                'status' => 'enrolled',
                'remarks' => $remarks,
            ]);
        });
    }

    /**
     * Withdraw a member from a program.
     */
    public function withdraw(ProgramEnrollment $enrollment, string $reason): bool
    {
        return DB::transaction(function () use ($enrollment, $reason) {
            return $enrollment->withdraw($reason);
        });
    }

    /**
     * Mark enrollment as completed.
     */
    public function markCompleted(ProgramEnrollment $enrollment): bool
    {
        return DB::transaction(function () use ($enrollment) {
            return $enrollment->markAsCompleted();
        });
    }

    /**
     * Issue certificate for an enrollment.
     */
    public function issueCertificate(ProgramEnrollment $enrollment): bool
    {
        if ($enrollment->status !== 'completed') {
            throw new \Exception('Certificate can only be issued for completed enrollments');
        }

        return DB::transaction(function () use ($enrollment) {
            return $enrollment->issueCertificate();
        });
    }

    /**
     * Get programs with filters.
     */
    public function getPrograms(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Program::with(['creator']);

        // Apply filters
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if (isset($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get member's program enrollments.
     */
    public function getMemberEnrollments(Member $member): Collection
    {
        return $member->programEnrollments()
            ->with(['program'])
            ->orderBy('enrolled_at', 'desc')
            ->get();
    }

    /**
     * Get program's enrollments.
     */
    public function getProgramEnrollments(Program $program, int $perPage = 15): LengthAwarePaginator
    {
        return $program->enrollments()
            ->with(['member'])
            ->orderBy('enrolled_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get program statistics.
     */
    public function getProgramStats(Program $program): array
    {
        $totalEnrollments = $program->enrollments()->count();
        $enrolledCount = $program->enrollments()->where('status', 'enrolled')->count();
        $completedCount = $program->enrollments()->where('status', 'completed')->count();
        $withdrawnCount = $program->enrollments()->where('status', 'withdrawn')->count();
        $certificatesIssued = $program->enrollments()->where('certificate_issued', true)->count();

        return [
            'total_enrollments' => $totalEnrollments,
            'enrolled_count' => $enrolledCount,
            'completed_count' => $completedCount,
            'withdrawn_count' => $withdrawnCount,
            'certificates_issued' => $certificatesIssued,
            'completion_rate' => $totalEnrollments > 0 ? ($completedCount / $totalEnrollments) * 100 : 0,
            'available_slots' => $program->available_slots,
            'capacity' => $program->capacity,
        ];
    }

    /**
     * Check if member is already enrolled in program.
     */
    protected function isAlreadyEnrolled(Member $member, Program $program): bool
    {
        return $program->enrollments()
            ->where('member_id', $member->id)
            ->whereIn('status', ['enrolled', 'completed'])
            ->exists();
    }

    /**
     * Delete a program (only if no enrollments).
     */
    public function deleteProgram(Program $program): bool
    {
        if ($program->enrollments()->exists()) {
            throw new \Exception('Cannot delete program with existing enrollments');
        }

        return DB::transaction(function () use ($program) {
            return $program->delete();
        });
    }
}
