// public/js/login.js

document.addEventListener("DOMContentLoaded", function () {
  function getUrlParameter(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
    var results = regex.exec(location.search);
    return results === null
      ? null
      : decodeURIComponent(results[1].replace(/\+/g, " "));
  }
  var error = getUrlParameter("error");

  if (error === "cedula_no_encontrada") {
    var errorModal = new bootstrap.Modal(document.getElementById("errorModal"));
    document.getElementById("errorModalLabel").textContent = "Error de Acceso";

    const improvedErrorMessage = `
    <p class="text-xl font-bold mb-4 text-superarse-morado-oscuro">
        ¡Atención! La identificación ingresada no se encuentra registrada en nuestro sistema.
    </p>
    <p class="text-gray-700 mb-4">
        Si deseas más información sobre nuestras carreras, contáctanos inmediatamente por WhatsApp: 
    </p>
    <p class="text-3xl font-extrabold my-4">
        <a 
            href='https://wa.me/593987289072' 
            target='_blank' 
            class='text-superarse-rosa hover:text-superarse-morado-medio transition duration-300'
            aria-label='Contactar a Superarse por WhatsApp'
        >
            098 728 9072
        </a>
    </p>
    <hr class="my-4 border-superarse-morado-medio/30" />
    <p class="text-gray-600">
        También te invitamos a explorar nuestra 
        <a 
            href='https://superarse.edu.ec/' 
            target='_blank' 
            class='text-superarse-morado-medio hover:text-superarse-rosa font-bold transition duration-300'
            aria-label='Ver Oferta Académica de Superarse'
        >
            oferta académica
        </a> 
        y descubrir las oportunidades que tenemos para ti.
    </p>
  `;
    document.getElementById("errorMessage").innerHTML = improvedErrorMessage;
    errorModal.show();
    if (window.history.replaceState) {
      var urlSinError =
        window.location.protocol +
        "//" +
        window.location.host +
        window.location.pathname;
      window.history.replaceState({ path: urlSinError }, "", urlSinError);
    }
  }
});
