<x-form.text name="company_name" label="Company Name" :value="$settings['company_name'] ?? 'Tanker'" required />

<x-form.text name="admin_whatsapp_number" label="Owner WhatsApp Number"
    :value="$settings['admin_whatsapp_number'] ?? ''" placeholder="919812345678"
    help="With country code, digits only. All owner notifications and the daily report go here." />

<x-form.text name="default_driver_rate" label="Default Driver Rate (₹ per delivery)" type="number"
    :value="$settings['default_driver_rate'] ?? 150" :attrs="['step' => '0.01', 'min' => 0]" required />

<x-form.text name="default_order_amount" label="Default Order Amount (₹)" type="number"
    :value="$settings['default_order_amount'] ?? 0" help="Flat price per delivery. Pre-filled on a new order."
    :attrs="['step' => '0.01', 'min' => 0]" required />

<x-form.text name="price_per_tanker" label="Price per Tanker (₹)" type="number"
    :value="$settings['price_per_tanker'] ?? 0"
    help="The price of one tanker-load, used for vehicles with no rate of their own. Leave at 0 to charge the flat amount above."
    :attrs="['step' => '0.01', 'min' => 0]" required />

<x-form.text name="daily_report_time" label="Daily Report Time" type="time"
    :value="$settings['daily_report_time'] ?? '23:59'"
    help="For reference — the schedule itself is set in routes/console.php." required />

<x-form.actions :cancel="route('admin.dashboard')" label="Save Settings" />
