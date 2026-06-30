@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <!-- HEADER -->
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    {{ isset($medal) ? 'Edit' : 'Add' }} Medal
                </h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('medals.index') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <!-- BODY -->
    <div class="card-body">

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form
            action="{{ isset($medal) ? route('medals.store', $medal->id) : route('medals.store') }}"
            method="POST"
            enctype="multipart/form-data">
            @csrf

            <div class="row g-4">

                <!-- LEFT SIDE -->
                <div class="col-md-6">

                    <!-- TITLE -->
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control"
                            value="{{ old('title', $medal->title ?? '') }}" required>
                    </div>

                    <!-- TYPE -->
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-control" required>
                            <option value="achievement"
                                {{ old('type', $medal->type ?? '') == 'achievement' ? 'selected' : '' }}>
                                Achievement
                            </option>
                            <option value="event"
                                {{ old('type', $medal->type ?? '') == 'event' ? 'selected' : '' }}>
                                Event
                            </option>
                        </select>
                    </div>

                    <!-- STATUS -->
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="1" {{ old('status', $medal->status ?? 1) == 1 ? 'selected' : '' }}>
                                Active
                            </option>
                            <option value="0" {{ old('status', $medal->status ?? 1) == 0 ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>
                    </div>

                </div>

                <!-- RIGHT SIDE -->
                <div class="col-md-6">

                    <label class="form-label mb-2">Icon</label>

                    <div class="border rounded-4 p-3 shadow-sm">

                        <input type="file" name="icon"
                            class="d-none image-input" id="icon">

                        <label for="icon"
                            class="upload-box d-flex flex-column align-items-center justify-content-center w-100"
                            style="height:170px; cursor:pointer; border:2px dashed #d1d5db; border-radius:12px;">


                            <img
                                src="{{ isset($medal) && $medal->icon ? asset('storage/'.$medal->icon) : '' }}"
                                class="preview-image {{ isset($medal) && $medal->icon ? '' : 'd-none' }}"
                                style="max-height:100px; object-fit:contain;">

                            <div class="upload-placeholder {{ isset($medal) && $medal->icon ? 'd-none' : '' }}">
                                <i class="fa fa-cloud-upload-alt mb-1" style="font-size:20px; color:#999;"></i>
                                <div style="font-size:13px; color:#666;">Click to upload</div>
                                <small class="text-muted">PNG, JPG</small>
                            </div>

                        </label>

                    </div>

                </div>

            </div>

            <div class="mt-4 text-end">
                <button class="btn btn-primary">
                    {{ isset($medal) ? 'Update' : 'Save' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection

@section('js')
<script>
    document.querySelectorAll('.image-input').forEach(input => {
        input.addEventListener('change', function() {
            let file = this.files[0];
            let container = this.closest('.border');
            let preview = container.querySelector('.preview-image');
            let placeholder = container.querySelector('.upload-placeholder');

            if (file) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                    placeholder.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    });
</script>
@endsection