<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0" style="font-size: 1.25rem; font-weight: 600;">Quản Lý Đặt Sân</h2>
</div>

<!-- Bộ lọc -->
<form action="<?= BASE_URL ?>/owner/bookings" method="GET" class="admin-card glass-panel p-3 mb-4" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
    <label class="form-label font-weight-500" style="margin:0; font-size:0.85rem;">Chi nhánh</label>
    <select name="branch_id" class="form-control" style="width:auto; min-width:180px;" onchange="this.form.submit()">
        <option value="0">Tất cả chi nhánh</option>
        <?php foreach ($branches as $b): ?>
            <option value="<?= (int)($b['id']) ?>" <?= ((int)($branch_filter ?? 0) === (int)($b['id'])) ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label class="form-label font-weight-500" style="margin:0; font-size:0.85rem;">Trạng thái</label>
    <select name="status" class="form-control" style="width:auto; min-width:150px;" onchange="this.form.submit()">
        <option value="">Tất cả</option>
        <option value="pending" <?= ((string)($status_filter ?? '') === 'pending') ? 'selected' : '' ?>>Chờ thanh toán</option>
        <option value="confirmed" <?= ((string)($status_filter ?? '') === 'confirmed') ? 'selected' : '' ?>>Đã xác nhận</option>
        <option value="completed" <?= ((string)($status_filter ?? '') === 'completed') ? 'selected' : '' ?>>Hoàn thành</option>
        <option value="cancelled" <?= ((string)($status_filter ?? '') === 'cancelled') ? 'selected' : '' ?>>Đã hủy</option>
    </select>

    <label class="form-label font-weight-500" style="margin:0; font-size:0.85rem;">Ngày</label>
    <input type="date" name="date" class="form-control" style="width:auto; min-width:160px;" value="<?= htmlspecialchars($date_filter ?? '') ?>" onchange="this.form.submit()">
</form>

<?php if (empty($bookings)): ?>
    <div class="admin-card glass-panel p-4 text-center">
        <h3 class="m-0 text-muted font-weight-500">Không có đặt sân nào phù hợp.</h3>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="admin-table w-100">
            <thead>
                <tr>
                    <th style="text-align:left;">Mã Đặt</th>
                    <th style="text-align:left;">Khách Hàng</th>
                    <th style="text-align:left;">SĐT</th>
                    <th style="text-align:left;">Chi Nhánh</th>
                    <th style="text-align:center;">Ngày / Giờ</th>
                    <th style="text-align:right;">Tổng Tiền</th>
                    <th style="text-align:center;">Trạng Thái</th>
                    <th style="text-align:center;">Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:600; font-size:0.85rem;"><?= htmlspecialchars($b['booking_code'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['customer_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['customer_phone'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['branch_name'] ?? '') ?></td>
                        <td style="text-align:center;">
                            <div><?= htmlspecialchars($b['booking_date'] ?? '') ?></div>
                            <div style="font-size:0.85rem; color:var(--text-muted);"><?= htmlspecialchars($b['start_time'] ?? '') ?> - <?= htmlspecialchars($b['end_time'] ?? '') ?></div>
                        </td>
                        <td style="text-align:right; font-weight:600;"><?= number_format($b['total_price'] ?? 0, 0, ',', '.') ?> đ</td>
                        <td style="text-align:center;">
                            <span class="badge <?= $b['status'] === 'confirmed' ? 'badge-success' : ($b['status'] === 'pending' ? 'badge-warning' : ($b['status'] === 'cancelled' ? 'badge-danger' : 'badge-secondary')) ?>">
                                <?= ucfirst(htmlspecialchars($b['status'] ?? '')) ?>
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <form action="<?= BASE_URL ?>/owner/booking/update" method="POST" style="display:inline;" onsubmit="return confirm('Cập nhật trạng thái đặt sân này?')">
                                <?= \Core\Session::csrfField() ?>
                                <input type="hidden" name="booking_id" value="<?= (int)($b['id']) ?>">
                                <input type="hidden" name="branch_id" value="<?= (int)($b['branch_id']) ?>">
                                <select name="status" class="form-control" style="width:110px; font-size:0.8rem;" onchange="this.form.submit()">
                                    <option value="pending" <?= $b['status'] === 'pending' ? 'selected' : '' ?>>Chờ</option>
                                    <option value="confirmed" <?= $b['status'] === 'confirmed' ? 'selected' : '' ?>>Xác nhận</option>
                                    <option value="completed" <?= $b['status'] === 'completed' ? 'selected' : '' ?>>Hoàn thành</option>
                                    <option value="cancelled" <?= $b['status'] === 'cancelled' ? 'selected' : '' ?>>Hủy</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<style>
.badge-warning { background: #ffab00; color: #000; }
.badge-secondary { background: #64748b; color: #fff; }
.table-responsive { overflow-x: auto; }
.admin-table { border-collapse: collapse; width: 100%; }
.admin-table th, .admin-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left; font-size: 0.9rem; }
.admin-table th { font-weight: 600; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; }
.admin-table tr:hover { background: rgba(255,255,255,0.03); }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--glass-border); border-radius: 6px; background: rgba(255,255,255,0.03); color: #fff; font-size: 0.9rem; }
.form-label { font-size: 0.85rem; font-weight: 500; color: var(--text-muted); margin-bottom: 0.4rem; display: block; }
</style>

<?php $content = ob_get_clean(); require ROOT . '/app/Views/layouts/admin.php'; ?>
