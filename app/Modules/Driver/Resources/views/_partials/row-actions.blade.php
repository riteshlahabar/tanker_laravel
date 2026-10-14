@include('admin.inc.action', [
    'edit' => 'admin.driver.edit',
    'delete' => 'admin.driver.destroy',
    'data' => $driver,
    'extra' => view('driver::_partials.change-tanker-link', ['driver' => $driver])->render(),
])
