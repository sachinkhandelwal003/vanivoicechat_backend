@extends('layouts.app')

@section('content')

<div class="card">

    <div class="card-header">
        <h5>User Medals</h5>
    </div>

    <div class="card-body">

        <table class="table table-striped table-datatable w-100">

            <thead>
                <tr>
                    <th>User</th>
                    <th>Total Medals</th>
                    <th>Medals</th>
                </tr>
            </thead>

        </table>

    </div>

</div>

<style>
    .medal-popup {
        box-shadow: none !important;
        padding: 0 !important;
        background: transparent !important;
    }
</style>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(function() {

        $('.table-datatable').DataTable({

            processing: true,
            serverSide: true,

            ajax: "{{ route('user.medals') }}",

            columns: [{
                    data: 'user',
                    name: 'user',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'total_medals',
                    name: 'total_medals'
                },
                {
                    data: 'medals',
                    name: 'medals',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $(document).on('click', '.medal-image', function() {

            let image = $(this).data('image');

            Swal.fire({
                imageUrl: image,
                imageWidth: 250,
                imageHeight: 250,
                showConfirmButton: false,
                showCloseButton: true,
                background: 'transparent',
                backdrop: 'rgba(0,0,0,0.8)',
                customClass: {
                    popup: 'medal-popup'
                }
            });

        });

    });
</script>

@endsection