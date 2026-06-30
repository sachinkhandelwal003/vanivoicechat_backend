@extends('layouts.app')

@section('css')
<link rel="stylesheet" type="text/css" href="{{ asset('assets/plugins/summernote/summernote.min.css') }}">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">Coupons :: Add Coupon</h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('coupon.list') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Go Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <form class="row" id="addCouponForm" method="POST" action="{{ route('coupon.store') }}">
            @csrf

            {{-- Coupon Code --}}
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="coupon_code">Coupon Code <span class="required">*</span></label>
                <input class="form-control" id="coupon_code" name="coupon_code" type="text" placeholder="Enter Coupon Code" value="{{ old('coupon_code') }}">
                @error('coupon_code')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            {{-- Type --}}
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="type">Type <span class="required">*</span></label>
                <select name="type" id="type" class="form-select">
                    <option value="percentage" @selected(old('type')=='percentage' )>Percentage</option>
                    <option value="fixed" @selected(old('type')=='fixed' )>Fixed Amount</option>
                </select>
                @error('type')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            {{-- Value --}}
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="value">Value <span class="required">*</span></label>
                <input class="form-control" id="value" name="value" type="number" placeholder="Enter Value" value="{{ old('value') }}">
                @error('value')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            {{-- Minimum Order Amount --}}
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="min_order_amount">Minimum Order Amount</label>
                <input class="form-control" id="min_order_amount" name="min_order_amount" type="number" placeholder="Enter Minimum Order Amount" value="{{ old('min_order_amount') }}">
                @error('min_order_amount')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            {{-- Maximum Uses --}}
            <!-- <div class="col-lg-6 mt-2">
                <label class="form-label" for="max_uses">Maximum Uses</label>
                <input class="form-control" id="max_uses" name="max_uses" type="number" placeholder="Enter Maximum Uses" value="{{ old('max_uses') }}">
                @error('max_uses')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div> -->

            {{-- Valid From --}}
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="valid_from">Valid From <span class="required">*</span></label>
                <input class="form-control datetimepicker" id="valid_from" name="valid_from" type="text" placeholder="Select Start Date" value="{{ old('valid_from') }}">
                @error('valid_from')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            {{-- Valid Until --}}
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="valid_until">Valid Until <span class="required">*</span></label>
                <input class="form-control datetimepicker" id="valid_until" name="valid_until" type="text" placeholder="Select End Date" value="{{ old('valid_until') }}">
                @error('valid_until')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            {{-- Status --}}
            <div class="col-lg-6 mt-2">
                <label class="form-label" for="status">Status</label>
                <select name="status" class="form-select" id="status">
                    <option value="active" @selected(old('status')=='active' )>Active</option>
                    <option value="inactive" @selected(old('status')=='inactive' )>Inactive</option>
                </select>
                @error('status')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>

            {{-- Submit --}}
            <div class="col-lg-12 mt-3 d-flex justify-content-start">
                <button class="btn btn-secondary submitbtn" type="submit">Add Coupon</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('assets/plugins/summernote/summernote.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $(".datetimepicker").flatpickr({
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });

        $("#addCouponForm").validate({
            rules: {
                coupon_code: {
                    required: true
                },
                type: {
                    required: true
                },
                value: {
                    required: true,
                    number: true
                },
                valid_from: {
                    required: true
                },
                valid_until: {
                    required: true
                },
            },
            messages: {
                coupon_code: "Please enter coupon code",
                type: "Please select coupon type",
                value: "Please enter a valid value",
                valid_from: "Please select valid from date",
                valid_until: "Please select valid until date",
            }
        });
    });
</script>
@endsection