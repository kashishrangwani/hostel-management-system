document.addEventListener("DOMContentLoaded", function () {
    initCreateModal();
    initViewModal();
    initModalClose();
    initFilters();
});

function initCreateModal() {
    const openBtn = document.getElementById("openCreateModal");
    const modal = document.getElementById("createNoticeModal");
    if (!openBtn || !modal) return;

    openBtn.addEventListener("click", () => {
        resetCreateForm();
        document.getElementById("cn-modalTitle").textContent = "Create Notice";
        document.getElementById("cn-submitBtn").name = "create_notice";
        document.getElementById("cn-submitBtn").textContent = "PUBLISH NOTICE";
        modal.classList.add("show");
    });
}

function resetCreateForm() {
    document.getElementById("createNoticeForm").reset();
    document.getElementById("cn-noticeId").value = "";
}

function initViewModal() {
    document.querySelectorAll(".open-view-modal").forEach(btn => {
        btn.addEventListener("click", function () {
            const d = this.dataset;

            document.getElementById("vn-title").textContent = d.title;
            document.getElementById("vn-category").textContent = d.category;
            document.getElementById("vn-published").textContent = d.publishDateNice;
            document.getElementById("vn-audience").textContent = d.audience;
            document.getElementById("vn-status").textContent = d.statusLabel;
            document.getElementById("vn-status").className = d.status === "published" ? "text-green" : "text-orange";
            document.getElementById("vn-description").textContent = d.description;
            document.getElementById("vn-deleteId").value = d.id;

            // "Edit Notice" pre-fills and re-purposes the create modal.
            document.getElementById("vn-editBtn").onclick = function () {
                document.getElementById("cn-noticeId").value = d.id;
                document.getElementById("cn-title").value = d.title;
                document.getElementById("cn-category").value = d.category;
                document.getElementById("cn-audience").value = d.audience;
                document.getElementById("cn-publishDate").value = d.publishDate;
                document.getElementById("cn-description").value = d.description;

                document.getElementById("cn-modalTitle").textContent = "Edit Notice";
                document.getElementById("cn-submitBtn").name = "edit_notice";
                document.getElementById("cn-submitBtn").textContent = "SAVE CHANGES";

                document.getElementById("viewNoticeModal").classList.remove("show");
                document.getElementById("createNoticeModal").classList.add("show");
            };

            document.getElementById("viewNoticeModal").classList.add("show");
        });
    });
}

function initModalClose() {
    document.querySelectorAll(".modal-overlay").forEach(overlay => {
        overlay.querySelectorAll(".close-modal-btn, .close-modal-btn-secondary").forEach(btn => {
            btn.addEventListener("click", () => overlay.classList.remove("show"));
        });
        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) overlay.classList.remove("show");
        });
    });
}

function initFilters() {
    const searchInput = document.getElementById("noticeSearch");
    const categoryFilter = document.getElementById("categoryFilter");
    const statusFilter = document.getElementById("statusFilter");
    const rows = document.querySelectorAll("#noticeTable tbody tr");
    if (!rows.length) return;

    function apply() {
        const query = (searchInput?.value || "").toLowerCase();
        const category = categoryFilter?.value || "all";
        const status = statusFilter?.value || "all";

        rows.forEach(row => {
            const matchesSearch = row.innerText.toLowerCase().includes(query);
            const matchesCategory = category === "all" || row.dataset.category === category;
            const matchesStatus = status === "all" || row.dataset.status === status;
            row.style.display = (matchesSearch && matchesCategory && matchesStatus) ? "" : "none";
        });
    }

    searchInput?.addEventListener("keyup", apply);
    categoryFilter?.addEventListener("change", apply);
    statusFilter?.addEventListener("change", apply);
}
