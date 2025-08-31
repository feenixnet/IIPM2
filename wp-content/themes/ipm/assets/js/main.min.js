// Gallery Swiper initialization
document.addEventListener("DOMContentLoaded", function () {
  // Only initialize Swiper if .gallerySwiper exists
  const gallerySwiper = document.querySelector(".gallerySwiper");
  if (gallerySwiper) {
    new Swiper(".gallerySwiper", {
      slidesPerView: 4,
      spaceBetween: 5,
      loop: true,
      freeMode: true,
      autoplay: {
        delay: 2500,
        disableOnInteraction: false,
      },
      speed: 1500,
      breakpoints: {
        1024: {
          slidesPerView: 4,
          spaceBetween: 5,
        },
        768: {
          slidesPerView: 2,
          spaceBetween: 5,
        },
        0: {
          slidesPerView: 1,
          spaceBetween: 5,
        },
      },
    });
  }
});

// Home page body class
document.addEventListener("DOMContentLoaded", function () {
  if (window.location.pathname === "/") {
    document.body.classList.add("home");
  }
});

// Mobile menu functionality
document.addEventListener("DOMContentLoaded", function () {
  const burger = document.getElementById("burger");
  const header = document.querySelector(".header");
  const body = document.body;
  const overlay = document.querySelector(".overlay");

  // Only add event listeners if all elements exist
  if (burger && header && overlay) {
    burger.addEventListener("click", function () {
      const isOpen = header.classList.toggle("open");
      body.classList.toggle("open");

      // Prevent scrolling when menu is open
      document.documentElement.style.overflow = isOpen ? "hidden" : "";
      body.style.overflow = isOpen ? "hidden" : "";

      // Show/hide overlay
      overlay.style.visibility = isOpen ? "visible" : "hidden";
      overlay.style.opacity = isOpen ? "1" : "0";
    });

    // Close menu when clicking overlay
    overlay.addEventListener("click", function () {
      header.classList.remove("open");
      body.classList.remove("open");

      // Restore scrolling
      document.documentElement.style.overflow = "";
      body.style.overflow = "";

      // Hide overlay
      overlay.style.visibility = "hidden";
      overlay.style.opacity = "0";
    });
  }
});
