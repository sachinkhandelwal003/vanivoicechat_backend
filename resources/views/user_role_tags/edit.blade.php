@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">

            <div class="card-header fw-bold">
                Edit User Role Tag
            </div>

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert">
                        </button>
                    </div>
                @endif

                <form action="{{ route('user-role-tags.edit', $userRoleTag->id) }}" method="POST"
                    enctype="multipart/form-data">

                    @csrf

                    {{-- Tag Name --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Tag Name
                            <span class="text-danger">*</span>
                        </label>

                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $userRoleTag->name) }}">

                        @error('name')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>


                    {{-- Role Type --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Role Type
                            <span class="text-danger">*</span>
                        </label>

                        <select name="role_type" class="form-select @error('role_type') is-invalid @enderror">

                            <option value="admin_center"
                                {{ old('role_type', $userRoleTag->role_type) == 'admin_center' ? 'selected' : '' }}>
                                Admin Center
                            </option>

                            <option value="bd" {{ old('role_type', $userRoleTag->role_type) == 'bd' ? 'selected' : '' }}>
                                BD
                            </option>

                            <option value="agency"
                                {{ old('role_type', $userRoleTag->role_type) == 'agency' ? 'selected' : '' }}>
                                Agency
                            </option>

                            <option value="host"
                                {{ old('role_type', $userRoleTag->role_type) == 'host' ? 'selected' : '' }}>
                                Host
                            </option>

                            <option value="coinseller"
                                {{ old('role_type', $userRoleTag->role_type) == 'coinseller' ? 'selected' : '' }}>
                                Coin Seller
                            </option>

                            <option value="merchant"
                                {{ old('role_type', $userRoleTag->role_type) == 'merchant' ? 'selected' : '' }}>
                                Merchant
                            </option>

                        </select>

                        @error('role_type')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>


                    {{-- File Type --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            File Type
                            <span class="text-danger">*</span>
                        </label>

                        <select name="file_type" class="form-select @error('file_type') is-invalid @enderror">

                            <option value="image"
                                {{ old('file_type', $userRoleTag->file_type) == 'image' ? 'selected' : '' }}>
                                Image
                            </option>

                            <option value="gif"
                                {{ old('file_type', $userRoleTag->file_type) == 'gif' ? 'selected' : '' }}>
                                GIF
                            </option>

                            <option value="svga"
                                {{ old('file_type', $userRoleTag->file_type) == 'svga' ? 'selected' : '' }}>
                                SVGA
                            </option>

                        </select>

                        @error('file_type')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>


                    {{-- File Upload --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            File
                        </label>

                        <input type="file" name="file" id="file" class="d-none" accept="image/*,.gif,.svga">

                        <label for="file"
                            class="border rounded d-flex align-items-center justify-content-center position-relative"
                            style="width:180px;height:130px;cursor:pointer;overflow:hidden;">

                            <img id="previewFile"
                                src="{{ $userRoleTag->file ? Helper::showImage($userRoleTag->file) : '' }}"
                                class="position-absolute w-100 h-100 {{ $userRoleTag->file ? '' : 'd-none' }}"
                                style="object-fit:cover;">

                            <span id="plusFile" class="fs-1 {{ $userRoleTag->file ? 'd-none' : '' }}">
                                +
                            </span>

                        </label>

                        @error('file')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>


                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Status
                        </label>

                        <select name="status" class="form-select">

                            <option value="1" {{ $userRoleTag->status == 1 ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="0" {{ $userRoleTag->status == 0 ? 'selected' : '' }}>
                                Inactive
                            </option>

                        </select>
                    </div>


                    <div class="d-flex justify-content-end gap-2">

                        <a href="{{ route('user-role-tags') }}" class="btn btn-secondary">
                            Cancel
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Update
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>
@endsection


@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const input = document.getElementById('file');
            const preview = document.getElementById('previewFile');
            const plus = document.getElementById('plusFile');

            input.addEventListener('change', function() {

                const file = this.files[0];

                if (!file) {
                    return;
                }

                if (file.type.startsWith('image')) {

                    const reader = new FileReader();

                    reader.onload = function(e) {

                        preview.src = e.target.result;

                        preview.classList.remove('d-none');

                        plus.classList.add('d-none');
                    };

                    reader.readAsDataURL(file);
                }
            });

        });
    </script>
@endsection
