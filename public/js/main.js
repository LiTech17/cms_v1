// ===== NGO CMS Frontend Scripts =====

document.addEventListener("DOMContentLoaded", () => {
  const slides = document.querySelectorAll(".slide");
  const body = document.body;
  const themeToggle = document.querySelector(".theme-toggle");
  const navMenu = document.querySelector("nav ul");
  const hamburger = document.querySelector(".hamburger");

  // Hero Carousel
  let current = 0;
  if (slides.length > 0) {
    slides[current].classList.add("active");
    setInterval(() => {
      slides[current].classList.remove("active");
      current = (current + 1) % slides.length;
      slides[current].classList.add("active");
    }, 30000);
  }

  // Theme Toggle
  themeToggle?.addEventListener("click", () => {
    body.classList.toggle("dark");
    localStorage.setItem("theme", body.classList.contains("dark") ? "dark" : "light");
  });

  // Preserve Theme Preference
  if (localStorage.getItem("theme") === "dark") {
    body.classList.add("dark");
  }

  // Hamburger Toggle
  hamburger?.addEventListener("click", () => {
    navMenu.classList.toggle("open");
  });
});


// Retractable Panels (Accordion)
document.querySelectorAll(".retractable .panel-header").forEach(header => {
  header.addEventListener("click", () => {
    const parent = header.parentElement;
    parent.classList.toggle("active");
  });
});

// Scroll Reveal Animation
const reveals = document.querySelectorAll(".reveal, .fade-up");

window.addEventListener("scroll", () => {
  const triggerPoint = window.innerHeight * 0.9;
  reveals.forEach(el => {
    const top = el.getBoundingClientRect().top;
    if (top < triggerPoint) {
      el.classList.add(el.classList.contains("fade-up") ? "visible" : "active");
    }
  });
});
