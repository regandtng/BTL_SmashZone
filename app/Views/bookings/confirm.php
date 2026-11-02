<?php ob_start(); ?>

<div class="page-wrap">
    <div class="container" style="max-width:1000px;">
        <div class="step-indicator">
            <span class="step done">1. Chọn sân ✓</span>
            <span class="step active">2. Thông tin cá nhân</span>
            <span class="step">3. Thanh toán</span>
            <span class="step">4. Hoàn tất</span>
        </div>

        <div class="checkout-grid">
            <!-- Left: Summary -->
            <div class="card p-4">
                <h3 class="h5 text-accent mb-3">Tóm tắt đặt sân</h3>
                <ul class="summary-list">
                    <li><span class="text-muted">Chi nhánh</span> <strong><?= htmlspecialchars($branch['name'] ?? ($pending['branch_name'] ?? 'SmashZone')) ?></strong></li>
                    <li><span class="text-muted">Sân</span> <strong><?= htmlspecialchars($pending['court_name'] ?? 'Sân') ?></strong></li>
                    <li><span class="text-muted">Ngày</span> <strong><?= htmlspecialchars(date('d/m/Y', strtotime($pending['booking_date'] ?? date('Y-m-d')))) ?></strong></li>
                    <li><span class="text-muted">Thời gian</span> <strong><?= sprintf('%02d:00 - %02d:00', $pending['start_hour'] ?? 0, $pending['end_hour'] ?? 0) ?></strong></li>
                </ul>
                <div class="summary-total">
                    <span>Tổng cộng</span>
                    <span><?= number_format($pending['total_price'] ?? 0) ?>đ</span>
                </div>
            </div>

            <!-- Right: Form -->
            <div class="card p-4">
                <h3 class="h5 mb-3">Thông tin liên hệ</h3>
                <form action="<?= BASE_URL ?>/booking/confirm" method="POST">
                    <?php \Core\Session::generateCsrfToken(); ?>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\Core\Session::get('csrf_token')) ?>">

                    <div class="form-group">
                        <label class="form-label">Họ tên *</label>
                        <input type="text" name="customer_name" class="form-control" required value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="tel" name="customer_phone" class="form-control" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email (Tùy chọn)</label>
                        <input type="email" name="customer_email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Ghi chú thêm..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-accent w-100">Xác Nhận Đặt Sân</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
