@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    {{ isset($privilege) ? 'Edit' : 'Add' }} Privilege
                </h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('svip-privilege.list') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
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

        <form
           action="{{ isset($privilege) ? route('svip-privilege.add', $privilege->id) : route('svip-privilege.add') }}"
            method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-4">

                <!-- NAME -->
                <div class="col-md-6">
                    <label class="form-label">Privilege Name</label>
                    <input type="text" name="name" class="form-control"
                        value="{{ old('name', $privilege->name ?? '') }}">
                </div>

                <!-- SORT ORDER -->
                <div class="col-md-3">
                    <label class="form-label">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control"
                        value="{{ old('sort_order', $privilege->sort_order ?? '') }}">
                </div>

                <!-- STATUS -->
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ old('status', $privilege->status ?? '') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $privilege->status ?? '') == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <!-- ICON -->
                <div class="col-md-4">
                    <div class="border rounded-3 p-3">
                        <label class="form-label">Icon</label>

                        <input type="file" name="icon" class="d-none image-input" id="icon">

                        <label for="icon"
                            class="upload-box w-100 d-flex align-items-center justify-content-center text-center"
                            style="height:140px; cursor:pointer; border:2px dashed #ccc;">

                            <img
                                src="{{ isset($privilege) && $privilege->icon 
                                    ? asset('storage/'.$privilege->icon) 
                                    : '' }}"
                                class="preview-image {{ isset($privilege) && $privilege->icon ? '' : 'd-none' }}"
                                style="width:100%; height:100%; object-fit:cover;">

                            <div class="upload-placeholder {{ isset($privilege) && $privilege->icon ? 'd-none' : '' }}">
                                <small>Click to upload</small>
                            </div>

                        </label>
                    </div>
                </div>

            </div>

            <div class="mt-4 text-end">
                <button class="btn btn-primary">
                    {{ isset($privilege) ? 'Update' : 'Save' }}
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