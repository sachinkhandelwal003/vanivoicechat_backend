@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Update Lucky Gift Winning Setting
        </div>

        <div class="card-body">

            <form action="{{ route('lucky-gift-setting.edit', $luckyGift->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            Quantity <span class="text-danger">*</span>
                        </label>

                        <select name="quantity" id="quantity"
                            class="form-control @error('quantity') is-invalid @enderror">
                            <option value="1" {{ old('quantity', $luckyGift->quantity)=='1'?'selected':'' }}>1</option>
                            <option value="10" {{ old('quantity', $luckyGift->quantity)=='10'?'selected':'' }}>10</option>
                            <option value="66" {{ old('quantity', $luckyGift->quantity)=='66'?'selected':'' }}>66</option>
                            <option value="88" {{ old('quantity', $luckyGift->quantity)=='88'?'selected':'' }}>88</option>
                            <option value="100" {{ old('quantity', $luckyGift->quantity)=='100'?'selected':'' }}>100</option>
                            <option value="500" {{ old('quantity', $luckyGift->quantity)=='500'?'selected':'' }}>500</option>
                            <option value="1314" {{ old('quantity', $luckyGift->quantity)=='1314'?'selected':'' }}>1314</option>
                        </select>

                        @error('quantity')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3" id="multiple">
                        <label class="form-label fw-bold">Multiple</label>

                        <input type="text" name="multiple" value="{{ old('multiple', $luckyGift->multiple) }}"
                            class="form-control @error('multiple') is-invalid @enderror" placeholder="0">

                        @error('multiple')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3" id="appBox">
                        <label class="form-label fw-bold">Is it the whole site</label>

                        <select name="is_whole_site" id="appType"
                            class="form-control @error('is_whole_site') is-invalid @enderror">
                            <option value="0" {{ old('is_whole_site', $luckyGift->is_whole_site)=='0'?'selected':'' }}>N0</option>
                            <option value="1" {{ old('is_whole_site', $luckyGift->is_whole_site)=='1'?'selected':'' }}>Yes</option>
                        </select>

                        @error('is_whole_site')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3" id="probability">
                        <label class="form-label fw-bold">Probability of winning</label>

                        <input type="text" name="probability"
                            value="{{ old('probability', $luckyGift->probability) }}"
                            class="form-control @error('probability') is-invalid @enderror"
                            placeholder="">
                        <span>% Minimum support 0.001%</span>

                        @error('probability')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('lucky-gift-setting', $luckyGift->gift_id) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


@section('js')

@endsection