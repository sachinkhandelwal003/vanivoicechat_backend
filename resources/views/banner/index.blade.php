@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Banner :: Banner List</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    @if(Helper::userCan(104, 'can_add'))
                    <a href="{{ route('banner.add') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i>
                        Add Banner
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="row mb-3">

            <div class="col-md-2">
                <select id="expired" class="form-control">
                    <option value="">Select Expiration</option>
                    <option value="expired">Expired</option>
                    <option value="not_expired">Not Expired</option>
                </select>
            </div>
            <div class="col-md-2">
                <select id="jump" class="form-control">
                    <option value="">Select Jump Type</option>
                    <option value="h5">H5</option>
                    <option value="app">App</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" id="searchBtn">Search</button>
            </div>

        </div>

        <div class="table-responsive">
            <table class="table table-striped table-datatable" style="width:100%">

                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Cover</th>
                        <th>Jump Type</th>
                        <th>Display Space</th>
                        <th>Logical Area</th>
                        <th>Redirect Address</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Operate</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>
        </div>

    </div>
</div>
@endsection


@section('js')
<script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
<script>
    $(function() {

        let table = $('.table-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('banner') }}",
                data: function(d) {
                    d.expired = $('#expired').val();
                    d.jump = $('#jump').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'large_banner'
                },
                {
                    data: 'jump'
                },
                {
                    data: 'display'
                },
                {
                    data: 'country_name'
                },
                {
                    data: 'redirect_address'
                },
                {
                    data: 'start_time'
                },
                {
                    data: 'end_time'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('#searchBtn').click(() => table.ajax.reload());

        $(document).on('click', ".delete", function() {
            var id = $(this).data('id')
            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('banner') }} ",
                        data: {
                            'id': id
                        },
                        type: 'DELETE',
                        success: function(data) {
                            if (data.status) {
                                Swal.fire('', data?.message, "success")
                                table.draw();
                            } else {
                                toastr.error(data.message);
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endsection