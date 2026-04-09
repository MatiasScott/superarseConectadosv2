
document.addEventListener("DOMContentLoaded", function () {
  const tabButtons = document.querySelectorAll(".tab-button");
  const tabPanes = document.querySelectorAll(".tab-pane");
  const searchParams = new URLSearchParams(window.location.search);

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

  const allowedMainTabs = ["informacion", "asignaturas", "pasantias", "credenciales", "pagos"];
  const moduleParam = (searchParams.get("module") || "").toLowerCase();
  const tabParam = (searchParams.get("tab") || "").toLowerCase();

  let initialTab = "informacion";
  if (allowedMainTabs.includes(moduleParam)) {
    initialTab = moduleParam;
  } else if (allowedMainTabs.includes(tabParam)) {
    initialTab = tabParam;
  } else if (["programa", "actividades", "calificaciones", "manual"].includes(tabParam)) {
    initialTab = "pasantias";
  }

  activateTab(initialTab);

  if (initialTab === "pasantias") {
    const pane = document.getElementById("pasantias");
    if (pane) {
      pane.scrollIntoView({ behavior: "auto", block: "start" });
    }

    // Si el tab específico dentro de pasantias es 'actividades', scroll a esa sección
    if (tabParam === "actividades") {
      setTimeout(() => {
        const actividadesSection = document.getElementById("section-actividades-diarias");
        if (actividadesSection) {
          actividadesSection.scrollIntoView({ behavior: "smooth", block: "start" });
        }
      }, 100);
    }
  }
});
