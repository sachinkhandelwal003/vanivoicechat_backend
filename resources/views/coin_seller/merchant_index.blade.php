@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Merchant :: List</h5>
            </div>

            <div class="col-auto ms-auto d-flex gap-2">

                @if(Helper::userCan(143, 'can_add'))
                <a href="{{ route('merchant.form') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-plus me-1"></i> Add Merchant
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
                        <th>User</th>
                        <th>Role</th>
                        <th>Balance</th>
                        <th>Sold</th>
                        <th>Country</th>
                        <th>Whatsapp Number</th>
                        <!-- <th>Created Date</th> -->
                        <th width="100px">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
<!-- Recharge Modal -->
<div class="modal fade" id="rechargeModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5>Recharge</h5></div>
            <div class="modal-body">
                <input type="hidden" id="recharge_id">
                <input type="number" id="recharge_amount" class="form-control">
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" id="submitRecharge">Submit</button>
            </div>
        </div>
    </div>
</div>

<!-- Deduct Modal -->
<div class="modal fade" id="deductModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5>Deduct</h5></div>
            <div class="modal-body">
                <input type="hidden" id="deduct_id">
                <input type="number" id="deduct_amount" class="form-control">
            </div>
            <div class="modal-footer">
                <button class="btn btn-warning" id="submitDeduct">Submit</button>
            </div>
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
            ajax: "{{ route('merchant') }}",
            order: [[4, 'desc']],
            columns: [
                { data: 'user', name: 'user.name' },
                { data: 'is_merchant', name: 'is_merchant' },
                { data: 'balance', name: 'user.total_points' },
                { data: 'sold', name: 'sold' },
                { data: 'country', name: 'country.nicename' },
                { data: 'whatsapp', name: 'whatsapp_number' },
                { data: 'action', orderable: false, searchable: false }
            ]
        });

        // DELETE
        $(document).on('click', ".delete", function () {
            var id = $(this).data('id');

            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('merchant.delete') }}",
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

        // REMOVE MERCHANT → back to seller
        $(document).on('click', '.remove-merchant', function () {
            let id = $(this).data('id');

            Swal.fire({
                title: 'Are you sure?',
                text: "This will move user to Coin Seller",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes'
            }).then((result) => {
                if (result.isConfirmed) {

                    $.post("{{ route('merchant.remove') }}", {
                        _token: "{{ csrf_token() }}",
                        id: id
                    }, function (res) {
                        if (res.status) {
                            Swal.fire('', res.message, 'success');
                            table.draw();
                        } else {
                            toastr.error(res.message);
                        }
                    });

                }
            });
        });

        // RECHARGE
        $(document).on('click', '.recharge', function() {
            $('#recharge_id').val($(this).data('id'));
            $('#rechargeModal').modal('show');
        });

        $('#submitRecharge').click(function() {
            $.post("{{ route('coin_seller.recharge') }}", {
                _token: "{{ csrf_token() }}",
                id: $('#recharge_id').val(),
                amount: $('#recharge_amount').val()
            }, function(res) {
                if (res.status) {
                    $('#rechargeModal').modal('hide');
                    Swal.fire('', res.message, 'success');
                    table.draw();
                }
            });
        });

        // DEDUCT
        $(document).on('click', '.deduct', function() {
            $('#deduct_id').val($(this).data('id'));
            $('#deductModal').modal('show');
        });

        $('#submitDeduct').click(function() {
            $.post("{{ route('coin_seller.deduct') }}", {
                _token: "{{ csrf_token() }}",
                id: $('#deduct_id').val(),
                amount: $('#deduct_amount').val()
            }, function(res) {
                if (res.status) {
                    $('#deductModal').modal('hide');
                    Swal.fire('', res.message, 'success');
                    table.draw();
                }
            });
        });

    });
</script>
@endsection
