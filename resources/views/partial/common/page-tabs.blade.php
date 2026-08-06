<style>
    .page-tabs-wrapper {
        background: #fff;
        border-bottom: 1px solid #dee2e6;
        overflow-x: auto;
        overflow-y: hidden;
        white-space: nowrap;
        padding: 0 10px;
    }

    .page-tabs {
        display: flex;
        align-items: center;
        min-height: 44px;
        gap: 5px;
    }

    .page-tab {
        display: inline-flex;
        align-items: center;
        padding: 8px 14px;
        background: #f4f4f4;
        color: #555;
        border-radius: 6px 6px 0 0;
        text-decoration: none;
        transition: .2s;
        flex-shrink: 0;
    }

    .page-tab:hover {
        background: #ececec;
        color: #000;
    }

    .page-tab.active {
        background: #7367f0;
        color: #fff;
    }

    .page-tab .close-tab {
        margin-left: 10px;
        cursor: pointer;
        font-size: 12px;
    }

    .page-tab .close-tab:hover {
        color: #ff3b3b;
    }
</style>

<div class="page-tabs-wrapper">
    <div class="page-tabs" id="pageTabs"></div>
</div>
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script>
    const STORAGE_KEY = "admin_page_tabs";

    function getTabs() {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
    }

    function saveTabs(tabs) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(tabs));
    }

    function getPageTitle() {

        // Card Header Title
        let title = $(".card-header h5:first").text().trim();

        if (title) {

            title = title
                .replace(":: List", "")
                .replace(":: Form", "")
                .replace("Add ", "")
                .replace("Edit ", "")
                .trim();

            return title;
        }

        // Breadcrumb
        title = $(".breadcrumb .active").text().trim();

        if (title) {
            return title;
        }

        return document.title;
    }

    function renderTabs() {

        let tabs = getTabs();

        let html = "";

        tabs.forEach(function(tab) {

            html += `
                <a href="${tab.url}" class="page-tab ${tab.url===location.href?'active':''}">
                    <span>${tab.title}</span>

                    <span class="close-tab" data-url="${tab.url}">
                        <i class="fa fa-times"></i>
                    </span>
                </a>
            `;
        });

        $("#pageTabs").html(html);
    }

    $(function() {

        let url = window.location.href;
        let title = getPageTitle();

        let tabs = getTabs();

        let index = tabs.findIndex(function(tab) {
            return tab.url === url;
        });

        if (index === -1) {

            tabs.push({
                title: title,
                url: url
            });

        } else {

            tabs[index].title = title;

        }

        saveTabs(tabs);

        renderTabs();

    });

    $(document).on("click", ".close-tab", function(e) {

        e.preventDefault();
        e.stopPropagation();

        let url = $(this).data("url");

        let tabs = getTabs().filter(function(tab) {
            return tab.url !== url;
        });

        saveTabs(tabs);

        if (url === window.location.href) {

            if (tabs.length) {

                window.location.href = tabs[tabs.length - 1].url;

            } else {

                localStorage.removeItem(STORAGE_KEY);

                window.location.href = "{{ route('dashboard') }}";

            }

        } else {

            renderTabs();

        }

    });
</script>
