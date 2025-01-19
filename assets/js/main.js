document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector(".needs-validation");
  const idTypeInputs = document.querySelectorAll('input[name="idType"]');
  const idNumberInput = document.getElementById("idNumber");
  const idNumberLabel = document.getElementById("idNumberLabel");
  const idNumberFeedback = document.getElementById("idNumberFeedback");

  // Kimlik türü değiştiğinde
  idTypeInputs.forEach((input) => {
    input.addEventListener("change", function () {
      const isTc = this.value === "tc";

      // Label ve placeholder güncelleme
      idNumberLabel.textContent = isTc
        ? "TC Kimlik Numarası"
        : "Pasaport Numarası";
      idNumberInput.placeholder = isTc
        ? "TC Kimlik numaranızı giriniz"
        : "Pasaport numaranızı giriniz";

      // Input değerini temizle
      idNumberInput.value = "";

      // Validasyon kurallarını güncelle
      if (isTc) {
        idNumberInput.pattern = "[0-9]{11}";
        idNumberInput.maxLength = 11;
        idNumberFeedback.textContent =
          "Geçerli bir TC Kimlik numarası giriniz (11 haneli)";
      } else {
        idNumberInput.pattern = "[A-Z0-9]{7,9}";
        idNumberInput.maxLength = 9;
        idNumberFeedback.textContent = "Geçerli bir Pasaport numarası giriniz";
      }
    });
  });

  // Form validasyonu
  if (form) {
    form.addEventListener("submit", function (e) {
      if (!this.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
      }

      // TC/Pasaport validasyonu
      /*const idType = document.querySelector('input[name="idType"]:checked').value;
          const idNumber = idNumberInput.value;
          
          if (idType === 'tc' && !validateTcNumber(idNumber)) {
              e.preventDefault();
              showAlert('Geçerli bir TC Kimlik numarası giriniz!', 'danger');
              return;
          } else if (idType === 'passport' && !validatePassportNumber(idNumber)) {
              e.preventDefault();
              showAlert('Geçerli bir Pasaport numarası giriniz!', 'danger');
              return;
          }*/

      this.classList.add("was-validated");
    });
  }

  // TC Kimlik validasyonu
  function validateTcNumber(value) {
    if (!/^[0-9]{11}$/.test(value)) return false;

    let odd = 0,
      even = 0,
      total = 0,
      check = 0;

    for (let i = 0; i < 9; i++) {
      if (i % 2 === 0) {
        odd += parseInt(value[i]);
      } else {
        even += parseInt(value[i]);
      }
      total += parseInt(value[i]);
    }

    check = (odd * 7 - even) % 10;

    return (
      check === parseInt(value[9]) && (total * 8) % 10 === parseInt(value[10])
    );
  }

  // Pasaport validasyonu
  function validatePassportNumber(value) {
    return /^[A-Z0-9]{7,9}$/.test(value);
  }

  // Telefon doğrulama butonu için basit bir kontrol
  const phoneVerifyBtn = document.querySelector('.btn-success[type="button"]');
  if (phoneVerifyBtn) {
    phoneVerifyBtn.addEventListener("click", function () {
      const phone = document.getElementById("phone").value;
      if (!phone) {
        alert("Lütfen telefon numarası giriniz!");
        return;
      }
      alert("Telefon numarası doğrulama işlemi başlatıldı");
    });
  }

  // Flatpickr tarih seçici konfigürasyonu
  const birthDateInput = document.getElementById("birthDate");
  if (birthDateInput) {
    flatpickr("#birthDate", {
      locale: "tr",
      dateFormat: "Y-m-d",
      maxDate: new Date(),
      disableMobile: false,
      monthSelectorType: "static",
      yearSelectorType: "static",
      altInput: true,
      altFormat: "d F Y",
      theme: "material_green",
      placeholder: "Doğum tarihinizi seçin",
      plugins: [
        new monthSelectPlugin({
          shorthand: true,
          dateFormat: "Y-m-d",
          altFormat: "F Y",
        }),
      ],
    });
  }

  document.getElementById("fullName").addEventListener("input", function (e) {
    const value = e.target.value;
    // Sadece harf ve boşluk karakterlerine izin ver
    if (!/^[A-Za-zğüşıöçĞÜŞİÖÇ\s]*$/.test(value)) {
      e.target.value = value.replace(/[^A-Za-zğüşıöçĞÜŞİÖÇ\s]/g, "");
    }
  });
});

function showAlert(message, type = "success") {
  const alertContainer = document.getElementById("alertContainer");
  const alertDiv = document.createElement("div");

  alertDiv.className = `alert alert-${type} floating-alert alert-dismissible fade show`;
  alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

  alertContainer.appendChild(alertDiv);

  // 3 saniye sonra alert'i kaldır
  setTimeout(() => {
    alertDiv.classList.add("alert-fade-out");
    setTimeout(() => {
      alertDiv.remove();
    }, 500);
  }, 3000);

  // Kapatma butonuna tıklanınca
  alertDiv.querySelector(".btn-close").addEventListener("click", () => {
    alertDiv.classList.add("alert-fade-out");
    setTimeout(() => {
      alertDiv.remove();
    }, 500);
  });
}

// Birden fazla alert gösterildiğinde önceki alert'leri temizle
function clearAlerts() {
  const alertContainer = document.getElementById("alertContainer");
  alertContainer.innerHTML = "";
}
