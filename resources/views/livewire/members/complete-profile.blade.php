<?php

use App\Models\ContributionPlan;
use App\Models\HealthcareProvider;
use App\Models\Lga;
use App\Models\Member;
use App\Models\State;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app', ['title' => 'Complete Registration'])] class extends Component {
    use WithFileUploads;

    public Member $member;

    /** @var array<string, mixed> */
    public array $form = [
        'occupation' => '',
        'workplace' => '',
        'address' => '',
        'hometown' => '',
        'state_id' => '',
        'lga_id' => '',
        'healthcare_provider_id' => '',
        'health_status' => '',
        'contribution_plan_id' => '',
        'phone' => '',
        'notes' => '',
    ];

    public $states;
    public $lgas = [];
    public $healthcareProviders;
    public $contributionPlans;
    public $photo;

    public function mount(): void
    {
        $this->member = Auth::user()?->member ?? abort(404);

        if ($this->member->is_complete) {
            session()->flash('success', 'Your registration is already complete.');
            $this->redirectRoute('dashboard', navigate: true);
        }

        $this->states = State::orderBy('name')->get(['id', 'name']);
        $this->healthcareProviders = HealthcareProvider::active()->orderBy('name')->get(['id', 'name']);
        $this->contributionPlans = ContributionPlan::active()->orderBy('amount')->get(['id', 'name', 'amount', 'frequency']);

        $user = Auth::user();

        $this->form = [
            'occupation' => $this->member->occupation ?? '',
            'workplace' => $this->member->workplace ?? '',
            'address' => $this->member->address ?? '',
            'hometown' => $this->member->hometown ?? '',
            'state_id' => $this->member->state_id ? (string) $this->member->state_id : '',
            'lga_id' => $this->member->lga_id ? (string) $this->member->lga_id : '',
            'healthcare_provider_id' => $this->member->healthcare_provider_id ? (string) $this->member->healthcare_provider_id : '',
            'health_status' => $this->member->health_status ?? '',
            'contribution_plan_id' => $this->member->contribution_plan_id ? (string) $this->member->contribution_plan_id : '',
            'phone' => $user?->phone ?? '',
            'notes' => $this->member->notes ?? '',
        ];

        $this->loadLgas();
    }

    public function updatedFormStateId($stateId): void
    {
        $this->loadLgas();

        if (! $this->lgas->where('id', (int) $this->form['lga_id'])->count()) {
            $this->form['lga_id'] = '';
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.occupation' => ['required', 'string', 'max:150'],
            'form.workplace' => ['required', 'string', 'max:200'],
            'form.address' => ['required', 'string'],
            'form.hometown' => ['required', 'string', 'max:150'],
            'form.state_id' => ['required', Rule::exists(State::class, 'id')],
            'form.lga_id' => ['required', Rule::exists(Lga::class, 'id')],
            'form.healthcare_provider_id' => ['nullable', Rule::exists(HealthcareProvider::class, 'id')],
            'form.health_status' => ['nullable', 'string'],
            'form.contribution_plan_id' => ['nullable', Rule::exists(ContributionPlan::class, 'id')],
            'form.phone' => ['required', 'string', 'max:20'],
            'form.notes' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png'],
        ]);

        $payload = [
            'occupation' => $this->form['occupation'],
            'workplace' => $this->form['workplace'],
            'address' => $this->form['address'],
            'hometown' => $this->form['hometown'],
            'state_id' => (int) $this->form['state_id'],
            'lga_id' => (int) $this->form['lga_id'],
            'healthcare_provider_id' => $this->form['healthcare_provider_id'] ?: null,
            'health_status' => $this->form['health_status'] ?: null,
            'contribution_plan_id' => $this->form['contribution_plan_id'] ?: null,
            'notes' => $this->form['notes'] ?: null,
        ];

        if ($this->photo) {
            $photoPath = $this->photo->store('member-photos', 'public');

            if ($this->member->photo_path) {
                Storage::disk('public')->delete($this->member->photo_path);
            }

            $payload['photo_path'] = $photoPath;
        }

        $this->member->completeRegistration($payload);

        $this->member->user?->update([
            'phone' => $this->form['phone'],
            'address' => $this->form['address'],
            'state_id' => (int) $this->form['state_id'],
            'lga_id' => (int) $this->form['lga_id'],
        ]);

        session()->flash('success', 'Registration submitted successfully. We will review and approve shortly.');

        $this->redirectRoute('dashboard', navigate: true);
    }

    private function loadLgas(): void
    {
        $stateId = $this->form['state_id'];

        if (! $stateId) {
            $this->lgas = collect();

            return;
        }

        $this->lgas = Lga::where('state_id', $stateId)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}; ?>

<div class="mx-auto max-w-4xl space-y-6">
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-900/20">
        <div class="flex items-start gap-3">
            <flux:icon name="information-circle" class="size-5 text-amber-600 dark:text-amber-300" />
            <div class="space-y-1">
                <flux:heading size="sm" class="font-medium text-amber-900 dark:text-amber-100">
                    Finish your registration
                </flux:heading>
                <flux:text class="text-sm text-amber-800 dark:text-amber-200">
                    We only need a few more details to activate your membership. Please review and submit the form below.
                </flux:text>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <flux:input
                wire:model="form.occupation"
                label="Occupation"
                required
            />

            <flux:input
                wire:model="form.workplace"
                label="Place of Work"
                required
            />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <flux:input
                wire:model="form.phone"
                label="Phone Number"
                required
            />

            <flux:input
                wire:model="form.hometown"
                label="Hometown"
                required
            />
        </div>

        <flux:textarea
            wire:model="form.address"
            label="Residential Address"
            rows="3"
            required
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <flux:select
                wire:model.live="form.state_id"
                label="State of Residence"
                placeholder="Select state"
                required
            >
                <option value="">Select state</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                @endforeach
            </flux:select>

            <flux:select
                wire:model="form.lga_id"
                label="Local Government Area"
                placeholder="Select LGA"
                :disabled="$lgas->isEmpty()"
                required
            >
                <option value="">Select LGA</option>
                @foreach($lgas as $lga)
                    <option value="{{ $lga->id }}">{{ $lga->name }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <flux:select
                wire:model="form.healthcare_provider_id"
                label="Preferred Healthcare Provider"
                placeholder="Select provider"
            >
                <option value="">No preference</option>
                @foreach($healthcareProviders as $provider)
                    <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                @endforeach
            </flux:select>

            <flux:select
                wire:model="form.contribution_plan_id"
                label="Contribution Plan"
                placeholder="Select plan"
            >
                <option value="">No plan selected</option>
                @foreach($contributionPlans as $plan)
                    <option value="{{ $plan->id }}">
                        {{ $plan->label }} — ₦{{ number_format($plan->amount) }} ({{ ucfirst($plan->frequency) }})
                    </option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <flux:input
                wire:model="photo"
                type="file"
                label="Profile Photo"
                accept="image/*"
            />

            <flux:textarea
                wire:model="form.health_status"
                label="Health Notes"
                rows="3"
            />
        </div>

        <flux:textarea
            wire:model="form.notes"
            label="Additional Notes"
            rows="3"
        />

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove>Submit for Review</span>
                <span wire:loading>Submitting...</span>
            </flux:button>
        </div>
    </form>
</div>
