<x-layouts.auth>
    <div
        x-data="registrationForm({
            states: @js($stateOptions),
            initialState: '{{ old('state_id') }}',
            initialLga: '{{ old('lga_id') }}'
        })"
        x-init="init()"
        class="flex flex-col gap-6"
    >
        <x-auth-header :title="__('Membership Registration')" :description="__('Enter your details below to register your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="space-y-6">
            @csrf

            @if ($errors->any())
                <flux:callout variant="danger">
                    <ul class="list-inside list-disc space-y-1 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </flux:callout>
            @endif

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-900/20">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <flux:icon name="sparkles" class="size-5 text-amber-600 dark:text-amber-300" />
                        <div class="space-y-1">
                            <flux:heading size="sm" class="font-semibold text-amber-900 dark:text-amber-100">
                                Complete your pre-registration
                            </flux:heading>
                            <flux:text class="text-sm text-amber-800 dark:text-amber-200">
                                Provide the required details now. You can submit additional documents after signing in.
                            </flux:text>
                        </div>
                    </div>
                    <flux:text class="text-xs text-amber-700 dark:text-amber-200">
                        Step 1 of 2 · Basic member information
                    </flux:text>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-700 dark:bg-neutral-800">
                <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-700 sm:px-6">
                    <flux:heading size="sm" class="font-semibold text-neutral-900 dark:text-neutral-100">
                        Personal details
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                        Tell us who you are and verify your identity.
                    </flux:text>
                </div>
                <div class="space-y-4 px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2">
                        <flux:input
                            name="full_name"
                            :label="__('First Name')"
                            type="text"
                            value="{{ old('full_name') }}"
                            required
                            autofocus
                            autocomplete="given-name"
                            :placeholder="__('First name')"
                        />

                        <flux:input
                            name="family_name"
                            :label="__('Family Name')"
                            type="text"
                            value="{{ old('family_name') }}"
                            required
                            autocomplete="family-name"
                            :placeholder="__('Family name')"
                        />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2">
                        <flux:input
                            name="date_of_birth"
                            :label="__('Date of Birth')"
                            type="date"
                            value="{{ old('date_of_birth') }}"
                            required
                            max="{{ now()->toDateString() }}"
                        />

                        <flux:select
                            name="marital_status"
                            :label="__('Marital Status')"
                            placeholder="Select marital status"
                            required
                        >
                            <option value="">{{ __('Select marital status') }}</option>
                            <option value="single" @selected(old('marital_status') === 'single')>{{ __('Single') }}</option>
                            <option value="married" @selected(old('marital_status') === 'married')>{{ __('Married') }}</option>
                            <option value="divorced" @selected(old('marital_status') === 'divorced')>{{ __('Divorced') }}</option>
                        </flux:select>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2">
                        <flux:input
                            name="nin"
                            :label="__('(NIN)')"
                            type="text"
                            minlength="11"
                            maxlength="11"
                            value="{{ old('nin') }}"
                            required
                            placeholder="11-digit NIN"
                        >
                            <x-slot:helperText>
                                {{ __('Enter the 11-digit number on your National ID card.') }}
                            </x-slot:helperText>
                        </flux:input>

                        <flux:input
                            name="hometown"
                            :label="__('Hometown')"
                            type="text"
                            value="{{ old('hometown') }}"
                            placeholder="{{ __('Place of origin (optional)') }}"
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-700 dark:bg-neutral-800">
                <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-700 sm:px-6">
                    <flux:heading size="sm" class="font-semibold text-neutral-900 dark:text-neutral-100">
                        Contact & address
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                        We use this information to reach you and confirm eligibility.
                    </flux:text>
                </div>
                <div class="space-y-4 px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2">
                        <flux:input
                            name="phone"
                            :label="__('Phone Number')"
                            type="tel"
                            value="{{ old('phone') }}"
                            required
                            autocomplete="tel"
                            placeholder="+234..."
                        >
                            <x-slot:helperText>{{ __('Use a phone number we can call for verification.') }}</x-slot:helperText>
                        </flux:input>

                        <flux:input
                            name="email"
                            :label="__('Email address')"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            placeholder="email@example.com"
                        />
                    </div>

                    <flux:textarea
                        name="address"
                        :label="__('Residential Address')"
                        rows="3"
                        required
                        placeholder="Street, city, landmark"
                    >{{ old('address') }}</flux:textarea>

                    <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2">
                        <flux:select
                            name="state_id"
                            :label="__('State of Residence')"
                            placeholder="Select state"
                            x-model="stateId"
                            x-on:change="handleStateChange($event)"
                            required
                        >
                            <option value="">{{ __('Select state') }}</option>
                            @foreach ($states as $state)
                                <option value="{{ $state->id }}" @selected(old('state_id') == $state->id)>{{ $state->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select
                            name="lga_id"
                            :label="__('Local Government Area')"
                            placeholder="Select LGA"
                            x-model="lgaId"
                            x-bind:disabled="! availableLgas.length"
                            required
                        >
                            <option value="">{{ __('Select LGA') }}</option>
                            <template x-for="lga in availableLgas" :key="lga.id">
                                <option :value="lga.id" x-text="lga.name"></option>
                            </template>
                        </flux:select>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-700 dark:bg-neutral-800">
                <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-700 sm:px-6">
                    <flux:heading size="sm" class="font-semibold text-neutral-900 dark:text-neutral-100">
                        Work & optional details
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                        These help us tailor your benefits.
                    </flux:text>
                </div>
                <div class="space-y-4 px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2">
                        <flux:input
                            name="occupation"
                            :label="__('Occupation')"
                            type="text"
                            value="{{ old('occupation') }}"
                            placeholder="{{ __('Your current role (optional)') }}"
                        />

                        <flux:input
                            name="workplace"
                            :label="__('Place of Work')"
                            type="text"
                            value="{{ old('workplace') }}"
                            placeholder="{{ __('Organisation name (optional)') }}"
                        />
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 bg-white shadow-xs dark:border-neutral-700 dark:bg-neutral-800">
                <div class="border-b border-neutral-200 px-4 py-3 dark:border-neutral-700 sm:px-6">
                    <flux:heading size="sm" class="font-semibold text-neutral-900 dark:text-neutral-100">
                        Account security
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                        Create your login credentials.
                    </flux:text>
                </div>
                <div class="space-y-4 px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:gap-6 md:grid-cols-2">
                        <flux:input
                            name="password"
                            :label="__('Password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            :placeholder="__('Password')"
                            viewable
                        />

                        <flux:input
                            name="password_confirmation"
                            :label="__('Confirm password')"
                            type="password"
                            required
                            autocomplete="new-password"
                            :placeholder="__('Confirm password')"
                            viewable
                        />
                    </div>
                </div>
                <div class="border-t border-neutral-200 bg-neutral-50 px-4 py-4 dark:border-neutral-700 dark:bg-neutral-900/40 sm:px-6">
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                        By creating an account you agree to our membership terms and privacy policy.
                    </flux:text>
                    <div class="mt-4">
                        <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                            {{ __('Create account') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
    <script>
        function registrationForm({ states, initialState = null, initialLga = null }) {
            return {
                states,
                stateId: initialState,
                lgaId: initialLga,
                availableLgas: [],
                init() {
                    if (this.stateId) {
                        this.availableLgas = this.findLgas(this.stateId);
                        if (! this.availableLgas.find(lga => String(lga.id) === String(this.lgaId))) {
                            this.lgaId = '';
                        }
                    }
                },
                handleStateChange(event) {
                    this.stateId = event.target.value;
                    this.availableLgas = this.findLgas(this.stateId);
                    if (! this.availableLgas.find(lga => String(lga.id) === String(this.lgaId))) {
                        this.lgaId = '';
                    }
                },
                findLgas(stateId) {
                    const state = this.states.find(state => String(state.id) === String(stateId));
                    return state ? state.lgas : [];
                }
            };
        }
    </script>
</x-layouts.auth>
