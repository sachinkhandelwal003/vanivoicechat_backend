@extends('layouts.app')

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0" data-anchor="data-anchor">Family :: Family List</h5>
                </div>
                <!-- <div class="col-auto ms-auto">
                        <div class="nav nav-pills nav-pills-falcon">
                            @if (Helper::userCan(104, 'can_add'))
                                <a href="{{ route('gift.add') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-plus me-1"></i>
                                    Add Gift
                                </a>
                            @endif
                        </div>
                    </div> -->
            </div>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-striped table-datatable" style="width:100%">

                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Family Information</th>
                            <th>User Information</th>
                            <th>Number of Members</th>
                            <th>Family Rank</th>
                            <th>Total Contribution Value</th>
                            <th>Members</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Operate</th>
                        </tr>
                    </thead>
                    <tbody></tbody>

                </table>
            </div>

        </div>
    </div>

    <div class="modal fade" id="editFamilyNameModal" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Family Name</h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="family_id">

                    <div class="mb-3">
                        <label class="form-label">Family Name</label>

                        <input type="text" class="form-control" id="family_name">
                    </div>

                </div>

                <div class="modal-footer">

                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button class="btn btn-primary" id="updateFamilyName">
                        Update
                    </button>

                </div>

            </div>

        </div>

    </div>
@endsection

@section('js')
    <script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>
    <script>
        $(function() {

            let table = $('.table-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('family') }}",
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'family_info'
                    },
                    {
                        data: 'user_info'
                    },
                    {
                        data: 'family_member'
                    },
                    {
                        data: 'family_rank'
                    },
                    {
                        data: 'total_points'
                    },
                    {
                        data: 'members'
                    },
                    {
                        data: 'time'
                    },
                    {
                        data: 'status'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            $(document).on('click', ".delete", function() {
                var id = $(this).data('id')
                Swal.fire(deleteMessageSwalConfig).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('family') }} ",
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


        $(document).on('click', '.deleteFamilyImage', function() {

            let id = $(this).data('id');

            Swal.fire({
                title: 'Delete Family Image?',
                text: 'This will permanently remove the family image.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({

                        url: '/family/' + id + '/delete-image',

                        type: 'POST',

                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },

                        success: function(res) {

                            Swal.fire('Success', res.message, 'success');

                            $('.table-datatable').DataTable().ajax.reload(null, false);

                        },

                        error: function(xhr) {

                            Swal.fire(
                                'Error',
                                xhr.responseJSON?.message || 'Something went wrong.',
                                'error'
                            );

                        }

                    });

                }

            });

        });

        // Open Modal

        $(document).on('click', '.editFamilyName', function() {

            $('#family_id').val($(this).data('id'));

            $('#family_name').val($(this).data('name'));

            $('#editFamilyNameModal').modal('show');

        });


        // Update

        $('#updateFamilyName').click(function() {

            $.ajax({

                url: "{{ route('family.updateName') }}",

                type: "POST",

                data: {

                    _token: "{{ csrf_token() }}",

                    id: $('#family_id').val(),

                    name: $('#family_name').val()

                },

                success: function(res) {

                    $('#editFamilyNameModal').modal('hide');

                    Swal.fire(
                        'Success',
                        res.message,
                        'success'
                    );

                    $('.table-datatable')
                        .DataTable()
                        .ajax.reload(null, false);

                },

                error: function(xhr) {

                    Swal.fire(
                        'Error',
                        xhr.responseJSON.message,
                        'error'
                    );

                }

            });

        });


        $(document).on('click', '.deleteFamilyName', function() {

            let id = $(this).data('id');

            Swal.fire({
                title: 'Delete Family Name?',
                text: 'This will remove the family name.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Delete'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({

                        url: '/family/' + id + '/delete-name',

                        type: 'POST',

                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },

                        success: function(res) {

                            Swal.fire('Success', res.message, 'success');

                            $('.table-datatable').DataTable().ajax.reload(null, false);

                        },

                        error: function(xhr) {

                            Swal.fire(
                                'Error',
                                xhr.responseJSON?.message || 'Something went wrong.',
                                'error'
                            );

                        }

                    });

                }

            });

        });
    </script>
@endsection
