@extends('layouts.app')

@section('content')

<div class="card mb-3">

    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">

            <h5 class="mb-0">
                Reported Posts
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
                        <th>#</th>
                        <th>Reported By</th>
                        <th>Post Owner</th>
                        <th>Post</th>
                        <th>Reason</th>
                        <th>Reported At</th>
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
            url: "{{ route('user.post.reports') }}"
        },

        columns: [

            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                searchable: false,
                orderable: false
            },

            {
                data: 'reporter',
                name: 'reporter'
            },

            {
                data: 'post_owner',
                name: 'post_owner'
            },

            {
                data: 'post',
                name: 'post',
                orderable: false,
                searchable: false
            },

            {
                data: 'reason',
                name: 'reason'
            },

            {
                data: 'reported_at',
                name: 'created_at'
            },

        ],

        pageLength: 10,
        responsive: true

    });

});

</script>

@endsection