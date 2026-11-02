<?php ob_start(); ?>

<div class="admin-stats row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card glass-panel p-3">
            <div class="stat-label text-muted font-weight-500">Tổng đặt sân</div>
            <div class="stat-value text-accent font-weight-700" style="font-size: 1.75rem;"><?= (int)($total_bookings ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card glass-panel p-3">
            <div class="stat-label text-muted font-weight-500">Tổng doanh thu</div>
            <div class="stat-value text-accent font-weight-700" style="font-size: 1.75rem;"><?= number_format($total_revenue ?? 0, 0, ',', '.') ?> đ</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card glass-panel p-3">
            <div class="stat-label text-muted font-weight-500">Đặt hôm nay</div>
            <div class="stat-value text-accent font-weight-700" style="font-size: 1.75rem;"><?= (int)($today_bookings ?? 0) ?></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card glass-panel p-3">
            <div class="stat-label text-muted font-weight-500">Chi nhánh quản lý</div>
            <div class="stat-value text-accent font-weight-700" style="font-size: 1.75rem;"><?= count($branches ?? []) ?></div>
        </div>
    </div>
</div>

<?php if (!empty($branches)): ?>
    <div class="admin-card glass-panel p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0" style="font-size: 1.125rem; font-weight: 600;">Danh Sách Chi Nhánh Của Bạn</h3>
            <a href="<?= BASE_URL ?>/owner/bookings" class="btn btn-accent btn-sm">Xem Tất Cả Đặt Sân</a>
        </div>
        <div class="row g-3">
            <?php foreach ($branches as $branch): ?>
                <div class="col-md-4 col-6">
                    <div class="branch-item glass-panel p-3 h-100 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="font-weight-600"><?= htmlspecialchars($branch['name']) ?></span>
                            <span class="badge badge-accent"><?= htmlspecialchars($branch['city'] ?? '') ?></span>
                        </div>
                        <div class="text-muted font-weight-500 mb-1 flex-grow-1">
                            <?= htmlspecialchars($branch['address'] ?? '') ?>
                        </div>
                        <a href="<?= BASE_URL ?>/owner/bookings?branch_id=<?= (int)($branch['id']) ?>" class="btn btn-outline-accent btn-sm mt-auto">
                            Xem Đặt Sân
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <div class="admin-card glass-panel p-4 text-center mb-4">
        <h3 class="m-0 text-muted font-weight-500">Bạn chưa được phân quyền quản lý chi nhánh nào.</h3>
        <p class="text-muted font-weight-400" style="font-size: 0.9rem;">Vui lòng liên hệ super admin để được gán quyền quản lý chi nhánh.</p>
    </div>
<?php endif; ?>

<style>
.admin-stats { --bs-gutter-x: 1rem; --bs-gutter-y: 1rem; }
.admin-stats > * { padding-left: calc(var(--bs-gutter-x) * 0.5); padding-right: calc(var(--bs-gutter-x) * 0.5); margin-top: var(--bs-gutter-y); }
.stat-card { background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(255,255,255,0.02)); }
.stat-label { font-size: 0.85rem; margin-bottom: 0.5rem; display: block; }
.stat-value { display: block; }
.admin-card { border-radius: 12px; }
.branch-item { border-radius: 10px; transition: transform 0.15s; }
.branch-item:hover { transform: translateY(-2px); background: rgba(255,255,255,0.05); }
.badge-accent { background: var(--accent-blue); color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.row { display: flex; flex-wrap: wrap; margin-left: -0.5rem; margin-right: -0.5rem; }
.col-md-3 { width: 25%; padding-left: 0.5rem; padding-right: 0.5rem; }
.col-md-4 { width: 33.333333%; padding-left: 0.5rem; padding-right: 0.5rem; }
.col-6 { width: 50%; padding-left: 0.5rem; padding-right: 0.5rem; }
@media (max-width: 768px) {
    .col-md-3, .col-md-4, .col-6 { width: 100%; }
}
</style>

<?php $content = ob_get_clean(); require ROOT . '/app/Views/layouts/admin.php'; ?>
