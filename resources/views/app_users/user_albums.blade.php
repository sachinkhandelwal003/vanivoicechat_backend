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
                    </tr>
                </thead>

                <tbody></tbody>

            </table>

        </div>

    </div>

</div>

<style>
    .album-thumb{
        width:45px;
        height:45px;
        object-fit:cover;
        border-radius:8px;
        cursor:pointer;
        border:1px solid #ddd;
        transition:.2s;
    }

    .album-thumb:hover{
        transform:scale(1.08);
    }

    .swal2-popup.album-popup{
        background:transparent !important;
        box-shadow:none !important;
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
                [0, 'desc']
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
</script>

@endsection