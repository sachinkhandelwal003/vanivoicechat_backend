@extends('layouts.app')

@section('content')
<style>
    .post-list-table th,
    .post-list-table td {
        vertical-align: middle !important;
        font-size: 14px;
    }

    .post-list-table thead th {
        background: #f8f9fa;
        font-weight: 700;
        color: #344050;
        border-bottom: 1px solid #dee2e6;
    }

    /* .post-list-table img,
    .post-list-table video {
        display: block;
        margin: 0 auto;
    } */

    .post-preview-media {
        display: block;
        margin: 0 auto;
    }

    .post-list-table .dataTables_filter {
        margin-bottom: 12px;
    }

    .post-list-table .badge-soft-approved {
        display: inline-block;
        padding: 6px 10px;
        border-radius: 8px;
        background: #e7f1ff;
        color: #2c7be5;
        font-size: 12px;
        font-weight: 600;
    }
</style>

<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Posts :: Post List</h5>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-responsive post-list-table">
            <table class="table table-bordered table-striped table-datatable w-100">
                <thead>
                    <tr>
                        <th>S No.</th>
                        <th>user</th>
                        <th>type</th>
                        <th>title</th>
                        <th>picture</th>
                        <th>Location</th>
                        <th>Likes</th>
                        <th>Number of comments</th>
                        <!-- <th>Recommended value</th>
                        <th>state</th> -->
                        <th>Submission time</th>
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
            ajax: "{{ route('posts.index') }}",
            order: [
                [0, 'desc']
            ],
            pageLength: 10,
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'user_info',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'post_type',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'title_text',
                    name: 'description'
                },
                {
                    data: 'picture_preview',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'location_name',
                    name: 'country'
                },
                {
                    data: 'likes_count_show',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'comments_count_show',
                    orderable: false,
                    searchable: false
                },
                // {
                //     data: 'recommended_value',
                //     orderable: false,
                //     searchable: false
                // },
                // {
                //     data: 'state_text',
                //     orderable: false,
                //     searchable: false
                // },
                {
                    data: 'submission_time',
                    name: 'created_at'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $(document).on('click', ".delete", function() {
            var id = $(this).data('id')
            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('post.delete') }} ",
                        data: {
                            'id': id
                        },
                        type: 'DELETE',
                        success: function(data) {
                            if (data.status) {
                                Swal.fire('', data?.message, "success")
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