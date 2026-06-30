@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header fw-bold">
            Edit Data Card
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('topic.edit', $topic->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-bold">Category</label>
                    <select name="category" id="category" class="form-control">
                        @foreach ($category as $data)
                        <option value="{{ $data->id }}" {{ $topic->category == $data->id ? 'selected' : '' }}>{{ $data->name }}</option>
                        @endforeach
                    </select>

                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold"> Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $topic->name) }}">
                    @error('name')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Description</label>
                    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror">{{ $topic->description }}</textarea>
                    @error('description')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>


                <div class="mb-4">
                    <label class="form-label fw-bold">
                        Picture <span class="text-danger">*</span>
                    </label>

                    <input type="file" name="icon" id="icon" class="d-none" accept="image/*">

                    <label for="icon"
                        class="border rounded d-flex align-items-center justify-content-center position-relative"
                        style="width:150px;height:120px;cursor:pointer;overflow:hidden;">

                        <img id="coverPreview"
                            src="{{ $topic->icon ? Helper::showImage($topic->icon, true) : '' }}"
                            class="position-absolute w-100 h-100 {{ $topic->icon ? '' : 'd-none' }}"
                            style="object-fit:cover;">

                        <span id="coverPlus" class="fs-1 {{ $topic->icon ? 'd-none' : '' }}">+</span>
                    </label>

                    @error('icon')
                    <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>


                <div class="mb-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ $topic->status == '1' ? 'selected' : '' }}>Enable</option>
                        <option value="0" {{ $topic->status == '0' ? 'selected' : '' }}>Disable</option>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('topic') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {

        const coverInput = document.getElementById('icon');
        const coverPreview = document.getElementById('coverPreview');
        const coverPlus = document.getElementById('coverPlus');

        coverInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                coverPreview.src = e.target.result;
                coverPreview.classList.remove('d-none');
                coverPlus.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });

    });
</script>

@endsection