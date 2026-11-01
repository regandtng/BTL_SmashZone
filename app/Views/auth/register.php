<?php ob_start(); ?>

<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Đăng Ký <span>Tài Khoản</span></h1>
        <p class="auth-subtitle">Tạo tài khoản để đặt sân nhanh hơn và quản lý lịch của bạn.</p>

        <?php if(isset($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/register" method="POST">
            <div class="form-group">
                <label class="form-label">Họ và tên</label>
                <input type="text" name="name" class="form-control" placeholder="Họ và tên" required>
            </div>
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="Email" required>
            </div>
            <div class="form-group">
                <label class="form-label">Số điện thoại</label>
                <input type="tel" name="phone" class="form-control" placeholder="Số điện thoại" required>
            </div>
            <div class="form-group">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự" required>
            </div>
            <div class="form-group">
                <label class="form-label">Xác nhận mật khẩu</label>
                <input type="password" name="password_confirmation" class="form-control" placeholder="Nhập lại mật khẩu" required>
            </div>

            <button type="submit" class="btn btn-accent w-100" style="padding:0.85rem; font-size:1rem;">Đăng Ký</button>
        </form>

        <div class="auth-links">
            <a href="<?= BASE_URL ?>/login">Đã có tài khoản? Đăng nhập</a>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
