const theme = document.getElementById("themeToggle");
const icon = document.getElementById("icon");

theme.addEventListener("click",()=> {
	if (document.body.getAttribute("data-theme") === "dark"){
		document.body.removeAttribute("data-theme");
		icon.textContent = "🌙";
	}else{
		document.body.setAttribute("data-theme","dark");
		icon.textContent="☀️";
	}
});

const filterBtn = document.getElementById("filterBtn");
const filterPopup = document.getElementById("filterPopup");
filterBtn.addEventListener("click", () => {
	if(filterPopup.style.display === "block"){
		filterPopup.style.display = "none";
	}else{
		filterPopup.style.display = "block";
	}
});

const categoryBtn = document.getElementById("categoryBtn");
const originBtn = document.getElementById("originBtn");
const categorySection = document.getElementById("categorySection");
const originSection = document.getElementById("originSection");
categorySection.style.display = "none";
originSection.style.display = "none";
categoryBtn.addEventListener("click", function(){
    categoryBtn.classList.toggle("active");
    if(categoryBtn.classList.contains("active")){
        categorySection.style.display = "block";
    }else{
        categorySection.style.display = "none";
    }
});

originBtn.addEventListener("click", function(){
    originBtn.classList.toggle("active");
    if(originBtn.classList.contains("active")){
        originSection.style.display = "block";
    }else{
        originSection.style.display = "none";
    }
});

const resetBtn = document.getElementById("resetBtn");
resetBtn.addEventListener("click", function () {
    const checkboxes = document.querySelectorAll(
        '#filterPopup input[type="checkbox"]'
    );

    checkboxes.forEach(function (checkbox) {
        checkbox.checked = false;
    });
});
const cards = document.querySelectorAll(".plant-card");
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
let current = 0;
function updateCarousel(){
    cards.forEach(card=>{
        card.classList.remove("active-card","left-card","right-card","hide-card");
    });
	
    const total = cards.length;
    const left = (current - 1 + total) % total;
    const right = (current + 1) % total;
    cards.forEach((card,index)=>{
        if(index === current){
            card.classList.add("active-card");
            card.style.left = "50%";
            card.style.top = "50%";
            card.style.transform = "translate(-50%,-50%) scale(1.15)";
        }
        else if(index === left){
            card.classList.add("left-card");
            card.style.left = "25%";
            card.style.top = "50%";
            card.style.transform = "translate(-50%,-50%) scale(.85)";
        }
        else if(index === right){
            card.classList.add("right-card");
            card.style.left = "75%";
            card.style.top = "50%";
            card.style.transform = "translate(-50%,-50%) scale(.85)";
        }
        else{
			card.classList.add("hide-card");
            card.style.left = "50%";
            card.style.top = "50%";
        }
    });
}
function nextSlide(){
    current++;
    if(current >= cards.length){
        current = 0;
    }
    updateCarousel();
}
function prevSlide(){
    current--;
    if(current < 0){
        current = cards.length - 1;
    }
    updateCarousel();
}
nextBtn.addEventListener("click", nextSlide);
prevBtn.addEventListener("click", prevSlide);
cards.forEach((card,index)=>{
    card.addEventListener("click",()=>{
        current = index;
        updateCarousel();
    });
});
let autoSlide = setInterval(nextSlide,3000);
const carouselContainer = document.querySelector(".carousel-container");
carouselContainer.addEventListener("mouseenter",()=>{
    clearInterval(autoSlide);
});
carouselContainer.addEventListener("mouseleave",()=>{
    autoSlide = setInterval(nextSlide,3000);
});
updateCarousel();