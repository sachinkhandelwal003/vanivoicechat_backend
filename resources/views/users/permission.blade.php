@extends('layouts.app')

@section('css')
<style>
    .perm-card {
        transition: all 0.2s ease-in-out;
        border: 1px solid #e3e6ed;
    }
    .perm-card:hover {
        border-color: #615dfa;
        box-shadow: 0 4px 12px rgba(97, 93, 250, 0.08);
    }
    .section-select-card {
        border: 2px solid #edf2f9;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #ffffff;
    }
    .section-select-card.active {
        border-color: #615dfa;
        background: #f7f7ff;
    }
    .op-checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 6px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        cursor: pointer;
        user-select: none;
        transition: all 0.15s ease;
        font-size: 0.85rem;
        font-weight: 600;
    }
    .op-checkbox-label:hover {
        background: #eef2ff;
        border-color: #c7d2fe;
    }
    .op-checkbox-label.sub-act-label {
        background: #fff8e6;
        border-color: #ffe0b2;
    }
    .op-checkbox-label.sub-act-label:hover {
        background: #fff3cd;
        border-color: #ffe8a1;
    }
    .op-checkbox-label input[type="checkbox"] {
        width: 17px;
        height: 17px;
        accent-color: #615dfa;
        cursor: pointer;
    }
    .badge-access-high { background-color: #28a745; color: #fff; }
    .badge-access-medium { background-color: #fd7e14; color: #fff; }
    .badge-access-low { background-color: #dc3545; color: #fff; }
    .sticky-summary {
        position: sticky;
        top: 20px;
    }
</style>
@endsection

@section('content')
@php
    // Group permissions into logical categories
    $categories = [
        'User Management' => [
            'icon' => 'fas fa-users',
            'color' => 'primary',
            'modules' => [103, 104, 176, 107, 177, 138, 139, 140, 141, 142, 143, 111, 155, 125, 156, 157]
        ],
        'Finance Management' => [
            'icon' => 'fas fa-wallet',
            'color' => 'success',
            'modules' => [160, 161, 162, 163, 164, 165, 170, 171, 172, 146, 145, 144]
        ],
        'Room Management' => [
            'icon' => 'fas fa-door-open',
            'color' => 'info',
            'modules' => [129, 130, 131, 132, 133, 134, 135, 136, 137, 174]
        ],
        'Relationship & Content' => [
            'icon' => 'fas fa-handshake',
            'color' => 'warning',
            'modules' => [127, 128, 175, 166, 167, 152, 153, 150, 151, 173, 169, 168, 181]
        ],
        'Gifts, Props & Games' => [
            'icon' => 'fas fa-gift',
            'color' => 'danger',
            'modules' => [108, 109, 110, 112, 113, 114, 115, 116, 154, 118, 119, 120, 121, 122, 178, 123, 179, 124, 126, 180]
        ],
        'System Settings' => [
            'icon' => 'fas fa-cogs',
            'color' => 'dark',
            'modules' => [101, 102, 159, 158, 147, 105, 106, 148, 149]
        ],
    ];

    // Specific custom sub-actions for modules with extra functions
    $moduleCustomActions = [
        // App Users / User List (module_id 104)
        104 => [
            ['key' => 'view_details',   'label' => 'User Details',       'icon' => 'fas fa-eye text-info'],
            ['key' => 'edit_wealth',    'label' => 'Edit Wealth Level',  'icon' => 'fas fa-gem text-warning'],
            ['key' => 'edit_charm',     'label' => 'Edit Charm Level',   'icon' => 'fas fa-heart text-danger'],
            ['key' => 'disable_user',   'label' => 'Disable User',       'icon' => 'fas fa-user-slash text-danger'],
            ['key' => 'blacklist_user', 'label' => 'Blacklist User',     'icon' => 'fas fa-user-lock text-dark'],
            ['key' => 'delete_profile', 'label' => 'Delete Profile Pic', 'icon' => 'fas fa-image text-secondary'],
        ],
        // User Album (module_id 105)
        105 => [
            ['key' => 'ban_album',      'label' => 'Ban / Unban Album',  'icon' => 'fas fa-ban text-danger'],
        ],
        // Item Delivery (module_id 126)
        126 => [
            ['key' => 'deliver_item',   'label' => 'Deliver Item to User', 'icon' => 'fas fa-paper-plane text-success'],
        ],
        // Room List (module_id 129)
        129 => [
            ['key' => 'ban_room',       'label' => 'Ban / Unban Room',   'icon' => 'fas fa-ban text-danger'],
            ['key' => 'pin_room',       'label' => 'Pin / Unpin Room',   'icon' => 'fas fa-thumbtack text-warning'],
        ],
        // User's Custom Themes (module_id 134)
        134 => [
            ['key' => 'approve_theme',  'label' => 'Approve / Reject Theme', 'icon' => 'fas fa-check-circle text-success'],
        ],
        // Room Kick Log (module_id 174)
        174 => [
            ['key' => 'remove_kick_log', 'label' => 'Remove Kick Log',   'icon' => 'fas fa-trash-alt text-danger'],
        ],
        // Admin Center (module_id 138)
        138 => [
            ['key' => 'toggle_admin_status', 'label' => 'Toggle Status', 'icon' => 'fas fa-toggle-on text-primary'],
        ],
        // BD (module_id 139)
        139 => [
            ['key' => 'assign_agency',  'label' => 'Assign Agency',     'icon' => 'fas fa-briefcase text-info'],
        ],
        // Agency (module_id 140)
        140 => [
            ['key' => 'transfer_agency', 'label' => 'Transfer Agency',   'icon' => 'fas fa-exchange-alt text-primary'],
            ['key' => 'assign_host',     'label' => 'Assign Host',       'icon' => 'fas fa-user-plus text-success'],
            ['key' => 'remove_host',     'label' => 'Remove Host',       'icon' => 'fas fa-user-minus text-danger'],
            ['key' => 'export_agency',   'label' => 'Export Data',       'icon' => 'fas fa-file-export text-secondary'],
        ],
        // Hosts (module_id 141)
        141 => [
            ['key' => 'transfer_host',  'label' => 'Transfer Host',    'icon' => 'fas fa-exchange-alt text-primary'],
            ['key' => 'disable_host',   'label' => 'Disable Host',     'icon' => 'fas fa-user-slash text-danger'],
        ],
        // Coin Seller (module_id 142)
        142 => [
            ['key' => 'recharge_seller', 'label' => 'Recharge Coins',   'icon' => 'fas fa-coins text-warning'],
        ],
        // Merchant (module_id 143)
        143 => [
            ['key' => 'recharge_merchant', 'label' => 'Recharge Merchant', 'icon' => 'fas fa-coins text-warning'],
        ],
        // Relationship Fee Configure (module_id 175)
        175 => [
            ['key' => 'toggle_fee_config', 'label' => 'Toggle Status',  'icon' => 'fas fa-toggle-on text-primary'],
        ],
        // Customer Support Management (module_id 159)
        159 => [
            ['key' => 'reply_support',  'label' => 'Reply Support Msg', 'icon' => 'fas fa-reply text-info'],
        ],
        // Withdrawal Requests (module_id 170)
        170 => [
            ['key' => 'approve_withdrawal', 'label' => 'Approve Request', 'icon' => 'fas fa-check text-success'],
            ['key' => 'reject_withdrawal',  'label' => 'Reject Request',  'icon' => 'fas fa-times text-danger'],
        ],
        // Manual Coins Send / Deduct (module_id 172)
        172 => [
            ['key' => 'send_coins',     'label' => 'Send Coins',       'icon' => 'fas fa-plus-circle text-success'],
            ['key' => 'deduct_coins',   'label' => 'Deduct Coins',     'icon' => 'fas fa-minus-circle text-danger'],
        ],
        // Manual Money Transfer (module_id 163)
        163 => [
            ['key' => 'send_money',     'label' => 'Send Money',       'icon' => 'fas fa-arrow-up text-success'],
            ['key' => 'take_money',     'label' => 'Take Money',       'icon' => 'fas fa-arrow-down text-danger'],
        ],
        // Abuse Word Control (module_id 173)
        173 => [
            ['key' => 'import_words',   'label' => 'Bulk Import Words', 'icon' => 'fas fa-file-import text-primary'],
        ],
        // Roles (module_id 102)
        102 => [
            ['key' => 'manage_permissions', 'label' => 'Manage Permissions', 'icon' => 'fas fa-key text-warning'],
        ],
        // Sub Admin / Users (module_id 103)
        103 => [
            ['key' => 'manage_user_permissions', 'label' => 'Manage Permissions', 'icon' => 'fas fa-user-lock text-warning'],
        ],
    ];

    // Build permissions map by module_id
    $permMap = [];
    foreach ($permissions as $p) {
        $mId = $p->module_id ?? $p['module_id'];
        $permMap[$mId] = $p;
    }

    // Identify mapped module IDs
    $mappedIds = [];
    foreach ($categories as $cat) {
        $mappedIds = array_merge($mappedIds, $cat['modules']);
    }

    // Add unmapped modules to Other
    $otherModules = [];
    foreach ($permissions as $p) {
        $mId = $p->module_id ?? $p['module_id'];
        if (!in_array($mId, $mappedIds)) {
            $otherModules[] = $mId;
        }
    }
    if (!empty($otherModules)) {
        $categories['Other Modules'] = [
            'icon' => 'fas fa-cubes',
            'color' => 'secondary',
            'modules' => $otherModules
        ];
    }
@endphp

<div class="row g-3">

    {{-- Main Content (Left 8 Cols) --}}
    <div class="col-12 col-xl-9">

        {{-- Top Admin Header Card --}}
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-3xl rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold fs-2" style="width: 60px; height: 60px;">
                            {{ strtoupper(substr($user['name'], 0, 1)) }}
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="mb-0 fw-bold text-dark">{{ $user['name'] }}</h4>
                                <span class="badge bg-primary-soft text-primary px-3 py-1 rounded-pill fw-semibold">Sub Admin</span>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-id-badge me-1"></i>ID: {{ $user['id'] }} &nbsp;|&nbsp;
                                <i class="fas fa-envelope me-1"></i>{{ $user['email'] ?? 'N/A' }} &nbsp;|&nbsp;
                                <i class="fas fa-calendar-alt me-1"></i>Added: {{ \Carbon\Carbon::parse($user['created_at'] ?? now())->format('d M Y') }}
                            </small>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('users') }}" class="btn btn-outline-secondary btn-sm px-3">
                            <i class="fas fa-arrow-left me-1"></i> Go Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section Select Cards Grid --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-1"><i class="fas fa-th-large text-primary me-2"></i>Select Permission Sections</h6>
                        <small class="text-muted">Choose the module sections this admin can access</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAllSections">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnUnselectAllSections">Unselect All</button>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach ($categories as $catName => $catInfo)
                        @php
                            $catTotal = 0;
                            $catActive = 0;
                            foreach ($catInfo['modules'] as $mId) {
                                if (isset($permMap[$mId])) {
                                    $p = $permMap[$mId];
                                    $catTotal++;
                                    if ($p['can_view'] || $p['can_add'] || $p['can_edit'] || $p['can_delete'] || $p['allow_all']) {
                                        $catActive++;
                                    }
                                }
                            }
                            $isCatActive = $catActive > 0;
                        @endphp
                        <div class="col-12 col-md-4 col-lg-4">
                            <div class="section-select-card p-3 {{ $isCatActive ? 'active' : '' }}" data-category="{{ Str::slug($catName) }}">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="p-2 rounded bg-{{ $catInfo['color'] }}-soft text-{{ $catInfo['color'] }}">
                                            <i class="{{ $catInfo['icon'] }} fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold fs-6 text-dark">{{ $catName }}</h6>
                                            <small class="text-muted cat-count-text">{{ $catActive }}/{{ $catTotal }} Active</small>
                                        </div>
                                    </div>
                                    <input type="checkbox" class="form-check-input section-toggle-cb" data-category="{{ Str::slug($catName) }}" {{ $isCatActive ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Detailed Module Permissions Grouped by Category --}}
        @foreach ($categories as $catName => $catInfo)
            <div class="card mb-4 border-0 shadow-sm category-block" id="cat-block-{{ Str::slug($catName) }}">
                <div class="card-header bg-light py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="{{ $catInfo['icon'] }} text-{{ $catInfo['color'] }} fs-5"></i>
                        <h6 class="mb-0 fw-bold text-dark">{{ $catName }}</h6>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-xs btn-outline-primary btn-cat-all" data-category="{{ Str::slug($catName) }}" data-value="1">Select All</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary btn-cat-all" data-category="{{ Str::slug($catName) }}" data-value="0">Unselect All</button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        @foreach ($catInfo['modules'] as $mId)
                            @if (isset($permMap[$mId]))
                                @php
                                    $p = $permMap[$mId];
                                    $mName = $p['name'] ?? ('Module ' . $mId);
                                    $actList = is_array($p['actions'] ?? null) ? $p['actions'] : (json_decode($p['actions'] ?? '', true) ?: []);
                                @endphp
                                <div class="col-12 col-md-6">
                                    <div class="card perm-card h-100 p-3 rounded-3" data-module-id="{{ $mId }}" data-category="{{ Str::slug($catName) }}">
                                        <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                                            <h6 class="fw-bold text-dark mb-0 fs-6">
                                                <i class="fas fa-cube text-muted me-1 small"></i> {{ $mName }}
                                            </h6>
                                            <label class="op-checkbox-label bg-white border-0 p-0 m-0 text-primary">
                                                <input type="checkbox" class="cb-perm cb-allow-all" data-module-id="{{ $mId }}" data-type="allow_all" {{ $p['allow_all'] == 1 ? 'checked' : '' }}>
                                                <span class="small fw-bold">Allow All</span>
                                            </label>
                                        </div>

                                        {{-- Standard Operation Checkboxes --}}
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <label class="op-checkbox-label">
                                                <input type="checkbox" class="cb-perm cb-op" data-module-id="{{ $mId }}" data-type="can_view" {{ $p['can_view'] == 1 ? 'checked' : '' }}>
                                                <i class="fas fa-eye text-primary"></i> View
                                            </label>
                                            <label class="op-checkbox-label">
                                                <input type="checkbox" class="cb-perm cb-op" data-module-id="{{ $mId }}" data-type="can_add" {{ $p['can_add'] == 1 ? 'checked' : '' }}>
                                                <i class="fas fa-plus text-success"></i> Add / Create
                                            </label>
                                            <label class="op-checkbox-label">
                                                <input type="checkbox" class="cb-perm cb-op" data-module-id="{{ $mId }}" data-type="can_edit" {{ $p['can_edit'] == 1 ? 'checked' : '' }}>
                                                <i class="fas fa-edit text-warning"></i> Edit
                                            </label>
                                            <label class="op-checkbox-label">
                                                <input type="checkbox" class="cb-perm cb-op" data-module-id="{{ $mId }}" data-type="can_delete" {{ $p['can_delete'] == 1 ? 'checked' : '' }}>
                                                <i class="fas fa-trash text-danger"></i> Delete
                                            </label>
                                        </div>

                                        {{-- Specific Custom Sub-Operation Checkboxes --}}
                                        @if (isset($moduleCustomActions[$mId]))
                                            <div class="mt-2 pt-2 border-top">
                                                <small class="text-muted fw-bold d-block mb-1" style="font-size:0.75rem;">SPECIALIZED OPERATIONS:</small>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach ($moduleCustomActions[$mId] as $customAct)
                                                        @php
                                                            $isActChecked = in_array($customAct['key'], $actList);
                                                        @endphp
                                                        <label class="op-checkbox-label sub-act-label">
                                                            <input type="checkbox" class="cb-perm cb-custom-action" data-module-id="{{ $mId }}" data-action-key="{{ $customAct['key'] }}" {{ $isActChecked ? 'checked' : '' }}>
                                                            <i class="{{ $customAct['icon'] }}"></i> {{ $customAct['label'] }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

    </div>

    {{-- Right Summary Sidebar (Right 3 Cols) --}}
    <div class="col-12 col-xl-3">
        <div class="card border-0 shadow-sm sticky-summary">
            <div class="card-header bg-primary text-white py-3">
                <h6 class="mb-0 text-white fw-bold"><i class="fas fa-shield-alt me-2"></i>Permissions Summary</h6>
            </div>
            <div class="card-body p-3">

                {{-- Live Metrics --}}
                <div class="mb-3 p-3 bg-light rounded text-center border">
                    <small class="text-muted fw-semibold text-uppercase d-block mb-1">Access Level</small>
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                        <span class="badge rounded-pill fs-6 px-3 py-2" id="badgeAccessLevel">0% Access</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" id="barAccessLevel" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>

                <div class="row g-2 mb-3 text-center">
                    <div class="col-6">
                        <div class="p-2 border rounded bg-white">
                            <h4 class="fw-bold mb-0 text-primary" id="summaryActiveSections">0</h4>
                            <small class="text-muted small">Sections</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded bg-white">
                            <h4 class="fw-bold mb-0 text-success" id="summaryTotalPerms">0</h4>
                            <small class="text-muted small">Total Perms</small>
                        </div>
                    </div>
                </div>

                {{-- Selected Sections List --}}
                <h6 class="fw-bold small text-muted text-uppercase mb-2">Active Sections</h6>
                <ul class="list-group list-group-flush mb-3 small border rounded" id="listActiveSections">
                    {{-- Dynamically populated --}}
                </ul>

                {{-- Alert Note --}}
                <div class="alert alert-info p-2 small mb-3 border-0 bg-info-soft text-info">
                    <i class="fas fa-info-circle me-1"></i> Changes take effect immediately upon toggling checkboxes.
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary" id="btnSavePermissions">
                        <i class="fas fa-save me-1"></i> Save Permissions
                    </button>
                    <a href="{{ route('users') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Return to Sub Admins
                    </a>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@section('js')
<script type="text/javascript">
$(function () {

    let userId = "{{ $user['id'] }}";
    let updateUrl = "{{ route('users.permission.update') }}";

    // Recalculate Summary Metrics
    function updateMetrics() {
        let totalModules = $('.perm-card').length;
        let totalPossiblePerms = totalModules * 4;
        let totalActivePerms = 0;
        let activeSections = new Set();

        $('.category-block').each(function () {
            let catName = $(this).find('.card-header h6').text().trim();
            let catSlug = $(this).attr('id').replace('cat-block-', '');
            let catModules = $(this).find('.perm-card').length;

            $(this).find('.cb-op:checked, .cb-custom-action:checked').each(function () {
                totalActivePerms++;
                activeSections.add(catName);
            });

            let activeModulesInCat = 0;
            $(this).find('.perm-card').each(function() {
                if ($(this).find('.cb-op:checked, .cb-custom-action:checked').length > 0) {
                    activeModulesInCat++;
                }
            });

            $('.section-select-card[data-category="' + catSlug + '"]').find('.cat-count-text').text(activeModulesInCat + '/' + catModules + ' Active');
            if (activeModulesInCat > 0) {
                $('.section-select-card[data-category="' + catSlug + '"]').addClass('active').find('.section-toggle-cb').prop('checked', true);
            } else {
                $('.section-select-card[data-category="' + catSlug + '"]').removeClass('active').find('.section-toggle-cb').prop('checked', false);
            }
        });

        $('#summaryActiveSections').text(activeSections.size);
        $('#summaryTotalPerms').text(totalActivePerms);

        let accessPercent = totalPossiblePerms > 0 ? Math.round((totalActivePerms / totalPossiblePerms) * 100) : 0;
        $('#barAccessLevel').css('width', accessPercent + '%');

        let badge = $('#badgeAccessLevel');
        badge.removeClass('badge-access-high badge-access-medium badge-access-low');
        if (accessPercent >= 60) {
            badge.addClass('badge-access-high').text(accessPercent + '% High Access');
        } else if (accessPercent >= 25) {
            badge.addClass('badge-access-medium').text(accessPercent + '% Medium Access');
        } else {
            badge.addClass('badge-access-low').text(accessPercent + '% Low Access');
        }

        let listHtml = '';
        if (activeSections.size === 0) {
            listHtml = '<li class="list-group-item text-muted py-2">No sections selected</li>';
        } else {
            activeSections.forEach(function (sec) {
                listHtml += '<li class="list-group-item py-1 d-flex align-items-center justify-content-between"><span class="fw-semibold">' + sec + '</span><i class="fas fa-check-circle text-success"></i></li>';
            });
        }
        $('#listActiveSections').html(listHtml);
    }

    updateMetrics();

    // Toggle single standard permission checkbox
    $(document).on('change', '.cb-op, .cb-allow-all', function () {
        let cb        = $(this);
        let mId       = cb.data('module-id');
        let type      = cb.data('type');
        let isChecked = cb.prop('checked') ? 1 : 0;
        let card      = cb.closest('.perm-card');

        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                user_id: userId,
                module_id: mId,
                type: type,
                value: isChecked
            },
            success: function (res) {
                if (type === 'allow_all') {
                    card.find('.cb-op, .cb-custom-action').prop('checked', isChecked === 1);
                } else {
                    let allChecked = card.find('.cb-op:checked').length === 4;
                    card.find('.cb-allow-all').prop('checked', allChecked);
                }
                updateMetrics();
                toastr.success("Permission updated successfully");
            },
            error: function () {
                cb.prop('checked', !isChecked);
                toastr.error("Failed to update permission");
            }
        });
    });

    // Toggle custom action checkbox
    $(document).on('change', '.cb-custom-action', function () {
        let cb        = $(this);
        let mId       = cb.data('module-id');
        let actKey    = cb.data('action-key');
        let isChecked = cb.prop('checked') ? 1 : 0;

        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                user_id: userId,
                module_id: mId,
                action_key: actKey,
                value: isChecked
            },
            success: function (res) {
                updateMetrics();
                toastr.success("Sub-permission updated successfully");
            },
            error: function () {
                cb.prop('checked', !isChecked);
                toastr.error("Failed to update sub-permission");
            }
        });
    });

    // Category Select All / Unselect All
    $(document).on('click', '.btn-cat-all', function () {
        let catSlug = $(this).data('category');
        let val     = parseInt($(this).data('value'));
        let block   = $('#cat-block-' + catSlug);
        let mIds    = [];

        block.find('.perm-card').each(function () {
            mIds.push($(this).data('module-id'));
        });

        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                user_id: userId,
                module_ids: mIds,
                value: val
            },
            success: function (res) {
                block.find('.cb-perm').prop('checked', val === 1);
                updateMetrics();
                toastr.success(val === 1 ? "Category permissions enabled" : "Category permissions disabled");
            }
        });
    });

    // Section Grid Card Select All / Unselect All
    $('#btnSelectAllSections, #btnUnselectAllSections').click(function () {
        let val = $(this).attr('id') === 'btnSelectAllSections' ? 1 : 0;
        let allMIds = [];
        $('.perm-card').each(function () {
            allMIds.push($(this).data('module-id'));
        });

        $.ajax({
            url: updateUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                user_id: userId,
                module_ids: allMIds,
                value: val
            },
            success: function (res) {
                $('.cb-perm').prop('checked', val === 1);
                updateMetrics();
                toastr.success(val === 1 ? "All permissions granted" : "All permissions revoked");
            }
        });
    });

    // Save Permissions Button
    $('#btnSavePermissions').click(function () {
        toastr.success("Permissions saved & active for Sub Admin!");
    });

});
</script>
@endsection
