@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Add Family Level Privilege
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('family.level.privilege.add', $levelId) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <input type="hidden" name="level_id" value="{{$levelId}}">
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Level Badge <span class="text-danger">*</span>
                    </label>

                    <div>
                        <input type="file" name="level_badge" id="level_badge" class="d-none" accept="image/*">

                        <label for="level_badge"
                            class="border rounded d-flex align-items-center justify-content-center position-relative"
                            style="width:150px;height:120px;cursor:pointer;overflow:hidden;">
                            <img id="badgePreview" class="position-absolute w-100 h-100 d-none" style="object-fit:cover;">
                            <span id="badgePlus" class="fs-1">+</span>
                        </label>


                        <small class="d-block mt-1 text-muted">
                            Recommended size: 50 × 50
                        </small>

                        @error('cover')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Level Frame <span class="text-danger">*</span>
                    </label>

                    <div>
                        <input type="file" name="level_frame" id="level_frame" class="d-none" accept="image/*">

                        <label for="level_frame"
                            class="border rounded d-flex align-items-center justify-content-center position-relative"
                            style="width:150px;height:120px;cursor:pointer;overflow:hidden;">
                            <img id="framePreview" class="position-absolute w-100 h-100 d-none" style="object-fit:cover;">
                            <span id="framePlus" class="fs-1">+</span>
                        </label>
                       

                        <small class="d-block mt-1 text-muted">
                            Recommended size: 50 × 50
                        </small>

                        @error('cover')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Member <span class="text-danger">*</span></label>
                    <input type="text" name="member"
                        class="form-control @error('member') is-invalid @enderror"
                        value="{{ old('member') }}">
                    @error('member')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Admin <span class="text-danger">*</span></label>
                    <input type="text" name="admin"
                        class="form-control @error('admin') is-invalid @enderror"
                        value="{{ old('admin') }}">
                    @error('admin')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('family.level.privilege', $levelId) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {

    function previewImage(inputId, previewId, plusId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const plus = document.getElementById(plusId);

        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                plus.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    }

    previewImage('level_badge', 'badgePreview', 'badgePlus');
    previewImage('level_frame', 'framePreview', 'framePlus');

});
</script>
@endsection
