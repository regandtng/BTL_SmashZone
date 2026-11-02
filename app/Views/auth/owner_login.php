<?php ob_start(); ?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Đăng Nhập Quản Trị <span>SmashZone</span></h1>
        <p class="auth-subtitle">Chỉ dành cho chủ sân &amp; super admin. Quản lý chi nhánh, giá và doanh thu.</p>

        <?php if(isset($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/owner/login" method="POST" novalidate>
            <div class="form-group">
                <label class="form-label">Tên đăng nhập</label>
                <input type="text" name="username" class="form-control" placeholder="admin" required>
            </div>
            <div class="form-group">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-accent w-100" style="padding:0.85rem; font-size:1rem;">Đăng Nhập Quản Trị</button>
        </form>

        <div class="auth-links">
            <a href="<?= BASE_URL ?>/login">Quay lại đăng nhập khách hàng</a>
            <a href="<?= BASE_URL ?>/" class="text-muted">Trang chủ</a>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
