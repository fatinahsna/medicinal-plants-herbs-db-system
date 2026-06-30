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