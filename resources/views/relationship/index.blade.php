@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Relationship :: Item List</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    @if(Helper::userCan(127, 'can_add'))
                    <a href="{{ route('relationship.item.form') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i>
                        Add Item
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-striped table-datatable w-100">

                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Icon</th>
                        <th>Coins</th>
                        <th>Operate</th>
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
    $(function() {

        let table = $('.table-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('relationship.item') }}",
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'name' },
                { data: 'type' },
                { data: 'icon' },
                { data: 'required_coins' },
                { data: 'action', orderable: false, searchable: false }
            ]
        });

        $(document).on('click', ".delete", function() {
            let id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {

                    $.ajax({
                        url: "{{ route('relationship.item.delete') }}",
                        type: "DELETE",
                        data: {
                            _token: "{{ csrf_token() }}",
                            id: id
                        },
                        success: function(data) {
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
