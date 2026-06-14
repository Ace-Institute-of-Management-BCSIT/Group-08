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

// Signup Form Validation
const signupForm = document.getElementById("signupForm");

signupForm.addEventListener("submit", function (event) {

    const username = document.getElementById("username").value.trim();
    const fullName = document.getElementById("fullName").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confirmPassword").value.trim();

    // Check if all fields are filled
    if (fullName === "" || username === "" || email === "" || password === "" || confirmPassword === "") {
        event.preventDefault();
        alert("Please fill in all fields.");
        return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        event.preventDefault();
        alert("Please enter a valid email address.");
        return;
    }

    // Password length validation
    if (password.length < 6) {
        event.preventDefault();
        alert("Password must be at least 6 characters long.");
        return;
    }

    // Confirm password validation
    if (password !== confirmPassword) {
        event.preventDefault();
        alert("Passwords do not match.");
        return;
    }
});
