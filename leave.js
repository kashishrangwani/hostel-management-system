/**
 * `leaveHistory` is injected by leave.php: every leave_requests row
 * (student_id, from_date, to_date, leave_type, cancelled), used to
 * build the "Leave History" table inside the details modal.
 */
document.addEventListener("DOMContentLoaded", function () {
    initModal();
    initModalClose();
    initFilters();
});

// Mirrors the PHP leaveStatus() helper, so the JS-rendered history
// table agrees with the server-rendered main table.
function computeStatus(fromDate, toDate, cancelled) {
    if (cancelled == 1) return "Cancelled";
    const today = new Date().toISOString().slice(0, 10);
    if (today < fromDate) return "Upcoming";
    if (today > toDate) return "Returned";
    return "On Leave";
}

function formatDate(isoDate) {
    return new Date(isoDate + "T00:00:00").toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
}

function initModal() {
    document.querySelectorAll(".open-modal-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const d = this.dataset;

            document.getElementById("ld-name").textContent = d.name;
            document.getElementById("ld-roll").textContent = d.roll;
            document.getElementById("ld-room").textContent = d.room;
            document.getElementById("ld-type").textContent = d.type;
            document.getElementById("ld-from").textContent = d.from;
            document.getElementById("ld-to").textContent = d.to;
            document.getElementById("ld-days").textContent = d.days;
            document.getElementById("ld-reason").textContent = d.reason;

            const badge = document.getElementById("ld-statusBadge");
            badge.textContent = d.statusLabel;
            badge.className = "status-badge " + d.status;

            document.getElementById("ld-historyTitle").textContent = `Leave History (${d.name} - ${d.roll})`;

            const rows = leaveHistory.filter(l => String(l.student_id) === String(d.studentId));
            document.getElementById("ld-historyMeta").textContent = `Total Records: ${rows.length}`;

            const historyBody = document.getElementById("ld-historyBody");
            historyBody.innerHTML = rows.length
                ? rows.map(l => {
                    const status = computeStatus(l.from_date, l.to_date, l.cancelled);
                    return `
                        <tr>
                            <td>${formatDate(l.from_date)}</td>
                            <td>${formatDate(l.to_date)}</td>
                            <td>${l.leave_type}</td>
                            <td><span class="status-badge ${status.toLowerCase().replace(' ', '-')}">${status}</span></td>
                        </tr>`;
                }).join("")
                : `<tr><td colspan="4" style="text-align:center; padding: 16px;">No leave history.</td></tr>`;

            document.getElementById("leaveDetailsModal").classList.add("show");
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
    const searchInput = document.getElementById("leaveSearch");
    const statusFilter = document.getElementById("statusFilter");
    const dateFilter = document.getElementById("dateFilter");
    const rows = document.querySelectorAll("#leaveTable tbody tr");
    if (!rows.length) return;

    function apply() {
        const query = (searchInput?.value || "").toLowerCase();
        const status = statusFilter?.value || "all";
        const date = dateFilter?.value || "";

        rows.forEach(row => {
            const matchesSearch = row.innerText.toLowerCase().includes(query);
            const matchesStatus = status === "all" || row.dataset.status === status;
            const matchesDate = !date || row.dataset.date === date;
            row.style.display = (matchesSearch && matchesStatus && matchesDate) ? "" : "none";
        });
    }

    searchInput?.addEventListener("keyup", apply);
    statusFilter?.addEventListener("change", apply);
    dateFilter?.addEventListener("change", apply);
}
