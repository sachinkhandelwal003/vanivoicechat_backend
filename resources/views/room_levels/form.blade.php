@extends('layouts.app')

@section('content')

    <div class="card mb-3">

        <div class="card-header">
            <div class="row flex-between-end">

                <div class="col-auto align-self-center">
                    <h5 class="mb-0">
                        {{ isset($roomLevel) ? 'Edit' : 'Add' }} Room Level
                    </h5>
                </div>

                <div class="col-auto ms-auto">
                    <a href="{{ route('room-levels') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </a>
                </div>

            </div>
        </div>

        <div class="card-body">

            {{-- Success --}}
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Error --}}
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Validation --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('room-levels.save', $roomLevel->id ?? null) }}" method="POST">

                @csrf

                <div class="row">

                    {{-- Level --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Level <span class="text-danger">*</span>
                        </label>

                        <input type="number" name="level" class="form-control" placeholder="Enter Level"
                            value="{{ old('level', $roomLevel->level ?? '') }}">
                    </div>

                    {{-- XP --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            XP <span class="text-danger">*</span>
                        </label>

                        <input type="number" name="xp" class="form-control" placeholder="Enter XP"
                            value="{{ old('xp', $roomLevel->xp ?? '') }}">
                    </div>

                    {{-- Admins --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Admins <span class="text-danger">*</span>
                        </label>

                        <input type="number" name="admins" class="form-control" placeholder="Enter Admin Limit"
                            value="{{ old('admins', $roomLevel->admins ?? '') }}">
                    </div>

                    {{-- Members --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Members <span class="text-danger">*</span>
                        </label>

                        <input type="number" name="members" class="form-control" placeholder="Enter Member Limit"
                            value="{{ old('members', $roomLevel->members ?? '') }}">
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Status
                        </label>

                        <select name="status" class="form-control">

                            <option value="1" {{ old('status', $roomLevel->status ?? 1) == 1 ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="0" {{ old('status', $roomLevel->status ?? 1) == 0 ? 'selected' : '' }}>
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>

                <div class="text-end">

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save me-1"></i>
                        {{ isset($roomLevel) ? 'Update' : 'Save' }}
                    </button>

                </div>

            </form>

        </div>

    </div>

@endsection
