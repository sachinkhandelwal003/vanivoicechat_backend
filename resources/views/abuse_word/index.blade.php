@extends('layouts.app')

@section('content')
<div class="card mb-3">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-1">
                <i class="fas fa-ban text-danger me-2"></i>
                Abuse Word Control
            </h5>
            <small class="text-muted">Globally banned words — applied across entire app (chat, profiles, comments, posts, etc.)</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addWordModal">
                <i class="fas fa-plus me-1"></i> Add Word
            </button>
            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                <i class="fas fa-file-import me-1"></i> Bulk Import
            </button>
        </div>
    </div>

    <div class="card-body">

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-list text-primary fa-2x mb-2"></i>
                        <h3 class="fw-bold text-primary mb-1" id="card_total">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Total Words</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-ban text-success fa-2x mb-2"></i>
                        <h3 class="fw-bold text-success mb-1" id="card_active">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Active (Restricted)</small>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border text-center h-100 shadow-sm">
                    <div class="card-body py-3">
                        <i class="fas fa-toggle-off text-secondary fa-2x mb-2"></i>
                        <h3 class="fw-bold text-secondary mb-1" id="card_disabled">0</h3>
                        <small class="text-muted text-uppercase fw-semibold">Disabled</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="row g-2 mb-3 align-items-end">
            <div class="col-6 col-md-4">
                <label class="form-label fw-semibold">Search Word</label>
                <input type="text" class="form-control" id="search_keyword" placeholder="Type to search...">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label fw-semibold">Category</label>
                <select class="form-select" id="category_filter">
                    <option value="">All Categories</option>
                    <option value="general">General</option>
                    <option value="chat">Chat</option>
                    <option value="profile">Profile</option>
                    <option value="content">Content</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label fw-semibold">Status</label>
                <select class="form-select" id="status_filter">
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Disabled</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary" id="btnSearch"><i class="fas fa-search me-1"></i>Search</button>
                    <button class="btn btn-secondary" id="btnReset"><i class="fas fa-sync-alt me-1"></i>Reset</button>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive scrollbar">
            <table class="table table-bordered table-striped align-middle w-100 small" id="bannedWordsTable">
                <thead class="bg-200">
                    <tr>
                        <th>#</th>
                        <th>Banned Word</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Added By</th>
                        <th>Date Added</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

{{-- Add Word Modal --}}
<div class="modal fade" id="addWordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#dc3545,#f99ca3);color:#fff;">
                <h5 class="modal-title"><i class="fas fa-ban me-2"></i>Add Banned Word</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addWordForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Word / Phrase <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_word" name="word" placeholder="e.g. badword" required>
                        <div class="form-text text-muted">Word will be saved in lowercase. It will match case-insensitively in the app.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="add_category" name="category" required>
                            <option value="general">General (all fields)</option>
                            <option value="chat">Chat Only</option>
                            <option value="profile">Profile Fields</option>
                            <option value="content">Content / Posts</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger w-50"><i class="fas fa-plus me-1"></i>Add Word</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Edit Word Modal --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#0d6efd,#86b7fe);color:#fff;">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Banned Word</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editWordForm">
                    <input type="hidden" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Word / Phrase <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_word" name="word" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_category" name="category" required>
                            <option value="general">General (all fields)</option>
                            <option value="chat">Chat Only</option>
                            <option value="profile">Profile Fields</option>
                            <option value="content">Content / Posts</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary w-50"><i class="fas fa-save me-1"></i>Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Bulk Import Modal --}}
<div class="modal fade" id="bulkImportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fd7e14,#ffc107);color:#fff;">
                <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>Bulk Import Words</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkImportForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Words (comma or newline separated) <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="bulk_words" name="words" rows="6" placeholder="word1, word2, word3&#10;word4&#10;word5" required></textarea>
                        <div class="form-text">Enter multiple words separated by commas or new lines. Duplicates will be skipped.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select class="form-select" id="bulk_category" name="category">
                            <option value="general">General (all fields)</option>
                            <option value="chat">Chat Only</option>
                            <option value="profile">Profile Fields</option>
                            <option value="content">Content / Posts</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning w-50"><i class="fas fa-upload me-1"></i>Import All</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {

    let storeUrl       = '{{ route("abuse-words.store") }}';
    let bulkImportUrl  = '{{ route("abuse-words.bulk-import") }}';
    let baseUrl        = '{{ url("admin/abuse-words") }}';

    // ── DataTable ─────────────────────────────────────────────────────────────
    let table = $('#bannedWordsTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        responsive: true,
        ajax: {
            url: '{{ route("abuse-words.index") }}',
            data: function (d) {
                d.search_keyword = $('#search_keyword').val();
                d.category       = $('#category_filter').val();
                d.status         = $('#status_filter').val();
            },
            dataSrc: function (json) {
                $('#card_total').text(json.summary.total);
                $('#card_active').text(json.summary.active);
                $('#card_disabled').text(json.summary.disabled);
                return json.data;
            }
        },
        columns: [
            { data: 'DT_RowIndex',    name: 'DT_RowIndex',    searchable: false, orderable: false },
            { data: 'word',           name: 'word' },
            { data: 'category',       name: 'category' },
            { data: 'status',         name: 'status' },
            { data: 'created_by_name',name: 'created_by_name' },
            { data: 'created_at',     name: 'created_at' },
            { data: 'action',         name: 'action', searchable: false, orderable: false },
        ]
    });

    $('#btnSearch').click(function () { table.ajax.reload(); });
    $('#btnReset').click(function () {
        $('#search_keyword').val('');
        $('#category_filter').val('');
        $('#status_filter').val('');
        table.ajax.reload();
    });

    // ── Add Word ──────────────────────────────────────────────────────────────
    $('#addWordForm').submit(function (e) {
        e.preventDefault();
        let btn = $(this).find('[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Adding...');
        $.ajax({
            url: storeUrl,
            method: 'POST',
            data: {
                _token:   '{{ csrf_token() }}',
                word:     $('#add_word').val(),
                category: $('#add_category').val(),
            },
            success: function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i>Add Word');
                if (res.status) {
                    toastr.success(res.message);
                    $('#addWordModal').modal('hide');
                    $('#addWordForm')[0].reset();
                    table.ajax.reload();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="fas fa-plus me-1"></i>Add Word');
                toastr.error('Network error.');
            }
        });
    });

    // ── Edit Word – open modal ────────────────────────────────────────────────
    $(document).on('click', '.btn-edit', function () {
        $('#edit_id').val($(this).data('id'));
        $('#edit_word').val($(this).data('word'));
        $('#edit_category').val($(this).data('category'));
    });

    $('#editWordForm').submit(function (e) {
        e.preventDefault();
        let id  = $('#edit_id').val();
        let btn = $(this).find('[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving...');
        $.ajax({
            url: baseUrl + '/' + id,
            method: 'PUT',
            data: {
                _token:   '{{ csrf_token() }}',
                word:     $('#edit_word').val(),
                category: $('#edit_category').val(),
            },
            success: function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save Changes');
                if (res.status) {
                    toastr.success(res.message);
                    $('#editModal').modal('hide');
                    table.ajax.reload();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Save Changes');
                toastr.error('Network error.');
            }
        });
    });

    // ── Toggle Status ─────────────────────────────────────────────────────────
    $(document).on('click', '.btn-toggle', function () {
        let id     = $(this).data('id');
        let status = $(this).data('status');
        let label  = status == 1 ? 'disable' : 'enable';
        if (!confirm('Are you sure you want to ' + label + ' this word?')) return;

        $.ajax({
            url: baseUrl + '/' + id + '/toggle',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    table.ajax.reload();
                } else {
                    toastr.error(res.message);
                }
            }
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    $(document).on('click', '.btn-delete', function () {
        let id   = $(this).data('id');
        let word = $(this).data('word');
        if (!confirm('Delete "' + word + '" from banned list? This cannot be undone.')) return;

        $.ajax({
            url: baseUrl + '/' + id,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function (res) {
                if (res.status) {
                    toastr.success(res.message);
                    table.ajax.reload();
                } else {
                    toastr.error(res.message);
                }
            }
        });
    });

    // ── Bulk Import ───────────────────────────────────────────────────────────
    $('#bulkImportForm').submit(function (e) {
        e.preventDefault();
        let btn = $(this).find('[type=submit]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Importing...');
        $.ajax({
            url: bulkImportUrl,
            method: 'POST',
            data: {
                _token:   '{{ csrf_token() }}',
                words:    $('#bulk_words').val(),
                category: $('#bulk_category').val(),
            },
            success: function (res) {
                btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i>Import All');
                if (res.status) {
                    toastr.success(res.message);
                    $('#bulkImportModal').modal('hide');
                    $('#bulkImportForm')[0].reset();
                    table.ajax.reload();
                } else {
                    toastr.error(res.message);
                }
            },
            error: function () {
                btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i>Import All');
                toastr.error('Network error.');
            }
        });
    });
});
</script>
@endpush
