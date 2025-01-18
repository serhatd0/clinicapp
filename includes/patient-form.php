<form method="POST" class="row g-3 needs-validation" novalidate>
    <div class="col-md-12">
        <div class="form-floating">
            <input type="text" class="form-control" id="fullName" name="fullName" placeholder="Ad Soyad"
                value="<?php echo isset($_POST['fullName']) ? htmlspecialchars($_POST['fullName']) : ''; ?>" required>
            <label for="fullName">Ad Soyad</label>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-floating">
            <select class="form-select" id="idType" name="idType" required>
                <option value="tc" <?php echo (!isset($_POST['idType']) || $_POST['idType'] == 'tc') ? 'selected' : ''; ?>>T.C. Kimlik No</option>
                <option value="passport" <?php echo (isset($_POST['idType']) && $_POST['idType'] == 'passport') ? 'selected' : ''; ?>>Pasaport No</option>
            </select>
            <label for="idType">Kimlik Türü</label>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-floating">
            <input type="text" class="form-control" id="idNumber" name="idNumber" placeholder="Kimlik Numarası"
                value="<?php echo isset($_POST['idNumber']) ? htmlspecialchars($_POST['idNumber']) : ''; ?>" required>
            <label for="idNumber">Kimlik Numarası</label>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-floating">
            <input type="date" class="form-control" id="birthDate" name="birthDate" placeholder="Doğum Tarihi"
                value="<?php echo isset($_POST['birthDate']) ? htmlspecialchars($_POST['birthDate']) : ''; ?>" required>
            <label for="birthDate">Doğum Tarihi</label>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-floating">
            <select class="form-select" id="gender" name="gender" required>
                <option value="E" <?php echo (!isset($_POST['gender']) || $_POST['gender'] == 'E') ? 'selected' : ''; ?>>
                    Erkek</option>
                <option value="K" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'K') ? 'selected' : ''; ?>>
                    Kadın</option>
            </select>
            <label for="gender">Cinsiyet</label>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-floating">
            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Telefon"
                value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
            <label for="phone">Telefon</label>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-floating">
            <input type="email" class="form-control" id="email" name="email" placeholder="E-posta"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <label for="email">E-posta</label>
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-floating">
            <input type="text" class="form-control" id="reference" name="reference" placeholder="Referans"
                value="<?php echo isset($_POST['reference']) ? htmlspecialchars($_POST['reference']) : ''; ?>">
            <label for="reference">Referans</label>
        </div>
    </div>

    <div class="col-md-12">
        <div class="form-floating">
            <textarea class="form-control" id="emptyField" name="emptyField" placeholder="Açıklama"
                style="height: 100px"><?php echo isset($_POST['emptyField']) ? htmlspecialchars($_POST['emptyField']) : ''; ?></textarea>
            <label for="emptyField">Açıklama</label>
        </div>
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-primary w-100 py-3">Kaydet</button>
    </div>
</form>

<script>
    // Form doğrulama
    (function () {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()

    // Kimlik türüne göre input mask
    document.querySelectorAll('input[name="idType"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const idNumber = document.getElementById('idNumber');
            if (this.value === 'tc') {
                idNumber.setAttribute('maxlength', '11');
                idNumber.setAttribute('pattern', '[0-9]{11}');
            } else {
                idNumber.setAttribute('maxlength', '9');
                idNumber.setAttribute('pattern', '[A-Z0-9]{7,9}');
            }
        });
    });
</script>