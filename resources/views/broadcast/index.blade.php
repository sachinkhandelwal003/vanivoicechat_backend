@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Broadcast :: Broadcast List</h5>
            </div>
            <!-- <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    @if(Helper::userCan(104, 'can_add'))
                    <a href="{{ route('banner.add') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i>
                        Add Banner
                    </a>
                    @endif
                </div>
            </div> -->
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-striped table-datatable" style="width:100%">

                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Broadcast Message</th>
                        <th>Broadcast Time</th>
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
                url: "{{ route('broadcast') }}"
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'user_info'
                },
                {
                    data: 'message'
                },
                {
                    data: 'time'
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
                        url: "{{ route('broadcast') }} ",
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
