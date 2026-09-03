@extends('layouts.app')

@section('content')

<div class="card mb-3">

    <div class="card-header">
        <div class="row align-items-center">

            <div class="col">
                <h5 class="mb-1">
                    <i class="fas fa-money-check-alt text-primary me-2"></i>
                    Salary Auto-Credit Log
                </h5>

                <small class="text-muted">
                    Bi-monthly host salary payment audit trail
                </small>
            </div>

        </div>
    </div>

    <div class="card-body">
        <div class="row mb-4">
            <div class="col-xl col-md-6 mb-3">

                <div class="card border h-100 text-center shadow-sm">

                    <div class="card-body">
                        <i class="far fa-file-alt text-primary fa-2x mb-2"></i>
                        <h3 class="fw-bold mb-1" id="total_records">0</h3>
                        <small class="text-muted text-uppercase">
                            Total Records
                        </small>
                    </div>

                </div>

            </div>

            <div class="col-xl col-md-6 mb-3">

                <div class="card border h-100 text-center shadow-sm">

                    <div class="card-body">
                        <i class="far fa-check-circle text-success fa-2x mb-2"></i>
                        <h3 class="fw-bold text-success mb-1" id="credited_count">0</h3>
                        <small class="text-muted text-uppercase">
                            Credited
                        </small>
                    </div>

                </div>

            </div>

            <div class="col-xl col-md-6 mb-3">

                <div class="card border h-100 text-center shadow-sm">

                    <div class="card-body">
                        <i class="far fa-times-circle text-danger fa-2x mb-2"></i>
                        <h3 class="fw-bold text-danger mb-1" id="failed_count">0</h3>
                        <small class="text-muted text-uppercase">
                            Failed
                        </small>
                    </div>

                </div>

            </div>

            <div class="col-xl col-md-6 mb-3">

                <div class="card border h-100 text-center shadow-sm">

                    <div class="card-body">
                        <i class="fas fa-dollar-sign text-primary fa-2x mb-2"></i>
                        <h3 class="fw-bold text-primary mb-1">
                            $<span id="host_salary_total">0.00</span>
                        </h3>
                        <small class="text-muted text-uppercase">
                            Host Salary
                        </small>
                    </div>

                </div>

            </div>

            <div class="col-xl col-md-6 mb-3">

                <div class="card border h-100 text-center shadow-sm">

                    <div class="card-body">
                        <i class="fas fa-university text-purple fa-2x mb-2"></i>
                        <h3 class="fw-bold text-purple mb-1">
                            $<span id="agency_commission_total">0.00</span>
                        </h3>
                        <small class="text-muted text-uppercase">
                            Agency Comm.
                        </small>
                    </div>

                </div>

            </div>

        </div>

        <div class="card border-info mb-4">

            <div class="card-body">

                <div class="row align-items-center">
                    <div class="col-md-4">
                        <h5 class="mb-2">
                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                            Manual Salary Run
                        </h5>
                        <small class="text-muted">
                            Only use if the cron did not execute automatically.
                        </small>

                    </div>

                    <div class="col-md-3">
                        <select class="form-select" id="salary_cycle">

                            @foreach($cycles as $cycle)
                            <option value="{{ $cycle['value'] }}">
                                {{ $cycle['label'] }}
                            </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" id="runSalary">
                            <i class="fas fa-play me-1"></i>Run Now</button>
                    </div>

                    <div class="col-md-3">
                        <small class="text-muted">
                            Cron auto-runs at 12:00 AM on the 1st & 16th of every month.
                        </small>
                    </div>

                </div>

            </div>

        </div>

        <div class="row g-2 mb-4 align-items-end">

            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">Cycle</label>
                <select class="form-select" id="cycle">
                    <option value="">All Cycles</option>
                    <option value="1">Cycle 1</option>
                    <option value="2">Cycle 2</option>
                </select>
            </div>

            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">Host UID</label>
                <input type="text"
                    class="form-control"
                    id="host_uid"
                    placeholder="Host UID">
            </div>

            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">Agency ID</label>
                <input type="text"
                    class="form-control"
                    id="agency_id"
                    placeholder="Agency ID">
            </div>

            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">Country</label>
                <select class="form-select" id="country_id">
                    <option value="">All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}">{{ $country->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" id="status">
                    <option value="">All</option>
                    <option value="settled">Settled</option>
                    <option value="failed">Failed</option>
                    <!-- <option value="skipped">Skipped</option> -->
                </select>
            </div>

            {{-- Action Buttons --}}
            <div class="col-12 col-sm-12 col-md-2">
                <div class="d-flex gap-2">
                    <button class="btn btn-primary flex-fill" id="btnSearch">
                        <i class="fas fa-search me-1"></i>Search
                    </button>
                    <button class="btn btn-secondary flex-fill" id="btnReset">
                        <i class="fas fa-sync-alt me-1"></i>Reset
                    </button>
                </div>
            </div>

            {{-- Export Button --}}
            <div class="col-12 col-sm-12 col-md-12">
                <button class="btn btn-success" id="btnExport">
                    <i class="fas fa-file-excel me-1"></i> Export Excel
                </button>
            </div>

        </div>


        <div class="table-responsive scrollbar">

            <table class="table table-bordered table-striped align-middle w-100" id="settlementTable">

                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cycle</th>
                        <th>Host</th>
                        <th>Agency</th>
                        <th>Target Value</th>
                        <th>Level</th>
                        <th>Host Salary</th>
                        <th>Agency Commission</th>
                        <!-- <th>Credited At</th>
                        <th width="80">Action</th> -->
                    </tr>
                </thead>

            </table>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>
    $(document).ready(function() {

        let table = $('#settlementTable').DataTable({

            processing: true,
            serverSide: true,
            destroy: true,
            searching: false,
            responsive: true,

            ajax: {

                url: "{{ route('settlement-log') }}",

                data: function(d) {

                    d.cycle = $("#cycle").val();
                    d.host_uid = $("#host_uid").val();
                    d.agency_id = $("#agency_id").val();
                    d.country_id = $("#country_id").val();
                    d.status = $("#status").val();

                },

                dataSrc: function(json) {

                    $("#total_records").text(json.summary.total_records);
                    $("#credited_count").text(json.summary.credited);
                    $("#failed_count").text(json.summary.failed);
                    $("#host_salary_total").text(json.summary.host_salary);
                    $("#agency_commission_total").text(json.summary.agency_commission);

                    return json.data;

                }


            },

            order: [
                [0, 'desc']
            ],

            columns: [
                {
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'cycle',
                    name: 'cycle'
                },
                {
                    data: 'host',
                    name: 'host',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'agency',
                    name: 'agency',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'target_value',
                    name: 'target_value'
                },
                {
                    data: 'level',
                    name: 'level'
                },
                {
                    data: 'host_salary',
                    name: 'host_salary'
                },
                {
                    data: 'agency_salary',
                    name: 'agency_salary',
                    searchable: false,
                    orderable: false
                },
                // {
                //     data: 'credited_at',
                //     name: 'credited_at'
                // },
                // {
                //     data: 'action',
                //     name: 'action',
                //     searchable: false,
                //     orderable: false
                // }
            ]
        });

        $("#btnSearch").click(function() {
            table.ajax.reload();
        });



        $("#btnReset").click(function() {

            $("#cycle").val("");
            $("#host_uid").val("");
            $("#agency_id").val("");
            $("#country_id").val("");
            $("#status").val("");
            table.ajax.reload();

        });

        $("#btnExport").click(function() {

            let params = new URLSearchParams({
                cycle:      $("#cycle").val(),
                host_uid:   $("#host_uid").val(),
                agency_id:  $("#agency_id").val(),
                country_id: $("#country_id").val(),
                status:     $("#status").val(),
            });

            let url = "{{ route('settlement.export') }}?" + params.toString();

            $(this)
                .prop("disabled", true)
                .html('<i class="fas fa-spinner fa-spin me-1"></i> Exporting...');

            let self = this;

            // Use hidden iframe trick so button re-enables after download starts
            let iframe = $('<iframe style="display:none"></iframe>').appendTo('body');
            iframe.attr('src', url);

            setTimeout(function() {
                $(self)
                    .prop("disabled", false)
                    .html('<i class="fas fa-file-excel me-1"></i> Export Excel');
                iframe.remove();
            }, 3000);

        });

    });

    $("#runSalary").click(function() {

        let cycle = $("#salary_cycle").val();

        $.ajax({

            url: "{{ route('settlement.run-host-salary') }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                cycle: cycle
            },

            beforeSend: function() {

                $("#runSalary")
                    .prop("disabled", true)
                    .html('<i class="fas fa-spinner fa-spin"></i> Running...');
            },

            success: function(res) {

                $("#runSalary")
                    .prop("disabled", false)
                    .html('<i class="fas fa-play"></i> Run Now');

                toastr.success(res.message);

                $('#settlementTable').DataTable().ajax.reload();

            },

            error: function() {

                $("#runSalary")
                    .prop("disabled", false)
                    .html('<i class="fas fa-play"></i> Run Now');

                toastr.error("Something went wrong.");

            }

        });

    });
</script>

@endpush