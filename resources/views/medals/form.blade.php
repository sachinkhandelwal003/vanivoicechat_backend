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

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
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

            <form action="{{ isset($medal) ? route('medals.store', $medal->id) : route('medals.store') }}" method="POST"
                enctype="multipart/form-data">
                @csrf

                <div class="row g-4">

                    <!-- LEFT SIDE -->
                    <div class="col-lg-6">

                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control"
                                value="{{ old('title', $medal->title ?? '') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="achievement"
                                    {{ old('type', $medal->type ?? '') == 'achievement' ? 'selected' : '' }}>
                                    Achievement
                                </option>
                                <option value="event" {{ old('type', $medal->type ?? '') == 'event' ? 'selected' : '' }}>
                                    Event
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Level</label>
                            <input type="number" name="level" class="form-control"
                                value="{{ old('level', $medal->level ?? '') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Target Value</label>
                            <input type="number" name="target_value" class="form-control"
                                value="{{ old('target_value', $medal->target_value ?? '') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sort</label>
                            <input type="number" name="sort" class="form-control"
                                value="{{ old('sort', $medal->sort ?? '') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">File Type</label>
                            <select name="file_type" class="form-select">
                                <option value="image"
                                    {{ old('file_type', $medal->file_type ?? 'image') == 'image' ? 'selected' : '' }}>
                                    Image
                                </option>
                                <option value="svga"
                                    {{ old('file_type', $medal->file_type ?? 'svga') == 'svga' ? 'selected' : '' }}>
                                    SVGA
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
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
                    <!-- RIGHT SIDE -->
                    <div class="col-lg-6">

                        <label class="form-label">Icon</label>

                        <div class="border rounded-4 p-3 shadow-sm">

                            <input type="file" name="icon" id="icon" class="d-none image-input"
                                accept=".png,.jpg,.jpeg,.gif,.svga">

                            <label for="icon"
                                class="upload-box d-flex flex-column align-items-center justify-content-center w-100"
                                style="height:320px; cursor:pointer; border:2px dashed #d1d5db; border-radius:12px;">

                                <img src="{{ isset($medal) && $medal->icon ? asset('storage/' . $medal->icon) : '' }}"
                                    class="preview-image {{ isset($medal) && $medal->icon ? '' : 'd-none' }}"
                                    style="max-height:250px; max-width:100%; object-fit:contain;">

                                <div class="upload-placeholder {{ isset($medal) && $medal->icon ? 'd-none' : '' }}">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-secondary mb-3"></i>
                                    <h6 class="mb-1">Click to Upload</h6>
                                    <small class="text-muted">
                                        PNG, JPG, JPEG, GIF, SVGA
                                    </small>
                                </div>

                            </label>

                        </div>

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
