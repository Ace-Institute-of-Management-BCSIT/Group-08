/* ===========================
   Handles Contact Us page navigation interactions.
=========================== */

// ===== Contact Form Handling =====
const contactForm = document.getElementById('contactForm');

contactForm?.addEventListener('submit', function (e) {
  const firstName = document.getElementById('firstName').value.trim();
  const lastName = document.getElementById('lastName').value.trim();
  const email = document.getElementById('email').value.trim();
  const message = document.getElementById('message').value.trim();

  if (!firstName || !lastName || !email || !message) {
    e.preventDefault();
    alert('Please fill in all fields before sending.');
    return;
  }

  if (!validateEmail(email)) {
    e.preventDefault();
    alert('Please enter a valid email address.');
  }
});

// ===== Newsletter Subscribe Form =====
const subscribeForm = document.getElementById('subscribeForm');

subscribeForm?.addEventListener('submit', function (e) {
  const emailInput = subscribeForm.querySelector('input[type="email"]');
  const email = emailInput.value.trim();

  if (!validateEmail(email)) {
    e.preventDefault();
    alert('Please enter a valid email address to subscribe.');
  }
});

// ===== Email Validation Helper =====

    // ===========================
    // Helper Functions
    // ===========================

function validateEmail(email) {
  const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return re.test(email);
}
