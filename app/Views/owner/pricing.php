<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0" style="font-size: 1.25rem; font-weight: 600;">Quản Lý Giá Theo Khung Giờ</h2>
    <?php if ($is_super): ?>
        <a href="#" class="btn btn-accent btn-sm" onclick="document.getElementById('addPricingModal').style.display='block'">+ Thêm Quy Giá</a>
    <?php endif; ?>
</div>

<!-- Chọn chi nhánh (chỉ super admin) -->
<?php if ($is_super && !empty($branches)): ?>
    <form action="<?= BASE_URL ?>/owner/pricing" method="GET" class="admin-card glass-panel p-3 mb-4" style="display:inline-flex; gap:1rem; align-items:center;">
        <label class="form-label font-weight-500" style="margin:0; font-size:0.85rem;">Xem chi nhánh</label>
        <select name="branch_id" class="form-control" style="width:auto; min-width:220px;" onchange="this.form.submit()">
            <option value="0">Tất cả chi nhánh</option>
            <?php foreach ($branches as $b): ?>
                <option value="<?= (int)($b['id']) ?>" <?= ((int)($branch_id ?? 0) === (int)($b['id'])) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
<?php endif; ?>

<?php if (empty($rules)): ?>
    <div class="admin-card glass-panel p-4 text-center">
        <h3 class="m-0 text-muted font-weight-500">Chưa có quy giá nào.</h3>
        <p class="text-muted font-weight-400" style="font-size:0.9rem;">Vui lòng thêm quy giá để hệ thống tính toán tiền đặt sân.</p>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="admin-table w-100">
            <thead>
                <tr>
                    <th style="text-align:left;">Loại Ngày</th>
                    <th style="text-align:left;">Khung Giờ</th>
                    <th style="text-align:left;">Chi Nhánh</th>
                    <th style="text-align:right;">Giá / Giờ</th>
                    <th style="text-align:left;">Nhãn</th>
                    <th style="text-align:center;">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rules as $r): ?>
                    <tr>
                        <td><?= ucfirst(htmlspecialchars($r['day_type'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($r['start_hour'] ?? '') ?> - <?= htmlspecialchars($r['end_hour'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['branch_name'] ?? ($r['branch_id'] ? 'Chi nhánh #' . $r['branch_id'] : 'Toàn hệ thống')) ?></td>
                        <td style="text-align:right; font-weight:600;"><?= number_format($r['price_per_hour'] ?? 0, 0, ',', '.') ?> đ</td>
                        <td><?= htmlspecialchars($r['label'] ?? '') ?></td>
                        <td style="text-align:center;">
                            <?php if ($is_super || !$r['branch_id']): ?>
                                <a href="#" class="btn btn-outline-accent btn-sm" onclick="editPricing(<?= (int)($r['id']) ?>, '<?= htmlspecialchars($r['day_type']) ?>', '<?= htmlspecialchars($r['start_hour']) ?>', '<?= htmlspecialchars($r['end_hour']) ?>', <?= (float)($r['price_per_hour']) ?>, '<?= htmlspecialchars($r['label']) ?>')">Sửa</a>
                                <form action="<?= BASE_URL ?>/owner/pricing/delete" method="POST" style="display:inline;" onsubmit="return confirm('Xóa quy giá này?')">
                                    <?= \Core\Session::csrfField() ?>
                                    <input type="hidden" name="id" value="<?= (int)($r['id']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm ml-2">Xóa</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted font-weight-500" style="font-size:0.85rem;">Không thể sửa</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($is_super): ?>
    <div id="addPricingModal" class="modal" style="display:none;">
        <div class="modal-overlay" onclick="this.parentElement.style.display='none'"></div>
        <div class="modal-content glass-panel p-4" style="max-width:520px; border-radius:12px;">
            <h3 class="m-0 mb-3" style="font-size:1.1rem; font-weight:600;">Thêm Quy Giá Mới</h3>
            <form action="<?= BASE_URL ?>/owner/pricing/store" method="POST">
                <?= \Core\Session::csrfField() ?>
                <div class="form-group mb-3">
                    <label class="form-label">Chi nhánh (để trống = toàn hệ thống)</label>
                    <select name="branch_id" class="form-control">
                        <option value="">Toàn hệ thống</option>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= (int)($b['id']) ?>"><?= htmlspecialchars($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Loại ngày</label>
                    <select name="day_type" class="form-control">
                        <option value="weekday">Thứ 2 - Thứ 6</option>
                        <option value="weekend">Thứ 7 - Chủ Nhật</option>
                        <option value="all">Tất cả ngày</option>
                    </select>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Giờ bắt đầu</label>
                    <input type="time" name="start_hour" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Giờ kết thúc</label>
                    <input type="time" name="end_hour" class="form-control" required>
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Giá mỗi giờ (đ)</label>
                    <input type="number" name="price_per_hour" class="form-control" required min="0">
                </div>
                <div class="form-group mb-3">
                    <label class="form-label">Nhãn</label>
                    <input type="text" name="label" class="form-control" placeholder="Ví dụ: Peak Tối">
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-accent btn-sm" onclick="document.getElementById('addPricingModal').style.display='none'">Hủy</button>
                    <button type="submit" class="btn btn-accent btn-sm">Lưu</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<style>
.badge { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; color: #fff; }
.badge-success { background: #00e676; color: #000; }
.badge-warning { background: #ffab00; color: #000; }
.badge-danger { background: #ff5252; color: #fff; }
.badge-secondary { background: #64748b; color: #fff; }
.btn-sm { padding: 6px 14px; font-size: 0.85rem; }
.btn-outline-accent { background: transparent; border: 1px solid var(--accent-blue); color: var(--accent-blue); }
.btn-outline-accent:hover { background: var(--accent-blue); color: #fff; }
.btn-accent { background: var(--accent-blue); border: none; color: #fff; }
.btn-accent:hover { background: #1a6bf2; }
.btn-danger { background: #ff5252; border: none; color: #fff; }
.btn-danger:hover { background: #ff1744; }
.ml-2 { margin-left: 0.5rem; }
.modal { position: fixed; inset: 0; z-index: 1000; }
.modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); }
.modal-content { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: var(--bg-dark); border: 1px solid var(--glass-border); }
.admin-table { border-collapse: collapse; width: 100%; }
.admin-table th, .admin-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left; font-size: 0.9rem; }
.admin-table th { font-weight: 600; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; }
.admin-table tr:hover { background: rgba(255,255,255,0.03); }
.form-group { margin-bottom: 1rem; }
.form-label { display: block; margin-bottom: 0.4rem; font-size: 0.85rem; font-weight: 500; color: var(--text-muted); }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--glass-border); border-radius: 6px; background: rgba(255,255,255,0.03); color: #fff; font-size: 0.9rem; }
.d-flex { display: flex; }
.gap-2 { gap: 0.5rem; }
.table-responsive { overflow-x: auto; }
</style>

<script>
function editPricing(id, dayType, startHour, endHour, price, label) {
    // TODO: connect to edit form
    alert('Chức năng sửa chưa triển khai giao diện. Vui lòng xóa và tạo lại.');
}
</script>

<?php $content = ob_get_clean(); require ROOT . '/app/Views/layouts/admin.php'; ?>
