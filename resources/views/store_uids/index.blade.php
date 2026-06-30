@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Store Uid :: Store Uid List</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
    
                    @if(Helper::userCan(105, 'can_add'))
                    <a href="{{ route('rank') }}" class="btn btn-outline-primary" style="margin-right: 5px;">
                        <i class="fa fa-layer-group me-1"></i>
                        Add Rank
                    </a>
                    @endif

                    @if(Helper::userCan(106, 'can_add'))
                    <a href="{{ route('pattern') }}" class="btn btn-outline-info" style="margin-right: 5px;">
                        <i class="fa fa-shapes me-1"></i>
                        Add Pattern
                    </a>
                    @endif
                    @if(Helper::userCan(104, 'can_add'))
                    <a href="{{ route('store.uid.add') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i>
                        Add Store Uid
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-striped table-datatable" style="width:100%">

                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Rank</th>
                        <th>Pattern</th>
                        <th>Visibility Type</th>
                        <th>Unique ID</th>
                        <th>UID Badge</th>
                        <th>Rank Badge</th>
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
                url: "{{ route('store.uid') }}",
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'rank_name'
                },
                {
                    data: 'pattern_name'
                },
                {
                    data: 'visibility_type'
                },
                {
                    data: 'unique_id'
                },
                {
                    data: 'badge'
                },
                {
                    data: 'rank_badge'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $(document).on('click', ".delete", function() {
            var id = $(this).data('id')
            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('store.uid') }} ",
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