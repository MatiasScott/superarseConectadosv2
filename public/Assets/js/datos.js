
document.addEventListener("DOMContentLoaded", function () {
  const tabButtons = document.querySelectorAll(".tab-button");
  const tabPanes = document.querySelectorAll(".tab-pane");

  function activateTab(tabName) {
    tabButtons.forEach((btn) => {
      if (btn.dataset.tab === tabName) {
        btn.classList.add(
          "text-superarse-morado-oscuro",
          "border-superarse-rosa"
        );
        btn.classList.remove("text-gray-600", "border-transparent");
      } else {
        btn.classList.remove(
          "text-superarse-morado-oscuro",
          "border-superarse-rosa"
        );
        btn.classList.add("text-gray-600", "border-transparent");
      }
    });

    tabPanes.forEach((pane) => {
      if (pane.id === tabName) {
        pane.classList.remove("hidden");
      } else {
        pane.classList.add("hidden");
      }
    });
  }

  tabButtons.forEach((button) => {
    button.addEventListener("click", () => {
      activateTab(button.dataset.tab);
    });
  });
  activateTab("informacion");
});
