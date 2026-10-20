document.addEventListener("DOMContentLoaded", function () {
    if (typeof Chart === "undefined") return;

    // Room Occupancy (doughnut)
    const occupancyEl = document.getElementById("occupancyChart");
    if (occupancyEl && typeof occupancyData !== "undefined") {
        new Chart(occupancyEl, {
            type: "doughnut",
            data: {
                labels: ["Occupied", "Vacant"],
                datasets: [{
                    data: [occupancyData.occupied, occupancyData.vacant],
                    backgroundColor: ["#4f46e5", "#e5e7eb"]
                }]
            },
            options: { responsive: true, plugins: { legend: { position: "bottom" } } }
        });
    }

    // Students by Course (bar)
    const courseEl = document.getElementById("courseChart");
    if (courseEl && typeof courseLabels !== "undefined") {
        new Chart(courseEl, {
            type: "bar",
            data: {
                labels: courseLabels,
                datasets: [{ label: "Students", data: courseCounts, backgroundColor: "#f59e0b" }]
            },
            options: { responsive: true, plugins: { legend: { display: false } } }
        });
    }

    // Fees Status (pie)
    const feesEl = document.getElementById("feesChart");
    if (feesEl && typeof feesData !== "undefined") {
        new Chart(feesEl, {
            type: "pie",
            data: {
                labels: ["Paid", "Due"],
                datasets: [{ data: [feesData.paid, feesData.due], backgroundColor: ["#22c55e", "#ef4444"] }]
            },
            options: { responsive: true, plugins: { legend: { position: "bottom" } } }
        });
    }
});
