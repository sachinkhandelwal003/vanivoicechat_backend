@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0" data-anchor="data-anchor">Edit Privilege</h5>
                <p class="text-muted mb-0">Update privilege details</p>
            </div>
            <div class="col-auto ms-auto">
                <div class="nav nav-pills nav-pills-falcon">
                    @if(Helper::userCan(104, 'can_add'))
                    <a href="{{ route('privilege.index', $privilege->vip_id) }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i>
                        Back
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="card-body p-4">

            @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('privilege.update', $privilege->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-4">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text"
                            name="name"
                            class="form-control rounded-3 @error('name') is-invalid @enderror"
                            value="{{ old('name', $privilege->name) }}"
                            placeholder="Enter privilege name">

                        @error('name')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status"
                            class="form-select rounded-3 @error('status') is-invalid @enderror">
                            <option value="1" {{ old('status', (string)$privilege->status) == '1' ? 'selected' : '' }}>Enable</option>
                            <option value="0" {{ old('status', (string)$privilege->status) == '0' ? 'selected' : '' }}>Disable</option>
                        </select>

                        @error('status')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Icon</label>

                        <input type="file"
                            name="icon"
                            id="iconInput"
                            class="d-none @error('icon') is-invalid @enderror"
                            accept="image/png,image/jpeg,image/jpg,image/webp">

                        <label for="iconInput"
                            class="upload-box w-100 rounded-4 border border-2 border-dashed d-flex flex-column align-items-center justify-content-center text-center position-relative overflow-hidden bg-white"
                            style="height: 250px; cursor: pointer;">

                            <img id="previewImage"
                                src="{{ $privilege->icon ? Helper::showImage($privilege->icon, true) : '' }}"
                                class="{{ $privilege->icon ? '' : 'd-none' }} position-absolute top-0 start-0 w-100 h-100"
                                style="object-fit: cover; pointer-events: none;">

                            <div id="uploadPlaceholder" class="{{ $privilege->icon ? 'd-none' : '' }}">
                                <div class="upload-icon mb-2">
                                    <i class="fa fa-cloud-upload-alt"></i>
                                </div>
                                <h6 class="fw-bold">{{ $privilege->icon ? 'Click to change image' : 'Click to upload image' }}</h6>
                                <p class="text-muted small mb-0">PNG, JPG, JPEG, WEBP</p>
                            </div>
                        </label>

                        <div id="fileName" class="small text-muted mt-2">
                            {{ $privilege->icon ? basename($privilege->icon) : 'No file selected' }}
                        </div>

                        @error('icon')
                        <small class="text-danger d-block mt-1">{{ $message }}</small>
                        @enderror
                    </div>

                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('privilege.index', $privilege->vip_id) }}" class="btn px-4"
                        style="border:1px solid #f59e0b; color:#f59e0b; background:#fff;">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary px-4">
                        Update Privilege
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>
@endsection

@section('css')
<style>
    .upload-box {
        transition: all 0.25s ease;
    }

    .upload-box:hover {
        border-color: #0d6efd !important;
        background: #f8fbff;
    }

    .upload-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #e9f2ff;
        color: #0d6efd;
        font-size: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: auto;
    }
</style>
@endsection

@section('js')
<script>
    document.getElementById('iconInput').addEventListener('change', function() {
        let file = this.files[0];
        let preview = document.getElementById('previewImage');
        let placeholder = document.getElementById('uploadPlaceholder');
        let fileName = document.getElementById('fileName');

        if (!file) return;

        fileName.innerText = file.name;

        let reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            placeholder.classList.add('d-none');
        };
        reader.readAsDataURL(file);
    });
</script>
@endsection