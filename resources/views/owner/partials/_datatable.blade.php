{{--
  DataTables initializer.
  Variables:
    $tableId    – HTML id of the <table> (required)
    $exportName – filename for exports
    $noSortCols – array of column indexes (0-based or negative) not sortable
    $scrollX    – bool, horizontal scroll
--}}
@php
    $tableId    = $tableId    ?? 'bk-dt';
    $exportName = $exportName ?? $tableId;
    $noSortCols = $noSortCols ?? [];
    $scrollX    = $scrollX    ?? false;

    $columnDefsJson = json_encode(array_values(array_filter([
        ['targets' => '_all', 'defaultContent' => ''],
        empty($noSortCols) ? null : ['targets' => $noSortCols, 'orderable' => false],
    ])));
@endphp

@push('scripts')
@once
<style>
/* ── DataTables overrides ────────────────────────────────── */

/* Hide DT native search/length/paging — we use our own */
.dataTables_filter,
.dataTables_length,
.dataTables_paginate,
div.dt-search,
div.dt-length { display: none !important; }

/* Info */
.dataTables_info, div.dt-info {
    font-size: .78rem;
    color: rgba(255,255,255,.4);
    padding: 8px 16px 6px !important;
}
.bk-theme-light .dataTables_info,
.bk-theme-light div.dt-info { color: rgba(0,0,0,.4); }

/* Sort icons */
table.dataTable thead th.sorting { cursor: pointer; user-select: none; }
table.dataTable thead th.sorting_asc::after  { color: #C9A227 !important; opacity: 1 !important; }
table.dataTable thead th.sorting_desc::after { color: #C9A227 !important; opacity: 1 !important; }

/* Export buttons */
.dt-buttons {
    display: flex; flex-wrap: wrap; gap: 6px;
    padding: 10px 16px 6px;
    border-bottom: 1px solid rgba(255,255,255,.05);
}
.bk-theme-light .dt-buttons { border-bottom-color: rgba(0,0,0,.05); }

.dt-button,
.dt-button:active,
.dt-button:focus {
    display: inline-flex !important; align-items: center; gap: 5px;
    height: 30px; padding: 0 12px !important;
    font-size: .76rem !important; font-weight: 600;
    border-radius: 8px !important;
    border: 1px solid rgba(255,255,255,.12) !important;
    background: rgba(255,255,255,.06) !important;
    color: rgba(255,255,255,.65) !important;
    box-shadow: none !important; text-shadow: none !important;
    cursor: pointer; outline: none !important;
    transition: background .15s, color .15s, border-color .15s;
}
.dt-button:hover { background: rgba(255,255,255,.13) !important; color: #fff !important; }
.bk-theme-light .dt-button {
    background: #f0f2f5 !important; color: #374151 !important;
    border-color: rgba(0,0,0,.1) !important;
}
.bk-theme-light .dt-button:hover { background: #e2e6ea !important; color: #111 !important; }

.dt-button.buttons-excel:hover { background: rgba(34,197,94,.15) !important; color: #4ade80 !important; border-color: rgba(34,197,94,.35) !important; }
.dt-button.buttons-pdf:hover   { background: rgba(239,68,68,.15)  !important; color: #f87171 !important; border-color: rgba(239,68,68,.35)  !important; }
.dt-button.buttons-print:hover { background: rgba(99,102,241,.15) !important; color: #a5b4fc !important; border-color: rgba(99,102,241,.35) !important; }
.bk-theme-light .dt-button.buttons-excel:hover { color: #16a34a !important; }
.bk-theme-light .dt-button.buttons-pdf:hover   { color: #dc2626 !important; }
.bk-theme-light .dt-button.buttons-print:hover { color: #4f46e5 !important; }

/* "no results" row highlight */
table.dataTable tbody td.dataTables_empty {
    text-align: center; padding: 40px; font-size: .87rem;
    color: rgba(255,255,255,.35);
}
.bk-theme-light table.dataTable tbody td.dataTables_empty { color: rgba(0,0,0,.35); }

/* wrapper cleanup */
.dataTables_wrapper { padding: 0; }
</style>
@endonce

<script>
(function () {
    'use strict';

    function bkInitDt() {
        if (typeof $.fn.DataTable === 'undefined') return;

        var tableId  = '{{ $tableId }}';
        var $table   = $('#' + tableId);
        if (!$table.length) return;
        if ($.fn.DataTable.isDataTable('#' + tableId)) return;

        // Detect empty table (single colspan row = empty state)
        var $bodyRows = $table.find('tbody tr');
        var isEmpty   = $bodyRows.length === 0 ||
                        ($bodyRows.length === 1 && $bodyRows.first().find('td[colspan]').length > 0);

        var dt = $table.DataTable({
            paging:    false,
            searching: true,    // DataTables handles search (no page refresh)
            info:      !isEmpty,
            ordering:  !isEmpty,

            columnDefs: {!! $columnDefsJson !!},

            @if($scrollX)
            scrollX: true,
            @endif

            // Put export buttons above table; DT search is hidden (we wire our own input)
            dom: '<"dt-buttons"B>t<"px-3 pb-2"i>',

            buttons: [
                {
                    extend:    'excelHtml5',
                    text:      '📊 Excel',
                    title:     '{{ $exportName }}',
                    filename:  '{{ Str::slug($exportName) }}',
                    className: 'buttons-excel',
                    exportOptions: { columns: ':visible:not(.no-export)' }
                },
                {
                    extend:    'pdfHtml5',
                    text:      '📄 PDF',
                    title:     '{{ $exportName }}',
                    filename:  '{{ Str::slug($exportName) }}',
                    className: 'buttons-pdf',
                    orientation: 'landscape',
                    pageSize:  'A4',
                    exportOptions: { columns: ':visible:not(.no-export)' }
                },
                {
                    extend:    'print',
                    text:      '🖨 طباعة',
                    title:     '{{ $exportName }}',
                    className: 'buttons-print',
                    exportOptions: { columns: ':visible:not(.no-export)' }
                },
            ],

            language: {
                info:           'عرض _START_ – _END_ من _TOTAL_ سجل',
                infoEmpty:      'لا توجد سجلات',
                infoFiltered:   '(من _MAX_ إجمالاً)',
                zeroRecords:    'لا نتائج مطابقة',
                loadingRecords: 'جارٍ التحميل…',
                search:         'بحث:',
            },
        });

        // Wire our custom search input to DataTables API
        var $inp   = $('#bk-dts-' + tableId);
        var $clear = $('#bk-dts-clear-' + tableId);

        if ($inp.length) {
            // Restore value if DataTables keeps state
            var currentSearch = dt.search();
            if (currentSearch) {
                $inp.val(currentSearch);
                $clear.show();
            }

            $inp.on('input', function () {
                var val = $(this).val();
                dt.search(val).draw();
                $clear.toggle(val.length > 0);
            });

            $clear.on('click', function () {
                $inp.val('');
                dt.search('').draw();
                $(this).hide();
                $inp.focus();
            });
        }

        // Re-init feather icons if any appear inside DT-rendered rows
        if (typeof feather !== 'undefined') {
            setTimeout(function () { feather.replace(); }, 50);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bkInitDt);
    } else {
        bkInitDt();
    }
})();
</script>
@endpush
