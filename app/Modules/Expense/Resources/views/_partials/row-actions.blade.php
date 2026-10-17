@include('admin.inc.action', [
    'edit' => 'admin.expense.edit',
    'delete' => 'admin.expense.destroy',
    'data' => $expense,
])
