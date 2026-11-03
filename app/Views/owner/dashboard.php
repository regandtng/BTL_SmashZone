<?php ob_start(); ?>

<main class="dashboard">
    <section class="dashboard-welcome">
        <div class="dashboard-welcome-copy">
            <span class="dashboard-eyebrow">BẢNG ĐIỀU KHIỂN</span>
            <h1>Chào mừng trở lại!</h1>
            <p>Theo dõi hoạt động và tình hình các chi nhánh của bạn tại đây.</p>
        </div>
        <div class="dashboard-date">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
            <span><?= date('d/m/Y') ?></span>
        </div>
    </section>

    <section class="dashboard-stats" aria-label="Tổng quan hoạt động">
        <article class="dashboard-stat">
            <div class="dashboard-stat-top">
                <span class="dashboard-stat-icon dashboard-icon-blue" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01"/></svg>
                </span>
                <span class="dashboard-stat-caption">TOÀN THỜI GIAN</span>
            </div>
            <p class="dashboard-stat-label">Tổng đặt sân</p>
            <strong class="dashboard-stat-value"><?= number_format((int)($total_bookings ?? 0)) ?></strong>
        </article>

        <article class="dashboard-stat">
            <div class="dashboard-stat-top">
                <span class="dashboard-stat-icon dashboard-icon-green" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </span>
                <span class="dashboard-stat-caption">TOÀN THỜI GIAN</span>
            </div>
            <p class="dashboard-stat-label">Tổng doanh thu</p>
            <strong class="dashboard-stat-value"><?= number_format((float)($total_revenue ?? 0), 0, ',', '.') ?><small>đ</small></strong>
        </article>

        <article class="dashboard-stat">
            <div class="dashboard-stat-top">
                <span class="dashboard-stat-icon dashboard-icon-amber" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </span>
                <span class="dashboard-stat-caption">HÔM NAY</span>
            </div>
            <p class="dashboard-stat-label">Đặt sân hôm nay</p>
            <strong class="dashboard-stat-value"><?= number_format((int)($today_bookings ?? 0)) ?></strong>
        </article>

        <article class="dashboard-stat">
            <div class="dashboard-stat-top">
                <span class="dashboard-stat-icon dashboard-icon-violet" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/></svg>
                </span>
                <span class="dashboard-stat-caption">ĐANG QUẢN LÝ</span>
            </div>
            <p class="dashboard-stat-label">Chi nhánh</p>
            <strong class="dashboard-stat-value"><?= number_format(count($branches ?? [])) ?></strong>
        </article>
    </section>

    <section class="dashboard-branches">
        <div class="dashboard-section-heading">
            <div>
                <span class="dashboard-eyebrow">VẬN HÀNH</span>
                <h2>Chi nhánh của bạn</h2>
                <p>Tổng quan nhanh và lối tắt đến lịch đặt từng chi nhánh.</p>
            </div>
            <a href="<?= BASE_URL ?>/owner/bookings" class="dashboard-primary-link">
                <span>Xem tất cả đặt sân</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>
        </div>

        <?php if (!empty($branches)): ?>
            <div class="dashboard-branch-grid">
                <?php foreach ($branches as $index => $branch): ?>
                    <article class="dashboard-branch-card">
                        <div class="dashboard-branch-heading">
                            <span class="dashboard-branch-index"><?= str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                            <span class="dashboard-branch-status"><i></i> Đang hoạt động</span>
                        </div>
                        <h3><?= htmlspecialchars($branch['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="dashboard-branch-metrics">
                            <div>
                                <span>Hôm nay</span>
                                <strong><?= number_format((int)($branch['today_bookings'] ?? 0)) ?> <small>lượt đặt</small></strong>
                            </div>
                            <div>
                                <span>Tổng lượt đặt</span>
                                <strong><?= number_format((int)($branch['total_bookings'] ?? 0)) ?></strong>
                            </div>
                            <div>
                                <span>Doanh thu</span>
                                <strong><?= number_format((float)($branch['total_revenue'] ?? 0), 0, ',', '.') ?><small>đ</small></strong>
                            </div>
                        </div>
                        <a class="dashboard-branch-link" href="<?= BASE_URL ?>/owner/bookings?branch_id=<?= (int)($branch['id'] ?? 0) ?>">
                            Quản lý lịch đặt
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="dashboard-empty">
                <span class="dashboard-empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M9 9v.01M9 12v.01M9 15v.01M9 18v.01"/></svg>
                </span>
                <h3>Chưa có chi nhánh được phân quyền</h3>
                <p>Vui lòng liên hệ quản trị viên để được hỗ trợ phân quyền quản lý chi nhánh.</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<style>
.dashboard { --dash-surface: rgba(255,255,255,.035); --dash-border: rgba(255,255,255,.075); --dash-muted: #9aa8bd; }
.dashboard-welcome { display:flex; justify-content:space-between; align-items:center; gap:1.5rem; padding:2rem 2.1rem; margin-bottom:1.4rem; border:1px solid rgba(41,121,255,.22); border-radius:18px; background:radial-gradient(ellipse at 86% 20%, rgba(41,121,255,.19), transparent 38%), linear-gradient(115deg, rgba(41,121,255,.09), rgba(0,230,118,.035)); }
.dashboard-eyebrow { display:block; color:#63a2ff; font-size:.69rem; font-weight:700; letter-spacing:.14em; }
.dashboard-welcome h1 { margin:.6rem 0 .45rem; color:#f8fafc; font-size:clamp(1.45rem, 3vw, 2rem); line-height:1.2; letter-spacing:-.04em; }
.dashboard-welcome p, .dashboard-section-heading p { margin:0; color:var(--dash-muted); font-size:.9rem; line-height:1.6; }
.dashboard-date { display:flex; align-items:center; gap:.65rem; flex:none; padding:.7rem .95rem; border:1px solid var(--dash-border); border-radius:11px; background:rgba(5,8,18,.38); color:#dce7f7; font-size:.85rem; font-weight:600; }
.dashboard-date svg { width:17px; height:17px; fill:none; stroke:#63a2ff; stroke-width:1.7; stroke-linecap:round; stroke-linejoin:round; }
.dashboard-stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:1rem; margin-bottom:2.1rem; }
.dashboard-stat { min-width:0; padding:1.2rem 1.25rem 1.3rem; border:1px solid var(--dash-border); border-radius:15px; background:linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.018)); box-shadow:0 12px 30px rgba(0,0,0,.1); }
.dashboard-stat-top { display:flex; align-items:center; justify-content:space-between; gap:.5rem; }
.dashboard-stat-icon { display:grid; width:39px; height:39px; place-items:center; border-radius:12px; }
.dashboard-stat-icon svg { width:20px; height:20px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.dashboard-icon-blue { color:#72aaff; background:rgba(41,121,255,.14); }
.dashboard-icon-green { color:#4ee3a1; background:rgba(0,230,118,.12); }
.dashboard-icon-amber { color:#ffc66d; background:rgba(245,158,11,.13); }
.dashboard-icon-violet { color:#b79aff; background:rgba(139,92,246,.14); }
.dashboard-stat-caption { color:#75839a; font-size:.59rem; font-weight:700; letter-spacing:.1em; text-align:right; }
.dashboard-stat-label { margin:1.05rem 0 .35rem; color:var(--dash-muted); font-size:.82rem; }
.dashboard-stat-value { display:block; overflow-wrap:anywhere; color:#f5f8fc; font-size:clamp(1.35rem, 2.2vw, 1.8rem); font-weight:700; letter-spacing:-.045em; line-height:1.25; }
.dashboard-stat-value small { margin-left:.3rem; color:#91a0b6; font-size:.78rem; font-weight:500; letter-spacing:0; }
.dashboard-section-heading { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; margin-bottom:1.1rem; }
.dashboard-section-heading h2 { margin:.35rem 0 .25rem; color:#f3f6fb; font-size:1.28rem; letter-spacing:-.025em; }
.dashboard-section-heading p { font-size:.82rem; }
.dashboard-primary-link, .dashboard-branch-link { display:inline-flex; align-items:center; justify-content:center; gap:.55rem; min-height:42px; padding:.65rem .9rem; border-radius:10px; font-size:.8rem; font-weight:600; transition:background .18s ease, border-color .18s ease, transform .18s ease; }
.dashboard-primary-link { flex:none; border:1px solid rgba(41,121,255,.35); background:rgba(41,121,255,.14); color:#a9cbff; }
.dashboard-primary-link:hover { border-color:rgba(41,121,255,.65); background:rgba(41,121,255,.22); transform:translateY(-1px); }
.dashboard-primary-link svg, .dashboard-branch-link svg { width:17px; height:17px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
.dashboard-branch-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:1rem; }
.dashboard-branch-card { min-width:0; padding:1.15rem; border:1px solid var(--dash-border); border-radius:15px; background:var(--dash-surface); transition:transform .18s ease, border-color .18s ease, background .18s ease; }
.dashboard-branch-card:hover { transform:translateY(-2px); border-color:rgba(41,121,255,.28); background:rgba(255,255,255,.05); }
.dashboard-branch-heading { display:flex; justify-content:space-between; align-items:center; gap:.5rem; }
.dashboard-branch-index { color:#6f7e95; font-size:.72rem; font-weight:700; letter-spacing:.1em; }
.dashboard-branch-status { display:inline-flex; align-items:center; gap:.4rem; color:#7ee5ae; font-size:.67rem; font-weight:600; white-space:nowrap; }
.dashboard-branch-status i { width:6px; height:6px; border-radius:50%; background:#38d98b; box-shadow:0 0 9px rgba(56,217,139,.65); }
.dashboard-branch-card h3 { margin:.8rem 0 1rem; color:#f2f6fc; font-size:1rem; line-height:1.4; }
.dashboard-branch-metrics { display:grid; grid-template-columns:1fr 1fr; gap:.85rem .65rem; padding:1rem 0; border-top:1px solid var(--dash-border); border-bottom:1px solid var(--dash-border); }
.dashboard-branch-metrics div:last-child { grid-column:1 / -1; }
.dashboard-branch-metrics span { display:block; margin-bottom:.3rem; color:#8290a6; font-size:.68rem; }
.dashboard-branch-metrics strong { color:#e7edf7; font-size:.9rem; font-weight:650; overflow-wrap:anywhere; }
.dashboard-branch-metrics small { margin-left:.25rem; color:#9aa8bd; font-size:.68rem; font-weight:500; }
.dashboard-branch-link { justify-content:space-between; width:100%; min-height:38px; margin-top:.85rem; padding:.55rem .7rem; border:1px solid transparent; color:#8db9ff; }
.dashboard-branch-link:hover { border-color:rgba(41,121,255,.2); background:rgba(41,121,255,.09); }
.dashboard-empty { display:flex; flex-direction:column; align-items:center; padding:2.8rem 1.5rem; border:1px dashed rgba(255,255,255,.13); border-radius:15px; background:rgba(255,255,255,.018); text-align:center; }
.dashboard-empty-icon { display:grid; width:52px; height:52px; place-items:center; margin-bottom:1rem; border-radius:16px; background:rgba(41,121,255,.12); color:#72aaff; }
.dashboard-empty-icon svg { width:25px; height:25px; fill:none; stroke:currentColor; stroke-width:1.7; stroke-linecap:round; stroke-linejoin:round; }
.dashboard-empty h3 { margin:0 0 .45rem; color:#e6edf7; font-size:1rem; }
.dashboard-empty p { max-width:440px; margin:0; color:var(--dash-muted); font-size:.84rem; line-height:1.6; }
@media (max-width:1100px) { .dashboard-stats { grid-template-columns:repeat(2,minmax(0,1fr)); } .dashboard-branch-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media (max-width:700px) { .dashboard-welcome { align-items:flex-start; flex-direction:column; padding:1.4rem; } .dashboard-date { padding:.55rem .75rem; } .dashboard-section-heading { align-items:flex-start; flex-direction:column; } .dashboard-primary-link { width:100%; } .dashboard-branch-grid { grid-template-columns:1fr; } }
@media (max-width:420px) { .dashboard-stats { gap:.65rem; } .dashboard-stat { padding:.9rem; border-radius:12px; } .dashboard-stat-icon { width:34px; height:34px; border-radius:10px; } .dashboard-stat-caption { font-size:.52rem; letter-spacing:.06em; } .dashboard-stat-label { min-height:2.2em; margin-top:.8rem; font-size:.72rem; } .dashboard-stat-value { font-size:1.35rem; } }
</style>

<?php $content = ob_get_clean(); require ROOT . '/app/Views/layouts/admin.php'; ?>
