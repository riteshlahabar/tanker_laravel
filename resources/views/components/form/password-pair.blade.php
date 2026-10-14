@props(['editing' => false])

<x-form.text name="password" label="App Password" type="password" :required="! $editing"
    :help="$editing ? 'Leave blank to keep the current password.' : 'At least 8 characters.'" />

<x-form.text name="password_confirmation" label="Confirm Password" type="password" :required="! $editing" />
