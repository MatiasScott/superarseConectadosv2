// public/js/transferencias.js

document.addEventListener("DOMContentLoaded", function () {
  const bankButtons = document.querySelectorAll(".bank-tab-button");
  const selectedBankNameDisplay = document.getElementById("selected-bank-name");
  const selectedAccountType = document.getElementById("selected-account-type");
  const selectedAccountNumber = document.getElementById(
    "selected-account-number"
  );
  const studentIdDataElement = document.getElementById("student-id-data");
  const sendComprobanteBtn = document.getElementById("send-comprobante-btn");
  let selectedBankId = null;
  let selectedBankName = "";
  const currentStudentId = studentIdDataElement
    ? studentIdDataElement.dataset.id
    : "ID_NO_ENCONTRADO";
  function updateBankDetails(button) {
    selectedBankNameDisplay.textContent = button.dataset.bankName;
    selectedAccountType.textContent = button.dataset.accountType;
    selectedAccountNumber.textContent = button.dataset.accountNumber;
    selectedBankName = button.dataset.bankName;
    selectedBankId = button.dataset.bankId;

    checkFormValidity();
  }

  function checkFormValidity() {
    const hasBankSelected = selectedBankId !== null;

    if (hasBankSelected) {
      const subject = encodeURIComponent(
        `Notificación de Pago - ID: ${currentStudentId}`
      );

      const body = encodeURIComponent(
        `Adjunto mi comprobante de pago.\n\n` +
        `✅ RECUERDE ADJUNTAR EL COMPROBANTE.\n\n` +
        `--- Detalles de la Operación ---\n` +
        `Estudiante ID: ${currentStudentId}\n` +
        `Banco Seleccionado: ${selectedBankName}`
      );
      const mailtoLink = `mailto:matriculas@superarse.edu.ec?subject=${subject}&body=${body}`;
      sendComprobanteBtn.setAttribute("href", mailtoLink);
      sendComprobanteBtn.classList.remove("opacity-50", "cursor-not-allowed");
      sendComprobanteBtn.classList.add("hover:bg-superarse-morado-medio");
    } else {
      sendComprobanteBtn.setAttribute("href", "#");
      sendComprobanteBtn.classList.add("opacity-50", "cursor-not-allowed");
      sendComprobanteBtn.classList.remove("hover:bg-superarse-morado-medio");
    }
  }
  bankButtons.forEach((button) => {
    button.addEventListener("click", function () {
      bankButtons.forEach((btn) => {
        btn.classList.remove(
          "bg-superarse-morado-oscuro",
          "hover:bg-superarse-morado-medio"
        );
        btn.classList.add("bg-gray-400", "hover:bg-gray-500");
      });
      this.classList.add(
        "bg-superarse-morado-oscuro",
        "hover:bg-superarse-morado-medio"
      );
      this.classList.remove("bg-gray-400", "hover:bg-gray-500");

      updateBankDetails(this);
    });
  });
  if (bankButtons.length > 0) {
    bankButtons[0].click();
  } else {
    checkFormValidity();
  }
});
