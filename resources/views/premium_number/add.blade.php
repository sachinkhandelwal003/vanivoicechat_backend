@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Add Premium Number
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('premium_number.add') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <!-- <div class="mb-4">
                    <label class="form-label fw-bold">
                        Cover <span class="text-danger">*</span>
                    </label>

                    <div class="d-flex gap-4"> 
                        <div>
                            <input type="file" name="resourse" class="d-none" id="resourse" accept="image/*">

                            <label for="resourse"
                                class="border rounded d-flex align-items-center justify-content-center position-relative"
                                style="width:150px;height:120px;cursor:pointer;overflow:hidden;">
                                <img id="smallPreview" class="position-absolute w-100 h-100 d-none"
                                    style="object-fit:cover;">
                                <span id="smallPlus" class="fs-1">+</span>
                            </label>

                            <small class="d-block text-center mt-1">Number resourse</small>

                            @error('resourse')
                            <small class="text-danger d-block text-center">{{ $message }}</small>
                            @enderror
                        </div>

                    </div>
                </div> -->

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">User ID</label>

                        <input type="text" name="uid"
                            value="{{ old('uid') }}"
                            class="form-control @error('uid') is-invalid @enderror">

                        @error('uid')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Premium Number</label>

                        <input type="text" name="premium_number"
                            value="{{ old('premium_number') }}"
                            class="form-control @error('premium_number') is-invalid @enderror">

                        @error('premium_number')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Valid Days</label>

                        <input type="text" name="valid_days"
                            value="{{ old('valid_days') }}"
                            class="form-control @error('valid_days') is-invalid @enderror">

                        @error('valid_days')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                   
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ route('premium_number') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>

    function previewImage(input, preview, plus) {
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.classList.remove('d-none');
                plus.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    }

    previewImage(
        document.getElementById('resourse'),
        document.getElementById('smallPreview'),
        document.getElementById('smallPlus')
    );
</script>
@endsection