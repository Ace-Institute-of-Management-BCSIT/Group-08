const menuToggle = document.querySelector(".menu-toggle");
const navLinks = document.querySelector(".nav-links");
const form = document.querySelector("#jobFilterForm");
const jobSearch = document.querySelector("#jobSearch");
const locationFilter = document.querySelector("#locationFilter");
const salaryRange = document.querySelector("#salaryRange");
const salaryValue = document.querySelector("#salaryValue");
const jobCards = Array.from(document.querySelectorAll(".job-card"));
const tagButtons = Array.from(document.querySelectorAll(".tags button"));
const pageLinks = Array.from(document.querySelectorAll("[data-page-link]"));
let currentPage = "1";
let shouldScrollToResults = false;

function formatCurrency(value) {
return Number(value).toLocaleString("en-IN");
}

function checkedValues(name) {
return Array.from(document.querySelectorAll(`input[name="${name}"]:checked`)).map(input => input.value);
}

function updatePagination() {
pageLinks.forEach(link => {
link.classList.toggle("active", link.dataset.pageLink === currentPage);
});
}

function applyFilters() {
const search = jobSearch.value.trim().toLowerCase();
const location = locationFilter.value;
const categories = checkedValues("category");
const types = checkedValues("type");
const maxSalary = Number(salaryRange.value);
const hasFilters = Boolean(search || location || categories.length || types.length || maxSalary < Number(salaryRange?.max || maxSalary));
let visible = 0;

jobCards.forEach(card => {
const matchesPage = hasFilters || card.dataset.page === currentPage;
const searchableText = `${card.dataset.title} ${card.dataset.category} ${card.dataset.location} ${card.dataset.type}`.toLowerCase();
const matchesSearch = !search || searchableText.includes(search);
const matchesLocation = !location || card.dataset.location === location;
const matchesCategory = categories.length === 0 || categories.includes(card.dataset.category);
const matchesType = types.length === 0 || types.includes(card.dataset.type);
const matchesSalary = Number(card.dataset.salary) <= maxSalary;
const show = matchesPage && matchesSearch && matchesLocation && matchesCategory && matchesType && matchesSalary;

card.hidden = !show;
if (show) visible += 1;
});

updatePagination();
if (shouldScrollToResults) {
document.querySelector(".job-container")?.scrollIntoView({ behavior: "smooth", block: "start" });
shouldScrollToResults = false;
}
}

menuToggle?.addEventListener("click", () => {
const isOpen = navLinks.classList.toggle("active");
menuToggle.setAttribute("aria-expanded", String(isOpen));
});

document.querySelectorAll(".nav-links a").forEach(link => {
link.addEventListener("click", () => {
navLinks.classList.remove("active");
menuToggle?.setAttribute("aria-expanded", "false");
});
});

form?.addEventListener("submit", event => {
event.preventDefault();
shouldScrollToResults = true;
if (jobCards.length) {
applyFilters();
}
});

form?.addEventListener("input", () => {
shouldScrollToResults = true;
applyFilters();
});
locationFilter?.addEventListener("change", () => {
shouldScrollToResults = true;
applyFilters();
});

salaryRange?.addEventListener("input", () => {
salaryValue.textContent = formatCurrency(salaryRange.value);
});

tagButtons.forEach(button => {
button.addEventListener("click", () => {
const value = button.dataset.tag;
const checkbox = document.querySelector(`input[name="category"][value="${value}"]`);

button.classList.toggle("active");
if (checkbox) {
checkbox.checked = button.classList.contains("active");
}
shouldScrollToResults = true;
applyFilters();
});
});

pageLinks.forEach(link => {
link.addEventListener("click", event => {
event.preventDefault();
currentPage = link.dataset.pageLink === "next"
? currentPage === "1" ? "2" : "1"
: link.dataset.pageLink;
shouldScrollToResults = true;
applyFilters();
});
});

applyFilters();
