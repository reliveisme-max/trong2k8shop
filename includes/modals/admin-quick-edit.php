<!-- includes/modals/admin-quick-edit.php -->
<div class="modal fade" id="quickEditModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Sửa nhanh Acc #<span id="qeIdDisplay"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="qeId">

                <div class="row g-3">
                    <!-- Trạng thái -->
                    <div class="col-12">
                        <label class="small fw-bold text-secondary">Trạng thái</label>
                        <select id="qeStatus" class="form-select">
                            <option value="1">Đang bán (Hiện)</option>
                            <option value="0">Đã bán (Ẩn)</option>
                        </select>
                    </div>

                    <!-- Tên Acc -->
                    <div class="col-12">
                        <label class="small fw-bold text-secondary">Tên Acc</label>
                        <input type="text" id="qeTitle" class="form-control" placeholder="Để trống = Lấy ID">
                    </div>

                    <!-- Danh mục -->
                    <div class="col-6">
                        <label class="small fw-bold text-secondary">Danh mục</label>
                        <select id="qeCategory" class="form-select">
                            <option value="0">-- Chọn --</option>
                            <!-- Biến $categories được lấy từ index.php -->
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Giá -->
                    <div class="col-6">
                        <label class="small fw-bold text-secondary">Giá tiền</label>
                        <!-- Hàm parsePrice đã có bên home.js -->
                        <input type="text" id="qePrice" class="form-control fw-bold text-success"
                            onblur="parsePrice(this)">
                    </div>

                    <!-- Ghi chú -->
                    <div class="col-12">
                        <label class="small fw-bold text-secondary">Ghi chú (Admin)</label>
                        <textarea id="qeNote" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <a href="#" id="btnFullEdit" class="btn btn-light text-primary fw-bold me-auto">Sửa đầy đủ (Ảnh)</a>
                <button type="button" class="btn btn-primary fw-bold px-4" onclick="saveQuickEdit()">LƯU NGAY</button>
            </div>
        </div>
    </div>
</div>