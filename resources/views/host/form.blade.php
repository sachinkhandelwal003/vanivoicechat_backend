@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    {{ isset($host) ? 'Edit' : 'Add' }} Host
                </h5>
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('host') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">

        <form action="{{ isset($host) ? route('host.save', $host->id) : route('host.save') }}"
            method="POST">
            @csrf

            <div class="row g-4">

                <!-- USER UID -->
                <div class="col-md-6">
                    <label class="form-label">User UID *</label>
                    <input type="text" name="user_uid" class="form-control"
                        placeholder="Enter User UID"
                        value="{{ old('user_uid', $host->user->uid ?? '') }}">
                </div>

                <!-- AGENCY UID -->
                <div class="col-md-6">
                    <label class="form-label">Agency UID </label>
                    <input type="text" name="agency_uid" class="form-control"
                        placeholder="Enter Agency UID"
                        value="{{ old('agency_uid', $host->agency->user->uid ?? '') }}">
                </div>

                <!-- COUNTRY -->
                <div class="col-md-6">
                    <label class="form-label">Country *</label>
                    <select name="country_id" class="form-control">
                        <option value="">-- Select Country --</option>
                        @foreach($countries as $country)
                        <option value="{{ $country->id }}"
                            {{ old('country_id', $host->country_id ?? '') == $country->id ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- STATUS -->
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="1" {{ old('status', $host->status ?? '') == 1 ? 'selected' : '' }}>
                            Active
                        </option>
                        <option value="0" {{ old('status', $host->status ?? '') == 0 ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                </div>

            </div>

            <div class="mt-4 text-end">
                <button class="btn btn-primary">
                    {{ isset($host) ? 'Update' : 'Save' }}
                </button>
            </div>

        </form>

    </div>
</div>
@endsection