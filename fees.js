/**
 * `allPayments` is injected by fees.php: a real array of every
 * fee_payments row, used to populate the per-student history modal.
 */
document.addEventListener("DOMContentLoaded", function () {
    initHistoryModal();
    initModalClose();
    initFeeFilter();
});

function initHistoryModal() {
    document.querySelectorAll(".open-modal-btn").forEach(btn => {
        btn.addEventListener("click", function () {
            const studentId = this.dataset.studentId;

            document.getElementById("pm-name").textContent = this.dataset.name;
            document.getElementById("pm-room").textContent = this.dataset.room;
            document.getElementById("pm-total").textContent = "₹ " + Number(this.dataset.total).toLocaleString("en-IN");
            document.getElementById("pm-due").textContent = "₹ " + Number(this.dataset.due).toLocaleString("en-IN");

            document.getElementById("sf-student-id").value = studentId;
            document.getElementById("sf-total-fee").value = this.dataset.total;
            document.getElementById("ap-student-id").value = studentId;

            const historyBody = document.getElementById("pm-history-body");
            const rows = allPayments.filter(p => String(p.student_id) === String(studentId));

            historyBody.innerHTML = rows.length
                ? rows.map(p => `
                    <tr>
                        <td>${formatDate(p.paid_on)}</td>
                        <td>₹ ${Number(p.amount).toLocaleString("en-IN")}</td>
                        <td>${p.method}</td>
                        <td>${p.transaction_id || "-"}</td>
                    </tr>`).join("")
                : `<tr><td colspan="4" style="text-align:center; padding: 16px;">No payments recorded yet.</td></tr>`;

            document.getElementById("paymentModal").classList.add("show");
        });
    });
}

function formatDate(isoDate) {
    const d = new Date(isoDate + "T00:00:00");
    return d.toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
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

function initFeeFilter() {
    const filter = document.getElementById("feeFilter");
    const rows = document.querySelectorAll(".data-table tbody tr");
    if (!filter) return;

    filter.addEventListener("change", function () {
        rows.forEach(row => {
            row.style.display = (this.value === "all" || row.dataset.filter === this.value) ? "" : "none";
        });
    });
}
