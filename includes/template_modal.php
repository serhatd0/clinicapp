<!-- Şablon Ekleme/Düzenleme Modal -->
<div class="modal fade" id="templateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalTitle">Yeni Şablon Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="template_id" id="templateId">
                    <input type="hidden" name="add_template" id="addTemplate" value="1">

                    <div class="mb-3">
                        <label class="form-label">İşlem Adı</label>
                        <input type="text" class="form-control" name="islem_adi" id="islemAdi" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kaçıncı Gün</label>
                        <input type="number" class="form-control" name="gun" id="gun" min="1" max="365" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sıra</label>
                        <input type="number" class="form-control" name="sira" id="sira" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showAddTemplateModal() {
        document.getElementById('templateModalTitle').textContent = 'Yeni Şablon Ekle';
        document.getElementById('templateId').value = '';
        document.getElementById('addTemplate').value = '1';
        document.getElementById('islemAdi').value = '';
        document.getElementById('gun').value = '';
        document.getElementById('sira').value = '';

        new bootstrap.Modal(document.getElementById('templateModal')).show();
    }

    function showEditTemplateModal(template) {
        document.getElementById('templateModalTitle').textContent = 'Şablon Düzenle';
        document.getElementById('templateId').value = template.ID;
        document.getElementById('addTemplate').name = 'update_template';
        document.getElementById('islemAdi').value = template.ISLEM_ADI;
        document.getElementById('gun').value = template.GUN;
        document.getElementById('sira').value = template.SIRA;

        new bootstrap.Modal(document.getElementById('templateModal')).show();
    }

    function deleteTemplate(id) {
        if (confirm('Bu şablonu silmek istediğinizden emin misiniz?')) {
            window.location.href = 'settings.php?delete_template=' + id;
        }
    }
</script>