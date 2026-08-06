@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">

                <h5 class="mb-0">User Own Items</h5>

                <a href="{{ route('app-users') }}" class="btn btn-outline-secondary btn-sm">
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
                            <th>Total Items</th>
                            <th>Items</th>
                        </tr>
                    </thead>

                    <tbody></tbody>

                </table>

            </div>

        </div>

    </div>

    <style>
        .item-thumb {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid #ddd;
            transition: .2s;
        }

        .item-thumb:hover {
            transform: scale(1.08);
        }

        .swal2-popup.item-popup {
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
                    url: "{{ route('user.items') }}"
                },

                columns: [{
                        data: 'user',
                        name: 'user'
                    },
                    {
                        data: 'total_items',
                        name: 'total_items'
                    },
                    {
                        data: 'items',
                        name: 'items',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

        });


        $(document).on('click', '.item-thumb', function() {

            let image = $(this).data('image');

            Swal.fire({

                imageUrl: image,
                imageWidth: 550,
                imageHeight: 550,

                showConfirmButton: false,
                showCloseButton: true,

                background: 'transparent',
                backdrop: 'rgba(0,0,0,0.85)',

                customClass: {
                    popup: 'item-popup'
                }

            });

        });

        let currentUserId = null;

        // View User Items
        $(document).on('click', '.view-items', function() {

            currentUserId = $(this).data('user');

            $.ajax({

                url: '/user-items/' + currentUserId,
                type: 'GET',

                success: function(response) {

                    let html = '<div class="row">';

                    response.data.forEach(function(item) {

                        html += `
                                <div class="col-md-3 mb-3">

                                    <div class="position-relative border rounded p-2">
                                        @if (Helper::userCan(105, 'can_delete'))
                                            <button class="btn btn-sm btn-danger position-absolute delete-user-item"
                                                    data-id="${item.transaction_id}"
                                                    data-source="${item.source}"
                                                    style="top:5px;right:5px;padding:2px 6px;z-index:10;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                        <img src="${item.image}"
                                            class="img-fluid rounded shadow item-popup-image"
                                            data-image="${item.image}"
                                            style="height:120px;object-fit:cover;cursor:pointer;">

                                        <div class="mt-2 fw-bold">
                                            ${item.type}
                                        </div>

                                    </div>

                                </div>`;
                    });

                    html += '</div>';

                    Swal.fire({

                        title: 'User Items',

                        html: `
                    <div style="max-height:550px;overflow-y:auto;">
                        ${html}
                    </div>
                `,

                        width: '900px',
                        showCloseButton: true,
                        showConfirmButton: false

                    });

                }

            });

        });


        // Delete Item
        $(document).on('click', '.delete-user-item', function() {

            let id = $(this).data('id');
            let source = $(this).data('source');

            Swal.fire({

                title: 'Remove Item?',
                text: 'This item will be removed from the user.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'

            }).then((result) => {

                if (!result.isConfirmed) return;

                $.ajax({

                    url: "{{ route('user.item.delete') }}",

                    type: 'DELETE',

                    data: {

                        id: id,
                        source: source,
                        _token: "{{ csrf_token() }}"

                    },

                    success: function(res) {

                        Swal.fire({
                            icon: 'success',
                            title: res.message,
                            timer: 1200,
                            showConfirmButton: false
                        });

                        // Refresh DataTable
                        $('.table-datatable').DataTable().ajax.reload(null, false);

                        // Reopen updated item list
                        setTimeout(function() {

                            $('.view-items[data-user="' + currentUserId + '"]').trigger(
                                'click');

                        }, 300);

                    },

                    error: function(xhr) {

                        Swal.fire(
                            'Error',
                            xhr.responseJSON?.message || 'Something went wrong.',
                            'error'
                        );

                    }

                });

            });

        });
    </script>
@endsection
