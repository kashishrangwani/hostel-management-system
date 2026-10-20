/**
 * `freeBeds` is injected by allocation.php as a real JSON array of
 * currently-vacant beds: [{id, bed_label, room_number, block, floor}, ...]
 */
document.addEventListener("DOMContentLoaded", function () {
    setupCascade("allocBlock", "allocFloor", "allocRoom", "allocBed");
    initChangeModal();
    initVacateModal();
    initModalClose();
    initAllocationSearch();
});

/**
 * Wires up a 4-level cascading select (Block -> Floor -> Room -> Bed)
 * driven entirely by the freeBeds array already in the page.
 */
function setupCascade(blockId, floorId, roomId, bedId) {
    const blockSelect = document.getElementById(blockId);
    const floorSelect = document.getElementById(floorId);
    const roomSelect = document.getElementById(roomId);
    const bedSelect = document.getElementById(bedId);
    if (!blockSelect) return;

    const blocks = [...new Set(freeBeds.map(b => b.block))].sort();
    blocks.forEach(b => blockSelect.add(new Option("Block " + b, b)));

    blockSelect.addEventListener("change", function () {
        resetSelect(floorSelect, "Select Floor", true);
        resetSelect(roomSelect, "Select Room", true);
        resetSelect(bedSelect, "Select Bed", true);

        const floors = [...new Set(freeBeds.filter(b => b.block === this.value).map(b => b.floor))].sort();
        floors.forEach(f => floorSelect.add(new Option("Floor " + f, f)));
        floorSelect.disabled = floors.length === 0;
    });

    floorSelect.addEventListener("change", function () {
        resetSelect(roomSelect, "Select Room", true);
        resetSelect(bedSelect, "Select Bed", true);

        const rooms = [...new Set(freeBeds
            .filter(b => b.block === blockSelect.value && b.floor === this.value)
            .map(b => b.room_number))].sort();
        rooms.forEach(r => roomSelect.add(new Option(r, r)));
        roomSelect.disabled = rooms.length === 0;
    });

    roomSelect.addEventListener("change", function () {
        resetSelect(bedSelect, "Select Bed", true);

        const beds = freeBeds.filter(b => b.room_number === this.value);
        beds.forEach(b => bedSelect.add(new Option(b.bed_label, b.id)));
        bedSelect.disabled = beds.length === 0;
    });
}

function resetSelect(select, placeholder, disable) {
    select.innerHTML = `<option value="">${placeholder}</option>`;
    select.disabled = !!disable;
}

/**
 * "Change Room" modal: pre-fills the current allocation info from the
 * clicked button's data-* attributes, then builds a fresh Room -> Bed
 * cascade (2 levels, since block/floor aren't shown in this modal).
 */
function initChangeModal() {
    document.querySelectorAll(".open-change-modal").forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("cr-studentName").textContent = this.dataset.name;
            document.getElementById("cr-currentRoom").textContent = this.dataset.room;
            document.getElementById("cr-currentBed").textContent = this.dataset.bed;
            document.getElementById("cr-oldBedId").value = this.dataset.bedId;

            const roomSelect = document.getElementById("cr-room");
            const bedSelect = document.getElementById("cr-bed");
            resetSelect(roomSelect, "Select Room", false);
            resetSelect(bedSelect, "Select Bed", true);

            const rooms = [...new Set(freeBeds.map(b => b.room_number))].sort();
            rooms.forEach(r => roomSelect.add(new Option(r, r)));

            roomSelect.onchange = function () {
                resetSelect(bedSelect, "Select Bed", true);
                const beds = freeBeds.filter(b => b.room_number === this.value);
                beds.forEach(b => bedSelect.add(new Option(b.bed_label, b.id)));
                bedSelect.disabled = beds.length === 0;
            };

            document.getElementById("changeRoomModal").classList.add("show");
        });
    });
}

/**
 * "Vacate Room" modal: just needs the bed id and display info.
 */
function initVacateModal() {
    document.querySelectorAll(".open-vacate-modal").forEach(btn => {
        btn.addEventListener("click", function () {
            document.getElementById("vr-studentName").textContent = this.dataset.name;
            document.getElementById("vr-currentRoom").textContent = this.dataset.room;
            document.getElementById("vr-currentBed").textContent = this.dataset.bed;
            document.getElementById("vr-bedId").value = this.dataset.bedId;
            document.getElementById("vacateRoomModal").classList.add("show");
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

function initAllocationSearch() {
    const input = document.getElementById("allocationSearch");
    const rows = document.querySelectorAll("#currentAllocationsTable tbody tr");
    if (!input || !rows.length) return;

    input.addEventListener("keyup", function () {
        const query = this.value.toLowerCase();
        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(query) ? "" : "none";
        });
    });
}
