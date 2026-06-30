@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    {{ isset($admin) ? 'Edit' : 'Add' }} Admin
                </h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.account') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">

        {{-- SUCCESS --}}
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- ERROR --}}
        @if(session('error'))
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

        <form
            action="{{ isset($admin) ? route('admin.account.save', $admin->id) : route('admin.account.save') }}"
            method="POST">
            @csrf

            <div class="row g-4">

                <!-- USER -->
                <div class="col-md-6">
                    <label class="form-label">Enter User UID *</label>
                    <input type="text" name="user_uid" class="form-control"
                        placeholder="Enter UID (e.g. 1000001)" value="{{ old('user_uid', $admin->user->uid ?? '') }}">
                </div>

                <!-- COUNTRY -->
                <div class="col-md-6">
                    <label class="form-label">Country *</label>

                    <div style="position: relative;">
                        <i class="fa-solid fa-globe text-muted" style="position:absolute; left:12px; top:50%; transform:translateY(-50%);"></i>

                        <select name="country_id" class="form-control" style="padding-left:35px;">
                            <option value="">-- Select Country --</option>
                            @foreach($countries as $country)
                            <option value="{{ $country->id }}"
                                {{ old('country_id', $admin->country_id ?? '') == $country->id ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- WHATSAPP -->
                <div class="col-md-6">
                    <label class="form-label">WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" class="form-control"
                        placeholder="Enter WhatsApp number"
                        value="{{ old('whatsapp_number', $admin->whatsapp_number ?? '') }}">
                </div>

                <!-- STATUS -->
                <div class="col-md-6">
                    <label class="form-label">Status *</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ old('status', $admin->status ?? 1) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('status', $admin->status ?? 1) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

            </div>

            <div class="mt-4 text-end">
                <button class="btn btn-primary">
                    {{ isset($admin) ? 'Update' : 'Save' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection