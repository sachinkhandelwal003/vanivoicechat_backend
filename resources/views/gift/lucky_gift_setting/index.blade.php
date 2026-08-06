@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Lucky Gift Winning Setting :: List</h5>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    @if(Helper::userCan(113, 'can_add'))
                    <a href="{{ route('lucky-gift-setting.add', $giftId) }}" class="btn btn-outline-secondary">
                        <i class="fa fa-plus me-1"></i>
                        Add Lucky Gift Winning Setting
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">

        <div class="table-responsive">
            <table class="table table-striped table-datatable" style="width:100%">

                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Quantity</th>
                        <th>Multiple</th>
                        <th>Probability of winning</th>
                        <th>Whether to notify the entire site</th>
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
                url: "{{ route('lucky-gift-setting',$giftId) }}",
                data: function(d) {
                    d.expired = $('#expired').val();
                    d.jump = $('#jump').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'quantity'
                },
                {
                    data: 'multiple'
                },
                {
                    data: 'probability'
                },
                {
                    data: 'is_whole_site'
                },
                {
                    data: 'action',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('#searchBtn').click(() => table.ajax.reload());

        $(document).on('click', ".delete", function() {
            var id = $(this).data('id')
            Swal.fire(deleteMessageSwalConfig).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('lucky-gift-setting.delete') }} ",
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
