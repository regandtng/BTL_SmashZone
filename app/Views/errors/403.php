<?php ob_start(); ?>

<div class="error-wrap">
    <div class="error-card">
        <div class="error-code"><?= htmlspecialchars($title ?? '403') ?></div>
        <div class="error-message"><?= htmlspecialchars($message ?? 'Bạn không có quyền truy cập trang này.') ?></div>
        <a href="<?= BASE_URL ?>/owner/dashboard" class="btn btn-accent">Về Dashboard</a>
        <a href="<?= BASE_URL ?>/" class="btn btn-outline-accent ml-2">Trang Chủ</a>
    </div>
</div>

<style>
.error-wrap { display: flex; align-items: center; justify-content: center; min-height: 60vh; }
.error-card { text-align: center; max-width: 400px; padding: 2rem; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
.error-code { font-size: 3rem; font-weight: 800; color: #ef4444; margin-bottom: 0.5rem; }
.error-message { color: #64748b; margin-bottom: 1.5rem; font-size: 1rem; }
.btn { display: inline-block; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 0.95rem; }
.btn-accent { background: #0f172a; color: #fff; }
.btn-accent:hover { background: #1e293b; }
.btn-outline-accent { border: 1px solid #cbd5e1; color: #0f172a; margin-left: 0.5rem; }
.btn-outline-accent:hover { background: #f8fafc; }
.ml-2 { margin-left: 0.5rem; }
</style>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>
