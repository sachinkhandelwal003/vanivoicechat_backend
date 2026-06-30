@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-header">
                <div class="row flex-between-end">
                    <div class="col-auto align-self-center">
                        <h5 class="mb-0" data-anchor="data-anchor">FAQ's</h5>
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
                <form class="row" action="{{ route('saveFaq') }}" method="POST">
                    @csrf
                    <div class="col-lg-12 mb-3">
                        <label class="form-label" for="question">FAQ's Question<span class="required">*</span></label>
                        <input class="form-control" name="question" type="text" value="{{ old('question', $faq->question ?? '') }}">
                        @error('question')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-lg-12 mb-3">
                        <label class="form-label" for="answer">FAQ's Answer<span class="required">*</span></label>
                        <textarea class="form-control" name="answer" rows="6" style="min-height:150px;">{{ old('answer', $faq->answer ?? '') }}</textarea>
                        @error('answer')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-lg-4 mt-2 mb-3">
                        <label class="form-label" for="status">Status</label>
                        <select name="status" class="form-select" id="status">
                            <option value="1" @selected(old('status')=='1' )>Active</option>
                            <option value="0" @selected(old('status')=='0' )>Inactive</option>
                        </select>
                        @error('status')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
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
