<!-- İşlem Ekleme Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionModalTitle">Yeni İşlem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="add_transaction.php">
                <div class="modal-body">
                    <input type="hidden" name="tur" id="islemTuru">

                    <div class="mb-3">
                        <label class="form-label">Tutar</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="tutar" step="0.01" min="0" required>
                            <span class="input-group-text">₺</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="aciklama" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tarih</label>
                        <input type="datetime-local" class="form-control" name="tarih"
                            value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="kategori_id" id="kategoriSelect" required>
                            <?php
                            $stmt = $db->query("SELECT * FROM cari_kategoriler ORDER BY TUR DESC, KATEGORI_ADI");
                            while ($kategori = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $kategori['ID'] . '" data-tur="' . $kategori['TUR'] . '">'
                                    . htmlspecialchars($kategori['KATEGORI_ADI']) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn" id="submitBtn">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showTransactionModal(tur) {
        document.getElementById('islemTuru').value = tur;
        document.getElementById('transactionModalTitle').textContent =
            tur === 'gelir' ? 'Yeni Gelir Ekle' : 'Yeni Gider Ekle';

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.className = 'btn btn-' + (tur === 'gelir' ? 'success' : 'danger');

        // Kategori filtreleme
        const kategoriSelect = document.getElementById('kategoriSelect');
        const options = kategoriSelect.getElementsByTagName('option');

        for (let option of options) {
            if (option.dataset.tur === tur) {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        }

        // İlk uygun kategoriyi seç
        for (let option of options) {
            if (option.dataset.tur === tur) {
                kategoriSelect.value = option.value;
                break;
            }
        }

        new bootstrap.Modal(document.getElementById('transactionModal')).show();
    }

    function deleteTransaction(id) {
        if (confirm('Bu işlemi silmek istediğinizden emin misiniz?')) {
            window.location.href = 'delete_transaction.php?id=' + id;
        }
    }
</script>