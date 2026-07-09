/* ===========================
   Handles interactive behavior on the worker details page.
=========================== */

const buttons=document.querySelectorAll(".person-card button");

buttons.forEach(button=>{

button.addEventListener("click",()=>{

alert("Request sent successfully!");

});

});

const apply=document.querySelector(".apply-btn");

apply.addEventListener("click",()=>{

alert("Job application submitted!");

});
