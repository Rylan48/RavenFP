document.documentElement.style.overflow = "auto";
document.body.style.overflow = "auto";

(function () {
  "use strict";

  /*
   * Header scroll state
   */
  function toggleScrolled() {
    const body = document.body;
    const header = document.querySelector("#header");

    if (!header) return;

    if (
      !header.classList.contains("scroll-up-sticky") &&
      !header.classList.contains("sticky-top") &&
      !header.classList.contains("fixed-top")
    ) {
      return;
    }

    if (window.scrollY > 100) {
      body.classList.add("scrolled");
    } else {
      body.classList.remove("scrolled");
    }
  }

  window.addEventListener("scroll", toggleScrolled);
  window.addEventListener("load", toggleScrolled);


  /*
   * Mobile navigation
   */
  const mobileNavToggleBtn = document.querySelector(".mobile-nav-toggle");

  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.addEventListener("click", function () {
      document.body.classList.toggle("mobile-nav-active");

      this.classList.toggle("bi-list");
      this.classList.toggle("bi-x");
    });
  }


  /*
   * Close mobile navigation when a navigation link is clicked
   */
  document.querySelectorAll("#navmenu a").forEach(function (link) {
    link.addEventListener("click", function () {
      if (document.body.classList.contains("mobile-nav-active")) {
        document.body.classList.remove("mobile-nav-active");

        if (mobileNavToggleBtn) {
          mobileNavToggleBtn.classList.remove("bi-x");
          mobileNavToggleBtn.classList.add("bi-list");
        }
      }
    });
  });


  /*
   * Mobile dropdowns
   */
  document.querySelectorAll(".navmenu .toggle-dropdown").forEach(function (dropdown) {
    dropdown.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      this.parentNode.classList.toggle("active");

      if (this.parentNode.nextElementSibling) {
        this.parentNode.nextElementSibling.classList.toggle("dropdown-active");
      }
    });
  });


  /*
   * Preloader
   */
  const preloader = document.querySelector("#preloader");

  if (preloader) {
    window.addEventListener("load", function () {
      preloader.remove();
    });
  }


  /*
   * Scroll-to-top button
   */
  const scrollTop = document.querySelector(".scroll-top");

  function toggleScrollTop() {
    if (!scrollTop) return;

    if (window.scrollY > 100) {
      scrollTop.classList.add("active");
    } else {
      scrollTop.classList.remove("active");
    }
  }

  if (scrollTop) {
    scrollTop.addEventListener("click", function (e) {
      e.preventDefault();

      window.scrollTo({
        top: 0,
        behavior: "smooth"
      });
    });
  }

  window.addEventListener("scroll", toggleScrollTop);
  window.addEventListener("load", toggleScrollTop);


  /*
   * AOS
   *
   * Only initialize it if the library is actually loaded.
   */
  window.addEventListener("load", function () {
    if (typeof AOS !== "undefined") {
      AOS.init({
        duration: 600,
        easing: "ease-in-out",
        once: true,
        mirror: false
      });
    }
  });


  /*
   * Smooth scrolling for navigation links
   *
   * IMPORTANT:
   * This uses a delegated click handler so it works
   * even if elements are added later.
   */
  document.addEventListener("click", function (e) {

    const link = e.target.closest('a[href^="#"]');

    if (!link) return;

    const href = link.getAttribute("href");

    /*
     * Ignore empty "#" links.
     */
    if (!href || href === "#") return;

    /*
     * Ignore links belonging to the request-service modal.
     */
    if (
      link.classList.contains("request-service-trigger") ||
      link.closest("#request-service-modal")
    ) {
      return;
    }

    const target = document.querySelector(href);

    if (!target) return;

    /*
     * Stop the browser's native jump.
     */
    e.preventDefault();

    /*
     * Calculate the position manually.
     * This avoids scrollIntoView() interacting with
     * nested/hidden elements.
     */
    const header = document.querySelector("#header");

    const headerHeight = header
      ? header.getBoundingClientRect().height
      : 0;

    const targetPosition =
      target.getBoundingClientRect().top +
      window.pageYOffset -
      headerHeight;

    window.scrollTo({
      top: targetPosition,
      behavior: "smooth"
    });

    /*
     * Remove the hash without causing another navigation.
     */
    history.replaceState(
      null,
      "",
      window.location.pathname + window.location.search
    );

    /*
     * Close mobile navigation.
     */
    if (document.body.classList.contains("mobile-nav-active")) {
      document.body.classList.remove("mobile-nav-active");

      if (mobileNavToggleBtn) {
        mobileNavToggleBtn.classList.remove("bi-x");
        mobileNavToggleBtn.classList.add("bi-list");
      }
    }
  });

})();