@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">
            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    {{ isset($policy) ? 'Edit' : 'Add' }} Host Policy
                </h5>
            </div>

            <div class="col-auto ms-auto">
                <a href="{{ route('host-policy') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">

        <form action="{{ isset($policy) ? route('host-policy.save', $policy->id) : route('host-policy.save') }}"
            method="POST">

            @csrf

            <div class="row g-4">

                <!-- LEVEL -->
                <div class="col-md-6">
                    <label class="form-label">Level *</label>
                    <input type="number"
                        name="level"
                        min="1"
                        class="form-control"
                        placeholder="Enter Level"
                        value="{{ old('level', $policy->level ?? '') }}">
                    @error('level')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- TIME HOURS -->
                <!-- <div class="col-md-6">
                    <label class="form-label">Time (Hours) *</label>
                    <input type="number"
                        step="0.01"
                        name="time_hours"
                        class="form-control"
                        placeholder="Enter Hours"
                        value="{{ old('time_hours', $policy->time_hours ?? 0) }}">
                    @error('time_hours')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div> -->

                <!-- TARGET (GIFT VALUE) -->
                <div class="col-md-6">
                    <label class="form-label">Target (Gift Value) *</label>
                    <input type="number"
                        name="target_value"
                        class="form-control"
                        placeholder="Enter Target (Gift Value)"
                        value="{{ old('target_value', $policy->target_value ?? '') }}">
                    @error('target_gift_value')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- HOST SALARY -->
                <div class="col-md-6">
                    <label class="form-label">Host Salary *</label>
                    <input type="number"
                        step="0.01"
                        name="host_salary"
                        class="form-control"
                        placeholder="Enter Host Salary"
                        value="{{ old('host_salary', $policy->host_salary ?? '') }}">
                    @error('host_salary')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- AGENT COMMISSION -->
                <div class="col-md-6">
                    <label class="form-label">Agent Commission *</label>
                    <input type="number"
                        step="0.01"
                        name="agent_commission"
                        class="form-control"
                        placeholder="Enter Agent Commission"
                        value="{{ old('agent_commission', $policy->agent_commission ?? '') }}">
                    @error('agent_commission')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- TOTAL SALARY -->
                <div class="col-md-6">
                    <label class="form-label">Total Salary *</label>
                    <input type="number"
                        step="0.01"
                        name="total_salary"
                        class="form-control"
                        placeholder="Enter Total Salary"
                        value="{{ old('total_salary', $policy->total_salary ?? '') }}">
                    @error('total_salary')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- COUNTRY -->
                <div class="col-md-6">
                    <label class="form-label">Country *</label>
                    <select name="country" id="" class="form-control">
                        <option value="">Select Country</option>
                        @foreach($countries as $country)
                        <option value="{{ $country->nicename }}"
                            {{ old('country', $policy->country ?? '') == $country->nicename ? 'selected' : '' }}>
                            {{ $country->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('country')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                    @enderror
                </div>

                <!-- STATUS -->
                <div class="col-md-6">
                    <label class="form-label">Status</label>

                    <select name="status" class="form-control">
                        <option value="1"
                            {{ old('status', $policy->status ?? 1) == 1 ? 'selected' : '' }}>
                            Active
                        </option>

                        <option value="0"
                            {{ old('status', $policy->status ?? 1) == 0 ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>
                </div>

            </div>

            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-primary">
                    {{ isset($policy) ? 'Update' : 'Save' }}
                </button>
            </div>

        </form>

    </div>

</div>
@endsection