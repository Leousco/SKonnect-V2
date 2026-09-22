



window.addEventListener("pageshow", function (event) {
  if (event.persisted) {
    window.location.reload();
  }
});

document.addEventListener("DOMContentLoaded", function () {

    
    const navbarToggle = document.getElementById("navbarToggle");
    const navbarMenu = document.getElementById("navbarMenu");

    if (navbarToggle && navbarMenu) {
        navbarToggle.addEventListener("click", function () {
            navbarMenu.classList.toggle("active");
        });
    }

    
    const counters = document.querySelectorAll(".counter");
    counters.forEach(counter => {
        const updateCount = () => {
            const target = +counter.getAttribute("data-target");
            const count = +counter.innerText;
            const increment = target / 100;

            if (count < target) {
                counter.innerText = Math.ceil(count + increment);
                setTimeout(updateCount, 20);
            } else {
                counter.innerText = target + "+";
            }
        };
        updateCount();
    });

});