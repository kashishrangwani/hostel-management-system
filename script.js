/**
 * HostelHub - Student Portal UI Behavior
 * All form submissions and data are now handled server-side by PHP.
 * This file only handles pure UI interactions: mobile nav, password
 * show/hide toggles, and the live search/filter on the complaints table.
 */

document.addEventListener("DOMContentLoaded", function () {
  initNavbar();
  highlightActiveNavLink();
  initPasswordToggles();
  initTableSearch();
});

/**
 * ----------------------------------------------------
 * 1. NAVBAR & MOBILE MENU
 * ----------------------------------------------------
 */
function initNavbar() {
  const hamburger = document.getElementById("hamburgerBtn");
  const navMenu = document.getElementById("navMenu");

  if (hamburger && navMenu) {
    hamburger.addEventListener("click", function () {
      hamburger.classList.toggle("active");
      navMenu.classList.toggle("active");
    });

    const navLinks = navMenu.querySelectorAll(".nav-link");
    navLinks.forEach(link => {
      link.addEventListener("click", () => {
        hamburger.classList.remove("active");
        navMenu.classList.remove("active");
      });
    });
  }
}

// Highlights the current page in the navigation bar (a fallback -
// pages also set the "active" class server-side via PHP).
function highlightActiveNavLink() {
  const currentPath = window.location.pathname.split("/").pop() || "index.php";
  document.querySelectorAll(".nav-link").forEach(link => {
    if (link.getAttribute("href") === currentPath) {
      link.classList.add("active");
    }
  });
}

/**
 * ----------------------------------------------------
 * 2. SHOW / HIDE PASSWORD TOGGLE
 * ----------------------------------------------------
 */
function initPasswordToggles() {
  document.querySelectorAll(".toggle-password-btn").forEach(btn => {
    btn.addEventListener("click", function () {
      const input = document.getElementById(btn.getAttribute("data-target"));
      if (!input) return;
      const showing = input.type === "password";
      input.type = showing ? "text" : "password";
      btn.textContent = showing ? "Hide" : "Show";
    });
  });
}

/**
 * ----------------------------------------------------
 * 3. COMPLAINT TABLE SEARCH / FILTER (table.php)
 * Rows are rendered by PHP with real data; this just
 * shows/hides rows already in the DOM based on the
 * search box and status dropdown.
 * ----------------------------------------------------
 */
function initTableSearch() {
  const searchInput = document.getElementById("tableSearchInput");
  const statusFilter = document.getElementById("tableStatusFilter");
  const tableBody = document.getElementById("complaintTableBody");
  if (!tableBody) return;

  function applyFilter() {
    const query = (searchInput?.value || "").toLowerCase();
    const status = statusFilter?.value || "All";

    tableBody.querySelectorAll("tr").forEach(row => {
      const rowText = row.innerText.toLowerCase();
      const rowStatus = row.querySelector(".badge")?.textContent.trim() || "";

      const matchesSearch = rowText.includes(query);
      const matchesStatus = status === "All" || rowStatus === status;

      row.style.display = (matchesSearch && matchesStatus) ? "" : "none";
    });
  }

  if (searchInput) searchInput.addEventListener("keyup", applyFilter);
  if (statusFilter) statusFilter.addEventListener("change", applyFilter);
}
