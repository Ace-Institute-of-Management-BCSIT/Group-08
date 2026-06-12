document.addEventListener("DOMContentLoaded", () => {

    const jobs = [
        { title: "House Helper", company: "Ghar Sathi Care", location: "Kathmandu", category: "House Work" },
        { title: "Garden Assistant", company: "Green Home Services", location: "Lalitpur", category: "House Work" },
        { title: "Private Chef", company: "Kitchen Pro Nepal", location: "Kathmandu", category: "Culinary Aid" },
        { title: "Meal Prep Assistant", company: "Daily Bites", location: "Bhaktapur", category: "Culinary Aid" },
        { title: "Elder Care Assistant", company: "Comfort Care", location: "Kathmandu", category: "Self Care" },
        { title: "Personal Care Helper", company: "Wellness Home", location: "Lalitpur", category: "Self Care" },
        { title: "Plumber", company: "Quick Fix Nepal", location: "Kathmandu", category: "Plumbing" },
        { title: "Pipe Repair Specialist", company: "AquaFlow Services", location: "Bhaktapur", category: "Plumbing" },
        { title: "House Cleaner", company: "Sparkle Clean", location: "Lalitpur", category: "House Cleaning" },
        { title: "Deep Cleaning Staff", company: "Fresh Spaces", location: "Kathmandu", category: "House Cleaning" },
        { title: "Math Tutor", company: "Bright Minds", location: "Kathmandu", category: "Home Tuition" },
        { title: "English Tutor", company: "Learn at Home", location: "Bhaktapur", category: "Home Tuition" },
        { title: "Laptop Repair Technician", company: "TechFix Nepal", location: "Kathmandu", category: "Tech Repair" },
        { title: "Mobile Repair Expert", company: "Gadget Care", location: "Lalitpur", category: "Tech Repair" },
        { title: "Dog Walker", company: "Pet Pals", location: "Kathmandu", category: "Pet Care" },
        { title: "Pet Sitter", company: "Happy Tails", location: "Bhaktapur", category: "Pet Care" }
    ];

    const searchForm = document.getElementById("heroSearchForm");
    const jobInput = document.getElementById("jobSearchInput");
    const locationSelect = document.getElementById("locationSelect");
    const categorySelect = document.getElementById("categorySelect");
    const searchBtn = document.getElementById("heroSearchBtn");
    const searchResults = document.getElementById("searchResults");
    const searchResultsList = document.getElementById("searchResultsList");
    const searchResultsTitle = document.getElementById("searchResultsTitle");
    const closeSearchResults = document.getElementById("closeSearchResults");
    const categoryCards = document.querySelectorAll(".card[data-category]");
    const scrollSearchButtons = document.querySelectorAll(".search-job-btn");

    function normalize(value) {
        return value.trim().toLowerCase();
    }

    function filterJobs(jobQuery, location, category) {
        const query = normalize(jobQuery);

        return jobs.filter((job) => {
            const matchesQuery = !query ||
                normalize(job.title).includes(query) ||
                normalize(job.company).includes(query) ||
                normalize(job.category).includes(query);

            const matchesLocation = !location || job.location === location;
            const matchesCategory = !category || job.category === category;

            return matchesQuery && matchesLocation && matchesCategory;
        });
    }

    function highlightCategoryCards(category) {
        categoryCards.forEach((card) => {
            card.classList.toggle("active", category && card.dataset.category === category);
        });
    }

    function renderResults(results, jobQuery, location, category) {
        searchResultsList.innerHTML = "";

        if (results.length === 0) {
            searchResultsTitle.textContent = "No jobs found";
            searchResultsList.innerHTML =
                '<li class="search-empty">Try a different keyword, location, or category.</li>';
            searchResults.hidden = false;
            return;
        }

        const filters = [jobQuery, location, category].filter(Boolean);
        searchResultsTitle.textContent = filters.length
            ? `${results.length} job${results.length > 1 ? "s" : ""} found`
            : `Showing ${results.length} available jobs`;

        results.forEach((job) => {
            const item = document.createElement("li");
            item.innerHTML = `
                <div class="job-info">
                    <h4>${job.title}</h4>
                    <p>${job.company} · ${job.location}</p>
                </div>
                <span class="job-tag">${job.category}</span>
            `;
            searchResultsList.appendChild(item);
        });

        searchResults.hidden = false;
    }

    function runSearch() {
        const jobQuery = jobInput.value;
        const location = locationSelect.value;
        const category = categorySelect.value;

        searchBtn.disabled = true;
        searchBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Searching...';

        setTimeout(() => {
            const results = filterJobs(jobQuery, location, category);
            renderResults(results, jobQuery, location, category);
            highlightCategoryCards(category);

            if (category) {
                document.getElementById("categories").scrollIntoView({ behavior: "smooth", block: "start" });
            }

            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Search Job';
        }, 450);
    }

    function scrollToSearch() {
        document.getElementById("hero").scrollIntoView({ behavior: "smooth" });
        setTimeout(() => jobInput.focus(), 500);
    }

    searchForm.addEventListener("submit", (event) => {
        event.preventDefault();
        runSearch();
    });

    closeSearchResults.addEventListener("click", () => {
        searchResults.hidden = true;
    });

    scrollSearchButtons.forEach((button) => {
        button.addEventListener("click", scrollToSearch);
    });

    categoryCards.forEach((card) => {
        const selectCategory = () => {
            categorySelect.value = card.dataset.category;
            highlightCategoryCards(card.dataset.category);
            scrollToSearch();
            runSearch();
        };

        card.addEventListener("click", selectCategory);
        card.addEventListener("keydown", (event) => {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                selectCategory();
            }
        });
    });

    jobInput.addEventListener("input", () => {
        if (normalize(jobInput.value).length >= 2) {
            const results = filterJobs(jobInput.value, locationSelect.value, categorySelect.value);
            renderResults(results, jobInput.value, locationSelect.value, categorySelect.value);
        } else if (!jobInput.value && !locationSelect.value && !categorySelect.value) {
            searchResults.hidden = true;
            highlightCategoryCards("");
        }
    });

    locationSelect.addEventListener("change", () => {
        if (jobInput.value || locationSelect.value || categorySelect.value) {
            runSearch();
        }
    });

    categorySelect.addEventListener("change", () => {
        highlightCategoryCards(categorySelect.value);
        if (jobInput.value || locationSelect.value || categorySelect.value) {
            runSearch();
        }
    });

});
