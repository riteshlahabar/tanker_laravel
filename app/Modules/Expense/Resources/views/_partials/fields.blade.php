@php($expense = $expense ?? null)

@if ($expense === null)
    {{-- Only on create: an edit never moves spending from one driver to
         another, which would quietly rewrite both their totals. --}}
    <x-form.select name="driver_id" label="Driver" :value="$expense?->driver_id" :options="$driverOptions"
        placeholder="— Select driver —" help="Whose expense this is." required />
@else
    <x-form.text name="driver_name" label="Driver" :value="$expense->driver?->full_name"
        help="The driver cannot be changed on an edit. Delete and re-add to move it."
        :attrs="['disabled' => 'disabled']" />
@endif

<x-form.text name="expense_date" label="Date Spent" type="date"
    :value="$expense?->expense_date?->toDateString() ?? now()->toDateString()"
    help="When the money was spent, not when it was entered."
    :attrs="['max' => now()->toDateString(), 'min' => now()->subDays(\App\Modules\Expense\Requests\StoreExpenseRequest::BACKDATE_DAYS)->toDateString()]"
    required />

<x-form.select name="category" label="Category" :value="$expense?->category?->value"
    :options="$categoryOptions" placeholder="— Select category —" required />

<x-form.text name="amount" label="Amount (₹)" type="number" :value="$expense?->amount"
    :attrs="['step' => '0.01', 'min' => '0.01']" required />

<x-form.select name="tanker_id" label="Tanker" :value="$expense?->tanker_id" :options="$tankerOptions"
    placeholder="— Not vehicle-specific —" help="Leave blank for a toll or a meal." />

<x-form.textarea name="notes" label="Notes" :value="$expense?->notes" rows="2" />

<x-form.actions :cancel="route('admin.expense.index')"
    :label="$expense ? 'Update Expense' : 'Save Expense'" />
