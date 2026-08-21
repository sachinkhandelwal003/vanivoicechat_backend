@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">Game :: Game List</h5>
                </div>

                <div class="col-auto ms-auto d-flex gap-2">

                    @if (Helper::userCan(142, 'can_add'))
                        <a href="{{ route('game.add') }}" class="btn btn-outline-secondary">
                            <i class="fa fa-plus me-1"></i> Add Game
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
                            <th width="70px">Icon</th>
                            <th>Game Name</th>
                            <th>SUD Game ID</th>
                            <th>Entry Coins</th>
                            <th>Min Coins</th>
                            <th>Max Coins</th>
                            <th>Featured</th>
                            <th>Status</th>
                            <th>Sort Order</th>
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

                ajax: "{{ route('game') }}",

                order: [
                    [9, 'desc']
                ],

                columns: [

                    {
                        data: 'icon',
                        name: 'icon',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'name',
                        name: 'name'
                    },

                    {
                        data: 'sud_game_id',
                        name: 'sud_game_id'
                    },

                    {
                        data: 'entry_coins',
                        name: 'entry_coins'
                    },

                    {
                        data: 'min_coins',
                        name: 'min_coins'
                    },

                    {
                        data: 'max_coins',
                        name: 'max_coins'
                    },

                    {
                        data: 'is_featured',
                        name: 'is_featured'
                    },

                    {
                        data: 'status',
                        name: 'status'
                    },

                    {
                        data: 'sort_order',
                        name: 'sort_order'
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


            /*
            |--------------------------------------------------------------------------
            | DELETE GAME
            |--------------------------------------------------------------------------
            */

            $(document).on('click', ".delete", function() {

                var id = $(this).data('id');

                Swal.fire(deleteMessageSwalConfig).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({

                            url: "{{ route('game') }}",

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
                                        "success"
                                    );

                                    table.draw();

                                } else {

                                    toastr.error(data.message);

                                }

                            },

                            error: function(xhr) {

                                toastr.error(
                                    xhr.responseJSON?.message ??
                                    'Something went wrong.'
                                );

                            }

                        });

                    }

                });

            });


            /*
            |--------------------------------------------------------------------------
            | STATUS TOGGLE
            |--------------------------------------------------------------------------
            */

            $(document).on('change', '.status-toggle', function() {

                var id = $(this).data('id');

                var status = $(this).is(':checked') ? 1 : 0;

                $.ajax({

                    url: "{{ route('game.status') }}",

                    type: "POST",

                    data: {

                        id: id,

                        status: status,

                        _token: "{{ csrf_token() }}"

                    },

                    success: function(data) {

                        if (data.status) {

                            toastr.success(data.message);

                        } else {

                            toastr.error(data.message);

                        }

                    },

                    error: function() {

                        toastr.error(
                            'Unable to update game status.'
                        );

                    }

                });

            });


            /*
            |--------------------------------------------------------------------------
            | FEATURED TOGGLE
            |--------------------------------------------------------------------------
            */

            $(document).on('change', '.featured-toggle', function() {

                var id = $(this).data('id');

                var isFeatured = $(this).is(':checked') ? 1 : 0;

                $.ajax({

                    url: "{{ route('game.featured') }}",

                    type: "POST",

                    data: {

                        id: id,

                        is_featured: isFeatured,

                        _token: "{{ csrf_token() }}"

                    },

                    success: function(data) {

                        if (data.status) {

                            toastr.success(data.message);

                        } else {

                            toastr.error(data.message);

                        }

                    },

                    error: function() {

                        toastr.error(
                            'Unable to update featured status.'
                        );

                    }

                });

            });

        });
    </script>
@endsection
