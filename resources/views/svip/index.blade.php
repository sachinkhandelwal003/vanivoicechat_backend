@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">SVIP :: SVIP List</h5>
            </div>

            <div class="col-auto ms-auto d-flex gap-2">

                {{-- ADD PRIVILEGE --}}
                @if(Helper::userCan(123, 'can_add'))
                <a href="{{ route('svip-privilege.list') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-star me-1"></i> Add Privilege
                </a>
                @endif

                {{-- ADD SVIP --}}
                @if(Helper::userCan(123, 'can_add'))
                <a href="{{ route('svip.form') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-plus me-1"></i> Add SVIP
                </a>
                @endif

            </div>
        </div>
    </div>

    <div class="card-body table-padding">
        <div class="table-responsive scrollbar">
            <table class="table table-striped table-datatable w-100">
                <thead>
                    <tr>
                        <th>Medal</th>
                        <!-- <th>GIF</th> -->
                        <th>Name</th>
                        <th>Coins</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th width="100px">Action</th>
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
    $(function () {

        var table = $('.table-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('svip') }}",
            order: [[6, 'desc']],
            columns: [
                { data: 'medal', name: 'medal', orderable: false, searchable: false },
                // { data: 'medal_gif', name: 'medal_gif', orderable: false, searchable: false },
                { data: 'name', name: 'name' },
                { data: 'need_coins', name: 'need_coins' },
                { data: 'days', name: 'days' },
                { data: 'status', name: 'status' },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // DELETE
        $(document).on('click', ".delete", function () {
            var id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('svip.delete') }}",
                        type: "DELETE",
                        data: {
                            id: id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (data) {
                            if (data.status) {
                                Swal.fire('', data.message, "success");
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
