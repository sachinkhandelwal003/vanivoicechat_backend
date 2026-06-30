@extends('layouts.app')

@section('content')
<div class="card mb-3">

    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Level Setting</h5>
            <a href="{{ route('levels') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card-body">

        {{-- ALERTS --}}
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('level-setting.add') }}" method="POST">
            @csrf

            <div class="row g-4">

                <!-- WEALTH -->
                <div class="col-md-6">
                    <div class="p-3 rounded-3 border h-100"
                        style="background: #fff9e6; border: 1px solid #ffe082;">

                        <label class="form-label fw-semibold text-warning mb-2">
                            💰 Wealth Description
                        </label>

                        <textarea name="wealth_description"
                            class="form-control shadow-none"
                            rows="6"
                            placeholder="Enter wealth description...">{{ trim(old('wealth_description', $wealth->description ?? '')) }}</textarea>
                    </div>
                </div>

                <!-- CHARM -->
                <div class="col-md-6">
                    <div class="p-3 rounded-3 border h-100"
                        style="background: #fff0f3; border: 1px solid #ffccd5;">

                        <label class="form-label fw-semibold text-danger mb-2">
                            ❤️ Charm Description
                        </label>

                        <textarea name="charm_description"
                            class="form-control shadow-none"
                            rows="6"
                            placeholder="Enter charm description...">{{ trim(old('charm_description', $charm->description ?? '')) }}</textarea>
                    </div>
                </div>

            </div>

            <!-- BUTTON -->
            <div class="mt-4 text-end">
                <button class="btn btn-primary px-4 shadow-sm">
                    <i class="fa fa-save me-1"></i> Save Settings
                </button>
            </div>

        </form>

    </div>
</div>
@endsection