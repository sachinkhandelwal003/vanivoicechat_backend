@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="mb-0" data-anchor="data-anchor">About Us</h5>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="col-auto ms-auto">
                            <button class="btn btn-outline-secondary" onclick="history.back()">
                                <i class="fa fa-arrow-left me-1"></i> Go Back
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <form class="row" action="{{ route('save') }}" method="POST">
                    @csrf

                    <div class="col-lg-12 mb-3">
                        <label class="form-label" for="content">About Us <span class="required">*</span></label>
                        <textarea class="form-control" name="content" rows="6" style="min-height:150px;">{{ old('content', $about->content ?? '') }}</textarea>
                        @error('content')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-secondary w-100">
                            Save
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>
@endsection