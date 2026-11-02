<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="m-0" style="font-size: 1.25rem; font-weight: 600;">Báo Cáo Doanh Thu</h2>
</div>

<!-- Bộ lọc -->
<form action="<?= BASE_URL ?>/owner/revenue" method="GET" class="admin-card glass-panel p-3 mb-4" style="display:flex; gap:1rem; flex-wrap:wrap; align-items:center;">
    <?php if ($is_super): ?>
        <label class="form-label font-weight-500" style="margin:0; font-size:0.85rem;">Chi nhánh</label>
        <select name="branch_id" class="form-control" style="width:auto; min-width:200px;" onchange="this.form.submit()">
            <option value="0">Tất cả chi nhánh</option>
            <?php foreach ($branches as $b): ?>
                <option value="<?= (int)($b['id']) ?>" <?= ((int)($branch_id ?? 0) === (int)($b['id'])) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <label class="form-label font-weight-500" style="margin:0; font-size:0.85rem;">Từ ngày</label>
    <input type="date" name="from" class="form-control" style="width:auto; min-width:150px;" value="<?= htmlspecialchars($from ?? '') ?>">

    <label class="form-label font-weight-500" style="margin:0; font-size:0.85rem;">Đến ngày</label>
    <input type="date" name="to" class="form-control" style="width:auto; min-width:150px;" value="<?= htmlspecialchars($to ?? '') ?>">

    <button type="submit" class="btn btn-accent btn-sm">Xem Báo Cáo</button>
</form>

<!-- Thống kê tổng quan -->
<div class="admin-stats row g-3 mb-4">
    <div class="col-md-6 col-12">
        <div class="stat-card glass-panel p-3">
            <div class="stat-label text-muted font-weight-500">Tổng Doanh Thu</div>
            <div class="stat-value text-accent font-weight-700" style="font-size: 1.75rem;"><?= number_format($revenue ?? 0, 0, ',', '.') ?> đ</div>
        </div>
    </div>
    <div class="col-md-6 col-12">
        <div class="stat-card glass-panel p-3">
            <div class="stat-label text-muted font-weight-500">Số Đơn Đã Xác Nhận</div>
            <div class="stat-value text-accent font-weight-700" style="font-size: 1.75rem;"><?= count($bookings ?? []) ?></div>
        </div>
    </div>
</div>

<?php if (empty($bookings)): ?>
    <div class="admin-card glass-panel p-4 text-center">
        <h3 class="m-0 text-muted font-weight-500">Không có đơn hàng nào trong khoảng thời gian này.</h3>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="admin-table w-100">
            <thead>
                <tr>
                    <th style="text-align:left;">Mã Đặt</th>
                    <th style="text-align:left;">Khách Hàng</th>
                    <th style="text-align:left;">Chi Nhánh</th>
                    <th style="text-align:center;">Ngày</th>
                    <th style="text-align:right;">Tổng Tiền</th>
                    <th style="text-align:center;">Trạng Thái</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td style="font-family:monospace; font-weight:600; font-size:0.85rem;"><?= htmlspecialchars($b['booking_code'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['customer_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['branch_name'] ?? '') ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($b['booking_date'] ?? '') ?></td>
                        <td style="text-align:right; font-weight:600;"><?= number_format($b['total_price'] ?? 0, 0, ',', '.') ?> đ</td>
                        <td style="text-align:center;">
                            <span class="badge badge-<?= $b['status'] === 'confirmed' ? 'success' : ($b['status'] === 'completed' ? 'secondary' : 'danger') ?>">
                                <?= ucfirst(htmlspecialchars($b['status'] ?? '')) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<style>
.admin-stats { --bs-gutter-x: 1rem; --bs-gutter-y: 1rem; }
.admin-stats > * { padding-left: calc(var(--bs-gutter-x) * 0.5); padding-right: calc(var(--bs-gutter-x) * 0.5); margin-top: var(--bs-gutter-y); }
.col-md-6 { width: 50%; padding-left: 0.5rem; padding-right: 0.5rem; }
.col-12 { width: 100%; padding-left: 0.5rem; padding-right: 0.5rem; }
@media (max-width: 768px) { .col-md-6, .col-12 { width: 100%; } }
.stat-card { background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02)); }
.stat-label { font-size: 0.85rem; margin-bottom: 0.5rem; display: block; }
.stat-value { display: block; }
.badge { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; color: #fff; }
.badge-success { background: #00e676; color: #000; }
.badge-danger { background: #ff5252; color: #fff; }
.badge-secondary { background: #64748b; color: #fff; }
.btn-sm { padding: 6px 14px; font-size: 0.85rem; }
.btn-accent { background: var(--accent-blue); border: none; color: #fff; }
.btn-accent:hover { background: #1a6bf2; }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid var(--glass-border); border-radius: 6px; background: rgba(255,255,255,0.03); color: #fff; font-size: 0.9rem; }
.form-label { font-size: 0.85rem; font-weight: 500; color: var(--text-muted); margin-bottom: 0.4rem; display: block; }
.admin-table { border-collapse: collapse; width: 100%; }
.admin-table th, .admin-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.05); text-align: left; font-size: 0.9rem; }
.admin-table th { font-weight: 600; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; }
.admin-table tr:hover { background: rgba(255,255,255,0.03); }
.table-responsive { overflow-x: auto; }
.row { display: flex; flex-wrap: wrap; margin-left: -0.5rem; margin-right: -0.5rem; }
</style>

<?php $content = ob_get_clean(); require ROOT . '/app/Views/layouts/admin.php'; ?>
