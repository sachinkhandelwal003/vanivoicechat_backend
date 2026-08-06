@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="card">

            <div class="card-header fw-bold">
                Add User Role Tag
            </div>

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <form action="{{ route('user-role-tags.add') }}" method="POST" enctype="multipart/form-data">

                    @csrf

                    {{-- Tag Name --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Tag Name
                            <span class="text-danger">*</span>
                        </label>

                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}" placeholder="Enter Tag Name">

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

                            <option value="">
                                Select Role Type
                            </option>

                            <option value="admin_center">
                                Admin Center
                            </option>

                            <option value="bd">
                                BD
                            </option>

                            <option value="agency">
                                Agency
                            </option>

                            <option value="host">
                                Host
                            </option>

                            <option value="coinseller">
                                Coin Seller
                            </option>

                            <option value="merchant">
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

                            <option value="">
                                Select File Type
                            </option>

                            <option value="image">
                                Image
                            </option>

                            <option value="gif">
                                GIF
                            </option>

                            <option value="svga">
                                SVGA
                            </option>

                        </select>

                        @error('file_type')
                            <small class="text-danger">
                                {{ $message }}
                            </small>
                        @enderror
                    </div>


                    {{-- Upload File --}}
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            Upload File
                            <span class="text-danger">*</span>
                        </label>

                        <div>
                            <input type="file" name="file" id="file" class="d-none" accept="image/*,.gif,.svga">

                            <label for="file"
                                class="border rounded d-flex align-items-center justify-content-center position-relative"
                                style="width:180px;height:130px;cursor:pointer;overflow:hidden;">

                                <img id="previewFile" class="position-absolute w-100 h-100 d-none"
                                    style="object-fit:cover;">

                                <span id="plusFile" class="fs-1">
                                    +
                                </span>

                            </label>

                            <small class="d-block mt-1 text-muted">
                                Upload Image / GIF / SVGA
                            </small>

                            @error('file')
                                <small class="text-danger">
                                    {{ $message }}
                                </small>
                            @enderror
                        </div>
                    </div>


                    {{-- Status --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Status
                            <span class="text-danger">*</span>
                        </label>

                        <select name="status" class="form-select">

                            <option value="1">
                                Active
                            </option>

                            <option value="0">
                                Inactive
                            </option>

                        </select>
                    </div>


                    <div class="d-flex justify-content-end gap-2">

                        <a href="{{ route('user-role-tags') }}" class="btn btn-secondary">
                            Cancel
                        </a>

                        <button type="submit" class="btn btn-primary">
                            Save
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
