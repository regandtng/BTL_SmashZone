<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0" style="font-size: 1.25rem; font-weight: 600;">Quản Lý Chi Nhánh</h2>
    <?php if ($is_super): ?>
        <a href="#" class="btn btn-accent btn-sm" onclick="document.getElementById('addBranchModal').style.display='block'">+ Thêm Chi Nhánh</a>
    <?php endif; ?>
</div>

<?php if (empty($branches)): ?>
    <div class="admin-card glass-panel p-4 text-center">
        <h3 class="m-0 text-muted font-weight-500">Không có chi nhánh nào.</h3>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="admin-table w-100">
            <thead>
                <tr>
                    <th style="text-align:left;">Tên Chi Nhánh</th>
                    <th style="text-align:left;">Địa Chỉ</th>
                    <th style="text-align:left;">Thành Phố</th>
                    <th style="text-align:left;">Số Sân</th>
                    <th style="text-align:left;">Trạng Thái</th>
                    <th style="text-align:right;">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($branches as $branch): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($branch['name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($branch['address'] ?? '') ?></td>
                        <td><?= htmlspecialchars($branch['city'] ?? '') ?></td>
                        <td><?= (int)($branch['courts_count'] ?? 0) ?></td>
                        <td>
                            <span class="badge <?= ($branch['is_active'] ?? 1) ? 'badge-success' : 'badge-danger' ?>">
                                <?= ($branch['is_active'] ?? 1) ? 'Hoạt Động' : 'Bảo Trì' ?>
                            </span>
                        </td>
                        <td style="text-align:right;">
                            <a href="<?= BASE_URL ?>/owner/bookings?branch_id=<?= (int)($branch['id']) ?>" class="btn btn-outline-accent btn-sm">Xem Đặt Sân</a>
                            <?php if ($is_super): ?>
                                <button class="btn btn-danger btn-sm ml-2" onclick="confirmDeleteBranch(<?= (int)($branch['id']) ?>, '<?= htmlspecialchars($branch['name']) ?>')">Xóa</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($is_super): ?>
    <div id="addBranchModal" class="modal" style="display:none;">
        <div class="modal-overlay" onclick="this.parentElement.style.display='none'"></div>
        <div class="modal-content glass-panel p-4" style="max-width:500px; border-radius:12px;">
            <h3 class="m-0 mb-3" style="font-size:1.1rem; font-weight:600;">Thêm Chi Nhánh Mới</h3>
            <form action="<?= BASE_URL ?>/owner/branch/store" method="POST">
                <div class="form-group mb-3">
                    <label class="form-label">Tên chi nhánh</label>
                    <input type="text" name="name" class="form-control" required placeholder="SmashZone Quận 1">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Địa chỉ</label>
                    <input type="text" name="address" class="form-control" required placeholder="123 Đường abc">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Thành phố</label>
                    <input type="text" name="city" class="form-control" required placeholder="Hà Nội">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" placeholder="0901234567">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Số sân</label>
                    <input type="number" name="courts_count" class="form-control" value="10" min="1" required>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Giờ mở cửa</label>
                    <input type="time" name="open_time" class="form-control" value="05:00">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Giờ đóng cửa</label>
                    <input type="time" name="close_time" class="form-control" value="22:00">
                </div>
                <div class="form-group mb-3 form-check">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
                    <label class="form-check-label">Hoạt động</label>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-accent btn-sm" onclick="document.getElementById('addBranchModal').style.display='none'">Hủy</button>
                    <button type="submit" class="btn btn-accent btn-sm">Lưu</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($is_super): ?>
    <form id="deleteBranchForm" action="<?= BASE_URL ?>/owner/branch/delete" method="POST" style="display:none;"></form>
<?php endif; ?>

<style>
.admin-table { border-collapse: collapse; }
.admin-table th, .admin-table td { padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left; }
.admin-table th { font-weight: 600; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; }
.admin-table tr:hover { background: rgba(255,255,255,0.03); }
.badge { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; color: #fff; }
.badge-success { background: #00e676; color: #000; }
.badge-danger { background: #ff5252; color: #fff; }
.btn-sm { padding: 6px 14px; font-size: 0.85rem; }
.btn-outline-accent { background: transparent; border: 1px solid var(--accent-blue); color: var(--accent-blue); }
.btn-outline-accent:hover { background: var(--accent-blue); color: #fff; }
.btn-danger { background: #ff5252; border: none; color: #fff; }
.btn-danger:hover { background: #ff1744; }
.ml-2 { margin-left: 0.5rem; }
.modal { position: fixed; inset: 0; z-index: 1000; }
.modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); }
.modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--bg-dark); border: 1px solid var(--glass-border); }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; margin-bottom: 0.4rem; font-size: 0.85rem; font-weight: 500; color: var(--text-muted); }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--glass-border); border-radius: 6px; background: rgba(255,255,255,0.03); color: #fff; font-size: 0.9rem; }
.form-control::placeholder { color: var(--text-muted); }
.form-check { display: flex; align-items: center; gap: 0.5rem; }
.form-check-input { width: 18px; height: 18px; accent-color: var(--accent-green); }
.form-check-label { font-size: 0.85rem; color: var(--text-muted); margin: 0; }
.d-flex { display: flex; }
.gap-2 { gap: 0.5rem; }
</style>

<script>
function confirmDeleteBranch(id, name) {
    if (confirm('Bạn chắc chắn muốn xóa chi nhánh "' + name + '"? Tất cả dữ liệu liên quan sẽ bị xóa.')) {
        const form = document.getElementById('deleteBranchForm');
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'branch_id';
        input.value = id;
        form.appendChild(input);
        form.submit();
    }
}
</script>

<?php $content = ob_get_clean(); require ROOT . '/app/Views/layouts/admin.php'; ?>
