@extends('layouts.app')

@section('content')
<div class="card">

    <div class="card-header d-flex justify-content-between">
        <h5 class="mb-0">Room Members - {{ $room->room_name }}</h5>

        <a href="{{ route('room') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="card-body">

        <!-- TOP BAR -->
        <div class="d-flex justify-content-between align-items-center mb-4 p-2 bg-light rounded">

            <!-- LEFT: Info + Pagination -->
            <div>
                <div id="infoText" class="small text-muted mb-1"></div>
                <div id="pagination"></div>
            </div>

            <!-- RIGHT: Search -->
            <div style="width:260px; position:relative;">
                <i class="fa fa-search text-muted"
                    style="position:absolute; left:12px; top:50%; transform:translateY(-50%);"></i>

                <input type="text" id="search"
                    class="form-control"
                    placeholder="Search member..."
                    style="padding-left:35px; border-radius:50px;">
            </div>

        </div>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Joined At</th>
                    </tr>
                </thead>
                <tbody id="memberTable"></tbody>
            </table>
        </div>

    </div>
</div>
@endsection

@section('css')
<style>
    #search {
        border-radius: 50px;
        padding-left: 10px;
    }

    .input-group {
        background: #fff;
        border-radius: 50px;
        padding: 2px 10px;
    }

    #pagination .pagination {
        margin-bottom: 0;
    }
</style>
@endsection

@section('js')
<script>
    let page = 1;

    function loadMembers(search = '') {
        $.ajax({
            url: "{{ route('room.members.ajax', $room->id) }}",
            data: {
                search: search,
                page: page
            },
            success: function(res) {

                let html = '';

                if (res.data.length === 0) {
                    html = '<tr><td colspan="4" class="text-center">No Members Found</td></tr>';
                    $('#infoText').html('');
                } else {

                    res.data.forEach((item, index) => {
                        html += `
                        <tr>
                            <td>${(page - 1) * 10 + index + 1}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="${item.image}" width="40" height="40" class="rounded-circle">
                                    <div>
                                        <div class="fw-bold">${item.name}</div>
                                        <small class="text-muted">${item.uid}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge ${item.role_class}">
                                    ${item.role}
                                </span>
                            </td>
                            <td>${item.joined_at}</td>
                        </tr>
                    `;
                    });

                    $('#infoText').html(
                        `Showing ${res.from}–${res.to} of ${res.total} members`
                    );
                }

                $('#memberTable').html(html);
                $('#pagination').html(res.pagination);
            }
        });
    }

    // 🔍 Live search
    $('#search').on('keyup', function() {
        page = 1;
        loadMembers($(this).val());
    });

    // Pagination click
    $(document).on('click', '.page-link', function(e) {
        e.preventDefault();
        page = $(this).data('page');
        loadMembers($('#search').val());
    });

    // Initial load
    loadMembers();
</script>
@endsection