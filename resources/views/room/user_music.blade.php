@extends('layouts.app')

@section('content')

<div class="card mb-3">

    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">

            <h5 class="mb-0">User Music List</h5>

            <a href="{{ route('room') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>

        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-striped table-datatable w-100">

                <thead>
                    <tr>
                        <th>User</th>
                        <th>Total Music</th>
                        <th>Music</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>

        </div>

    </div>

</div>

<style>
    .card {
        border: none;
        border-radius: 15px;
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #6f42c1, #8b5cf6);
        color: #fff;
        padding: 18px 22px;
    }

    .card-header h5 {
        color: #fff;
        font-weight: 600;
    }

    .table-datatable tbody tr {
        transition: .3s;
    }

    .table-datatable tbody tr:hover {
        background: #f8f9ff;
    }

    .table-datatable td {
        vertical-align: middle;
    }

    .user-box {
        display: flex;
        align-items: center;
    }

    .user-box img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: 2px solid #6f42c1;
        object-fit: cover;
    }

    .user-info {
        margin-left: 12px;
    }

    .user-info .name {
        font-weight: 600;
        color: #2d3748;
    }

    .user-info .uid {
        color: #718096;
        font-size: 12px;
    }

    .music-badge {
        background: #e9f7ef;
        color: #28a745;
        padding: 8px 14px;
        border-radius: 30px;
        font-weight: 600;
    }

    .btn-playlist {
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border: none;
        color: #fff;
        border-radius: 10px;
        padding: 8px 16px;
        font-size: 13px;
    }

    .btn-playlist:hover {
        color: #fff;
        transform: translateY(-2px);
    }

    .dataTables_filter input {
        border-radius: 12px !important;
        border: 1px solid #ddd !important;
        padding: 8px 15px !important;
    }

    .playlist-card {
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 12px;
        background: #fff;
    }

    .playlist-card h6 {
        margin-bottom: 5px;
        font-weight: 600;
    }

    .playlist-card small {
        color: #888;
    }

    .swal2-popup {
        border-radius: 15px !important;
    }
</style>

@endsection


@section('js')

<script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>

<script>

    $(function () {

        $('.table-datatable').DataTable({

            processing: true,
            serverSide: true,

            ajax: {url: "{{ route('user_room.music') }}"},

            columns: [
                {data: 'user', name: 'user',},
                {data: 'total_music', name: 'total_music'},
                {data: 'musics', name: 'musics', orderable: false, searchable: false}
            ]

        });

    });


    $(document).on('click', '.view-musics', function () {

        let userId = $(this).data('user');

        $.ajax({

            url: '/user-room-musics/' + userId,
            type: 'GET',

            success: function(response) {

                let html = '';

                response.data.forEach(function(music) {

                    html += `
                        <div class="playlist-card">

                            <h6>🎵 ${music.title ?? '-'}</h6>

                            <small>
                                ${music.artist ?? 'Unknown Artist'}
                            </small>

                            <audio controls style="width:100%;margin-top:10px;">
                                <source src="/storage/${music.audio_url}">
                            </audio>

                        </div>
                    `;
                });

                Swal.fire({

                    title: '<i class="fas fa-music text-primary"></i> Music Playlist',

                    html: `
                        <div style="max-height:550px;overflow-y:auto;text-align:left;">
                            ${html}
                        </div>
                    `,

                    width: '900px',
                    showConfirmButton: false,
                    showCloseButton: true
                });

            }
        });

    });

</script>

@endsection