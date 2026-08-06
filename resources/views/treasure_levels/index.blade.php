@extends('layouts.app')

@section('content')
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Treasure Levels</h4>
            @if (Helper::userCan(111, 'can_add'))
                <a href="{{ route('treasure-levels.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> Add Treasure Level
                </a>
            @endif
        </div>


        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-striped treasureTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Level</th>
                            <th>Target Points</th>
                            <th>Chest Image</th>
                            <th>Rewards</th>
                            <th>Status</th>
                            <th width="120">Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>

    <script>
        $(function() {
            let table = $('.treasureTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('treasure-levels.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'level',
                        name: 'level'
                    },
                    {
                        data: 'target_points',
                        name: 'target_points'
                    },
                    {
                        data: 'chest_image',
                        name: 'chest_image',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'rewards_count',
                        name: 'rewards_count',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

            $(document).on('click', ".delete", function() {
                var id = $(this).data('id')
                Swal.fire(deleteMessageSwalConfig).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('treasure-levels.destroy') }} ",
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
@endpush
