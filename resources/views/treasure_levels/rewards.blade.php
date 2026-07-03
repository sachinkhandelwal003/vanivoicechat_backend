@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">

            <h5 class="mb-0">Treasure Level Rewards</h5>

            <div>
                <a href="{{ route('treasure-levels.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>

        </div>
    </div>

    <div class="card-body table-padding">

        <div class="table-responsive scrollbar">

            <table class="table table-striped table-datatable w-100">

                <thead>
                    <tr>
                        <th>Room</th>
                        <th>User</th>
                        <th>Level</th>
                        <th>Reward</th>
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

            ajax: {url: "{{ route('treasure-level-rewards') }}"},

            order: [[0, 'desc']],

            columns: [
                { data: 'room', name: 'room' },
                { data: 'user', name: 'user' },
                {
                    data: 'level',
                    name: 'level',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'reward',
                    name: 'reward',
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
        $(document).on('click', '.delete', function() {

            let id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({

                        url: "{{ route('treasure-level-rewards.delete') }}",

                        type: "DELETE",

                        data: {
                            id: id,
                            _token: "{{ csrf_token() }}"
                        },

                        success: function(data) {

                            if (data.status) {

                                Swal.fire(
                                    '',
                                    data.message,
                                    'success'
                                );

                                table.draw();

                            } else {

                                toastr.error(data.message);

                            }

                        },

                        error: function() {

                            toastr.error('Something went wrong');

                        }

                    });

                }

            });

        });

    });
</script>

@endsection