@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <h5>Users :: User List</h5>
    </div>

    <div class="card-body">

        {{-- FILTERS --}}
        <div class="row mb-3">

            <div class="col-md-2">
                <input type="text" id="uid" class="form-control" placeholder="Serial / UID">
            </div>

            <div class="col-md-3">
                <input type="text" id="username" class="form-control" placeholder="Enter username">
            </div>

            <!-- <div class="col-md-3">
                    <input type="text" id="equipment" class="form-control" placeholder="Equipment Number">
                </div> -->

            <div class="col-md-2">
                <select id="region" class="form-control">
                    <option value="">Region</option>
                    <option value="India">India</option>
                    <option value="Pakistan">Pakistan</option>
                    <option value="Philippines">Philippines</option>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary w-100" id="searchBtn">Search</button>
            </div>

        </div>

        <div class="table-responsive">
            <table class="table table-striped table-datatable" id="datatable" style="width:100%">

                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Invite Code</th>
                        <th>Status</th>
                        <th>Gender</th>
                        <th>Country</th>
                        <th>Time</th>
                        <th>Operate</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>
        </div>

    </div>
</div>

<div class="modal fade" id="disableUserModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="disableUserForm">
            @csrf
            <input type="hidden" id="disable_user_id">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Disable User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p id="disableUserName" class="fw-bold"></p>
                    <div class="mb-3">
                        <label>Reason</label>
                        <textarea id="disable_reason" class="form-control" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Disable Until</label>
                        <input type="date" id="disable_until" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Disable</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="activateUserModal">
    <div class="modal-dialog">
        <form id="activateUserForm">
            @csrf
            <input type="hidden" id="activate_user_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Activate User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="activateUserName"></p>
                    <p class="text-success">Are you sure you want to activate this user?</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Activate</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="blacklistUserModal">
    <div class="modal-dialog">
        <form id="blacklistUserForm">
            @csrf
            <input type="hidden" id="blacklist_user_id">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">Blacklist User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p id="blacklistUserName"></p>

                    <textarea id="blacklist_reason" class="form-control" required placeholder="Reason for blacklist..."></textarea>

                    <p class="mt-2 text-danger fw-bold">
                        This will permanently ban the user.
                    </p>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Blacklist</button>
                </div>
            </div>
        </form>
    </div>
</div>


@endsection


@section('js')
<script src="{{ asset('assets/js/sweetalert2.min.js') }}"></script>

<script>
    $(function() {

        let table = $('.table-datatable').DataTable({
            ajax: {
                url: "{{ route('app-users') }}",
                data: function(d) {
                    d.uid = $('#uid').val();
                    d.username = $('#username').val();
                    d.equipment = $('#equipment').val();
                    d.region = $('#region').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'user',
                    name: 'user'
                },
                {
                    data: 'invite_code'
                },
                {
                    data: 'disable_status'
                },
                {
                    data: 'gender'
                },
                {
                    data: 'country'
                },
                {
                    data: 'created_at'
                },
                {
                    data: 'operate'
                },
            ]
        });

        $('#searchBtn').click(() => table.ajax.reload());

    });

    $(document).on('click', '.disableUserBtn', function() {
        let userId = $(this).data('id');
        let userName = $(this).data('name');

        $('#disable_user_id').val(userId);
        $('#disableUserName').text("Disable user: " + userName);

        $('#disableUserModal').modal('show');
    });

    $('#disableUserForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('user.disable') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                user_id: $('#disable_user_id').val(),
                reason: $('#disable_reason').val(),
                disabled_until: $('#disable_until').val()
            },
            success: function(res) {
                $('#disableUserModal').modal('hide');
                toastr.success("User disabled successfully");
                $('#datatable').DataTable().ajax.reload();
            },
            error: function() {
                toastr.error("Failed to disable user");
            }
        });
    });

    $(document).on('click', '.activateUserBtn', function() {
        $('#activate_user_id').val($(this).data('id'));
        $('#activateUserName').text("Activate user: " + $(this).data('name'));
        $('#activateUserModal').modal('show');
    });

    $('#activateUserForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('user.activate') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                user_id: $('#activate_user_id').val()
            },
            success: function() {
                $('#activateUserModal').modal('hide');
                toastr.success("User activated successfully");
                $('#datatable').DataTable().ajax.reload();
            },
            error: function() {
                toastr.error("Failed to activate user");
            }
        });
    });

    $(document).on('click', '.blacklistUserBtn', function() {
        $('#blacklist_user_id').val($(this).data('id'));
        $('#blacklistUserName').text("Blacklist user: " + $(this).data('name'));
        $('#blacklistUserModal').modal('show');
    });


    $('#blacklistUserForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('user.blacklist') }}",
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                user_id: $('#blacklist_user_id').val(),
                reason: $('#blacklist_reason').val()
            },
            success: function() {
                $('#blacklistUserModal').modal('hide');
                toastr.success("User blacklisted successfully");
                $('#datatable').DataTable().ajax.reload();
            },
            error: function() {
                toastr.error("Failed to blacklist user");
            }
        });
    });

    // DELETE
    $(document).on('click', ".delete", function() {
        var id = $(this).data('id');

        Swal.fire(deleteMessageSwalConfig).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('delete.appuser') }}",
                    type: "DELETE",
                    data: {
                        id: id,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(data) {
                        if (data.status) {
                            Swal.fire('', data.message, "success");
                            $('#datatable').DataTable().ajax.reload(null, false);
                        } else {
                            toastr.error(data.message);
                        }
                    }
                });
            }
        });
    });
</script>
@endsection