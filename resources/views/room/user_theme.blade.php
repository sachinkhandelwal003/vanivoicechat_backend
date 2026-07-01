@extends('layouts.app')

@section('content')

<div class="card mb-3">

    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">

            <h5 class="mb-0">
                User Active Themes
            </h5>

            <a href="{{ url()->previous() }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>

        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">

            <table class="table table-striped table-datatable w-100">

                <thead>
                    <tr>
                        <th>User</th>
                        <th>Active Theme</th>
                        <th>Room Details</th>
                        <th>Activated At</th>
                    </tr>
                </thead>

                <tbody></tbody>

            </table>

        </div>

    </div>

</div>

@endsection

@section('js')

<script>

    $(function () {

        $('.table-datatable').DataTable({

            processing: true,
            serverSide: true,

            ajax: {
                url: "{{ route('user.themes') }}"
            },

            columns: [
                {
                    data: 'user',
                    name: 'user'
                },
                {
                    data: 'theme',
                    name: 'theme',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'room',
                    name: 'room',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'activated_at',
                    name: 'activated_at'
                }
            ],

            pageLength: 10,
            responsive: true
        });

    });

</script>

@endsection