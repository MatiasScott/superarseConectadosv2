// public/js/transferencias.js

document.addEventListener("DOMContentLoaded", function () {
  const bankButtons = document.querySelectorAll(".bank-tab-button");
  const selectedBankNameDisplay = document.getElementById("selected-bank-name");
  const selectedAccountType = document.getElementById("selected-account-type");
  const selectedAccountNumber = document.getElementById("selected-account-number");
  const sendComprobanteBtn = document.getElementById("send-comprobante-btn");
  const selectedBankInput = document.getElementById("banco_seleccionado");
  const fileInput = document.getElementById("comprobante");

  if (
    !selectedBankNameDisplay ||
    !selectedAccountType ||
    !selectedAccountNumber ||
    !sendComprobanteBtn ||
    !selectedBankInput ||
    !fileInput
  ) {
    return;
  }

  let selectedBankId = null;
  let selectedBankName = "";

  function updateSubmitState() {
    const hasBankSelected = selectedBankId !== null && selectedBankName !== "";
    const hasFileSelected = fileInput.files && fileInput.files.length > 0;
    const canSubmit = hasBankSelected && hasFileSelected;

    sendComprobanteBtn.disabled = !canSubmit;
    sendComprobanteBtn.classList.toggle("opacity-50", !canSubmit);
    sendComprobanteBtn.classList.toggle("cursor-not-allowed", !canSubmit);
    sendComprobanteBtn.classList.toggle("hover:bg-superarse-morado-medio", canSubmit);
  }

  function updateBankDetails(button) {
    selectedBankNameDisplay.textContent = button.dataset.bankName;
    selectedAccountType.textContent = button.dataset.accountType;
    selectedAccountNumber.textContent = button.dataset.accountNumber;
    selectedBankName = button.dataset.bankName;
    selectedBankId = button.dataset.bankId;
    selectedBankInput.value = selectedBankName;

    updateSubmitState();
  }

  bankButtons.forEach((button) => {
    button.addEventListener("click", function () {
      bankButtons.forEach((btn) => {
        btn.classList.remove("bg-superarse-morado-oscuro", "hover:bg-superarse-morado-medio");
        btn.classList.add("bg-gray-400", "hover:bg-gray-500");
      });

      this.classList.add("bg-superarse-morado-oscuro", "hover:bg-superarse-morado-medio");
      this.classList.remove("bg-gray-400", "hover:bg-gray-500");

      updateBankDetails(this);
    });
  });

  fileInput.addEventListener("change", updateSubmitState);

  if (bankButtons.length > 0) {
    bankButtons[0].click();
  } else {
    updateSubmitState();
  }
});
