@extends('layouts.app')

@section('content')

<div class="card mb-3">

    <div class="card-header">
        <div class="row flex-between-end">

            <div class="col-auto align-self-center">
                <h5 class="mb-0">
                    Minimum Available Coins Setting
                </h5>
            </div>

        </div>
    </div>

    <div class="card-body">

        {{-- Success --}}
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error --}}
        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        {{-- Validation --}}
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('system.setting.store') }}" method="POST">
            @csrf

            <div class="row">

                {{-- Minimum Available Coins --}}
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        Minimum Available Coins <span class="text-danger">*</span>
                    </label>

                    <input type="number"
                           name="setting_value"
                           class="form-control"
                           min="0"
                           placeholder="Enter minimum coins"
                           value="{{ old('setting_value', $setting->setting_value ?? 100000) }}">

                    <small class="text-muted">
                        Sellers having available coins below this value will be hidden from the Recharge Agency list.
                    </small>
                </div>

            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i>
                    Save Setting
                </button>
            </div>

        </form>

    </div>

</div>

@endsection