document.addEventListener("DOMContentLoaded", () => {
    const defaultJobs = [
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
    const jobs = Array.isArray(window.homepageJobs) && window.homepageJobs.length
        ? window.homepageJobs
        : defaultJobs;

    const searchForm = document.getElementById("heroSearchForm");
    const jobInput = document.getElementById("jobSearchInput");
    const searchResults = document.getElementById("searchResults");
    const searchResultsList = document.getElementById("searchResultsList");
    const searchResultsTitle = document.getElementById("searchResultsTitle");
    const closeSearchResults = document.getElementById("closeSearchResults");
    const categoryCards = document.querySelectorAll(".card[data-category]");
    const scrollSearchButtons = document.querySelectorAll(".search-job-btn");
    const menuToggle = document.querySelector(".menu-toggle");
    const navLinks = document.querySelector(".nav-links");

    function normalize(value) {
        return String(value || "").trim().toLowerCase();
    }

    function filterJobs(jobQuery) {
        const query = normalize(jobQuery);

        return jobs.filter((job) => !query ||
            normalize(job.title).includes(query) ||
            normalize(job.company).includes(query) ||
            normalize(job.category).includes(query)
        );
    }

    function highlightCategoryCards(category) {
        categoryCards.forEach((card) => {
            card.classList.toggle("active", category && card.dataset.category === category);
        });
    }

    function jobUrl(job) {
        const params = new URLSearchParams();
        params.set("search", job.title);
        if (job.category) params.set("category", job.category);
        return `../JobsPage/jobs.php?${params.toString()}`;
    }

    function renderResults(results, jobQuery) {
        searchResultsList.innerHTML = "";

        if (results.length === 0) {
            searchResultsTitle.textContent = "No jobs found";
            searchResultsList.innerHTML = '<li class="search-empty">Try a different keyword.</li>';
            searchResults.hidden = false;
            return;
        }

        searchResultsTitle.textContent = jobQuery.trim()
            ? `${results.length} suggestion${results.length > 1 ? "s" : ""} found`
            : `Showing ${results.length} available jobs`;

        results.slice(0, 8).forEach((job) => {
            const item = document.createElement("li");
            item.tabIndex = 0;
            item.setAttribute("role", "button");
            item.innerHTML = `
                <div class="job-info">
                    <h4>${job.title}</h4>
                    <p>${job.company} - ${job.location}</p>
                </div>
                <span class="job-tag">${job.category}</span>
            `;

            const openJob = () => {
                window.location.href = jobUrl(job);
            };

            item.addEventListener("click", openJob);
            item.addEventListener("keydown", (event) => {
                if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    openJob();
                }
            });
            searchResultsList.appendChild(item);
        });

        searchResults.hidden = false;
    }

    function runSearch() {
        const params = new URLSearchParams();
        const query = jobInput.value.trim();
        if (query) params.set("search", query);
        window.location.href = `../JobsPage/jobs.php${params.toString() ? `?${params.toString()}` : ""}`;
    }

    function scrollToSearch() {
        document.getElementById("hero").scrollIntoView({ behavior: "smooth" });
        setTimeout(() => jobInput.focus(), 500);
    }

    menuToggle?.addEventListener("click", () => {
        const isOpen = navLinks.classList.toggle("active");
        menuToggle.setAttribute("aria-expanded", String(isOpen));
    });

    document.querySelectorAll(".nav-links a").forEach((link) => {
        link.addEventListener("click", () => {
            navLinks.classList.remove("active");
            menuToggle?.setAttribute("aria-expanded", "false");
        });
    });

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
        card.addEventListener("click", () => highlightCategoryCards(card.dataset.category));
    });

    jobInput.addEventListener("input", () => {
        if (normalize(jobInput.value).length >= 2) {
            renderResults(filterJobs(jobInput.value), jobInput.value);
        } else if (!jobInput.value) {
            searchResults.hidden = true;
            highlightCategoryCards("");
        }
    });
});
