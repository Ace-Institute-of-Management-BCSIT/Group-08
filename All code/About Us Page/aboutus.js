const cards=document.querySelectorAll(".card");


cards.forEach(card=>{

card.addEventListener("mouseenter",()=>{

card.style.transform="translateY(-5px)";

});


card.addEventListener("mouseleave",()=>{

card.style.transform="translateY(0)";

});

});

const resumeModal = document.querySelector("#resumeModal");
const openResumeButtons = document.querySelectorAll(".js-open-resume");
const closeResumeButton = document.querySelector(".modal-close");

function setResumeModal(open) {
if (!resumeModal) return;
resumeModal.classList.toggle("is-open", open);
resumeModal.setAttribute("aria-hidden", String(!open));
}

openResumeButtons.forEach(button => {
button.addEventListener("click", event => {
event.preventDefault();
setResumeModal(true);
});
});

closeResumeButton?.addEventListener("click", () => setResumeModal(false));
resumeModal?.addEventListener("click", event => {
if (event.target === resumeModal) {
setResumeModal(false);
}
});
