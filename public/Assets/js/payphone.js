document
  .getElementById("payphone-link")
  .addEventListener("click", function (e) {
    e.preventDefault();
    const cantidadInput = document
      .getElementById("cantidad")
      .value.trim()
      .replace(",", ".");

    if (!/^\d+(\.\d{1,2})?$/.test(cantidadInput)) {
      alert("Por favor, ingresa una cantidad valida con maximo 2 decimales.");
      document.getElementById("cantidad").focus();
      return;
    }

    const cantidad = parseFloat(cantidadInput);

    if (!cantidad || isNaN(cantidad) || cantidad <= 0) {
      alert("Por favor, ingresa una cantidad válida y mayor a cero.");
      document.getElementById("cantidad").focus();
      return;
    }

    const referencia = document.getElementById("referencia").value.trim();
    const studentIdElement = document.getElementById("student-id-data");
    const identificacion = studentIdElement
      ? studentIdElement.getAttribute("data-id")
      : "SIN_ID";
    const referenciaFinal = `ID: ${identificacion} | Ref: ${referencia || "Pago Estudiantil"
      }`;
    const cantidadNormalizada = cantidad.toFixed(2);
    const url =
      `/superarseconectadosv2/public/pago?cantidad=` +
      encodeURIComponent(cantidadNormalizada) +
      `&referencia=` +
      encodeURIComponent(referenciaFinal) +
      `&vista=pasarela`;
    window.open(url, "_blank");
  });
