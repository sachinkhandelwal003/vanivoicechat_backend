@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Add Treasure Level</h4>

        <a href="{{ route('treasure-levels.index') }}" class="btn btn-secondary">
            Back
        </a>
    </div>

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('treasure-levels.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card mb-3">
            <div class="card-header">
                <strong>Level Details</strong>
            </div>

            <div class="card-body row">
                <div class="col-md-3 mb-3">
                    <label>Level</label>
                    <input type="number" name="level" class="form-control" placeholder="1" value="{{ old('level') }}">
                    @error('level') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label>Target Points</label>
                    <input type="number" name="target_points" class="form-control" placeholder="300000" value="{{ old('target_points') }}">
                    @error('target_points') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Chest Image</label>
                    <input type="file" name="chest_image" class="form-control">
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <strong>Rewards</strong>

                <button type="button" class="btn btn-sm btn-success" id="addRewardRow">
                    <i class="fa fa-plus"></i> Add Reward
                </button>
            </div>

            <div class="card-body">
                <div id="rewardWrapper">

                    <div class="reward-row row border rounded p-2 mb-2">
                        <div class="col-md-2 mb-2">
                            <label>Reward Type</label>
                            <select name="reward_type[]" class="form-control reward-type">
                                <option value="">Select</option>
                                <option value="coins">Coins</option>
                                <option value="vip">VIP</option>
                                <option value="theme">Theme</option>
                                <option value="entry">Entry</option>
                                <option value="entry_tag">Entry Tag</option>
                                <option value="frame">Frame</option>
                                <option value="chat_bubble">Chat Bubble</option>
                                <option value="profile_card">Profile Card</option>
                                <option value="voice">Voice</option>
                                <option value="id">UID</option>
                            </select>
                        </div>

                        <div class="col-md-3 mb-2 item-box d-none">
                            <label>Select Item</label>
                            <select name="reward_item_id[]" class="form-control reward-item">
                                <option value="">Select Item</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-2 coins-box d-none">
                            <label>Coins</label>
                            <input type="number" name="coins[]" class="form-control coins-input" placeholder="5000" value="0">
                        </div>

                        <div class="col-md-2 mb-2 valid-days-box d-none">
                            <label>Valid Days</label>
                            <input type="number" name="valid_days[]" class="form-control valid-days-input" placeholder="3">
                        </div>

                        <div class="col-md-1 mb-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger removeRewardRow">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <button class="btn btn-primary">
            Save
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function rewardRowHtml() {
        return `
            <div class="reward-row row border rounded p-2 mb-2">
                <div class="col-md-2 mb-2">
                    <label>Reward Type</label>
                    <select name="reward_type[]" class="form-control reward-type">
                        <option value="">Select</option>
                        <option value="coins">Coins</option>
                        <option value="vip">VIP</option>
                        <option value="theme">Theme</option>
                        <option value="entry">Entry</option>
                        <option value="entry_tag">Entry Tag</option>
                        <option value="frame">Frame</option>
                        <option value="chat_bubble">Chat Bubble</option>
                        <option value="profile_card">Profile Card</option>
                        <option value="voice">Voice</option>
                        <option value="id">UID</option>
                    </select>
                </div>

                <div class="col-md-3 mb-2 item-box d-none">
                    <label>Select Item</label>
                    <select name="reward_item_id[]" class="form-control reward-item">
                        <option value="">Select Item</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2 coins-box d-none">
                    <label>Coins</label>
                    <input type="number" name="coins[]" class="form-control coins-input" placeholder="5000" value="0">
                </div>

                <div class="col-md-2 mb-2 valid-days-box d-none">
                    <label>Valid Days</label>
                    <input type="number" name="valid_days[]" class="form-control valid-days-input" placeholder="3">
                </div>

                <div class="col-md-1 mb-2 d-flex align-items-end">
                    <button type="button" class="btn btn-danger removeRewardRow">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }

    $(document).on('click', '#addRewardRow', function() {
        $('#rewardWrapper').append(rewardRowHtml());
    });

    $(document).on('click', '.removeRewardRow', function() {
        if ($('.reward-row').length > 1) {
            $(this).closest('.reward-row').remove();
        }
    });

    $(document).on('change', '.reward-type', function() {
        let type = $(this).val();
        let row = $(this).closest('.reward-row');

        let itemBox = row.find('.item-box');
        let itemDropdown = row.find('.reward-item');

        let coinsBox = row.find('.coins-box');
        let coinsInput = row.find('.coins-input');

        let validDaysBox = row.find('.valid-days-box');
        let validDaysInput = row.find('.valid-days-input');

        itemDropdown.html('<option value="">Select Item</option>');
        coinsInput.val(0);
        validDaysInput.val('');

        itemBox.addClass('d-none');
        coinsBox.addClass('d-none');
        validDaysBox.addClass('d-none');

        if (type === '') {
            return;
        }

        if (type === 'coins') {
            coinsBox.removeClass('d-none');
            return;
        }

        itemBox.removeClass('d-none');
        validDaysBox.removeClass('d-none');

        itemDropdown.html('<option value="">Loading...</option>');

        $.ajax({
            url: "{{ route('treasure-levels.getRewardItems') }}",
            type: "GET",
            data: {
                type: type
            },
            success: function(res) {
                let html = '<option value="">Select Item</option>';

                if (res.status && res.data.length > 0) {
                    $.each(res.data, function(index, item) {
                        html += `<option value="${item.id}">${item.name}</option>`;
                    });
                } else {
                    html = '<option value="">No item found</option>';
                }

                itemDropdown.html(html);
            },
            error: function() {
                itemDropdown.html('<option value="">Something went wrong</option>');
            }
        });
    });
</script>
@endpush