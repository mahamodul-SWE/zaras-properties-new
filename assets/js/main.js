(() => {
  const toggle = document.querySelector(".menu-toggle");
  const nav = document.querySelector(".main-nav");
  if (toggle && nav) {
    toggle.addEventListener("click", () => {
      const open = toggle.getAttribute("aria-expanded") === "true";
      toggle.setAttribute("aria-expanded", String(!open));
      nav.classList.toggle("open", !open);
      document.body.classList.toggle("menu-open", !open);
    });
    nav.querySelectorAll("a").forEach(link => link.addEventListener("click", () => {
      toggle.setAttribute("aria-expanded", "false"); nav.classList.remove("open"); document.body.classList.remove("menu-open");
    }));
  }

  const slides = [...document.querySelectorAll(".hero-slide")];
  const dots = [...document.querySelectorAll(".hero-dot")];
  const captions = [
    "Thoughtfully improved homes, made for better living.",
    "Comfortable spaces for everyday life.",
    "Quality details. A more welcoming home.",
    "A considered approach to every property.",
    "Better homes. Brighter futures. Stronger communities."
  ];
  const caption = document.getElementById("hero-caption");
  let active = 0, interval;
  function showSlide(index) {
    if (!slides.length) return;
    active = (index + slides.length) % slides.length;
    slides.forEach((slide, i) => slide.classList.toggle("active", i === active));
    dots.forEach((dot, i) => dot.classList.toggle("active", i === active));
    if (caption) caption.textContent = captions[active] || captions[0];
  }
  function restart() { clearInterval(interval); if (slides.length > 1) interval = setInterval(() => showSlide(active + 1), 5500); }
  dots.forEach(dot => dot.addEventListener("click", () => { showSlide(Number(dot.dataset.slide)); restart(); }));
  showSlide(0); restart();

  document.querySelectorAll("select[id$='-subject']").forEach(select => {
    const form = select.closest("form");
    const other = form?.querySelector(".other-subject");
    const otherInput = other?.querySelector("input");
    function updateOther() {
      const show = select.value === "Other";
      if (other) other.hidden = !show;
      if (otherInput) { otherInput.required = show; if (!show) otherInput.value = ""; }
    }
    select.addEventListener("change", updateOther); updateOther();
  });
})();