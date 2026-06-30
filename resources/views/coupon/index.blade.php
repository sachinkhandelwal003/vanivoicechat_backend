@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Coupon :: Coupon List</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    @if(Helper::userCan(104, 'can_add'))
                    <a href="{{ route('coupon.add') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i> Add Coupon
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-body table-padding">
        <div class="table-responsive scrollbar">
            <table class="table custom-table table-striped dt-table-hover fs--1 mb-0 table-datatable" style="width:100%">
                <thead class="bg-200 text-900">
                    <tr>
                        <th>Coupon Code</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Min Order</th>
                        <th>Max Uses</th>
                        <!-- <th>Used Count</th> -->
                        <th>Valid From</th>
                        <th>Valid Until</th>
                        <th>Status</th>
                        <!-- <th>Created Date</th> -->
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

<script type="text/javascript">
    $(function() {

        var table = $('.table-datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('coupon.list') }}",
            order: [
                [8, 'desc'] 
            ],
            columns: [
                { data: 'coupon_code', name: 'coupon_code' },
                { data: 'type', name: 'type' },
                { data: 'value', name: 'value' },
                { data: 'min_order_amount', name: 'min_order_amount' },
                { data: 'max_uses', name: 'max_uses' },
                { data: 'valid_from', name: 'valid_from' },
                { data: 'valid_until', name: 'valid_until' },
                { data: 'status', name: 'status' },
               
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // Delete Function
        $(document).on('click', ".delete", function() {
            var id = $(this).data('id');
            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('coupon.destroy') }}",
                        data: { 'id': id },
                        type: 'DELETE',
                        success: function(data) {
                            if (data.status) {
                                Swal.fire('', data?.message, "success");
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
