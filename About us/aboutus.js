const signup=document.querySelector(".signup-btn");

signup.addEventListener("click",()=>{

window.location.href="login.html";

});


const cards=document.querySelectorAll(".card");


cards.forEach(card=>{

card.addEventListener("mouseenter",()=>{

card.style.transform="translateY(-5px)";

});


card.addEventListener("mouseleave",()=>{

card.style.transform="translateY(0)";

});

});