<?php

use App\Services\ReportService;
use App\Models\State;
use App\Models\Lga;
use Livewire\Volt\Component;

new class extends Component {
    public $selectedReport = 'membership';
    public $state_id = '';
    public $lga_id = '';
    public $date_from = '';
    public $date_to = '';
    public $status = '';
    public $member_id = '';
    public $claim_type = '';
    public $loan_type = '';
    public $source = '';

    public function mount(): void
    {
        // Set default date range to current month
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
    }

    public function updatedSelectedReport(): void
    {
        $this->resetFilters();
    }

    public function updatedStateId(): void
    {
        $this->lga_id = ''; // Reset LGA when state changes
    }

    public function resetFilters(): void
    {
        $this->state_id = '';
        $this->lga_id = '';
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
        $this->status = '';
        $this->member_id = '';
        $this->claim_type = '';
        $this->loan_type = '';
        $this->source = '';
    }

    public function generateReport(ReportService $reportService): void
    {
        $filters = $this->getFilters();
        $report = $reportService->{"generate" . ucfirst($this->selectedReport) . "Report"}($filters);
        
        // Store report data in session for display
        session(['report_data' => $report]);
        session(['report_type' => $this->selectedReport]);
        session(['report_filters' => $filters]);
    }

    public function exportReport(ReportService $reportService): void
    {
        $filters = $this->getFilters();
        $data = $reportService->exportToArray($this->selectedReport, $filters);
        
        // For now, we'll just show a success message
        // In a real implementation, you'd generate and download the file
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Report exported successfully. ' . count($data) . ' records exported.',
        ]);
    }

    protected function getFilters(): array
    {
        $filters = [];
        
        if ($this->state_id) $filters['state_id'] = $this->state_id;
        if ($this->lga_id) $filters['lga_id'] = $this->lga_id;
        if ($this->date_from) $filters['date_from'] = $this->date_from;
        if ($this->date_to) $filters['date_to'] = $this->date_to;
        if ($this->status) $filters['status'] = $this->status;
        if ($this->member_id) $filters['member_id'] = $this->member_id;
        if ($this->claim_type) $filters['claim_type'] = $this->claim_type;
        if ($this->loan_type) $filters['loan_type'] = $this->loan_type;
        if ($this->source) $filters['source'] = $this->source;
        
        return $filters;
    }

    public function getStatesProperty()
    {
        return State::orderBy('name')->get();
    }

    public function getLgasProperty()
    {
        if ($this->state_id) {
            return Lga::where('state_id', $this->state_id)->orderBy('name')->get();
        }
        return collect();
    }

    public function getReportTypesProperty()
    {
        return [
            'membership' => 'Membership Report',
            'contribution' => 'Contribution Report',
            'loan' => 'Loan Report',
            'healthClaims' => 'Health Claims Report',
            'fundLedger' => 'Fund Ledger Report',
            'eligibility' => 'Eligibility Report',
        ];
    }

    public function getStatusOptionsProperty()
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'pending' => 'Pending',
            'terminated' => 'Terminated',
        ];
    }

    public function getClaimTypeOptionsProperty()
    {
        return [
            'outpatient' => 'Outpatient',
            'inpatient' => 'Inpatient',
            'surgery' => 'Surgery',
            'maternity' => 'Maternity',
        ];
    }

    public function getLoanTypeOptionsProperty()
    {
        return [
            'cash' => 'Cash Loan',
            'item' => 'Item Loan',
        ];
    }

    public function getSourceOptionsProperty()
    {
        return [
            'contribution' => 'Contribution',
            'loan_repayment' => 'Loan Repayment',
            'donation' => 'Donation',
            'health_claim' => 'Health Claim',
            'loan_disbursement' => 'Loan Disbursement',
        ];
    }
}; ?>

<div>
    <div class="space-y-6">
        <!-- Header -->
        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <div class="space-y-1.5">
                <flux:heading size="lg" class="font-semibold text-neutral-900 dark:text-white">
                    Reports
                </flux:heading>
                <flux:text class="text-sm text-neutral-600 dark:text-neutral-400">
                    Generate comprehensive reports for all system data
                </flux:text>
            </div>
        </div>

        <!-- Report Selection and Filters -->
        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
            <form wire:submit="generateReport" class="space-y-6">
                <!-- Report Type Selection -->
                <div>
                    <flux:label for="report_type" value="Select Report Type" />
                    <flux:input 
                        id="report_type"
                        wire:model.live="selectedReport" 
                        placeholder="Select report type"
                        required
                    />
                    @error('selectedReport') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Geographic Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:label for="state" value="State (Optional)" />
                        <flux:input 
                            id="state"
                            wire:model.live="state_id" 
                            placeholder="Select state"
                        />
                    </div>

                    <div>
                        <flux:label for="lga" value="LGA (Optional)" />
                        <flux:input 
                            id="lga"
                            wire:model.live="lga_id" 
                            placeholder="Select LGA"
                        />
                    </div>
                </div>

                <!-- Date Range Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <flux:label for="date_from" value="From Date" />
                        <flux:input 
                            id="date_from"
                            wire:model="date_from" 
                            type="date"
                        />
                    </div>

                    <div>
                        <flux:label for="date_to" value="To Date" />
                        <flux:input 
                            id="date_to"
                            wire:model="date_to" 
                            type="date"
                        />
                    </div>
                </div>

                <!-- Additional Filters Based on Report Type -->
                @if($selectedReport === 'membership' || $selectedReport === 'eligibility')
                    <div>
                        <flux:label for="status" value="Member Status (Optional)" />
                        <flux:input 
                            id="status"
                            wire:model="status" 
                            placeholder="Filter by status"
                        />
                    </div>
                @endif

                @if($selectedReport === 'contribution')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:label for="member_id" value="Member ID (Optional)" />
                            <flux:input 
                                id="member_id"
                                wire:model="member_id" 
                                placeholder="Enter member ID"
                            />
                        </div>
                        <div>
                            <flux:label for="status" value="Contribution Status (Optional)" />
                            <flux:input 
                                id="status"
                                wire:model="status" 
                                placeholder="Filter by status"
                            />
                        </div>
                    </div>
                @endif

                @if($selectedReport === 'loan')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:label for="member_id" value="Member ID (Optional)" />
                            <flux:input 
                                id="member_id"
                                wire:model="member_id" 
                                placeholder="Enter member ID"
                            />
                        </div>
                        <div>
                            <flux:label for="loan_type" value="Loan Type (Optional)" />
                            <flux:input 
                                id="loan_type"
                                wire:model="loan_type" 
                                placeholder="Filter by loan type"
                            />
                        </div>
                    </div>
                @endif

                @if($selectedReport === 'healthClaims')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:label for="member_id" value="Member ID (Optional)" />
                            <flux:input 
                                id="member_id"
                                wire:model="member_id" 
                                placeholder="Enter member ID"
                            />
                        </div>
                        <div>
                            <flux:label for="claim_type" value="Claim Type (Optional)" />
                            <flux:input 
                                id="claim_type"
                                wire:model="claim_type" 
                                placeholder="Filter by claim type"
                            />
                        </div>
                    </div>
                @endif

                @if($selectedReport === 'fundLedger')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <flux:label for="source" value="Source (Optional)" />
                            <flux:input 
                                id="source"
                                wire:model="source" 
                                placeholder="Filter by source"
                            />
                        </div>
                        <div>
                            <flux:label for="status" value="Transaction Type (Optional)" />
                            <flux:input 
                                id="status"
                                wire:model="status" 
                                placeholder="inflow or outflow"
                            />
                        </div>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <flux:button variant="ghost" wire:click="resetFilters">
                        Reset Filters
                    </flux:button>
                    
                    <div class="flex items-center gap-2">
                        <flux:button variant="filled" wire:click="exportReport">
                            Export Report
                        </flux:button>
                        <flux:button variant="primary" type="submit">
                            Generate Report
                        </flux:button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Report Results -->
        @if(session('report_data'))
            <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-6 dark:border-neutral-700 dark:bg-neutral-800">
                <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <flux:heading size="md" class="font-semibold text-neutral-900 dark:text-white">
                        {{ $this->reportTypes[session('report_type')] }} Results
                    </flux:heading>
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                        Generated on {{ now()->format('M d, Y g:i A') }}
                    </flux:text>
                </div>

                <!-- Report Summary Cards -->
                @php
                    $reportData = session('report_data');
                    $reportType = session('report_type');
                @endphp

                @if($reportType === 'membership')
                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Members
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-blue-600 dark:text-blue-300">
                                {{ $reportData['total_members'] }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Eligible
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-green-600 dark:text-green-300">
                                {{ $reportData['eligible_members'] }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                New This Month
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-amber-600 dark:text-amber-300">
                                {{ $reportData['new_registrations'] }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Dependents
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-purple-600 dark:text-purple-300">
                                {{ $reportData['total_dependents'] }}
                            </flux:heading>
                        </div>
                    </div>
                @elseif($reportType === 'contribution')
                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Amount
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-blue-600 dark:text-blue-300">
                                ₦{{ number_format($reportData['total_amount'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Contributions
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-green-600 dark:text-green-300">
                                {{ $reportData['total_contributions'] }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Fines
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-red-600 dark:text-red-300">
                                ₦{{ number_format($reportData['total_fines'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Defaulters
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-amber-600 dark:text-amber-300">
                                {{ $reportData['defaulters']->count() }}
                            </flux:heading>
                        </div>
                    </div>
                @elseif($reportType === 'loan')
                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Amount
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-blue-600 dark:text-blue-300">
                                ₦{{ number_format($reportData['total_amount'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Repaid
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-green-600 dark:text-green-300">
                                ₦{{ number_format($reportData['total_repaid'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Outstanding
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-red-600 dark:text-red-300">
                                ₦{{ number_format($reportData['outstanding_balance'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Repayment Rate
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-amber-600 dark:text-amber-300">
                                {{ number_format($reportData['repayment_rate'], 1) }}%
                            </flux:heading>
                        </div>
                    </div>
                @elseif($reportType === 'healthClaims')
                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Billed
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-blue-600 dark:text-blue-300">
                                ₦{{ number_format($reportData['total_billed_amount'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Covered
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-green-600 dark:text-green-300">
                                ₦{{ number_format($reportData['total_covered_amount'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Copay
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-red-600 dark:text-red-300">
                                ₦{{ number_format($reportData['total_copay_amount'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Coverage Rate
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-amber-600 dark:text-amber-300">
                                {{ number_format($reportData['coverage_rate'], 1) }}%
                            </flux:heading>
                        </div>
                    </div>
                @elseif($reportType === 'fundLedger')
                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Current Balance
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-blue-600 dark:text-blue-300">
                                ₦{{ number_format($reportData['current_balance'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Inflows
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-green-600 dark:text-green-300">
                                ₦{{ number_format($reportData['total_inflows'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Outflows
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-red-600 dark:text-red-300">
                                ₦{{ number_format($reportData['total_outflows'], 2) }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Transactions
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-purple-600 dark:text-purple-300">
                                {{ $reportData['total_transactions'] }}
                            </flux:heading>
                        </div>
                    </div>
                @elseif($reportType === 'eligibility')
                    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Total Members
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-blue-600 dark:text-blue-300">
                                {{ $reportData['total_members'] }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Eligible Outpatient
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-green-600 dark:text-green-300">
                                {{ $reportData['eligible_outpatient'] }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Eligible Inpatient
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-amber-600 dark:text-amber-300">
                                {{ $reportData['eligible_inpatient'] }}
                            </flux:heading>
                        </div>
                        <div class="rounded-xl border border-neutral-200 bg-white p-4 sm:p-5 dark:border-neutral-700 dark:bg-neutral-800">
                            <flux:text class="text-xs sm:text-sm font-medium text-neutral-600 dark:text-neutral-400">
                                Not Eligible
                            </flux:text>
                            <flux:heading size="lg" class="mt-2 font-semibold text-red-600 dark:text-red-300">
                                {{ $reportData['not_eligible'] }}
                            </flux:heading>
                        </div>
                    </div>
                @endif

                <!-- Report Data Table -->
                @if(isset($reportData['members']) || isset($reportData['contributions']) || isset($reportData['loans']) || isset($reportData['claims']) || isset($reportData['transactions']))
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                            <thead class="bg-neutral-50 dark:bg-neutral-900">
                                <tr>
                                    @if($reportType === 'membership' || $reportType === 'eligibility')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Registration No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">State</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">LGA</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Status</th>
                                        @if($reportType === 'eligibility')
                                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Outpatient Eligible</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Inpatient Eligible</th>
                                        @endif
                                    @elseif($reportType === 'contribution')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Receipt No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Member</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Plan</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Payment Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Status</th>
                                    @elseif($reportType === 'loan')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Loan ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Member</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Outstanding</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Status</th>
                                    @elseif($reportType === 'healthClaims')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Claim No</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Member</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Provider</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Billed Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Covered Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Status</th>
                                    @elseif($reportType === 'fundLedger')
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Source</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Description</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Reference</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @php
                                    $dataKey = $reportType === 'healthClaims' ? 'claims' : ($reportType . 's');
                                    $items = $reportData[$dataKey] ?? [];
                                @endphp
                                
                                @foreach($items as $item)
                                    <tr>
                                        @if($reportType === 'membership' || $reportType === 'eligibility')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item['registration_no'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['full_name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['state'] ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['lga'] ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($item['status']) }}</td>
                                            @if($reportType === 'eligibility')
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($item['outpatient_eligible'])
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                                    @else
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">No</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($item['inpatient_eligible'])
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Yes</span>
                                                    @else
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">No</span>
                                                    @endif
                                                </td>
                                            @endif
                                        @elseif($reportType === 'contribution')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item['receipt_number'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['member_name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['plan_name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦{{ number_format($item['amount'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['payment_date'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($item['status']) }}</td>
                                        @elseif($reportType === 'loan')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ $item['id'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['member_name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['loan_type'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦{{ number_format($item['amount'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦{{ number_format($item['outstanding_balance'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['status'] }}</td>
                                        @elseif($reportType === 'healthClaims')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item['claim_number'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['member_name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['provider_name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['claim_type'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦{{ number_format($item['billed_amount'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦{{ number_format($item['covered_amount'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['status'] }}</td>
                                        @elseif($reportType === 'fundLedger')
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($item['type']) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($item['source']) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">₦{{ number_format($item['amount'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['description'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['transaction_date'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $item['reference'] ?? 'N/A' }}</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
