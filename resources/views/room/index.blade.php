@extends('layouts.app')

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5 class="mb-0" data-anchor="data-anchor">Room :: Room List</h5>
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
            @if ($pinnedRooms->count())
                <div class="mb-4">

                    <h5 class="mb-3">
                        <i class="fas fa-thumbtack text-warning"></i>
                        Pinned Rooms
                    </h5>

                    <div class="row">

                        @foreach ($pinnedRooms as $room)
                            <div class="col-md-4 mb-3">

                                <div class="card border-warning shadow-sm">

                                    <div class="card-body">

                                        <div class="d-flex align-items-center">

                                            <img src="{{ $room->room_image ? Helper::showImage($room->room_image, true) : asset('assets/img/avatar.png') }}"
                                                width="60" height="60" class="rounded-circle me-3">

                                            <div>

                                                <h6 class="mb-1">
                                                    {{ $room->room_name }}
                                                </h6>

                                                <small class="text-muted">
                                                    Room ID : {{ $room->room_id }}
                                                </small>

                                                <br>

                                                <small class="text-primary">
                                                    Owner :
                                                    {{ $room->user->name ?? '-' }}
                                                </small>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>
                        @endforeach

                    </div>

                </div>
            @endif
            {{-- Search & Filter Row --}}
            <div class="row g-2 mb-3 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label fw-semibold small">Search Room / Owner</label>
                    <input type="text" class="form-control form-control-sm" id="room_search" placeholder="Room ID, Name or Owner UID/Name...">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold small">Ban Status</label>
                    <select class="form-select form-select-sm" id="ban_status_filter">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="banned">Banned</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold small">Pin Status</label>
                    <select class="form-select form-select-sm" id="pin_status_filter">
                        <option value="">All</option>
                        <option value="1">Pinned</option>
                        <option value="0">Not Pinned</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label fw-semibold small">Room Level</label>
                    <input type="number" class="form-control form-control-sm" id="level_filter" placeholder="e.g. 1">
                </div>
                <div class="col-12 col-md-3">
                    <div class="d-flex gap-2 justify-content-end">
                        <button class="btn btn-primary btn-sm" id="btnRoomSearch">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                        <button class="btn btn-secondary btn-sm" id="btnRoomReset">
                            <i class="fas fa-sync-alt me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-datatable" style="width:100%">

                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Room Owner</th>
                            <th>Room Information</th>
                            <th>Room Seats</th>
                            <th>Room Members</th>
                            <th>Room Admins</th>
                            <th>Ban Status</th>
                            <th>Pin Status</th>
                            <th>Room Information</th>
                            <th>Time</th>
                            <th>Operate</th>
                        </tr>
                    </thead>
                    <tbody></tbody>

                </table>
            </div>

        </div>
    </div>

    <div class="modal fade" id="banRoomModal">

        <div class="modal-dialog">

            <div class="modal-content">

                <div class="modal-header">
                    <h5>Ban Room</h5>
                </div>

                <div class="modal-body">

                    <input type="hidden" id="ban_room_id">

                    <textarea class="form-control" id="ban_reason" rows="4" placeholder="Enter ban reason"></textarea>

                </div>

                <div class="modal-footer">

                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button class="btn btn-danger" id="confirmBanRoom">
                        Ban Room
                    </button>

                </div>

            </div>

        </div>

    </div>

    <div class="modal fade" id="accountProcessingModal">

        <div class="modal-dialog modal-lg">

            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Room Account Processing</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <table class="table table-bordered">

                        <tr>
                            <th width="180">Room</th>
                            <td id="ap_room"></td>
                        </tr>

                        <tr>
                            <th>Room ID</th>
                            <td id="ap_room_id"></td>
                        </tr>

                        <tr>
                            <th>Status</th>
                            <td id="ap_status"></td>
                        </tr>

                        <tr>
                            <th>Reason</th>
                            <td id="ap_reason"></td>
                        </tr>

                        <tr>
                            <th>Action By</th>
                            <td id="ap_action_by"></td>
                        </tr>

                        <tr>
                            <th>Action Date</th>
                            <td id="ap_action_date"></td>
                        </tr>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <div class="modal fade" id="adminLimitModal" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content">

                <form id="adminLimitForm">

                    @csrf

                    <input type="hidden" name="id" id="admin_room_id">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            Update Admin Limit
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <label class="form-label">
                            Admin Limit
                        </label>

                        <input type="number" name="admin_limit" id="admin_limit" class="form-control" min="0"
                            required>

                    </div>

                    <div class="modal-footer">

                        <button class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button class="btn btn-primary">
                            Update
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <div class="modal fade" id="memberLimitModal" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content">

                <form id="memberLimitForm">

                    @csrf

                    <input type="hidden" name="id" id="member_room_id">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            Update Member Limit
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <label class="form-label">
                            Member Limit
                        </label>

                        <input type="number" name="member_limit" id="member_limit" class="form-control" min="0"
                            required>

                    </div>

                    <div class="modal-footer">

                        <button class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button class="btn btn-primary">
                            Update
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <!-- Edit Room Name Modal -->
    <div class="modal fade" id="editRoomNameModal" tabindex="-1" aria-labelledby="editRoomNameModalLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="editRoomNameModalLabel">
                        Edit Room Name
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>

                </div>

                <div class="modal-body">

                    <input type="hidden" id="edit_room_id">

                    <div class="mb-3">

                        <label for="edit_room_name" class="form-label">
                            Room Name
                        </label>

                        <input type="text" class="form-control" id="edit_room_name" placeholder="Enter room name"
                            maxlength="255">

                        <div class="text-danger small mt-1" id="edit_room_name_error">
                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="button" class="btn btn-primary" id="saveRoomName">

                        <i class="fas fa-save me-1"></i>
                        Save Changes

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
                searching: true,
                ajax: {
                    url: "{{ route('room') }}",
                    data: function(d) {
                        d.room_search     = $('#room_search').val();
                        d.ban_status      = $('#ban_status_filter').val();
                        d.pin_status      = $('#pin_status_filter').val();
                        d.level           = $('#level_filter').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'room_owner',
                        orderable: false
                    },
                    {
                        data: 'room_info',
                        orderable: false
                    },
                    {
                        data: 'room_number'
                    },
                    {
                        data: 'room_members'
                    },
                    {
                        data: 'admins',
                        name: 'admins'
                    },
                    {
                        data: 'ban_status',
                        name: 'ban_status'
                    },
                    {
                        data: 'pin_status',
                        name: 'pin_status'
                    },
                    {
                        data: 'room_info',
                        visible: false
                    },
                    {
                        data: 'time'
                    },
                    {
                        data: 'action',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

            // Search button
            $('#btnRoomSearch').on('click', function() {
                table.ajax.reload();
            });

            // Reset button
            $('#btnRoomReset').on('click', function() {
                $('#room_search').val('');
                $('#ban_status_filter').val('');
                $('#pin_status_filter').val('');
                $('#level_filter').val('');
                table.ajax.reload();
            });

            // Enter key triggers search
            $('#room_search').on('keypress', function(e) {
                if (e.which === 13) table.ajax.reload();
            });

            $(document).on('click', ".delete", function() {
                var id = $(this).data('id')
                Swal.fire(deleteMessageSwalConfig).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ route('room') }} ",
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

        $(document).on('click', '.banRoom', function() {

            $('#ban_room_id').val($(this).data('id'));
            $('#ban_reason').val('');

            $('#banRoomModal').modal('show');

        });


        $('#confirmBanRoom').click(function() {

            let id = $('#ban_room_id').val();

            $.ajax({

                url: '/room/' + id + '/ban',

                type: 'POST',

                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    reason: $('#ban_reason').val()
                },

                success: function(res) {

                    $('#banRoomModal').modal('hide');

                    Swal.fire(
                        'Success',
                        res.message,
                        'success'
                    );

                    $('.table-datatable').DataTable().ajax.reload(null, false);

                }

            });

        });

        $(document).on('click', '.unbanRoom', function() {

            let id = $(this).data('id');

            Swal.fire({

                title: 'Unban Room?',

                icon: 'warning',

                showCancelButton: true

            }).then((result) => {

                if (result.isConfirmed) {

                    $.post('/room/' + id + '/unban', {

                        _token: $('meta[name="csrf-token"]').attr('content')

                    }, function(res) {

                        Swal.fire(
                            'Success',
                            res.message,
                            'success'
                        );

                        $('.table-datatable').DataTable().ajax.reload(null, false);

                    });

                }

            });

        });

        $(document).on('click', '.accountProcessing', function() {

            let id = $(this).data('id');

            $.get('/room/' + id + '/account-processing', function(res) {

                $('#ap_room').text(res.data.room_name);
                $('#ap_room_id').text(res.data.room_id);
                $('#ap_status').html(
                    '<span class="badge bg-danger">' + res.data.status + '</span>'
                );
                $('#ap_reason').text(res.data.reason ?? '-');
                $('#ap_action_by').text(res.data.action_by);
                $('#ap_action_date').text(res.data.action_date);

                $('#accountProcessingModal').modal('show');
            });

        });

        $(document).on('click', '.deleteRoomImage', function() {

            let id = $(this).data('id');

            Swal.fire({
                title: 'Delete Room Image?',
                text: 'This will permanently remove the room image.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, Delete'
            }).then((result) => {

                if (result.isConfirmed) {

                    $.ajax({
                        url: '/room/' + id + '/delete-image',
                        type: 'POST',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(res) {

                            Swal.fire(
                                'Success',
                                res.message,
                                'success'
                            );

                            $('.table-datatable').DataTable().ajax.reload(null, false);
                        }
                    });

                }

            });

        });

        $(document).on('click', '.pinRoom', function() {

            let id = $(this).data('id');

            $.post('/room/' + id + '/pin', {

                _token: $('meta[name="csrf-token"]').attr('content')

            }, function(res) {

                Swal.fire('Success', res.message, 'success');

                $('.table-datatable').DataTable().ajax.reload(null, false);

            }).fail(function(xhr) {

                Swal.fire('Error', xhr.responseJSON.message, 'error');

            });

        });

        $(document).on('click', '.unpinRoom', function() {

            let id = $(this).data('id');

            $.post('/room/' + id + '/unpin', {

                _token: $('meta[name="csrf-token"]').attr('content')

            }, function(res) {

                Swal.fire('Success', res.message, 'success');

                $('.table-datatable').DataTable().ajax.reload(null, false);

            });

        });

        $(document).on("click", ".updateAdminLimit", function() {

            $("#admin_room_id").val($(this).data("id"));

            $("#admin_limit").val($(this).data("limit"));

            $("#adminLimitModal").modal("show");

        });
        $("#adminLimitForm").submit(function(e) {

            e.preventDefault();

            $.ajax({

                url: "{{ route('room.update-admin-limit') }}",

                type: "POST",

                data: $(this).serialize(),

                success: function(res) {

                    if (res.status) {

                        $("#adminLimitModal").modal("hide");

                        Swal.fire("Success", res.message, "success");

                        $('.table-datatable').DataTable().ajax.reload(null, false);

                    }

                }

            });

        });

        $(document).on("click", ".updateMemberLimit", function() {

            $("#member_room_id").val($(this).data("id"));

            $("#member_limit").val($(this).data("limit"));

            $("#memberLimitModal").modal("show");

        });

        $("#memberLimitForm").submit(function(e) {

            e.preventDefault();

            $.ajax({

                url: "{{ route('room.update-member-limit') }}",

                type: "POST",

                data: $(this).serialize(),

                success: function(res) {

                    if (res.status) {

                        $("#memberLimitModal").modal("hide");

                        Swal.fire("Success", res.message, "success");

                        $('.table-datatable').DataTable().ajax.reload(null, false);

                    }

                }

            });

        });

        $(document).on('click', '.editRoomName', function() {

            let id = $(this).data('id');
            let name = $(this).data('name');

            $('#edit_room_id').val(id);
            $('#edit_room_name').val(name);

            $('#edit_room_name_error').text('');

            let modal = new bootstrap.Modal(
                document.getElementById('editRoomNameModal')
            );

            modal.show();
        });


        $(document).on('click', '#saveRoomName', function() {

            let id = $('#edit_room_id').val();
            let roomName = $('#edit_room_name').val().trim();

            $('#edit_room_name_error').text('');

            if (!roomName) {

                $('#edit_room_name_error')
                    .text('Room name is required.');

                return;
            }

            let button = $(this);

            button.prop('disabled', true);

            button.html(`
            <span class="spinner-border spinner-border-sm me-1"></span>
            Saving...
        `);

            $.ajax({

                url: "{{ route('room.update.name') }}",

                type: "POST",

                data: {

                    id: id,

                    room_name: roomName,

                    _token: "{{ csrf_token() }}"

                },

                success: function(res) {

                    if (res.status) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        });

                        let modalElement =
                            document.getElementById('editRoomNameModal');

                        let modal =
                            bootstrap.Modal.getInstance(modalElement);

                        if (modal) {
                            modal.hide();
                        }

                        $('.table-datatable')
                            .DataTable()
                            .ajax
                            .reload(null, false);

                    } else {

                        toastr.error(
                            res.message || 'Something went wrong.'
                        );
                    }

                },

                error: function(xhr) {

                    if (xhr.status === 422) {

                        $('#edit_room_name_error').text(
                            xhr.responseJSON?.message ||
                            'Invalid room name.'
                        );

                    } else {

                        toastr.error(
                            xhr.responseJSON?.message ||
                            'Something went wrong.'
                        );
                    }

                },

                complete: function() {

                    button.prop('disabled', false);

                    button.html(`
                    <i class="fas fa-save me-1"></i>
                    Save Changes
                `);

                }

            });

        });
    </script>
@endsection
