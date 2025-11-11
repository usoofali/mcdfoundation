<?php

namespace App\Actions\Fortify;

use App\Models\Lga;
use App\Models\State;
use App\Models\User;
use App\Notifications\MemberPreRegistered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'full_name' => ['required', 'string', 'max:150'],
            'family_name' => ['required', 'string', 'max:150'],
            'marital_status' => ['required', Rule::in(['single', 'married', 'divorced'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'nin' => ['required', 'string', 'size:11', 'unique:members,nin'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string'],
            'state_id' => ['required', 'exists:states,id'],
            'lga_id' => ['required', 'exists:lgas,id'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'workplace' => ['nullable', 'string', 'max:200'],
            'hometown' => ['nullable', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['full_name'].' '.$input['family_name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'phone' => $input['phone'],
                'address' => $input['address'],
                'state_id' => $input['state_id'],
                'lga_id' => $input['lga_id'],
            ]);

            $state = State::find($input['state_id']);
            $lga = Lga::find($input['lga_id']);

            $member = $user->member()->create([
                'user_id' => $user->id,
                'full_name' => $input['full_name'],
                'family_name' => $input['family_name'],
                'date_of_birth' => $input['date_of_birth'],
                'marital_status' => $input['marital_status'],
                'nin' => $input['nin'],
                'address' => $input['address'],
                'occupation' => $input['occupation'] ?? '',
                'workplace' => $input['workplace'] ?? '',
                'hometown' => $input['hometown'] ?? '',
                'state_id' => $state?->id,
                'lga_id' => $lga?->id,
                'country' => 'Nigeria',
                'registration_date' => now()->toDateString(),
                'status' => 'pre_registered',
                'is_complete' => false,
                'created_by' => $user->id,
            ]);

            $admins = User::whereHas('role', fn ($query) => $query->where('name', 'admin'))
                ->whereKeyNot($user->getKey())
                ->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new MemberPreRegistered($member));
            }

            return $user;
        });
    }
}
