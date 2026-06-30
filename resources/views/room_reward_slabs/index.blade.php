@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">

            <h5 class="mb-0">Room Reward Slab</h5>

            <div class="d-flex align-items-center gap-2">

                {{-- ADD BUTTON --}}
                @if(Helper::userCan(104, 'can_add'))
                <a href="{{ route('room_reward_slabs.add') }}" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa fa-plus me-1"></i> Add Room Reward
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
                        <th>Room Contribution</th>
                        <th>Reward Coins</th>
                        <th>Sort Order</th>
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
    $(function() {

        var table = $('.table-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('room_reward_slabs') }}",
            },
            order: [
                [1, 'asc']
            ],
            columns: [{
                    data: 'room_contribution',
                    name: 'room_contribution'
                },
                {
                    data: 'reward_coins',
                    name: 'reward_coins'
                },
                {
                    data: 'sort_order',
                    name: 'sort_order'
                },
                {
                    data: 'status',
                    name: 'status',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });


        // DELETE
        $(document).on('click', ".delete", function() {
            var id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('room_reward_slabs') }}",
                        type: "DELETE",
                        data: {
                            id: id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(data) {
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