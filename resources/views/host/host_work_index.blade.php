@extends('layouts.app')

@section('content')
    <div class="card">
        {{-- Header --}}
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    Host Work Data
                </h4>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card-body border-bottom py-3">
            <div class="row align-items-end g-3">
                {{-- Country --}}
                <div class="col-md-3">
                    <label for="filter_country" class="form-label"> Country</label>
                    <select id="filter_country" class="form-select">
                        <option value=""> All Countries</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}">
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Salary Cycle --}}
                <div class="col-md-3">
                    <label for="filter_cycle" class="form-label">Salary Cycle</label>
                    <select id="filter_cycle" class="form-select">
                        <option value="">All Cycles</option>
                        <option value="1-15">1 - 15 Cycle</option>
                        <option value="16-end">16 - End Cycle</option>
                    </select>
                </div>

                {{-- Month --}}
                <div class="col-md-3">
                    <label for="filter_month" class="form-label">Month</label>
                    <input type="month" id="filter_month" class="form-control">
                </div>

                {{-- Buttons --}}
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" id="filterBtn">
                            <i class="fas fa-filter me-1"></i>Search
                        </button>

                        <button type="button" class="btn btn-secondary" id="resetBtn">
                            <i class="fas fa-rotate-left me-1"></i>Reset
                        </button>

                        <button type="button" class="btn btn-success" id="exportBtn">
                            <i class="fas fa-file-excel me-1"></i>Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered w-100" id="hostTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Agency Information</th>
                            <th>Country</th>
                            <th>Cycle</th>
                            <th>Gift</th>
                            <th>Salary</th>
                            {{-- Status intentionally hidden --}}
                            {{-- <th>Status</th> --}}
                            <th>Time</th>
                            {{-- <th> Operate</th> --}}
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(function() {

            // DataTable

            let table = $('#hostTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('host.work') }}",
                    data: function(d) {
                        d.country = $('#filter_country').val();
                        d.cycle = $('#filter_cycle').val();
                        d.month = $('#filter_month').val();
                    }
                },

                order: [
                    [3, 'desc']
                ],
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'agency',
                        name: 'agency',
                        orderable: false
                    },
                    {
                        data: 'country',
                        name: 'country',
                        orderable: true
                    },
                    {
                        data: 'cycle',
                        name: 'cycle',
                        orderable: true
                    },
                    {
                        data: 'gift',
                        name: 'gift',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'salary',
                        name: 'salary',
                        orderable: false,
                        searchable: false
                    },
                    //    Status
                    // {
                    //     data: 'status',
                    //     name: 'status',
                    //     orderable: false,
                    //     searchable: false
                    // },
                    {
                        data: 'time',
                        name: 'time',
                        orderable: false,
                        searchable: false
                    },
                    // {
                    //     data: 'operate',
                    //     name: 'operate',
                    //     orderable: false,
                    //     searchable: false
                    // }
                ]
            });

            // Filter

            $('#filterBtn').on('click', function() {
                table.ajax.reload();
            });

            // Reset

            $('#resetBtn').on('click', function() {
                $('#filter_country').val('');
                $('#filter_cycle').val('');
                $('#filter_month').val('');
                table.ajax.reload();
            });

            // Export

            $('#exportBtn').on('click', function() {
                let country = $('#filter_country').val();
                let cycle = $('#filter_cycle').val();
                let month = $('#filter_month').val();

                let exportUrl =
                    "{{ route('host.work.export') }}" +
                    "?country=" + encodeURIComponent(country) +
                    "&cycle=" + encodeURIComponent(cycle) +
                    "&month=" + encodeURIComponent(month);

                window.location.href = exportUrl;
            });

            // Optional: Enter Key Filter

            $('#filter_country, #filter_cycle, #filter_month').on(
                'change',
                function() {
                    // Agar automatic filtering chahiye to
                    // neeche wali line uncomment kar sakte ho.

                    // table.ajax.reload();
                }
            );
        });
    </script>
@endsection
