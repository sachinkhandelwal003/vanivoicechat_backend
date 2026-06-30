@extends('layouts.app')

@section('content')

<style>
    .chat-header.hidden-header {
        padding: 0 !important;
        height: 0 !important;
        overflow: hidden;
    }

    .empty-state {
        margin: auto;
        text-align: center;
        color: #6c757d;
    }

    .empty-icon {
        font-size: 48px;
        margin-bottom: 15px;
        color: #c0c4cc;
    }

    .msg {
        animation: fadeIn 0.2s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .file-preview-container {
        display: flex;
        gap: 10px;
        padding: 10px;
        overflow-x: auto;
    }

    .file-preview-item {
        position: relative;
    }

    .file-preview-item img,
    .file-preview-item video {
        height: 80px;
        border-radius: 8px;
    }

    .remove-preview {
        position: absolute;
        top: -6px;
        right: -6px;
        background: red;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 12px;
        text-align: center;
        cursor: pointer;
    }

    /* ===== WhatsApp Style Input ===== */
    .chat-input-wrapper {
        display: flex;
        align-items: center;
        background: #fff;
        border-radius: 30px;
        padding: 6px 12px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }

    .chat-textbox {
        flex: 1;
        border: none;
        outline: none;
        padding: 8px;
        font-size: 14px;
    }

    .attach-wrapper {
        position: relative;
        margin-right: 10px;
        cursor: pointer;
    }

    .attach-icon {
        font-size: 18px;
        color: #6c757d;
        transition: 0.2s;
    }

    .attach-icon:hover {
        color: #4f46e5;
    }

    .attach-tooltip {
        position: absolute;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: #fff;
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 4px;
        opacity: 0;
        pointer-events: none;
        transition: 0.2s;
    }

    .attach-wrapper:hover .attach-tooltip {
        opacity: 1;
    }

    .send-btn {
        background: #4f46e5;
        border: none;
        color: #fff;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: 0.2s;
    }

    .send-btn:hover {
        background: #4338ca;
    }

    /* ===== Wrapper ===== */
    .support-wrapper {
        height: 85vh;
        display: flex;
        overflow: hidden;
    }

    /* ===== Left Sidebar ===== */
    .support-sidebar {
        background: #f8f9fb;
        border-right: 1px solid #e6e9f0;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    /* USER LIST SCROLL */
    #supportUsers {
        flex: 1;
        overflow-y: auto;
    }

    /* ===== Right Side ===== */
    .col-md-8 {
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    /* CHAT MESSAGE SCROLL */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        background: #f4f6fb;
    }
</style>

<div class="container-fluid ">

    <div class="card shadow-sm support-wrapper">
        <div class="row g-0 h-100">

            <!-- ================= LEFT SIDE ================= -->
            <div class="col-md-4 support-sidebar d-flex flex-column">

                <div class="p-3 fw-semibold border-bottom">
                    <i class="fa-solid fa-headset me-2"></i> Support Conversations
                </div>

                <div id="supportUsers" class="flex-grow-1">

                    @foreach($conversations as $conversation)
                    @include('customer_support.partials.conversation_row')
                    @endforeach

                </div>

            </div>

            <!-- ================= RIGHT SIDE ================= -->
            <div class="col-md-8 d-flex flex-column">

                <!-- Header -->
                <div class="p-3 chat-header fw-semibold d-flex align-items-center justify-content-between hidden-header">

                    <!-- LEFT SIDE : User Name -->
                    <span id="chatHeader" class="ms-2">Select a conversation</span>

                    <!-- RIGHT SIDE : Back Arrow -->
                    <i class="fa-solid fa-arrow-left"
                        id="backIcon"
                        style="cursor:pointer; display:none; font-size:18px;"
                        onclick="goBackToList()"></i>

                </div>

                <!-- Messages -->
                <div id="chatMessages"
                    class="flex-grow-1 p-3 d-flex flex-column chat-messages">

                    <div id="emptyState" class="empty-state">
                        <div class="empty-icon">
                            <i class="fa-regular fa-comments"></i>
                        </div>
                        <h5>Please select a conversation</h5>
                        <p class="text-muted">Choose a user from the left panel to start chatting.</p>
                    </div>

                </div>

                <!-- Input -->
                <div class="p-3 chat-input" id="chatInputSection" style="display:none;">
                    <div id="filePreviewContainer" class="file-preview-container"></div>
                    <div class="chat-input-wrapper">

                        <!-- Hidden File Input -->
                        <input type="file" id="fileInput" hidden multiple>

                        <!-- Attach Icon -->
                        <div class="attach-wrapper">
                            <i class="fa-solid fa-paperclip attach-icon"
                                onclick="document.getElementById('fileInput').click()"></i>
                            <span class="attach-tooltip">Attach</span>
                        </div>

                        <!-- Text Input -->
                        <input type="text"
                            id="adminMessage"
                            class="chat-textbox"
                            placeholder="Type a message..."
                            onkeydown="handleEnter(event)">

                        <!-- Send Button -->
                        <button class="send-btn" onclick="sendMessage()">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>

                    </div>
                </div>

            </div>

        </div>
    </div>

</div>

@endsection
@push('scripts')
<script>
    let currentConversationId = null;
    let activeChannelName = null;
    let allConversationIds = @json($conversations->pluck('id'));

    let selectedFiles = [];

    document.getElementById('fileInput').addEventListener('change', function(e) {

        selectedFiles = Array.from(e.target.files);
        renderPreview();
    });

    function subscribeToAllConversations() {

        allConversationIds.forEach(id => {

            Echo.channel('support-channel.' + id)
                .listen('.support.message', (e) => {

                    //console.log("Realtime received:", e);

                    updateSidebarRealtime(e.message);

                    if (parseInt(e.message.conversation_id) === parseInt(currentConversationId)) {
                        addMessageToUI(e.message);
                        scrollToBottom();
                    }
                });

        });
    }

    function renderPreview() {

        let container = document.getElementById('filePreviewContainer');
        container.innerHTML = '';

        selectedFiles.forEach((file, index) => {

            let previewItem = document.createElement('div');
            previewItem.classList.add('file-preview-item');

            let removeBtn = document.createElement('div');
            removeBtn.classList.add('remove-preview');
            removeBtn.innerText = '×';
            removeBtn.onclick = () => {
                selectedFiles.splice(index, 1);
                renderPreview();
            };

            if (file.type.startsWith('image')) {
                let img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                previewItem.appendChild(img);
            }

            if (file.type.startsWith('video')) {
                let video = document.createElement('video');
                video.src = URL.createObjectURL(file);
                video.muted = true;
                previewItem.appendChild(video);
            }

            previewItem.appendChild(removeBtn);
            container.appendChild(previewItem);
        });
    }

    function handleEnter(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    }

    function selectUser(element, conversationId) {

        currentConversationId = conversationId;
        localStorage.setItem('activeConversationId', conversationId);

        document.querySelectorAll('.support-user')
            .forEach(el => el.classList.remove('active'));

        element.classList.add('active');

        document.getElementById('chatHeader').innerText =
            element.querySelector('.fw-semibold').innerText;

        let emptyState = document.getElementById('emptyState');
        if (emptyState) {
            emptyState.remove();
        }
        document.getElementById('chatInputSection').style.display = "block";
        document.querySelector('.chat-header').classList.remove('hidden-header');
        let chatBox = document.getElementById('chatMessages');
        chatBox.innerHTML = '';

        loadMessages(conversationId);

        // LOAD DRAFT
        let messageInput = document.getElementById('adminMessage');
        let savedDraft = localStorage.getItem('draft_' + conversationId);
        messageInput.value = savedDraft ? savedDraft : '';

        document.getElementById('backIcon').style.display = "block";

        // remove unread badge instantly
        let badge = element.querySelector('.badge');
        if (badge) badge.remove();
    }

    function loadMessages(conversationId) {

        let chatBox = document.getElementById('chatMessages');
        chatBox.innerHTML = '';

        fetch('/support/messages/' + conversationId)
            .then(res => res.json())
            .then(data => {

                data.forEach(msg => addMessageToUI(msg));

                setTimeout(() => {
                    scrollToBottom();
                }, 100);
            });
    }

    function sendMessage() {

        let message = document.getElementById('adminMessage').value;

        if (!currentConversationId) {
            alert('Select conversation first');
            return;
        }

        let formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('message', message);

        selectedFiles.forEach(file => {
            formData.append('file[]', file);
        });

        fetch("{{ route('support.send') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Accept": "application/json"
                },
                body: formData
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error("Server error");
                }
                return res.json();
            })
            .then(data => {

                document.getElementById('adminMessage').value = '';
                selectedFiles = [];
                document.getElementById('filePreviewContainer').innerHTML = '';
                localStorage.removeItem('draft_' + currentConversationId);

            })
            .catch(err => {
                // console.error("Send error:", err);
            });
    }

    function addMessageToUI(data) {

        let msg = document.createElement('div');
        msg.classList.add('msg');
        msg.classList.add(data.sender_type === 'admin' ? 'msg-admin' : 'msg-user');

        if (data.message) {
            let text = document.createElement('div');
            text.innerText = data.message;
            msg.appendChild(text);
        }

        if (data.file) {

            let fileUrl = data.file.startsWith('http') ?
                data.file :
                "/storage/" + data.file + '?t=' + Date.now();

            if (data.file_type === 'image') {
                let img = document.createElement('img');
                img.src = fileUrl;
                img.style.maxWidth = "220px";
                img.classList.add('mt-2');
                msg.appendChild(img);
            }

            if (data.file_type === 'video') {
                let video = document.createElement('video');
                video.src = fileUrl;
                video.controls = true;
                video.style.maxWidth = "250px";
                video.classList.add('mt-2');
                msg.appendChild(video);
            }
        }

        document.getElementById('chatMessages').appendChild(msg);
    }

    function updateSidebarUnread(conversationId) {

        let userElement = document.querySelector(
            `[onclick="selectUser(this, ${conversationId})"]`
        );

        if (!userElement) return;

        let badge = userElement.querySelector('.badge');

        if (!badge) {
            badge = document.createElement('span');
            badge.classList.add('badge', 'bg-danger', 'rounded-pill');
            badge.innerText = 1;
            userElement.appendChild(badge);
        } else {
            badge.innerText = parseInt(badge.innerText) + 1;
        }
    }

    function updateSidebarRealtime(message) {

        let conversationId = message.conversation_id;

        let userElement = document.querySelector(
            `[onclick="selectUser(this, ${conversationId})"]`
        );

        if (!userElement) return;

        // 🔴 Unread count only if message from user
        if (message.sender_type === 'user') {

            let badge = userElement.querySelector('.badge');

            if (!badge) {
                badge = document.createElement('span');
                badge.classList.add('badge', 'bg-danger', 'rounded-pill');
                badge.innerText = 1;
                userElement.appendChild(badge);
            } else {
                badge.innerText = parseInt(badge.innerText) + 1;
            }
        }

        // 📝 Update last message preview
        let preview = userElement.querySelector('small.text-muted');
        if (preview) {
            preview.innerText = message.message ?
                message.message.substring(0, 40) :
                'Attachment';
        }

        // ⬆ Move conversation to top
        document.getElementById('supportUsers').prepend(userElement);
    }

    function scrollToBottom() {
        const chatBox = document.getElementById('chatMessages');
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    subscribeToAllConversations();

    Echo.channel('support-global')
        .listen('.support.message', (e) => {

            // console.log("Global message:", e);

            let conversationId = e.message.conversation_id;

            let existingUser = document.querySelector(
                `[onclick="selectUser(this, ${conversationId})"]`
            );

            // 🆕 If conversation not in sidebar
            if (!existingUser) {
                location.reload(); // simple safe fix
            }
        });

    document.addEventListener("DOMContentLoaded", function() {

        let savedConversationId = localStorage.getItem('activeConversationId');

        if (savedConversationId) {

            let userElement = document.querySelector(
                `[onclick="selectUser(this, ${savedConversationId})"]`
            );

            if (userElement) {
                selectUser(userElement, savedConversationId);
            }
        } else {
            document.querySelector('.chat-header').classList.add('hidden-header');
        }
    });

    // Draft Auto Save
    document.addEventListener("DOMContentLoaded", function() {

        const messageInput = document.getElementById('adminMessage');

        if (!messageInput) return;

        messageInput.addEventListener('input', function() {

            const activeId = localStorage.getItem('activeConversationId');

            if (activeId) {
                localStorage.setItem(
                    'draft_' + activeId,
                    this.value
                );
            }

        });

    });

    function goBackToList() {

        currentConversationId = null;

        localStorage.removeItem('activeConversationId');

        document.getElementById('chatInputSection').style.display = "none";

        document.getElementById('backIcon').style.display = "none";

        document.getElementById('chatHeader').innerText = "";
        document.querySelector('.chat-header').classList.add('hidden-header');

        document.querySelectorAll('.support-user')
            .forEach(el => el.classList.remove('active'));

        const chatMessages = document.getElementById('chatMessages');

        chatMessages.innerHTML = `
        <div id="emptyState" class="empty-state">
            <div class="empty-icon">
                <i class="fa-regular fa-comments"></i>
            </div>
            <h5>Please select a conversation</h5>
            <p class="text-muted">Choose a user from the left panel to start chatting.</p>
        </div>
        `;
    }
</script>
@endpush