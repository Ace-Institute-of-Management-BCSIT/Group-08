/* ===========================
   Handles signup form submission, OTP verification, and UI state transitions.
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

const signupForm = document.getElementById("signupForm");
const emailInput = document.getElementById("email");
const sendOtpBtn = document.getElementById("sendOtpBtn");
const verifyOtpBtn = document.getElementById("verifyOtpBtn");
const otpInput = document.getElementById("otpInput");
const otpSection = document.getElementById("otpSection");
const otpStatus = document.getElementById("otpStatus");
const emailVerifiedInput = document.getElementById("emailVerified");

let verifiedEmail = "";
let isEmailVerified = false;

    // ===========================
    // Helper Functions
    // ===========================

function setOtpStatus(message, type) {
    otpStatus.textContent = message;
    otpStatus.className = "otp-status" + (type ? " " + type : "");
}

function resetEmailVerification() {
    isEmailVerified = false;
    verifiedEmail = "";
    emailVerifiedInput.value = "0";
    emailInput.classList.remove("verified");
    otpInput.value = "";
    setOtpStatus("", "");
}

emailInput.addEventListener("input", () => {
    const currentEmail = emailInput.value.trim().toLowerCase();
    if (isEmailVerified && currentEmail !== verifiedEmail) {
        resetEmailVerification();
    }
});

sendOtpBtn.addEventListener("click", async () => {
    const email = emailInput.value.trim();

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert("Please enter a valid email address before requesting an OTP.");
        emailInput.focus();
        return;
    }

    sendOtpBtn.disabled = true;
    sendOtpBtn.textContent = "Sending...";
    setOtpStatus("Sending OTP to your email...", "info");

    try {
        const response = await fetch("send_otp.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ email })
        });
        const data = await response.json();

        if (data.success) {
            resetEmailVerification();
            otpSection.classList.add("visible");
            setOtpStatus(data.message || "OTP sent successfully. Check your inbox.", "success");
            otpInput.focus();
        } else {
            setOtpStatus(data.error || "Could not send OTP.", "error");
        }
    } catch (error) {
        setOtpStatus("Network error. Please try again.", "error");
    } finally {
        sendOtpBtn.disabled = false;
        sendOtpBtn.textContent = "Send OTP";
    }
});

verifyOtpBtn.addEventListener("click", async () => {
    const email = emailInput.value.trim();
    const otp = otpInput.value.trim();

    if (!email) {
        alert("Please enter your email address.");
        return;
    }

    if (!/^\d{6}$/.test(otp)) {
        alert("Please enter the 6-digit OTP from your email.");
        otpInput.focus();
        return;
    }

    verifyOtpBtn.disabled = true;
    verifyOtpBtn.textContent = "Verifying...";
    setOtpStatus("Verifying OTP...", "info");

    try {
        const response = await fetch("verify_otp.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ email, otp })
        });
        const data = await response.json();

        if (data.success) {
            isEmailVerified = true;
            verifiedEmail = email.toLowerCase();
            emailVerifiedInput.value = "1";
            emailInput.classList.add("verified");
            setOtpStatus("Email verified. You can complete sign up.", "success");
        } else {
            isEmailVerified = false;
            emailVerifiedInput.value = "0";
            emailInput.classList.remove("verified");
            setOtpStatus(data.error || "Verification failed.", "error");
        }
    } catch (error) {
        setOtpStatus("Network error. Please try again.", "error");
    } finally {
        verifyOtpBtn.disabled = false;
        verifyOtpBtn.textContent = "Verify";
    }
});

signupForm.addEventListener("submit", function (event) {
    const username = document.getElementById("username").value.trim();
    const fullName = document.getElementById("fullName").value.trim();
    const email = emailInput.value.trim();
    const password = document.getElementById("password").value.trim();
    const confirmPassword = document.getElementById("confirmPassword").value.trim();

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

    if (!isEmailVerified || email.toLowerCase() !== verifiedEmail) {
        event.preventDefault();
        alert("Please verify your email with the OTP before signing up.");
        if (!otpSection.classList.contains("visible")) {
            otpSection.classList.add("visible");
        }
        return;
    }

    if (password.length < 6) {
        event.preventDefault();
        alert("Password must be at least 6 characters long.");
        return;
    }

    if (password !== confirmPassword) {
        event.preventDefault();
        alert("Passwords do not match.");
        return;
    }
});
