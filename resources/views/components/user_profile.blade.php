<div class="user-profile-sidebar">

    {{-- Profile Header --}}
    <div class="profile-header mt-2">

        <div class="profile-cover"></div>

        <div class="profile-user text-center">

            <img src="{{ $user->image ? \App\Helper\Helper::showImage($user->image,true) : asset('assets/img/avatar.png') }}"
                class="profile-avatar">

            <h4 class="mt-3 mb-1 fw-bold">
                {{ $user->name }}
            </h4>

            <div class="text-muted mb-2">
                UID : {{ $user->uid }}
            </div>

            <div class="d-flex justify-content-center gap-2 flex-wrap">

                @if($user->premium)
                <span class="badge bg-warning text-dark px-3 py-2">
                    👑 VIP
                </span>
                @endif

                @if($user->host)
                <span class="badge bg-danger px-3 py-2">
                    🎙 HOST
                </span>
                @endif

                @if($user->agency)
                <span class="badge bg-primary px-3 py-2">
                    🏢 AGENCY
                </span>
                @endif

            </div>

        </div>

    </div>

    {{-- Quick Stats --}}
    <div class="row g-2 mt-3 mb-4">

        <div class="col-6">
            <div class="info-card">
                <small>Country</small>
                <strong>{{ $user->country ?? '-' }}</strong>
            </div>
        </div>

        <div class="col-6">
            <div class="info-card">
                <small>Gender</small>
                <strong>{{ ucfirst($user->gender ?? '-') }}</strong>
            </div>
        </div>

        <div class="col-12">
            <div class="info-card">
                <small>Coins</small>
                <strong>{{ number_format($user->total_points ?? 0) }}</strong>
            </div>
        </div>

    </div>

    {{-- Account Information --}}
    <div class="section-title mt-4">
        📋 Account Information
    </div>

    <div class="account-card">

        <table class="table table-borderless mb-0">

            <tr>
                <td>Name</td>
                <td class="text-end fw-bold">{{ $user->name }}</td>
            </tr>

            <tr>
                <td>UID</td>
                <td class="text-end fw-bold">{{ $user->uid }}</td>
            </tr>

            <tr>
                <td>Phone</td>
                <td class="text-end fw-bold">{{ $user->mobile_number ?? '-' }}</td>
            </tr>

            <tr>
                <td>Email</td>
                <td class="text-end fw-bold">{{ $user->email ?? '-' }}</td>
            </tr>

            <tr>
                <td>Level</td>
                <td class="text-end fw-bold">{{ $user->user_level ?? '-' }}</td>
            </tr>

            <tr>
                <td>Status</td>
                <td class="text-end">

                    @if($user->status)
                    <span class="badge bg-success">
                        Active
                    </span>
                    @else
                    <span class="badge bg-danger">
                        Inactive
                    </span>
                    @endif

                </td>
            </tr>

            <tr>
                <td>Created</td>
                <td class="text-end fw-bold">
                    {{ \Carbon\Carbon::parse($user->created_at)->timezone('Asia/Kolkata')->format('d M Y h:i A') }}
                </td>
            </tr>

        </table>

    </div>

    {{-- User Collection --}}
    <div class="section-title mt-4">
        🎁 User Collection
    </div>

    <div class="asset-grid">

        @forelse($items as $item)

        <div class="asset-card">

            <img src="{{ $item['image'] }}"
                class="asset-image">

            <div class="asset-name">
                {{ $item['type'] }}
            </div>

        </div>

        @empty

        <div class="text-center text-muted py-4">
            No Active Items Found
        </div>

        @endforelse

    </div>

</div>

<style>
    .user-profile-sidebar {
        padding: 20px;
        background: #f8fafc;
        min-height: 100vh;
    }

    .profile-header {
        margin: -20px -20px 20px;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 5px solid #fff;
        box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
    }

    .info-card {
        background: #fff;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
    }

    .info-card small {
        display: block;
        color: #888;
        margin-bottom: 4px;
    }

    .section-title {
        font-size: 15px;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .asset-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
    }

    .asset-card {
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
        transition: .25s;
    }

    .asset-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, .15);
    }

    .asset-image {
        width: 100%;
        height: 85px;
        object-fit: cover;
    }

    .asset-name {
        padding: 8px;
        text-align: center;
        font-size: 11px;
        font-weight: 600;
    }

    .account-card {
        background: #fff;
        border-radius: 14px;
        padding: 10px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    }

    .account-card table tr td {
        padding: 10px 5px;
        font-size: 13px;
    }
</style>