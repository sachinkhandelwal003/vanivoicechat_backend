@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                {{ isset($user) ? 'Edit' : 'Add' }} User
            </h5>

            <a href="{{ route('app-users') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card-body">

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ isset($user) ? route('user.save', $user->id) : route('user.save') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-4">

                <!-- NAME -->
                <div class="col-md-4">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name', $user->name ?? '') }}">
                </div>

                <!-- UID -->
                <div class="col-md-4">
                    <label class="form-label">UID</label>
                    <input type="text" name="uid" class="form-control"
                        value="{{ old('uid', $user->uid ?? '') }}">
                </div>

                <!-- GENDER -->
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-control">
                        <option value="">Select</option>
                        <option value="Boy" {{ old('gender', $user->gender ?? '') == 'Boy' ? 'selected' : '' }}>Boy</option>
                        <option value="Girl" {{ old('gender', $user->gender ?? '') == 'Girl' ? 'selected' : '' }}>Girl</option>
                    </select>
                </div>

                <!-- COUNTRY -->
                <div class="col-md-6">
                    <label class="form-label">Country</label>
                    <input type="text" name="country" class="form-control"
                        value="{{ old('country', $user->country ?? '') }}">
                </div>

                <!-- EMAIL -->
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                        value="{{ old('email', $user->email ?? '') }}">
                </div>

                <!-- PHONE -->
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                        value="{{ old('phone', $user->phone ?? '') }}">
                </div>

                <!-- BIRTH DATE -->
                <div class="col-md-6">
                    <label class="form-label">Birthdate</label>
                    <input type="text" name="birthdate" class="form-control"
                        value="{{ old('birthdate', $user->birthdate ?? '') }}">
                </div>

                <!-- IMAGE -->
                <div class="col-md-4">
                    <label class="form-label">Profile Image</label>
                    <div class="border rounded-3 p-3">

                        <input type="file" name="image" class="d-none image-input" id="image">

                        <label for="image"
                            class="upload-box w-100 d-flex align-items-center justify-content-center text-center"
                            style="height:140px; cursor:pointer; border:2px dashed #ccc;">

                            <img src="{{ isset($user) && $user->image ? (Str::startsWith($user->image, ['http://', 'https://']) 
                                            ? $user->image : asset('storage/'.$user->image)): '' }}"
                                class="preview-image {{ isset($user) && $user->image ? '' : 'd-none' }}"
                                style="width:100%; height:100%; object-fit:cover;">

                            <div class="upload-placeholder {{ isset($user) && $user->image ? 'd-none' : '' }}">
                                <small>Click to upload</small>
                            </div>

                        </label>
                    </div>
                </div>

            </div>

            <div class="mt-4 text-end">
                <button class="btn btn-primary">
                    {{ isset($user) ? 'Update' : 'Save' }}
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