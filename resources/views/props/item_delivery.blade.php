@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="card mb-3">
        <div class="card-header fw-bold">
            Item Delivery
        </div>

        <div class="card-body">

            @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('props.delivery.store') }}">
                @csrf

                <!-- Recipient -->
                <div class="mb-3 row">
                    <label class="col-sm-2 col-form-label text-danger fw-semibold">* Recipient</label>
                    <div class="col-sm-10">
                        <input type="text" name="recipient" class="form-control" placeholder="User IDs">
                    </div>
                </div>

                <!-- Type -->
                <div class="mb-3 row">
                    <label class="col-sm-2 col-form-label text-danger fw-semibold">* Select Type</label>
                    <div class="col-sm-10">
                        <select name="type" id="typeSelect" class="form-select">
                            <option value="">Select type</option>
                            <option value="theme">Theme</option>
                            <option value="frame">Frame</option>
                            <option value="entry">Entry</option>
                            <option value="chat bubble">Chat Bubble</option>
                            <option value="profile card">Profile Card</option>
                            <option value="entry tag">Entry Tag</option>
                            <option value="voice">Voice</option>
                            <option value="id">Ids</option>
                        </select>
                    </div>
                </div>

                <!-- Resource -->
                <div class="mb-3 row">
                    <label class="col-sm-2 col-form-label text-danger fw-semibold">* Choose Type</label>
                    <div class="col-sm-10">
                        <button type="button" id="openModalBtn" class="btn btn-outline-primary" disabled data-bs-toggle="modal" data-bs-target="#resourceModal">
                            Choose
                        </button>

                        <input type="hidden" name="resource_id" id="resource_id">
                        <div id="selectedPreview" class="mt-2"></div>
                    </div>
                </div>

                <!-- Days -->
                <div class="mb-3 row">
                    <label class="col-sm-2 col-form-label text-danger fw-semibold">* Valid Days</label>
                    <div class="col-sm-10">
                        <input type="number" name="valid_days" class="form-control" placeholder="Valid Days">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary px-4">Send</button>

            </form>
        </div>
    </div>
</div>


<!-- MODAL -->
<div class="modal fade" id="resourceModal">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5>Select Item</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="text" id="searchBox" class="form-control mb-3" placeholder="Search item...">

                <div class="row g-3" id="resourceGrid"></div>

                <div class="text-center mt-3">
                    <button id="loadMoreBtn" class="btn btn-outline-secondary d-none" style="color:black !important">Load More</button>
                </div>

            </div>
        </div>
    </div>
</div>


@endsection


@section('js')

<script>
    let selectedType = '';
    let page = 1;
    let search = '';

    document.getElementById('typeSelect').addEventListener('change', function() {
        selectedType = this.value;
        page = 1;
        loadItems(true);
        document.getElementById('openModalBtn').disabled = !selectedType;
    });

    document.getElementById('searchBox').addEventListener('input', function() {
        search = this.value;
        page = 1;
        loadItems(true);
    });

    document.getElementById('loadMoreBtn').addEventListener('click', function() {
        page++;
        loadItems(false);
    });

    function loadItems(reset = false) {
        if (!selectedType) return;

        fetch(`/props/get-items/${encodeURIComponent(selectedType)}?page=${page}&search=${search}`)
            .then(res => res.json())
            .then(data => {

                let html = reset ? '' : document.getElementById('resourceGrid').innerHTML;

                data.data.forEach(item => {

                    let preview = '';

                    if (item.type === 'image') {
                        preview = `<img src="${item.preview}" class="img-fluid rounded mb-1" style="width:100px; height:100px">`;
                    } else if (item.type === 'audio') {
                        preview = `<audio controls src="${item.preview}" style="width:100%"></audio>`;
                    } else {
                        preview = `<div class="fw-bold fs-5 text-primary">${item.title}</div>`;
                    }

                    html += `
                    <div class="col-3">
                        <div class="border p-2 text-center resource-item"
                            data-id="${item.id}"
                            data-preview="${item.preview || ''}"
                            data-type="${item.type}"
                            style="cursor:pointer">

                            ${preview}
                            <small>${item.title}</small>
                        </div>
                    </div>
                `;
                });

                document.getElementById('resourceGrid').innerHTML = html;

                document.getElementById('loadMoreBtn').classList.toggle('d-none', !data.next_page_url);

                bindItemClick();
            });
    }


    function bindItemClick() {
        document.querySelectorAll('.resource-item').forEach(item => {
            item.onclick = function() {

                document.getElementById('resource_id').value = this.dataset.id;

                let type = this.dataset.type;
                let preview = this.dataset.preview;
                let html = '';

                if (type === 'audio') {
                    html = `<audio controls src="${preview}" style="width:200px"></audio>`;
                } else if (preview) {
                    html = `<img src="${preview}" width="100" class="rounded border p-1">`;
                } else {
                    html = `<span class="fw-bold text-success">UID Selected</span>`;
                }

                document.getElementById('selectedPreview').innerHTML = html;

                bootstrap.Modal.getInstance(document.getElementById('resourceModal')).hide();
            };
        });
    }
</script>

@endsection