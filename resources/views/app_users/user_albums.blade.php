@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0">User Albums</h5>

                <a href="{{ route('app-users') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>

            </div>
        </div>

        <div class="card-body table-padding">

            <div class="table-responsive scrollbar">

                <table class="table table-striped table-datatable w-100">

                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Total Albums</th>
                            <th>Albums</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>

    </div>

    <style>
        .album-thumb {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid #ddd;
            transition: .2s;
        }

        .album-thumb:hover {
            transform: scale(1.08);
        }

        .swal2-popup.album-popup {
            background: transparent !important;
            box-shadow: none !important;
        }
    </style>
@endsection

@section('js')
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>

    <script>
        $(function() {

            $('.table-datatable').DataTable({

                processing: true,
                serverSide: true,

                ajax: {
                    url: "{{ route('user.albums') }}"
                },

                order: [
                    [1, 'desc']
                ],

                columns: [{
                        data: 'user',
                        name: 'user',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_albums',
                        name: 'total_albums'
                    },
                    {
                        data: 'albums',
                        name: 'albums',
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
                    }
                ]

            });

        });


        $(document).on('click', '.album-thumb', function() {

            let image = $(this).data('image');

            Swal.fire({

                imageUrl: image,
                imageWidth: 500,
                imageHeight: 500,

                showConfirmButton: false,
                showCloseButton: true,

                background: 'transparent',
                backdrop: 'rgba(0,0,0,0.85)',

                customClass: {
                    popup: 'album-popup'
                }

            });

        });

        $(document).on('click', '.album-video', function() {

            let video = $(this).data('video');

            Swal.fire({
                html: `
            <video controls autoplay
                   style="width:1000px;max-width:95vw;max-height:90vh;border-radius:10px;">
                <source src="${video}">
            </video>
            `,
                width: 'auto',
                showConfirmButton: false,
                showCloseButton: true,
                background: 'transparent',
                backdrop: 'rgba(0,0,0,0.85)',
                customClass: {
                    popup: 'album-popup'
                }
            });

        });

        $(document).on('click', '.deleteAlbumBtn', function(e) {

            e.stopPropagation();

            let id = $(this).data('id');

            Swal.fire({

                title: 'Delete Album?',
                text: 'Are you sure?',
                icon: 'warning',

                showCancelButton: true,

                confirmButtonText: 'Yes Delete'

            }).then((result) => {

                if (result.isConfirmed) {

                    let url = "{{ route('user.album.delete', ':id') }}";
                    url = url.replace(':id', id);

                    $.ajax({

                        url: url,

                        type: 'DELETE',

                        data: {
                            _token: "{{ csrf_token() }}"
                        },

                        success: function(res) {

                            Swal.fire(
                                'Deleted!',
                                res.message,
                                'success'
                            );

                            $('.table-datatable').DataTable().ajax.reload(null, false);

                        }

                    });

                }

            });

        });

        $(document).on('click', '.banAlbumBtn', function() {

            let id = $(this).data('id');

            Swal.fire({
                title: 'Ban Album Upload?',
                text: 'User will not be able to upload albums.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Ban'
            }).then((result) => {

                if (result.isConfirmed) {

                    let url = "{{ route('user.album.ban', ':id') }}";
                    url = url.replace(':id', id);

                    $.ajax({
                        url: url,
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {

                            Swal.fire('Success', res.message, 'success');

                            $('.table-datatable').DataTable().ajax.reload(null, false);
                        }
                    });

                }

            });

        });

        $(document).on('click', '.unbanAlbumBtn', function() {

            let id = $(this).data('id');

            Swal.fire({
                title: 'Unban Album Upload?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Unban'
            }).then((result) => {

                if (result.isConfirmed) {

                    let url = "{{ route('user.album.unban', ':id') }}";
                    url = url.replace(':id', id);

                    $.ajax({
                        url: url,
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}"
                        },
                        success: function(res) {

                            Swal.fire('Success', res.message, 'success');

                            $('.table-datatable').DataTable().ajax.reload(null, false);
                        }
                    });

                }

            });

        });
    </script>
@endsection
