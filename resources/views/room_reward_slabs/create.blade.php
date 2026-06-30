@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Add Room Reward Slab</h4>
        </div>

        <div class="card-body">
            <form action="{{ route('room_reward_slabs.add') }}" method="POST">
                @csrf

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Room Contribution</label>
                        <input type="number"
                            name="room_contribution"
                            class="form-control @error('room_contribution') is-invalid @enderror"
                            value="{{ old('room_contribution') }}"
                            placeholder="Example: 150000">

                        @error('room_contribution')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Reward Coins</label>
                        <input type="number"
                            name="reward_coins"
                            class="form-control @error('reward_coins') is-invalid @enderror"
                            value="{{ old('reward_coins') }}"
                            placeholder="Example: 22500">

                        @error('reward_coins')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Sort Order</label>
                        <input type="number"
                            name="sort_order"
                            class="form-control @error('sort_order') is-invalid @enderror"
                            value="{{ old('sort_order', 0) }}">

                        @error('sort_order')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control @error('status') is-invalid @enderror">
                            <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>Inactive</option>
                        </select>

                        @error('status')
                        <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        Save
                    </button>

                    <a href="{{ route('room_reward_slabs') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection