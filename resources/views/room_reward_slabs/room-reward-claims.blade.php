@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Room Reward Claims</h5>
        </div>
    </div>

    <div class="card-body table-padding">
        <div class="table-responsive scrollbar">
            <table class="table table-striped table-datatable w-100">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Room Owner</th>
                        <th>Reward Date</th>
                        <th>Room Contribution</th>
                        <th>Reward Coins</th>
                        <th>Claim Status</th>
                        <th>Claimed At</th>
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
                url: "{{ route('room_reward_claims') }}"
            },
            columns: [{
                    data: 'room_id',
                    name: 'room_id'
                },
                {
                    data: 'owner_id',
                    name: 'owner_id'
                },
                {
                    data: 'reward_date',
                    name: 'reward_date'
                },
                {
                    data: 'room_contribution',
                    name: 'room_contribution'
                },
                {
                    data: 'reward_coins',
                    name: 'reward_coins'
                },
                {
                    data: 'is_claimed',
                    name: 'is_claimed',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'claimed_at',
                    name: 'claimed_at'
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
        $(document).on('click', '.delete', function() {

            let id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: "{{ route('room_reward_claims.delete') }}",
                        type: "DELETE",
                        data: {id: id,_token: "{{ csrf_token() }}"},
                        success: function(data) {

                            if (data.status) {

                                Swal.fire('', data.message, 'success');
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