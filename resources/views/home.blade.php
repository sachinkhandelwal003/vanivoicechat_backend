@extends('layouts.app')

@section('content')
<style>
    .dashboard-container {
        padding: 24px;
        background-color: #f8f9fa;
        min-height: 100vh;
    }

    .dash-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid rgba(0, 0, 0, 0.04);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
        transition: all 0.25s ease-in-out;
    }

    .dash-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
    }

    .icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .bg-icon-purple { background-color: #f0ebfc; color: #6f42c1; }
    .bg-icon-orange { background-color: #fff3e6; color: #fd7e14; }
    .bg-icon-green  { background-color: #e6f7ed; color: #198754; }
    .bg-icon-blue   { background-color: #e6f0ff; color: #0d6efd; }
    .bg-icon-teal   { background-color: #e6f8f7; color: #20c997; }
    .bg-icon-pink   { background-color: #fce8f3; color: #d63384; }
    .bg-icon-cyan   { background-color: #e0f8ff; color: #0dcaf0; }
    .bg-icon-yellow { background-color: #fff9e6; color: #ffc107; }

    .badge-live {
        background-color: #e8f7ee;
        color: #198754;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 12px;
        letter-spacing: 0.5px;
    }

    .stat-title {
        color: #6c757d;
        font-size: 0.825rem;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .stat-value {
        color: #1a1d20;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -0.5px;
        line-height: 1.2;
    }

    .stat-sub {
        color: #959ead;
        font-size: 0.775rem;
        font-weight: 500;
        margin-top: 4px;
    }

    .country-progress-bar {
        height: 6px;
        border-radius: 10px;
        background-color: #f1f3f5;
        overflow: hidden;
    }

    .country-progress-fill {
        height: 100%;
        border-radius: 10px;
    }

    .activity-box {
        background: #fdfdfd;
        border: 1px solid #f1f3f5;
        border-radius: 12px;
        padding: 12px;
        transition: background 0.2s ease;
    }

    .activity-box:hover {
        background: #f8f9fa;
    }

    .badge-trend-up {
        background-color: #e6f7ed;
        color: #198754;
        font-size: 0.725rem;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 6px;
    }

    .badge-health {
        background-color: #e6f7ed;
        color: #198754;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .health-status-card {
        background-color: #f8f9fa;
        border-radius: 12px;
        padding: 14px;
        border: 1px solid #e9ecef;
    }
</style>

<div class="dashboard-container">

    {{-- Top Header Section --}}
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <div class="text-uppercase fw-bold text-primary mb-1" style="font-size: 0.75rem; letter-spacing: 1px;">VANI CONTROL CENTRE</div>
            <h2 class="fw-bold text-dark mb-0">Dashboard</h2>
            <small class="text-muted">Live platform performance and operational health</small>
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white px-3 py-2 rounded-pill shadow-sm border d-flex align-items-center gap-2">
                <span class="spinner-grow spinner-grow-sm text-success" role="status" style="width: 8px; height: 8px;"></span>
                <span class="small fw-semibold text-muted">Live data</span>
                <span class="small text-muted" id="lastUpdatedTime">{{ $last_updated ?? now()->format('d-m-Y, h:i A') }}</span>
            </div>
            <button class="btn btn-white shadow-sm border rounded-pill px-3 py-2 font-weight-semibold text-dark" id="btnRefreshDashboard">
                <i class="fas fa-sync-alt me-1 text-primary" id="refreshIcon"></i> Refresh
            </button>
        </div>
    </div>

    {{-- Top 10 Stat Cards (2 Rows of 5 Columns) --}}
    <div class="row g-3 mb-4">

        {{-- Card 1: Total Target --}}
        {{-- <div class="col-12 col-sm-6 col-md-4 col-xl-2-4">
            <div class="dash-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-box bg-icon-purple">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <span class="badge-live">~ LIVE</span>
                </div>
                <div class="stat-title">Total Target</div>
                <div class="stat-value" id="valTotalTarget">{{ $total_target }}</div>
                <div class="stat-sub">Current live salary cycle</div>
            </div>
        </div> --}}

        {{-- Card 2: Total Recharge --}}
        {{-- <div class="col-12 col-sm-6 col-md-4 col-xl-2-4">
            <div class="dash-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-box bg-icon-orange">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <span class="badge-live">~ LIVE</span>
                </div>
                <div class="stat-title">Total Recharge</div>
                <div class="stat-value" id="valTotalRecharge">{{ $total_recharge }}</div>
                <div class="stat-sub" id="valTodayRecharge">{{ $today_recharge }}</div>
            </div>
        </div> --}}

        {{-- Card 3: Total Salary --}}
        {{-- <div class="col-12 col-sm-6 col-md-4 col-xl-2-4">
            <div class="dash-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-box bg-icon-green">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <span class="badge-live">~ LIVE</span>
                </div>
                <div class="stat-title">Total Salary</div>
                <div class="stat-value" id="valTotalSalary">{{ $total_salary }}</div>
                <div class="stat-sub" id="valSalarySubtitle">{{ $salary_subtitle }}</div>
            </div>
        </div> --}}

        {{-- Card 4: User Downloads / Total Users --}}
        <div class="col-12 col-sm-6 col-md-4 col-xl-2-4">
            <div class="dash-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-box bg-icon-blue">
                        <i class="fas fa-download"></i>
                    </div>
                    <span class="badge-live">~ LIVE</span>
                </div>
                <div class="stat-title">User Downloads</div>
                <div class="stat-value" id="valTotalUsers">{{ $total_users }}</div>
                <div class="stat-sub text-success fw-semibold" id="valTodayUsers">{{ $today_users }}</div>
            </div>
        </div>

        {{-- Card 5: Active Users --}}
        <div class="col-12 col-sm-6 col-md-4 col-xl-2-4">
            <div class="dash-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-box bg-icon-teal">
                        <i class="fas fa-users font-size-18"></i>
                    </div>
                    <span class="badge-live">~ LIVE</span>
                </div>
                <div class="stat-title">Active Users</div>
                <div class="stat-value" id="valActiveUsers">{{ $active_users }}</div>
                <div class="stat-sub" id="valOnlineUsers">{{ $online_users }}</div>
            </div>
        </div>

        {{-- Card 6: Total Hosts --}}
        <div class="col-12 col-sm-6 col-md-4 col-xl-2-4">
            <div class="dash-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-box bg-icon-pink">
                        <i class="fas fa-user-astronaut"></i>
                    </div>
                    <span class="badge-live">~ LIVE</span>
                </div>
                <div class="stat-title">Total Hosts</div>
                <div class="stat-value" id="valTotalHosts">{{ $total_hosts }}</div>
                <div class="stat-sub">Active host accounts</div>
            </div>
        </div>

        {{-- Card 7: Total Agencies --}}
        <div class="col-12 col-sm-6 col-md-4 col-xl-2-4">
            <div class="dash-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-box bg-icon-cyan">
                        <i class="fas fa-building"></i>
                    </div>
                    <span class="badge-live">~ LIVE</span>
                </div>
                <div class="stat-title">Total Agencies</div>
                <div class="stat-value" id="valTotalAgencies">{{ $total_agencies }}</div>
                <div class="stat-sub" id="valActiveBd">{{ $active_bd }}</div>
            </div>
        </div>

        {{-- Card 8: Coins Sellers --}}
        <div class="col-12 col-sm-6 col-md-4 col-xl-2-4">
            <div class="dash-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-box bg-icon-yellow">
                        <i class="fas fa-coins"></i>
                    </div>
                    <span class="badge-live">~ LIVE</span>
                </div>
                <div class="stat-title">Coins Sellers</div>
                <div class="stat-value" id="valCoinSellers">{{ $coin_sellers }}</div>
                <div class="stat-sub">Active seller accounts</div>
            </div>
        </div>

        {{-- Card 9: Merchants --}}
        <div class="col-12 col-sm-6 col-md-4 col-xl-2-4">
            <div class="dash-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-box bg-icon-purple">
                        <i class="fas fa-store"></i>
                    </div>
                    <span class="badge-live">~ LIVE</span>
                </div>
                <div class="stat-title">Merchants</div>
                <div class="stat-value" id="valMerchants">{{ $merchants }}</div>
                <div class="stat-sub">Active merchant accounts</div>
            </div>
        </div>

        {{-- Card 10: Recharge Value --}}
        <div class="col-12 col-sm-6 col-md-4 col-xl-2-4">
            <div class="dash-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="icon-box bg-icon-green">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <span class="badge-live">~ LIVE</span>
                </div>
                <div class="stat-title">Recharge Value</div>
                <div class="stat-value" id="valRechargeValue">{{ $recharge_value }}</div>
                <div class="stat-sub" id="valRechargeToday">{{ $recharge_today }}</div>
            </div>
        </div>

    </div>

    {{-- Mid Section: 7-day growth chart & Active users by country --}}
    <div class="row g-3 mb-4">

        {{-- Left: 7-Day Growth Chart --}}
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="dash-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">7-day growth</h5>
                        <small class="text-muted">New registrations and successful recharge coins</small>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #6f42c1;"></span>
                            <span class="small fw-semibold text-muted">New Registrations</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle" style="width: 10px; height: 10px; background-color: #0dcaf0;"></span>
                            <span class="small fw-semibold text-muted">Successful Recharge Coins (Cr)</span>
                        </div>
                        <span class="badge-live">LIVE</span>
                    </div>
                </div>
                <div style="position: relative; height: 280px;">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Right: Active Users by Country --}}
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="dash-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">Active users by country</h5>
                        <small class="text-muted">Top platform markets</small>
                    </div>
                    <button class="btn btn-sm btn-light border rounded-pill px-3 fw-semibold text-muted">View All</button>
                </div>

                <div class="d-flex flex-column gap-3 mt-3">
                    @foreach($country_list as $country)
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 0.85rem;">
                                <span class="fw-bold text-dark">{{ $country['name'] }}</span>
                                <span class="fw-semibold text-muted">{{ $country['count'] }} ({{ $country['percentage'] }}%)</span>
                            </div>
                            <div class="country-progress-bar">
                                <div class="country-progress-fill" style="width: {{ $country['percentage'] }}%; background-color: {{ $country['color'] }};"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    {{-- Bottom Section: Platform Activity (Today) & System Health --}}
    <div class="row g-3">

        {{-- Left: Platform Activity --}}
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="dash-card p-4 h-100">
                <h5 class="fw-bold text-dark mb-3">Platform Activity (Today)</h5>
                <div class="row g-2">
                    <div class="col">
                        <div class="activity-box text-center">
                            <div class="icon-box bg-icon-blue mx-auto mb-2" style="width:36px; height:36px; font-size:14px;">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="small text-muted fw-semibold">New Users</div>
                            <div class="fw-bold text-dark fs-5 my-1" id="actNewUsers">+128</div>
                            <span class="badge-trend-up">↑ 18%</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="activity-box text-center">
                            <div class="icon-box bg-icon-green mx-auto mb-2" style="width:36px; height:36px; font-size:14px;">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="small text-muted fw-semibold">New Recharge</div>
                            <div class="fw-bold text-dark fs-5 my-1" id="actNewRecharge">204.07Cr</div>
                            <span class="badge-trend-up">↑ 12%</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="activity-box text-center">
                            <div class="icon-box bg-icon-pink mx-auto mb-2" style="width:36px; height:36px; font-size:14px;">
                                <i class="fas fa-user-astronaut"></i>
                            </div>
                            <div class="small text-muted fw-semibold">New Hosts</div>
                            <div class="fw-bold text-dark fs-5 my-1" id="actNewHosts">+7</div>
                            <span class="badge-trend-up">↑ 7%</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="activity-box text-center">
                            <div class="icon-box bg-icon-cyan mx-auto mb-2" style="width:36px; height:36px; font-size:14px;">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="small text-muted fw-semibold">New Agencies</div>
                            <div class="fw-bold text-dark fs-5 my-1" id="actNewAgencies">+3</div>
                            <span class="badge-trend-up">↑ 15%</span>
                        </div>
                    </div>
                    <div class="col">
                        <div class="activity-box text-center">
                            <div class="icon-box bg-icon-purple mx-auto mb-2" style="width:36px; height:36px; font-size:14px;">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="small text-muted fw-semibold">New Merchants</div>
                            <div class="fw-bold text-dark fs-5 my-1" id="actNewMerchants">+1</div>
                            <span class="badge-trend-up">↑ 5%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: System Health --}}
        <div class="col-12 col-lg-5 col-xl-4">
            <div class="dash-card p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-0">System Health</h5>
                        <small class="text-muted">All systems are running smoothly</small>
                    </div>
                    <span class="badge-health">Healthy</span>
                </div>

                <div class="row g-2 mt-2">
                    <div class="col-6">
                        <div class="health-status-card d-flex align-items-center gap-2">
                            <i class="fas fa-check-circle text-success fs-5"></i>
                            <div>
                                <div class="small text-muted fw-semibold" style="font-size:0.75rem;">Server Status</div>
                                <div class="fw-bold text-dark" style="font-size:0.85rem;">Online</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="health-status-card d-flex align-items-center gap-2">
                            <i class="fas fa-check-circle text-success fs-5"></i>
                            <div>
                                <div class="small text-muted fw-semibold" style="font-size:0.75rem;">Database</div>
                                <div class="fw-bold text-dark" style="font-size:0.85rem;">Online</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="health-status-card d-flex align-items-center gap-2">
                            <i class="fas fa-check-circle text-success fs-5"></i>
                            <div>
                                <div class="small text-muted fw-semibold" style="font-size:0.75rem;">Payment Gateway</div>
                                <div class="fw-bold text-dark" style="font-size:0.85rem;">Online</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="health-status-card d-flex align-items-center gap-2">
                            <i class="fas fa-check-circle text-success fs-5"></i>
                            <div>
                                <div class="small text-muted fw-semibold" style="font-size:0.75rem;">Live Data Sync</div>
                                <div class="fw-bold text-dark" style="font-size:0.85rem;">Online</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

{{-- Custom CSS for 5-column layout on large screens --}}
<style>
    @media (min-width: 1200px) {
        .col-xl-2-4 {
            flex: 0 0 auto;
            width: 20%;
        }
    }
</style>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('growthChart').getContext('2d');

        const labels = @json($chart_labels);
        const regData = @json($reg_growth_data);
        const rechargeData = @json($recharge_growth_data);

        const growthChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'New Registrations',
                        data: regData,
                        backgroundColor: '#6f42c1',
                        borderRadius: 6,
                        barThickness: 14,
                    },
                    {
                        label: 'Successful Recharge Coins (Cr)',
                        data: rechargeData,
                        backgroundColor: '#38bdf8',
                        borderRadius: 6,
                        barThickness: 14,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1a1d20',
                        padding: 12,
                        titleFont: { size: 13, weight: 'bold' },
                        bodyFont: { size: 12 },
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: '#6c757d', font: { size: 11, weight: '600' } }
                    },
                    y: {
                        min: 0,
                        suggestedMax: 200,
                        grid: { color: '#f1f3f5', drawBorder: false },
                        ticks: { stepSize: 50, color: '#6c757d', font: { size: 11 } }
                    }
                }
            }
        });

        // AJAX Refresh Handler
        $('#btnRefreshDashboard').on('click', function () {
            const $icon = $('#refreshIcon');
            $icon.addClass('fa-spin');

            $.ajax({
                url: '{{ route("dashboard") }}',
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    $icon.removeClass('fa-spin');
                    if (res.status && res.data) {
                        $('#lastUpdatedTime').text(res.time);
                        $('#valTotalTarget').text(res.data.total_target);
                        $('#valTotalRecharge').text(res.data.total_recharge);
                        $('#valTodayRecharge').text(res.data.today_recharge);
                        $('#valTotalSalary').text(res.data.total_salary);
                        $('#valSalarySubtitle').text(res.data.salary_subtitle);
                        $('#valTotalUsers').text(res.data.total_users);
                        $('#valTodayUsers').text(res.data.today_users);
                        $('#valActiveUsers').text(res.data.active_users);
                        $('#valOnlineUsers').text(res.data.online_users);
                        $('#valTotalHosts').text(res.data.total_hosts);
                        $('#valTotalAgencies').text(res.data.total_agencies);
                        $('#valActiveBd').text(res.data.active_bd);
                        $('#valCoinSellers').text(res.data.coin_sellers);
                        $('#valMerchants').text(res.data.merchants);
                        $('#valRechargeValue').text(res.data.recharge_value);
                        $('#valRechargeToday').text(res.data.recharge_today);

                        toastr.success('Dashboard data refreshed!');
                    }
                },
                error: function () {
                    $icon.removeClass('fa-spin');
                    toastr.error('Failed to refresh dashboard data.');
                }
            });
        });
    });
</script>
@endsection
