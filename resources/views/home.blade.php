@extends('layouts.app')

@section('content')

<div class="container-fluid mt-4">

    {{-- ------- WIDGETS ------- --}}
    <div class="row">

        <!-- Total Users -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0" style="border-left:5px solid #5d3eb1cf;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-secondary">Total Users</h6>
                        <h3 class="fw-bold">{{$user}}</h3>
                    </div>
                    <div class="rounded-circle p-3" style="background:#5d3eb1cf;">
                        <i class="fa-solid fa-users text-white fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pharmacy Categories -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0" style="border-left:5px solid #5d3eb1cf;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-secondary">Total Rooms</h6>
                        <h3 class="fw-bold">{{ $room }}</h3>
                    </div>
                    <div class="rounded-circle p-3" style="background:#5d3eb1cf;">
                        <i class="fa-solid fa-dollar-sign text-white fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Products -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0" style="border-left:5px solid #5d3eb1cf;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-secondary">Total Family</h6>
                        <h3 class="fw-bold">{{ $family }}</h3>
                    </div>
                    <div class="rounded-circle p-3" style="background:#5d3eb1cf;">
                        <i class="fa-solid fa-sack-dollar text-white fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today Appointments -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0" style="border-left:5px solid #5d3eb1cf;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-secondary">Today's Appointments</h6>
                        <h3 class="fw-bold">0</h3>
                    </div>
                    <div class="rounded-circle p-3" style="background:#5d3eb1cf;">
                        <i class="fa-solid fa-calendar-day text-white fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tomorrow Appointments -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0" style="border-left:5px solid #5d3eb1cf;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-secondary">Tomorrow Appointments</h6>
                        <h3 class="fw-bold">0</h3>
                    </div>
                    <div class="rounded-circle p-3" style="background:#5d3eb1cf;">
                        <i class="fa-solid fa-calendar-check text-white fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0" style="border-left:5px solid #5d3eb1cf;">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-secondary">Upcoming Appointments</h6>
                        <h3 class="fw-bold">6</h3>
                    </div>
                    <div class="rounded-circle p-3" style="background:#5d3eb1cf;">
                        <i class="fa-solid fa-calendar text-white fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>



    {{-- ------- GRAPHS SECTION ------- --}}
    <div class="row mt-4">

        <!-- Bar Chart -->
        <!-- <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header fw-bold" style="background:#5d3eb1cf; color:#fff;">
                    Monthly Users Growth
                </div>
                <div class="card-body">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div> -->

        <!-- Line Chart -->
        <!-- <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header fw-bold" style="background:#5d3eb1cf; color:#fff;">
                    Appointment Trend
                </div>
                <div class="card-body">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div> -->

    </div>

</div>


<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // ----- BAR CHART -----
    const barChart = new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Users',
                data: [120, 150, 180, 220, 260, 300],
                backgroundColor: '#5d3eb1cf'
            }]
        }
    });

    // ----- LINE CHART -----
    const lineChart = new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Appointments',
                data: [5, 7, 3, 9, 6, 4, 8],
                borderColor: '#5d3eb1cf',
                borderWidth: 3,
                fill: false,
                tension: 0.3
            }]
        }
    });
</script>



@endsection
