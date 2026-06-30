@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Add Lucky Gift Winning Setting
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('lucky-gift-setting.add', $giftId) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <input type="hidden" name="gift_id" value="{{ $giftId }}">

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            Quantity <span class="text-danger">*</span>
                        </label>

                        <select name="quantity" id="quantity"
                            class="form-control @error('quantity') is-invalid @enderror">
                            <option value="1" {{ old('quantity')=='1'?'selected':'' }}>1</option>
                            <option value="10" {{ old('quantity')=='10'?'selected':'' }}>10</option>
                            <option value="66" {{ old('quantity')=='66'?'selected':'' }}>66</option>
                            <option value="88" {{ old('quantity')=='88'?'selected':'' }}>88</option>
                            <option value="100" {{ old('quantity')=='100'?'selected':'' }}>100</option>
                            <option value="500" {{ old('quantity')=='500'?'selected':'' }}>500</option>
                            <option value="1314" {{ old('quantity')=='1314'?'selected':'' }}>1314</option>
                        </select>

                        @error('quantity')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3" id="multiple">
                        <label class="form-label fw-bold">Multiple</label>

                        <input type="text" name="multiple" value="{{ old('multiple') }}"
                            class="form-control @error('multiple') is-invalid @enderror" placeholder="0">

                        @error('multiple')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3" id="appBox">
                        <label class="form-label fw-bold">Is it the whole site</label>

                        <select name="is_whole_site" id="appType"
                            class="form-control @error('is_whole_site') is-invalid @enderror">
                            <option value="0" {{ old('is_whole_site')=='0'?'selected':'' }}>N0</option>
                            <option value="1" {{ old('is_whole_site')=='1'?'selected':'' }}>Yes</option>
                        </select>

                        @error('is_whole_site')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3" id="probability">
                        <label class="form-label fw-bold">Probability of winning</label>

                        <input type="text" name="probability"
                            value="{{ old('probability') }}"
                            class="form-control @error('probability') is-invalid @enderror"
                            placeholder="">
                        <span>% Minimum support 0.001%</span>

                        @error('probability')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('lucky-gift-setting',$giftId) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('js')

@endsection