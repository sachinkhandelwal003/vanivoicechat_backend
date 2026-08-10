@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        {{-- Header --}}
        <div class="card-header">

            <div class="row flex-between-end">

                <div class="col-auto align-self-center">

                    <h5 class="mb-0">
                        Coin Recharge History
                    </h5>

                </div>

            </div>

        </div>


        {{-- ================= FILTER SECTION ================= --}}
        <div class="card-body border-bottom py-3">

            <div class="row align-items-center g-2">

                {{-- User UID --}}
                <div class="col-md-2">

                    <input type="text" id="filter_user_uid" class="form-control" placeholder="User UID">

                </div>


                {{-- Seller / Merchant UID --}}
                <div class="col-md-2">

                    <input type="text" id="filter_sender_uid" class="form-control" placeholder="Seller / Merchant UID">

                </div>


                {{-- Role --}}
                <div class="col-md-2">

                    <select id="filter_role" class="form-select">

                        <option value="">
                            All Roles
                        </option>

                        <option value="coinseller">
                            Seller
                        </option>

                        <option value="merchant">
                            Merchant
                        </option>

                    </select>

                </div>


                {{-- Country --}}
                <div class="col-md-2">

                    <select id="filter_country" class="form-select">

                        <option value="">
                            All Countries
                        </option>

                        @foreach ($countries as $country)
                            <option value="{{ $country->name }}">
                                {{ $country->name }}
                            </option>
                        @endforeach

                    </select>

                </div>


                {{-- Date --}}
                <div class="col-md-2">

                    <input type="date" id="filter_date" class="form-control">

                </div>


                {{-- Month --}}
                <div class="col-md-2">

                    <input type="month" id="filter_month" class="form-control">

                </div>

            </div>


            {{-- Buttons --}}
            <div class="row mt-3">

                <div class="col-12 d-flex justify-content-end gap-2">

                    {{-- Filter --}}
                    <button type="button" class="btn btn-primary" id="filterBtn">

                        <i class="fas fa-filter me-1"></i>
                        Filter

                    </button>


                    {{-- Reset --}}
                    <button type="button" class="btn btn-secondary" id="resetBtn">

                        <i class="fas fa-rotate-left me-1"></i>
                        Reset

                    </button>


                    {{-- Export --}}
                    <button type="button" class="btn btn-success" id="exportBtn">

                        <i class="fas fa-file-excel me-1"></i>
                        Export

                    </button>

                </div>

            </div>

        </div>


        {{-- ================= TABLE ================= --}}
        <div class="card-body table-padding">

            <div class="table-responsive scrollbar">

                <table class="table table-striped table-datatable w-100">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>
                                Seller / Merchant
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Recharge To
                            </th>

                            <th>
                                Coins
                            </th>

                            <th>
                                Transaction Type
                            </th>

                            <th>
                                Remark
                            </th>

                            <th>
                                Date & Time
                            </th>

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

            /*
            |--------------------------------------------------------------------------
            | DATATABLE
            |--------------------------------------------------------------------------
            */

            var table = $('.table-datatable').DataTable({

                processing: true,

                serverSide: true,

                ajax: {

                    url: "{{ route('sellers.recharge.history') }}",

                    data: function(d) {

                        /*
                        |--------------------------------------------------------------------------
                        | SEND FILTER VALUES TO CONTROLLER
                        |--------------------------------------------------------------------------
                        */

                        d.user_uid =
                            $('#filter_user_uid').val();

                        d.sender_uid =
                            $('#filter_sender_uid').val();

                        d.role =
                            $('#filter_role').val();

                        d.country =
                            $('#filter_country').val();

                        d.date =
                            $('#filter_date').val();

                        d.month =
                            $('#filter_month').val();

                    }

                },


                order: [
                    [0, 'desc']
                ],


                columns: [

                    {
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        searchable: false,
                        orderable: false
                    },


                    {
                        data: 'seller',
                        name: 'seller.name'
                    },


                    {
                        data: 'role',
                        name: 'role'
                    },


                    {
                        data: 'receiver',
                        name: 'user.name'
                    },


                    {
                        data: 'coin',
                        name: 'coin'
                    },


                    {
                        data: 'transaction_type',
                        name: 'transaction_type'
                    },


                    {
                        data: 'remark',
                        name: 'remark'
                    },


                    {
                        data: 'created',
                        name: 'created_at'
                    }

                ]

            });


            /*
            |--------------------------------------------------------------------------
            | FILTER BUTTON
            |--------------------------------------------------------------------------
            */

            $('#filterBtn').on('click', function() {

                table.ajax.reload();

            });


            /*
            |--------------------------------------------------------------------------
            | RESET BUTTON
            |--------------------------------------------------------------------------
            */

            $('#resetBtn').on('click', function() {

                $('#filter_user_uid').val('');

                $('#filter_sender_uid').val('');

                $('#filter_role').val('');

                $('#filter_country').val('');

                $('#filter_date').val('');

                $('#filter_month').val('');

                table.ajax.reload();

            });


            /*
            |--------------------------------------------------------------------------
            | EXPORT BUTTON
            |--------------------------------------------------------------------------
            */

            $('#exportBtn').on('click', function() {

                let userUid =
                    $('#filter_user_uid').val();

                let senderUid =
                    $('#filter_sender_uid').val();

                let role =
                    $('#filter_role').val();

                let country =
                    $('#filter_country').val();

                let date =
                    $('#filter_date').val();

                let month =
                    $('#filter_month').val();


                /*
                |--------------------------------------------------------------------------
                | BUILD EXPORT URL
                |--------------------------------------------------------------------------
                */

                let url =
                    "{{ route('sellers.recharge.history.export') }}" +
                    "?user_uid=" + encodeURIComponent(userUid) +
                    "&sender_uid=" + encodeURIComponent(senderUid) +
                    "&role=" + encodeURIComponent(role) +
                    "&country=" + encodeURIComponent(country) +
                    "&date=" + encodeURIComponent(date) +
                    "&month=" + encodeURIComponent(month);


                /*
                |--------------------------------------------------------------------------
                | DOWNLOAD EXCEL
                |--------------------------------------------------------------------------
                */

                window.location.href = url;

            });


            /*
            |--------------------------------------------------------------------------
            | ENTER KEY FILTER
            |--------------------------------------------------------------------------
            */

            $('#filter_user_uid, #filter_sender_uid').on(
                'keypress',
                function(e) {

                    if (e.which === 13) {

                        table.ajax.reload();

                    }

                }
            );

        });
    </script>
@endsection
