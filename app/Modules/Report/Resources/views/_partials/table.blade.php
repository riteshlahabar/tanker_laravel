<div class="table-responsive custom-scrollbar">
    <table class="table table-striped mb-0">
        <thead>
            <tr>
                @foreach ($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $index => $cell)
                        <td>{{ \App\Modules\Report\Support\CellFormat::render($headings[$index] ?? '', $cell) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="text-center f-light" colspan="{{ count($headings) }}">
                        No records in this range.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
