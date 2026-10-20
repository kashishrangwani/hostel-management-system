/**
 * `reportData` is injected by reports.php with 5 real arrays
 * (students, rooms, fees, leave, complaints) straight from MySQL.
 * Everything below just filters/aggregates/renders them in the browser.
 */
let currentChart = null;

document.addEventListener("DOMContentLoaded", function () {
    const typeSelect = document.getElementById("reportTypeSelect");
    const filtersContainer = document.getElementById("dynamicFiltersContainer");
    const generateBtn = document.getElementById("generateReportBtn");
    const resetBtn = document.getElementById("resetReportBtn");
    const form = document.getElementById("reportConfigForm");

    typeSelect.addEventListener("change", function () {
        const tpl = document.getElementById("filterTpl-" + this.value);
        filtersContainer.innerHTML = "";
        if (tpl) {
            filtersContainer.appendChild(tpl.content.cloneNode(true));
            populateBlockOptions();
        }
        generateBtn.disabled = false;
    });

    form.addEventListener("submit", function (e) {
        e.preventDefault();
        generateReport(typeSelect.value);
    });

    resetBtn.addEventListener("click", function () {
        form.reset();
        filtersContainer.innerHTML = `<p class="placeholder-text text-gray text-sm"><i class="fa-solid fa-circle-info"></i> Please select a report type to view available filters.</p>`;
        generateBtn.disabled = true;
        document.getElementById("reportResultsWrapper").style.display = "none";
    });

    document.getElementById("printReportBtn").addEventListener("click", () => window.print());
    document.getElementById("exportPdfBtn").addEventListener("click", () => window.print());
});

// The rooms filter template needs real block names, so fill them in once cloned.
function populateBlockOptions() {
    const blockSelect = document.getElementById("f-block");
    if (!blockSelect) return;
    const blocks = [...new Set(reportData.rooms.map(r => r.block))].sort();
    blocks.forEach(b => blockSelect.add(new Option("Block " + b, b)));
}

function val(id, fallback = "All") {
    const el = document.getElementById(id);
    return el ? el.value : fallback;
}

function generateReport(type) {
    document.getElementById("printDate").textContent = new Date().toLocaleString("en-GB");

    const generators = {
        students: renderStudentsReport,
        rooms: renderRoomsReport,
        fees: renderFeesReport,
        leave: renderLeaveReport,
        complaints: renderComplaintsReport
    };
    if (generators[type]) generators[type]();

    document.getElementById("reportResultsWrapper").style.display = "block";
}

function setStats(cards) {
    document.getElementById("dynamicStatsRow").innerHTML = cards.map(c => `
        <div class="stat-card">
            <p><i class="fa-solid ${c.icon}"></i> ${c.label}</p>
            <h4>${c.value}</h4>
        </div>
    `).join("");
}

function setTable(headers, rows) {
    document.getElementById("reportTableHead").innerHTML = "<tr>" + headers.map(h => `<th>${h}</th>`).join("") + "</tr>";
    document.getElementById("reportTableBody").innerHTML = rows.length
        ? rows.map(r => "<tr>" + r.map(c => `<td>${c}</td>`).join("") + "</tr>").join("")
        : `<tr><td colspan="${headers.length}" style="text-align:center; padding:20px;">No matching records.</td></tr>`;
}

function renderChart(config) {
    if (currentChart) currentChart.destroy();
    const el = document.getElementById("dynamicReportChart");
    if (typeof Chart === "undefined" || !el) return;
    currentChart = new Chart(el, config);
}

/* ---------------- STUDENTS ---------------- */
function renderStudentsReport() {
    const course = val("f-course");
    const status = val("f-status");

    const rows = reportData.students.filter(s =>
        (course === "All" || s.course.startsWith(course)) &&
        (status === "All" || s.status === status)
    );

    document.getElementById("generatedReportTitle").textContent = "Student Report";
    document.getElementById("printReportTitle").textContent = "Student Report";
    document.getElementById("chartTitle").textContent = "Students by Status";

    setStats([
        { label: "Total Students", value: rows.length, icon: "fa-users", color: "blue" },
        { label: "Active", value: rows.filter(s => s.status === "Active").length, icon: "fa-user-check", color: "green" },
        { label: "Without Room", value: rows.filter(s => !s.room_number).length, icon: "fa-user-slash", color: "orange" },
    ]);

    setTable(
        ["Roll No", "Name", "Course", "Room", "Status"],
        rows.map(s => [s.roll_no, s.name, s.course, s.room_number || "Not allocated", s.status])
    );

    const statuses = ["Active", "Inactive", "Graduated", "Left"];
    renderChart({
        type: "doughnut",
        data: { labels: statuses, datasets: [{ data: statuses.map(st => rows.filter(s => s.status === st).length), backgroundColor: ["#10B981", "#64748B", "#3B82F6", "#EF4444"] }] },
        options: { responsive: true, plugins: { legend: { position: "bottom" } } }
    });
}

/* ---------------- ROOMS ---------------- */
function renderRoomsReport() {
    const block = val("f-block");
    const status = val("f-status");

    const rows = reportData.rooms.filter(r => {
        if (block !== "All" && r.block !== block) return false;
        if (status === "All") return true;
        if (status === "Maintenance") return r.status === "Maintenance";
        if (status === "Occupied") return r.occupied >= r.capacity;
        if (status === "Available") return r.occupied < r.capacity && r.status !== "Maintenance";
        return true;
    });

    document.getElementById("generatedReportTitle").textContent = "Room Allocation Report";
    document.getElementById("printReportTitle").textContent = "Room Allocation Report";
    document.getElementById("chartTitle").textContent = "Bed Occupancy";

    const totalCapacity = rows.reduce((sum, r) => sum + Number(r.capacity), 0);
    const totalOccupied = rows.reduce((sum, r) => sum + Number(r.occupied), 0);

    setStats([
        { label: "Total Rooms", value: rows.length, icon: "fa-door-closed", color: "blue" },
        { label: "Total Beds", value: totalCapacity, icon: "fa-bed", color: "purple" },
        { label: "Occupied Beds", value: totalOccupied, icon: "fa-user", color: "orange" },
    ]);

    setTable(
        ["Room", "Block", "Floor", "Capacity", "Occupied", "Status"],
        rows.map(r => [r.room_number, r.block, r.floor, r.capacity, r.occupied, r.status])
    );

    renderChart({
        type: "doughnut",
        data: { labels: ["Occupied", "Vacant"], datasets: [{ data: [totalOccupied, Math.max(0, totalCapacity - totalOccupied)], backgroundColor: ["#4f46e5", "#e5e7eb"] }] },
        options: { responsive: true, plugins: { legend: { position: "bottom" } } }
    });
}

/* ---------------- FEES ---------------- */
function renderFeesReport() {
    const status = val("f-status");
    const from = val("f-from", "");
    const to = val("f-to", "");

    const rows = reportData.fees.filter(f => {
        if (status !== "All" && f.status !== status) return false;
        if (from && (!f.last_paid_on || f.last_paid_on < from)) return false;
        if (to && (!f.last_paid_on || f.last_paid_on > to)) return false;
        return true;
    });

    document.getElementById("generatedReportTitle").textContent = "Fees Report";
    document.getElementById("printReportTitle").textContent = "Fees Report";
    document.getElementById("chartTitle").textContent = "Collection Status";

    const totalCollected = rows.reduce((s, f) => s + Number(f.paid), 0);
    const totalDue = rows.reduce((s, f) => s + Number(f.due), 0);

    setStats([
        { label: "Records", value: rows.length, icon: "fa-file-invoice", color: "blue" },
        { label: "Collected", value: "₹" + totalCollected.toLocaleString("en-IN"), icon: "fa-check-double", color: "green" },
        { label: "Pending", value: "₹" + totalDue.toLocaleString("en-IN"), icon: "fa-triangle-exclamation", color: "red" },
    ]);

    setTable(
        ["Student", "Room", "Total", "Paid", "Due", "Status"],
        rows.map(f => [f.name, f.room_number || "-", "₹" + Number(f.total_fee).toLocaleString("en-IN"), "₹" + Number(f.paid).toLocaleString("en-IN"), "₹" + Number(f.due).toLocaleString("en-IN"), f.status])
    );

    renderChart({
        type: "pie",
        data: { labels: ["Collected", "Pending"], datasets: [{ data: [totalCollected, totalDue], backgroundColor: ["#22c55e", "#ef4444"] }] },
        options: { responsive: true, plugins: { legend: { position: "bottom" } } }
    });
}

/* ---------------- LEAVE ---------------- */
function renderLeaveReport() {
    const status = val("f-status");
    const from = val("f-from", "");
    const to = val("f-to", "");

    const rows = reportData.leave.filter(l => {
        if (status !== "All" && l.status !== status) return false;
        if (from && l.to_date < from) return false;
        if (to && l.from_date > to) return false;
        return true;
    });

    document.getElementById("generatedReportTitle").textContent = "Leave Report";
    document.getElementById("printReportTitle").textContent = "Leave Report";
    document.getElementById("chartTitle").textContent = "Leave Status Breakdown";

    setStats([
        { label: "Total Requests", value: rows.length, icon: "fa-clipboard-list", color: "purple" },
        { label: "On Leave Now", value: rows.filter(l => l.status === "On Leave").length, icon: "fa-suitcase-rolling", color: "blue" },
        { label: "Upcoming", value: rows.filter(l => l.status === "Upcoming").length, icon: "fa-plane-departure", color: "orange" },
    ]);

    setTable(
        ["Student", "Room", "Type", "From", "To", "Status"],
        rows.map(l => [l.name, l.room_number || "-", l.leave_type, l.from_date, l.to_date, l.status])
    );

    const statuses = ["Upcoming", "On Leave", "Returned", "Cancelled"];
    renderChart({
        type: "bar",
        data: { labels: statuses, datasets: [{ label: "Requests", data: statuses.map(st => rows.filter(l => l.status === st).length), backgroundColor: "#f59e0b" }] },
        options: { responsive: true, plugins: { legend: { display: false } } }
    });
}

/* ---------------- COMPLAINTS ---------------- */
function renderComplaintsReport() {
    const category = val("f-category");
    const status = val("f-status");

    const rows = reportData.complaints.filter(c =>
        (category === "All" || c.category === category) &&
        (status === "All" || c.status === status)
    );

    document.getElementById("generatedReportTitle").textContent = "Complaint Report";
    document.getElementById("printReportTitle").textContent = "Complaint Report";
    document.getElementById("chartTitle").textContent = "Complaints by Category";

    setStats([
        { label: "Total", value: rows.length, icon: "fa-layer-group", color: "purple" },
        { label: "Open", value: rows.filter(c => c.status !== "Resolved" && c.status !== "Closed").length, icon: "fa-bell", color: "orange" },
        { label: "Resolved", value: rows.filter(c => c.status === "Resolved" || c.status === "Closed").length, icon: "fa-circle-check", color: "green" },
    ]);

    setTable(
        ["Student", "Category", "Status", "Date"],
        rows.map(c => [c.name, c.category, c.status, new Date(c.created_at).toLocaleDateString("en-GB")])
    );

    const categories = [...new Set(reportData.complaints.map(c => c.category))];
    renderChart({
        type: "doughnut",
        data: { labels: categories, datasets: [{ data: categories.map(cat => rows.filter(c => c.category === cat).length), backgroundColor: ["#8B5CF6", "#3B82F6", "#F59E0B", "#10B981", "#EF4444"] }] },
        options: { responsive: true, plugins: { legend: { position: "bottom" } } }
    });
}
