@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Add Theme Given
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('theme.save.give') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="theme_id" value="{{ $theme_id }}">
                <div class="mb-3">
                    <label class="form-label fw-bold">Id <span class="text-danger">*</span></label>
                    <input type="text" name="user_id"
                        class="form-control @error('user_id') is-invalid @enderror"
                        value="{{ old('user_id') }}">
                    @error('user_id')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div id="inAppFields" class="">
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Duration (Days) <span class="text-danger">*</span>
                        </label>
                        <input type="number" name="duration"
                            class="form-control @error('duration') is-invalid @enderror"
                            min="1"
                            value="{{ old('duration') }}"
                            placeholder="e.g. 7">

                        @error('duration')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('theme') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('js')

@endsection