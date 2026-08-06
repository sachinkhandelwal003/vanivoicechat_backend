@extends('layouts.app')

@section('content')
    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">
                        {{ isset($emoji) ? 'Edit' : 'Add' }} Room Emoji
                    </h5>
                </div>

                <div class="col-auto ms-auto">
                    <a href="{{ route('room-emojis') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </a>
                </div>

            </div>
        </div>

        <div class="card-body">

            {{-- SUCCESS --}}
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            {{-- ERROR --}}
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{-- VALIDATION --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('room-emojis.save', $emoji->id ?? null) }}" method="POST" enctype="multipart/form-data">

                @csrf

                <div class="row">

                    {{-- Title --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Title <span class="text-danger">*</span>
                        </label>

                        <input type="text" name="title" class="form-control" placeholder="Enter Emoji Title"
                            value="{{ old('title', $emoji->title ?? '') }}">
                    </div>

                    {{-- Type --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Type</label>

                        <input type="text" class="form-control" value="normal" readonly>

                        <input type="hidden" name="type" value="normal">
                    </div>

                    {{-- File --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Emoji File
                            @if (!isset($emoji))
                                <span class="text-danger">*</span>
                            @endif
                        </label>

                        <input type="file" name="file" class="form-control" accept=".png,.jpg,.jpeg,.gif,.webp,.svga">
                    </div>

                    {{-- Preview --}}
                    @if (isset($emoji) && $emoji->file)
                        <div class="col-md-6 mb-3">

                            <label class="form-label d-block">
                                Current File
                            </label>

                            @php
                                $extension = strtolower(pathinfo($emoji->file, PATHINFO_EXTENSION));
                            @endphp

                            @if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp']))
                                <img src="{{ Helper::showImage($emoji->file, true) }}" class="img-thumbnail"
                                    style="height:80px;">
                            @else
                                <a href="{{ Helper::showImage($emoji->file, true) }}" target="_blank"
                                    class="btn btn-outline-primary">
                                    View File
                                </a>
                            @endif

                        </div>
                    @endif

                    {{-- Status --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Status
                        </label>

                        <select name="status" class="form-control">

                            <option value="1" {{ old('status', $emoji->status ?? 1) == 1 ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="0" {{ old('status', $emoji->status ?? 1) == 0 ? 'selected' : '' }}>
                                Inactive
                            </option>

                        </select>
                    </div>

                </div>

                <div class="text-end">

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i>
                        {{ isset($emoji) ? 'Update' : 'Save' }}
                    </button>

                </div>

            </form>

        </div>

    </div>
@endsection
