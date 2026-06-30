@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Edit Treasure Level</h4>

        <a href="{{ route('treasure-levels.index') }}" class="btn btn-secondary">
            Back
        </a>
    </div>

    <form action="{{ route('treasure-levels.update', $level->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card mb-3">
            <div class="card-header">
                <strong>Level Details</strong>
            </div>

            <div class="card-body row">
                <div class="col-md-3 mb-3">
                    <label>Level</label>
                    <input type="number" name="level" class="form-control" value="{{ $level->level }}">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Target Points</label>
                    <input type="number" name="target_points" class="form-control" value="{{ $level->target_points }}">
                </div>

                <div class="col-md-3 mb-3">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ $level->status == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ $level->status == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label>Chest Image</label>
                    <input type="file" name="chest_image" class="form-control">

                    @if($level->chest_image)
                    <img src="{{ asset($level->chest_image) }}" width="70" class="mt-2">
                    @endif
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

                    @foreach($level->rewards as $reward)

                    <div class="reward-row row border rounded p-2 mb-2">

                        <div class="col-md-2 mb-2">
                            <label>Reward Type</label>
                            <select name="reward_type[]" class="form-control reward-type">
                                <option value="">Select</option>
                                <option value="coins" {{ $reward->reward_type == 'coins' ? 'selected' : '' }}>Coins</option>
                                <option value="vip" {{ $reward->reward_type == 'vip' ? 'selected' : '' }}>VIP</option>
                                <option value="theme" {{ $reward->reward_type == 'theme' ? 'selected' : '' }}>Theme</option>
                                <option value="entry" {{ $reward->reward_type == 'entry' ? 'selected' : '' }}>Entry</option>
                                <option value="entry_tags" {{ $reward->reward_type == 'entry_tags' ? 'selected' : '' }}>Entry Tag</option>
                                <option value="frame" {{ $reward->reward_type == 'frame' ? 'selected' : '' }}>Frame</option>
                                <option value="chat_bubble" {{ $reward->reward_type == 'chat_bubble' ? 'selected' : '' }}>Chat Bubble</option>
                                <option value="profile_card" {{ $reward->reward_type == 'profile_card' ? 'selected' : '' }}>Profile Card</option>
                                <option value="voice" {{ $reward->reward_type == 'voice' ? 'selected' : '' }}>Voice</option>
                                <option value="id" {{ $reward->reward_type == 'id' ? 'selected' : '' }}>UID</option>
                            </select>
                        </div>

                        <div class="col-md-3 mb-2 item-box">
                            <label>Select Item</label>
                            <select name="reward_item_id[]" class="form-control reward-item"
                                data-selected="{{ $reward->reward_item_id }}">
                            </select>
                        </div>

                        <div class="col-md-2 mb-2 coins-box">
                            <label>Coins</label>
                            <input type="number" name="coins[]" class="form-control coins-input"
                                value="{{ $reward->coins }}">
                        </div>

                        <div class="col-md-2 mb-2 valid-days-box">
                            <label>Valid Days</label>
                            <input type="number" name="valid_days[]" class="form-control valid-days-input"
                                value="{{ $reward->valid_days }}">
                        </div>

                        <div class="col-md-1 mb-2 d-flex align-items-end">
                            <button type="button" class="btn btn-danger removeRewardRow">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>

                    </div>

                    @endforeach

                </div>
            </div>
        </div>

        <button class="btn btn-primary">Update</button>
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
                    <option value="entry_tags">Entry Tag</option>
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
                <input type="number" name="coins[]" class="form-control coins-input" value="0">
            </div>

            <div class="col-md-2 mb-2 valid-days-box d-none">
                <label>Valid Days</label>
                <input type="number" name="valid_days[]" class="form-control valid-days-input">
            </div>

            <div class="col-md-1 mb-2 d-flex align-items-end">
                <button type="button" class="btn btn-danger removeRewardRow">
                    <i class="fa fa-trash"></i>
                </button>
            </div>

        </div>
    `;
    }

    // ADD ROW
    $(document).on('click', '#addRewardRow', function() {
        $('#rewardWrapper').append(rewardRowHtml());
    });

    // REMOVE ROW
    $(document).on('click', '.removeRewardRow', function() {
        if ($('.reward-row').length > 1) {
            $(this).closest('.reward-row').remove();
        }
    });
</script>
<script>
    function loadItems(row, type) {

        let dropdown = row.find('.reward-item');

        $.get("{{ route('treasure-levels.getRewardItems') }}", {
            type: type
        }, function(res) {

            let html = '<option value="">Select Item</option>';

            res.data.forEach(item => {
                html += `<option value="${item.id}">${item.name}</option>`;
            });

            dropdown.html(html);

            // set selected
            let selected = dropdown.data('selected');
            if (selected) {
                dropdown.val(selected);
            }
        });
    }

    $(document).on('change', '.reward-type', function() {

        let type = $(this).val();
        let row = $(this).closest('.reward-row');

        let itemBox = row.find('.item-box');
        let coinsBox = row.find('.coins-box');
        let validDaysBox = row.find('.valid-days-box');

        itemBox.addClass('d-none');
        coinsBox.addClass('d-none');
        validDaysBox.addClass('d-none');

        if (type === 'coins') {
            coinsBox.removeClass('d-none');
            return;
        }

        if (type) {
            itemBox.removeClass('d-none');
            validDaysBox.removeClass('d-none');
            loadItems(row, type);
        }
    });

    //  on page load trigger for all rows
    $(document).ready(function() {
        $('.reward-row').each(function() {
            let type = $(this).find('.reward-type').val();
            if (type) {
                $(this).find('.reward-type').trigger('change');
            }
        });
    });
</script>
@endpush