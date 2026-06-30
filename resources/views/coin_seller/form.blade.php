@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                {{ isset($coinSeller) ? 'Edit' : 'Add' }} Coin Seller
            </h5>

            <a href="{{ route('coin_seller') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card-body">

        <form action="{{ isset($coinSeller) ? route('coin_seller.save', $coinSeller->id) : route('coin_seller.save') }}"
            method="POST">
            @csrf

            <div class="row g-3">

                <!-- USER UID -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        UID (User ID) <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="user_uid" class="form-control"
                        placeholder="Enter User ID"
                        value="{{ old('user_uid', $coinSeller->user->uid ?? '') }}">

                    <small class="text-muted">
                        Enter the User ID. The system will verify if the user exists.
                    </small>
                </div>

                <!-- COUNTRY -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Country <span class="text-danger">*</span>
                    </label>
                    <select name="country_id" class="form-control">
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                        <option value="{{ $country->id }}"
                            {{ old('country_id', $coinSeller->country_id ?? '') == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- WHATSAPP -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Whatsapp Number <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="whatsapp_number" class="form-control"
                        placeholder="Enter Whatsapp Number"
                        value="{{ old('whatsapp_number', $coinSeller->whatsapp_number ?? '') }}">
                </div>

                <!-- MERCHANT RADIO -->
                @if(!isset($coinSeller))
                <div class="col-md-6">
                    <label class="form-label fw-semibold">
                        Do you want to make this user a Merchant?
                    </label>

                    <div class="d-flex gap-3 mt-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_merchant" value="1"
                                {{ old('is_merchant', 0) == 1 ? 'checked' : '' }}>
                            <label class="form-check-label">Yes</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_merchant" value="0"
                                {{ old('is_merchant', 0) == 0 ? 'checked' : '' }}>
                            <label class="form-check-label">No</label>
                        </div>
                    </div>
                </div>
                @endif

                <!-- STATUS -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ old('status', $coinSeller->status ?? 1) == 1 ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="0" {{ old('status', $coinSeller->status ?? 0) == 0 ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                </div>

            </div>

            <!-- BUTTONS -->
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-success px-4">
                    {{ isset($coinSeller) ? 'Update' : 'Add' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection