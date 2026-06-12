const menuToggle=document.querySelector(".menu-toggle");
const navLinks=document.querySelector(".nav-links");

menuToggle.addEventListener("click",()=>{

navLinks.classList.toggle("active");

});


document.querySelectorAll(".nav-links a").forEach(link=>{

link.addEventListener("click",()=>{

navLinks.classList.remove("active");

});

});



document.querySelectorAll(".job-info button").forEach(button=>{

button.addEventListener("click",()=>{

alert("Opening job details...");

});

});