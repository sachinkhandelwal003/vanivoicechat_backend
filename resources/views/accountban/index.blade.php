@extends('layouts.app')

@section('content')

<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">Blocking Management</h5>
    </div>

    <div class="card-body">

        {{-- TABS --}}
        <ul class="nav nav-tabs" id="blockTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#account-ban">Account Ban</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#device-ban">Blocking Equipment</a>
            </li>

            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#ip-ban">IP Blocking</a>
            </li>
        </ul>



        <div class="tab-content mt-3">

            {{-- ACCOUNT BAN TAB --}}
            <div class="tab-pane fade show active" id="account-ban">
                <table class="table table-striped dt-account table-datatable" style="width:100%">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Operator</th>
                            <th>Remark</th>
                            <th>Operation Time</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>


            {{-- DEVICE BLOCK TAB --}}
            <div class="tab-pane fade" id="device-ban">
                <table class="table table-striped dt-device table-datatable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Device Number</th>
                            <th>Operator</th>
                            <th>Remark</th>
                            <th>Operation Time</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>


            {{-- IP BLOCK TAB --}}
            <div class="tab-pane fade" id="ip-ban">
                <table class="table table-striped dt-ip table-datatable" style="width:100%">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Operator</th>
                            <th>Remark</th>
                            <th>Operation Time</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>

        </div>

    </div>
</div>

@endsection



@section('js')
<script>
$(function () {

    // ACCOUNT BAN TABLE
    $('.dt-account').DataTable({
        ajax: "{{ route('blocking-device') }}",
        columns: [
            { data: 'user_id' },
            { data: 'operator' },
            { data: 'remark' },
            { data: 'operation_time' },
            { data: 'created_at' },
            { data: 'action', orderable:false, searchable:false }
        ]
    });


    // DEVICE BAN TABLE
    $('.dt-device').DataTable({
        ajax: "{{ route('blocking-device.devices') }}",
        columns: [
            { data: 'device_number' },
            { data: 'operator' },
            { data: 'remark' },
            { data: 'operation_time' },
            { data: 'created_at' },
            { data: 'action', orderable:false, searchable:false }
        ]
    });


    // IP BAN TABLE
    $('.dt-ip').DataTable({
        ajax: "{{ route('blocking-device.ips') }}",
        columns: [
            { data: 'ip_address' },
            { data: 'operator' },
            { data: 'remark' },
            { data: 'operation_time' },
            { data: 'created_at' },
            { data: 'action', orderable:false, searchable:false }
        ]
    });

});

</script>
@endsection
