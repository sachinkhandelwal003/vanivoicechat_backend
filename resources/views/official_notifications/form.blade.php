@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <div class="row flex-between-end">
                <div class="col-auto align-self-center">
                    <h5>
                        {{ isset($notification) ? 'Edit Official Notification' : 'Add Official Notification' }}
                    </h5>
                </div>

                <div class="col-auto ms-auto">
                    <a href="javascript:void(0);"
                        onclick="window.history.back();"
                        class="btn btn-outline-secondary rounded-pill px-3">
                        <i class="fa fa-arrow-left me-1"></i>
                        Back
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form method="POST" enctype="multipart/form-data"
                action="{{ isset($notification) 
                                ? route('official_notifications.update', $notification->id) 
                                : route('official_notifications.store') }}">

                @csrf

                @if(isset($notification))
                @method('PUT')
                @endif

                <div class="row">

                    <!-- User -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            User Unique ID(s)
                        </label>

                        @php
                        $existingUid = '';
                        if(isset($notification) && $notification->user_id){
                        $existingUid = \App\Models\AppUser::where('id', $notification->user_id)->value('uid');
                        }
                        @endphp

                        <input type="text"
                            name="user_ids"
                            value="{{ old('user_ids', $existingUid) }}"
                            class="form-control @error('user_ids') is-invalid @enderror"
                            placeholder="Enter user UID (comma separated if multiple)">

                        <small class="text-muted">
                            Example: 10000008,10000009
                        </small>

                        @error('user_ids')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    <!-- Country -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">
                            Country
                        </label>

                        <div class="position-relative">

                            <select name="country"
                                class="form-control pe-5 @error('country') is-invalid @enderror"
                                style="appearance:none;">

                                <option value="">Select Country</option>

                                @foreach($countries as $country)
                                <option value="{{ $country->id }}"
                                    {{ old('country', $notification->country ?? '') == $country->nicename ? 'selected' : '' }}>
                                    {{ $country->name }}
                                </option>
                                @endforeach

                            </select>

                            <span class="position-absolute top-50 end-0 translate-middle-y me-3 text-muted">
                                <i class="fa-solid fa-chevron-down"></i>
                            </span>

                        </div>

                        @error('country')
                        <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                </div>



        </div>

        <div class="row m-2">

            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Redirect URL</label>

                <input type="text"
                    name="url"
                    value="{{ old('url', $notification->url ?? '') }}"
                    class="form-control @error('url') is-invalid @enderror"
                    placeholder="https://example.com">

                @error('url')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Notification Image</label>

                <input type="file"
                    name="image"
                    id="imageInput"
                    class="form-control @error('image') is-invalid @enderror"
                    accept="image/*">

                @error('image')
                <small class="text-danger">{{ $message }}</small>
                @enderror

                <!-- Image Preview -->
                <div class="mt-3">
                    <img id="imagePreview"
                        src="{{ isset($notification) && $notification->image ? asset('storage/'.$notification->image) : '' }}"
                        style="max-height:120px; {{ isset($notification->image) ? '' : 'display:none;' }}"
                        class="img-thumbnail">
                </div>
            </div>

            <!-- Notification -->
            <div class="mb-3 m-2">
                <label class="form-label fw-bold">
                    Notification Message <span class="text-danger">*</span>
                </label>

                <textarea name="notification"
                    rows="4"
                    class="form-control @error('notification') is-invalid @enderror"
                    placeholder="Enter notification message">{{ old('notification', $notification->notification ?? '') }}</textarea>

                @error('notification')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="d-flex justify-content-end gap-2 mt-3">
                <a href="{{ route('official_notifications.index') }}"
                    class="btn btn-secondary">
                    Cancel
                </a>

                <button type="submit"
                    class="btn btn-primary">
                    {{ isset($notification) ? 'Update' : 'Save' }}
                </button>
            </div>

            </form>

        </div>
    </div>
</div>
<script>
    document.getElementById('imageInput').addEventListener('change', function(e) {

        let reader = new FileReader();

        reader.onload = function(e) {
            let preview = document.getElementById('imagePreview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        }

        reader.readAsDataURL(this.files[0]);

    });
</script>
@endsection