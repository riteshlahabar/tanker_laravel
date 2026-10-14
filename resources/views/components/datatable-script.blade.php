@props(['table', 'export' => null, 'filters' => []])

{{-- Sends the page's filter inputs with every server-side draw, and keeps the
     export link pointed at the same filtered set. --}}
<script>
    $(function() {
        const table = window.LaravelDataTables['{{ $table }}'];
        const filterIds = @json($filters);

        function currentParams() {
            const params = new URLSearchParams();
            filterIds.forEach(id => params.set(id, $('#filter-' + id).val() ?? ''));
            params.set('search', table.search() || '');
            return params.toString();
        }

        table.on('preXhr.dt', function(e, settings, data) {
            filterIds.forEach(id => data[id] = $('#filter-' + id).val());
        });

        @if ($export)
            function syncExport() {
                $('#export-link').attr('href', '{{ $export }}?' + currentParams());
            }
            table.on('search.dt draw.dt', syncExport);
        @endif

        filterIds.forEach(id => $('#filter-' + id).on('change', () => table.draw()));
    });
</script>
