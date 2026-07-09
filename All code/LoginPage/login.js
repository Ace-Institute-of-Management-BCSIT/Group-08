/* ===========================
   Handles login page navigation and UI interactions.
=========================== */

// Mobile Navigation Toggle
const menuToggle = document.querySelector(".menu-toggle");
const navLinks = document.querySelector(".nav-links");

menuToggle.addEventListener("click", () => {
    navLinks.classList.toggle("active");
});

// Close menu when a navigation link is clicked
document.querySelectorAll(".nav-links a").forEach(link => {
    link.addEventListener("click", () => {
        navLinks.classList.remove("active");
    });
});

// Login Form Validation
const loginForm = document.getElementById("loginForm");

loginForm.addEventListener("submit", function (event) {

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();

    if (username === "" || password === "") {
        event.preventDefault();
        alert("Please fill in all fields.");
        return;
    }

    if (password.length < 6) {
        event.preventDefault();
        alert("Password must be at least 6 characters long.");
        return;
    }
});
