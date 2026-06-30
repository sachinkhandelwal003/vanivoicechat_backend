@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <!-- Profile Header -->
    <div class="card mb-3">
        <div class="card-body d-flex align-items-center">
            <img src="{{ 
                $user->image 
                ? (filter_var($user->image, FILTER_VALIDATE_URL) 
                    ? $user->image 
                    : asset('storage/'.$user->image)) 
                : asset('storage/default.png') 
            }}" class="rounded-circle me-3" width="80">

            <div>
                <h4 class="mb-0">{{ $user->name ?? 'N/A' }}</h4>
                <small class="text-muted">{{ $user->email ?? 'No Email' }}</small>
                <div class="mt-1">
                    <span class="badge bg-{{ $user->status == 1 ? 'success' : 'danger' }}">
                        {{ $user->status == 1 ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="badge bg-info">
                        {{ $user->role ?? 'User' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#basic">Basic Info</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#account">Account</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#device">Device</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#system">System</a>
                </li>
            </ul>
        </div>

        <div class="card-body tab-content">

            <!-- Basic Info -->
            <div class="tab-pane fade show active" id="basic">
                <div class="row">
                    <div class="col-md-6 mb-2"><strong>UID:</strong> {{ $user->uid }}</div>
                    <div class="col-md-6 mb-2"><strong>Phone:</strong> {{ $user->phone ?? 'N/A' }}</div>
                    <div class="col-md-6 mb-2"><strong>Gender:</strong> {{ ucfirst($user->gender ?? 'N/A') }}</div>
                    <div class="col-md-6 mb-2"><strong>Birthdate:</strong> {{ $user->birthdate ?? 'N/A' }}</div>
                    <div class="col-md-6 mb-2"><strong>Country:</strong> {{ $user->country ?? 'N/A' }}</div>
                </div>
            </div>

            <!-- Account -->
            <div class="tab-pane fade" id="account">
                <div class="row">
                    <div class="col-md-6 mb-2"><strong>Email Verified:</strong>
                        <span class="badge bg-{{ $user->is_email_bind ? 'success' : 'warning' }}">
                            {{ $user->is_email_bind ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div class="col-md-6 mb-2"><strong>Google Login:</strong>
                        {{ $user->google_id ? 'Yes' : 'No' }}
                    </div>
                    <div class="col-md-6 mb-2"><strong>Premium Number:</strong>
                        {{ $user->premium_number ?? 'N/A' }}
                    </div>
                    <div class="col-md-6 mb-2"><strong>Profile Visitors:</strong>
                        {{ $user->profile_visitors ?? 0 }}
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="device">
                <div class="row">
                    <div class="col-md-6 mb-2"><strong>Device Model:</strong> {{ $user->equipment_model ?? 'N/A' }}</div>
                    <div class="col-md-6 mb-2"><strong>Brand:</strong> {{ $user->brand ?? 'N/A' }}</div>
                    <div class="col-md-6 mb-2"><strong>OS:</strong> {{ $user->operating_system ?? 'N/A' }}</div>
                    <div class="col-md-6 mb-2"><strong>IMEI:</strong> {{ $user->imei ?? 'N/A' }}</div>
                </div>
            </div>

            <div class="tab-pane fade" id="system">
                <div class="row">
                    <div class="col-md-6 mb-2"><strong>App Version:</strong> {{ $user->app_version ?? 'N/A' }}</div>
                    <div class="col-md-6 mb-2"><strong>Timezone:</strong> {{ $user->timezone ?? 'N/A' }}</div>
                    <div class="col-md-6 mb-2"><strong>Registered IP:</strong> {{ $user->registered_ip ?? 'N/A' }}</div>
                    <div class="col-md-6 mb-2"><strong>Registered At:</strong> {{ $user->registration_time ?? $user->created_at }}</div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('js')
<script>
</script>
@endsection