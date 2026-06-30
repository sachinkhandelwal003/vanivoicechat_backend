@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">

            <h5 class="mb-0">Level :: Level List</h5>

            <div class="d-flex align-items-center gap-2">

                {{-- FILTER --}}
                <select id="typeFilter" class="form-select form-select-sm px-3 w-auto">
                    <option value="">All</option>
                    <option value="wealth">Wealth</option>
                    <option value="charm">Charm</option>
                </select>

                {{-- LEVEL SETTING BUTTON --}}
                @if(Helper::userCan(104, 'can_edit'))
                <a href="{{ route('level-setting.form') }}" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa fa-cog me-1"></i> Level Setting
                </a>
                @endif

                {{-- ADD BUTTON --}}
                @if(Helper::userCan(104, 'can_add'))
                <a href="{{ route('levels.form') }}" class="btn btn-outline-secondary btn-sm px-3">
                    <i class="fa fa-plus me-1"></i> Add Level
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
                        <th>Type</th>
                        <th>Level</th>
                        <th>Required Exp</th>
                        <th>Icon</th>
                        <th>Entry Effect</th>
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
    $(function () {

        var table = $('.table-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('levels') }}",
                data: function (d) {
                    d.type = $('#typeFilter').val(); 
                }
            },
            order: [[1, 'asc']],
            columns: [
                { data: 'type', name: 'type' },
                { data: 'level', name: 'level' },
                { data: 'required_exp', name: 'required_exp' },
                { data: 'icon', name: 'icon', orderable: false, searchable: false },
                { data: 'entry_effect', name: 'entry_effect', orderable: false, searchable: false },
                { data: 'created_at', name: 'created_at' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        $('#typeFilter').on('change', function() {
            table.ajax.reload();
        });

        // DELETE
        $(document).on('click', ".delete", function () {
            var id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('levels.delete') }}",
                        type: "DELETE",
                        data: {
                            id: id,
                            _token: "{{ csrf_token() }}"
                        },
                        success: function (data) {
                            if (data.status) {
                                Swal.fire('', data.message, "success");
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
</script>
@endsection