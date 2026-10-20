document.addEventListener("DOMContentLoaded", function () {
    initModals();
    initViewButtons();
    initEditButtons();
    initFilters();
});

/**
 * Generic open/close wiring for all modal-overlay elements on this page.
 */
function initModals() {
    const openAddBtn = document.getElementById("openAddModal");
    const addModal = document.getElementById("addStudentModal");
    if (openAddBtn && addModal) {
        openAddBtn.addEventListener("click", () => addModal.classList.add("show"));
    }

    document.querySelectorAll(".modal-overlay").forEach(overlay => {
        overlay.querySelectorAll(".close-modal-btn, .close-modal-btn-secondary").forEach(btn => {
            btn.addEventListener("click", () => overlay.classList.remove("show"));
        });
        // Click on the dark backdrop also closes it.
        overlay.addEventListener("click", (e) => {
            if (e.target === overlay) overlay.classList.remove("show");
        });
    });
}

/**
 * "View" button: reads the clicked row's data-* attributes
 * (rendered server-side with real data) and fills the view modal.
 */
function initViewButtons() {
    document.querySelectorAll(".open-view-modal").forEach(btn => {
        btn.addEventListener("click", function () {
            const row = this.closest("tr");
            const modal = document.getElementById("viewStudentModal");

            document.getElementById("vs-name").textContent = row.dataset.name;
            document.getElementById("vs-roll").textContent = row.dataset.roll;
            document.getElementById("vs-avatar").textContent = row.dataset.name.charAt(0).toUpperCase();
            document.getElementById("vs-email").textContent = row.dataset.email;
            document.getElementById("vs-phone").textContent = row.dataset.phone;
            document.getElementById("vs-gender").textContent = row.dataset.gender;
            document.getElementById("vs-dob").textContent = row.dataset.dob || "Not provided";
            document.getElementById("vs-course").textContent = row.dataset.courseFull;
            document.getElementById("vs-guardian").textContent = row.dataset.guardian || "Not provided";
            document.getElementById("vs-guardian-phone").textContent = row.dataset.guardianPhone || "Not provided";
            document.getElementById("vs-address").textContent = row.dataset.address || "Not provided";
            document.getElementById("vs-room").textContent = row.dataset.room;
            document.getElementById("vs-bed").textContent = row.dataset.bed;
            document.getElementById("vs-joined").textContent = row.dataset.joined;
            document.getElementById("vs-leaves").textContent = row.dataset.leaves;
            document.getElementById("vs-complaints").textContent = row.dataset.complaints;

            const statusBadge = document.getElementById("vs-status");
            statusBadge.textContent = row.dataset.status;
            statusBadge.className = "status-badge " + row.dataset.status.toLowerCase();

            modal.classList.add("show");
        });
    });
}

/**
 * "Edit" button: pre-fills the edit form from the row's data-*
 * attributes so the admin only has to change what's different.
 */
function initEditButtons() {
    document.querySelectorAll(".open-edit-modal").forEach(btn => {
        btn.addEventListener("click", function () {
            const row = this.closest("tr");
            const modal = document.getElementById("editStudentModal");

            document.getElementById("es-id").value = row.dataset.id;
            document.getElementById("es-name").value = row.dataset.name;
            document.getElementById("es-email").value = row.dataset.email;
            document.getElementById("es-phone").value = row.dataset.phone;
            document.getElementById("es-status").value = row.dataset.status;
            document.getElementById("es-course").value = row.dataset.courseFull;

            modal.classList.add("show");
        });
    });
}

/**
 * Search box + course/status dropdowns, filtering the real
 * server-rendered rows already in the DOM.
 */
function initFilters() {
    const searchInput = document.getElementById("studentSearch");
    const courseFilter = document.getElementById("courseFilter");
    const statusFilter = document.getElementById("statusFilter");
    const rows = document.querySelectorAll("#studentTable tbody tr");
    if (!rows.length) return;

    function applyFilters() {
        const query = (searchInput?.value || "").toLowerCase();
        const course = courseFilter?.value || "all";
        const status = statusFilter?.value || "all";

        rows.forEach(row => {
            const matchesSearch = row.innerText.toLowerCase().includes(query);
            const matchesCourse = course === "all" || row.dataset.course === course;
            const matchesStatus = status === "all" || row.dataset.status === status;
            row.style.display = (matchesSearch && matchesCourse && matchesStatus) ? "" : "none";
        });
    }

    searchInput?.addEventListener("keyup", applyFilters);
    courseFilter?.addEventListener("change", applyFilters);
    statusFilter?.addEventListener("change", applyFilters);
}
