<x-form.text name="company_name" label="Company Name" :value="$settings['company_name'] ?? 'Tanker'" required />

<x-form.text name="admin_whatsapp_number" label="Owner WhatsApp Number"
    :value="$settings['admin_whatsapp_number'] ?? ''" placeholder="919812345678"
    help="With country code, digits only. All owner notifications and the daily report go here." />

<x-form.text name="default_monthly_salary" label="Default Monthly Salary (₹)" type="number"
    :value="$settings['default_monthly_salary'] ?? 15000"
    help="Pre-filled on a new driver. Each driver's own salary is set on their form."
    :attrs="['step' => '0.01', 'min' => 0]" required />

<x-form.text name="daily_report_time" label="Daily Report Time" type="time"
    :value="$settings['daily_report_time'] ?? '23:59'"
    help="For reference — the schedule itself is set in routes/console.php." required />

<x-form.actions :cancel="route('admin.dashboard')" label="Save Settings" />
