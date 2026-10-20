/**
 * `categoryLabels`/`categoryCounts` and `allUpdates` are injected by
 * complaints.php with real data from the database.
 */
document.addEventListener("DOMContentLoaded", function () {
    initCategoryChart();
    initModal();
    initModalClose();
    initFilters();
});

function initCategoryChart() {
    const el = document.getElementById("categoryChart");
    if (!el || typeof Chart === "undefined" || typeof categoryLabels === "undefined") return;

    new Chart(el, {
        type: "doughnut",
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryCounts,
                backgroundColor: ["#8B5CF6", "#3B82F6", "#F59E0B", "#10B981", "#EF4444"]
            }]
        },
        options: { responsive: true, plugins: { legend: { position: "bottom" } } }
    });
}

// Maps a status to the timeline dot color used in the original design.
const DOT_COLOR = {
    "New": "bg-purple",
    "In Progress": "bg-orange",
    "Resolved": "bg-green",
    "Closed": "bg-gray"
};

function initModal() {
    document.querySelectorAll(".open-modal-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const id = this.dataset.id;

            document.getElementById("cm-idLabel").textContent = "ID: CMP" + String(id).padStart(4, "0");
            document.getElementById("cm-student").textContent = `${this.dataset.name} (${this.dataset.roll})`;
            document.getElementById("cm-room").textContent = this.dataset.room;
            document.getElementById("cm-category").textContent = this.dataset.category;
            document.getElementById("cm-date").textContent = this.dataset.date;
            document.getElementById("cm-description").textContent = this.dataset.description;
            document.getElementById("cm-complaintId").value = id;

            // Pre-select the current status in the update form.
            const statusMap = { "new": "New", "in-progress": "In Progress", "resolved": "Resolved", "closed": "Closed" };
            document.getElementById("updateStatus").value = statusMap[this.dataset.status] || "New";

            // Build the real timeline from allUpdates.
            const updates = allUpdates.filter(u => String(u.complaint_id) === String(id));
            const timeline = document.getElementById("cm-timeline");
            timeline.innerHTML = updates.map(u => `
                <div class="timeline-item">
                    <div class="timeline-dot ${DOT_COLOR[u.status] || 'bg-gray'}"></div>
                    <div class="timeline-content">
                        <span class="time">${formatDateTime(u.created_at)}</span>
                        <p><strong>${u.status === 'New' ? 'Complaint Submitted' : 'Status Changed: ' + u.status}</strong></p>
                        <small>${u.remarks || ''}</small>
                    </div>
                </div>
            `).join("") || `<p style="font-size:13px; color:var(--text-muted);">No updates yet.</p>`;

            document.getElementById("complaintModal").classList.add("show");
        });
    });
}

function formatDateTime(isoString) {
    const d = new Date(isoString.replace(' ', 'T'));
    const date = d.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
    const time = d.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" });
    return `${date}, ${time}`;
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
    const searchInput = document.getElementById("complaintSearch");
    const categoryFilter = document.getElementById("categoryFilter");
    const statusFilter = document.getElementById("statusFilter");
    const rows = document.querySelectorAll("#complaintTable tbody tr");
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
