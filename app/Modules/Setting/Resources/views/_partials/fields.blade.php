<x-form.text name="company_name" label="Company Name" :value="$settings['company_name'] ?? 'Tanker'" required />

<x-form.text name="admin_whatsapp_number" label="Owner WhatsApp Number"
    :value="$settings['admin_whatsapp_number'] ?? ''" placeholder="919812345678"
    help="With country code, digits only. All owner notifications and the daily report go here." />

<x-form.actions :cancel="route('admin.dashboard')" label="Save Settings" />
