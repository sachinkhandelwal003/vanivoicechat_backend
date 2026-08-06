@extends('layouts.app')

@section('content')

<div class="card mb-3">

    <div class="card-header">

        <div class="row align-items-center">

            <div class="col">

                <h4 class="mb-0">
                    <i class="fas fa-wallet text-primary me-2"></i>
                    Manual Money
                </h4>

                <small class="text-muted">
                    Credit / Deduct Wallet History
                </small>

            </div>

            <div class="col-auto">

                @if(Helper::userCan(163,'can_add'))
                <a href="{{ route('manual-transfer.form') }}"
                    class="btn btn-primary">

                    <i class="fas fa-plus me-1"></i>

                    Add Money

                </a>
                @endif

            </div>

        </div>

    </div>

    <div class="card-body">

        {{-- Filters --}}

        <div class="row mb-4">

            <div class="col-md-3">

                <label class="form-label">

                    User UID

                </label>

                <input
                    type="text"
                    id="uid"
                    class="form-control"
                    placeholder="Enter UID">

            </div>

            <div class="col-md-3">

                <label class="form-label">

                    Role

                </label>

                <select
                    id="role"
                    class="form-select">

                    <option value="">All</option>

                    <option value="admin">Admin</option>

                    <option value="bd">BD</option>

                    <option value="agency">Agency</option>

                    <option value="host">Host</option>

                    <option value="coinseller">Coin Seller</option>

                    <option value="merchant">Merchant</option>

                </select>

            </div>

            <div class="col-md-2">

                <label class="form-label">

                    Type

                </label>

                <select
                    id="type"
                    class="form-select">

                    <option value="">All</option>

                    <option value="credit">

                        Credit

                    </option>

                    <option value="deduct">

                        Deduct

                    </option>

                </select>

            </div>

            <div class="col-md-2">

                <label class="form-label">

                    Date

                </label>

                <input
                    type="date"
                    id="date"
                    class="form-control">

            </div>

            <div class="col-md-2 d-flex align-items-end">

                <button
                    id="btnSearch"
                    class="btn btn-primary w-100">

                    <i class="fas fa-search me-1"></i>

                    Search

                </button>

            </div>

        </div>

        <div class="table-responsive scrollbar">

            <table
                class="table table-bordered table-striped align-middle w-100"
                id="manualMoneyTable">

                <thead class="bg-200">

                    <tr>

                        <th>#</th>

                        <th>User</th>

                        <th>Role</th>

                        <th>Type</th>

                        <th>Amount</th>

                        <!-- <th>Before</th>

                        <th>After</th> -->

                        <th>Reason</th>

                        <th>Admin</th>

                        <th>Created At</th>

                    </tr>

                </thead>

            </table>

        </div>

    </div>

</div>

@endsection


@push('scripts')

<script>
    $(function() {

        let table = $('#manualMoneyTable').DataTable({

            processing: true,

            serverSide: true,

            searching: false,

            ordering: false,

            ajax: {

                url: "{{ route('manual-transfer.index') }}",

                data: function(d) {

                    d.uid = $("#uid").val();

                    d.role = $("#role").val();

                    d.type = $("#type").val();

                    d.date = $("#date").val();

                }

            },

            columns: [

                {

                    data: 'DT_RowIndex',

                    name: 'DT_RowIndex',

                    searchable: false,

                    orderable: false

                },

                {

                    data: 'user',

                    name: 'user'

                },

                {

                    data: 'role',

                    name: 'role'

                },

                {

                    data: 'type',

                    name: 'type'

                },

                {

                    data: 'amount',

                    name: 'amount'

                },

                // {

                //     data: 'before_balance',

                //     name: 'before_balance'

                // },

                // {

                //     data: 'after_balance',

                //     name: 'after_balance'

                // },

                {

                    data: 'reason',

                    name: 'reason'

                },

                {

                    data: 'admin',

                    name: 'admin'

                },

                {

                    data: 'created_at',

                    name: 'created_at'

                }

            ]

        });



        $("#btnSearch").click(function() {

            table.ajax.reload();

        });

    });
</script>

@endpush
