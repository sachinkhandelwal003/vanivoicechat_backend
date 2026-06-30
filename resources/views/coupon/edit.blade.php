@extends('layouts.app')

@section('css')
<!-- Optional CSS for additional plugins -->
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Coupon :: Edit</h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('coupon.list') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Go Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <form class="row" method="POST" action="{{ route('coupon.update', $coupon->id) }}">
            @csrf
            @method('PUT')

            <!-- Code -->
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="code">Coupon Code</label>
                <input class="form-control" id="coupon_code" name="coupon_code" type="text" value="{{ old('coupon_code', $coupon->coupon_code) }}" />
                @error('code')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <!-- Type -->
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="type">Coupon Type</label>
                <select name="type" class="form-select" id="type">
                    <option value="percentage" @selected(old('type', $coupon->type) == 'percentage')>Percentage</option>
                    <option value="fixed" @selected(old('type', $coupon->type) == 'fixed')>Fixed Amount</option>
                </select>
                @error('type')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <!-- Value -->
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="value">Value</label>
                <input class="form-control" id="value" name="value" type="number" step="0.01" value="{{ old('value', $coupon->value) }}" />
                @error('value')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <!-- Minimum Order Amount -->
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="min_order_amount">Minimum Order Amount</label>
                <input class="form-control" id="min_order_amount" name="min_order_amount" type="number" step="0.01" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" />
                @error('min_order_amount')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <!-- Maximum Uses -->
            <!-- <div class="col-lg-6 mt-2">
                <label class="form-label" for="max_uses">Maximum Uses</label>
                <input class="form-control" id="max_uses" name="max_uses" type="number" value="{{ old('max_uses', $coupon->max_uses) }}" />
                @error('max_uses')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div> -->

            <!-- Valid From -->
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="valid_from">Valid From</label>
                <input class="form-control" id="valid_from" name="valid_from" type="datetime-local" 
                       value="{{ old('valid_from', \Carbon\Carbon::parse($coupon->valid_from)->format('Y-m-d\TH:i')) }}" />
                @error('valid_from')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <!-- Valid Until -->
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="valid_until">Valid Until</label>
                <input class="form-control" id="valid_until" name="valid_until" type="datetime-local" 
                       value="{{ old('valid_until', \Carbon\Carbon::parse($coupon->valid_until)->format('Y-m-d\TH:i')) }}" />
                @error('valid_until')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <!-- Status -->
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="status">Status</label>
                <select name="status" class="form-select" id="status">
                    <option value="active" @selected(old('status', $coupon->status) == 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $coupon->status) == 'inactive')>Inactive</option>
                </select>
                @error('status')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            <!-- Submit -->
            <div class="col-lg-12 mt-3">
                <button class="btn btn-secondary" type="submit">Update Coupon</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<!-- Optional JS for additional plugins -->
@endsection
