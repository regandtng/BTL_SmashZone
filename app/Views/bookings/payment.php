<?php ob_start(); ?>

<div class="page-wrap">
    <div class="container" style="max-width:1000px;">
        <div class="step-indicator">
            <span class="step done">1. Chọn sân ✓</span>
            <span class="step done">2. Thông tin ✓</span>
            <span class="step active">3. Thanh toán</span>
            <span class="step">4. Hoàn tất</span>
        </div>

        <!-- Countdown Timer -->
        <div class="countdown-banner" id="countdownBanner">
            <div class="countdown-content">
                <span class="countdown-icon">⏱️</span>
                <span class="countdown-text">Thời gian thanh toán còn lại: <strong id="countdownTimer">03:00</strong></span>
                <span class="countdown-hint">Quá 3 phút đơn sẽ bị hủy tự động</span>
            </div>
        </div>

        <div class="checkout-grid">
            <!-- Left: Summary -->
            <div class="card p-4">
                <h3 class="h5 text-accent mb-3">Thông tin đặt sân</h3>
                <ul class="summary-list">
                    <li><span class="text-muted">Mã đặt sân</span> <strong><?= htmlspecialchars($booking['booking_code'] ?? ('#TMP-' . rand(1000,9999))) ?></strong></li>
                    <li><span class="text-muted">Khách hàng</span> <strong><?= htmlspecialchars($booking['customer_name'] ?? '') ?></strong></li>
                    <li><span class="text-muted">SĐT</span> <strong><?= htmlspecialchars($booking['customer_phone'] ?? '') ?></strong></li>
                </ul>
                <div class="summary-total">
                    <span>Tổng thanh toán</span>
                    <span><?= number_format($booking['total_price'] ?? 0) ?>đ</span>
                </div>
            </div>

            <!-- Right: Payment Methods -->
            <div class="card p-4">
                <h3 class="h5 mb-3">Phương thức thanh toán</h3>
                <form action="<?= BASE_URL ?>/payment/process" method="POST">
                    <?= \Core\Session::csrfField() ?>
                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id'] ?? '') ?>">
                    <label>
                        <input type="radio" name="payment_method" value="qr" style="display:none;" required>
                        <div class="payment-card">
                            <span class="pay-icon pay-icon-bank">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="#fff" d="M12 2.4 2.4 8.6h19.2L12 2.4z"/>
                                    <rect x="5" y="11" width="3.2" height="7" rx="0.6" fill="#fff"/>
                                    <rect x="10.4" y="11" width="3.2" height="7" rx="0.6" fill="#fff"/>
                                    <rect x="15.8" y="11" width="3.2" height="7" rx="0.6" fill="#fff"/>
                                    <path fill="#fff" d="M3 20.6h18v-2H3v2z"/>
                                </svg>
                            </span>
                            <div class="pay-label">
                                <div class="pay-title">Chuyển khoản QR Code</div>
                                <div class="pay-desc">Quét mã QR qua app ngân hàng</div>
                            </div>
                        </div>
                        <div class="payment-details">
                            <p>Quét mã QR để thanh toán. Giao dịch sẽ tự động xác nhận.</p>
                            <div style="width: 200px; height: 200px; margin: 1rem auto 0; display:flex; align-items:center; justify-content:center; border:1px solid var(--border); border-radius:8px; overflow:hidden; background:#fff;">
                                <img src="<?= BASE_URL ?>/assets/images/qr-bank.png" alt="QR Code ngân hàng" style="max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div style="display:none; width:100%; height:100%; background:#fff; align-items:center; justify-content:center; color:#000; font-size:0.8rem;">[Chưa có file QR: assets/images/qr-bank.png]</div>
                            </div>
                        </div>
                    </label>

                    <label>
                        <input type="radio" name="payment_method" value="momo" style="display:none;">
                        <div class="payment-card">
                            <span class="pay-icon pay-icon-momo">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="#fff" d="M6.5 18V8.2L12 14.1l5.5-5.9V18h-2.2v-3.9L12 18H8.7V18H6.5z"/>
                                </svg>
                            </span>
                            <div class="pay-label">
                                <div class="pay-title">Ví điện tử MoMo</div>
                                <div class="pay-desc">Chuyển khoản đến số 0981622131</div>
                            </div>
                        </div>
                        <div class="payment-details">
                            <p class="m-0">Chuyển tiền đến số MoMo: <strong>0981622131</strong></p>
                            <p class="m-0">Nội dung: <strong>Thanh toan dat san </strong></p>
                        </div>
                    </label>

                    <label>
                        <input type="radio" name="payment_method" value="cash" style="display:none;">
                        <div class="payment-card">
                            <span class="pay-icon pay-icon-cash">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill="#fff" d="M2.5 8A1.5 1.5 0 0 1 4 6.5h16A1.5 1.5 0 0 1 21.5 8v8a1.5 1.5 0 0 1-1.5 1.5H4A1.5 1.5 0 0 1 2.5 16V8z"/>
                                    <circle cx="12" cy="12" r="3" fill="#0f172a" opacity="0.15"/>
                                    <circle cx="12" cy="12" r="3" fill="none" stroke="#0f172a" stroke-width="1.5" opacity="0.7"/>
                                    <circle cx="16" cy="9.5" r="1.2" fill="#0f172a" opacity="0.7"/>
                                    <circle cx="8" cy="14.5" r="1.2" fill="#0f172a" opacity="0.7"/>
                                </svg>
                            </span>
                            <div class="pay-label">
                                <div class="pay-title">Tiền mặt tại sân</div>
                                <div class="pay-desc">Thanh toán khi đến nhận sân</div>
                            </div>
                        </div>
                        <div class="payment-details">
                            <p class="m-0 text-accent font-weight-600">Bạn có thể thanh toán trực tiếp tại quầy khi đến sân.</p>
                            <p class="m-0 mt-1">Vui lòng đến trước 10 phút để nhận sân.</p>
                        </div>
                    </label>

                    <button type="submit" class="btn btn-accent w-100 mt-4" style="padding:0.9rem; font-size:1.05rem;">Hoàn Tất Đặt Sân</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.payment-card { background: #fff; border: 2px solid var(--border-strong); border-radius: var(--radius); padding: 0.9rem 1rem; margin-bottom: 0.5rem; cursor: pointer; display: flex; align-items: center; gap: 1rem; transition: all 0.2s; }
.payment-card:hover { border-color: var(--accent-border); background: var(--accent-light); }
.payment-card.selected { border-color: var(--accent); background: var(--accent-light); box-shadow: 0 0 0 3px var(--accent-glow); }
.payment-details { display: none; margin: 0.5rem 0 1rem; padding: 1rem; background: #f8fafc; border: 1px solid var(--border); border-radius: var(--radius-sm); font-size: 0.9rem; color: var(--text-secondary); }
input[name="payment_method"]:checked + .payment-card + .payment-details { display: block; }
.pay-icon { width: 46px; height: 46px; border-radius: 13px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: var(--shadow-sm); }
.pay-icon svg { width: 27px; height: 27px; display: block; }
.pay-icon-bank { background: linear-gradient(135deg, #1e40af, #3b82f6); }
.pay-icon-momo { background: linear-gradient(135deg, #d4145a, #a50064); }
.pay-icon-cash { background: linear-gradient(135deg, #047857, #10b981); }
.pay-label { display: flex; flex-direction: column; gap: 2px; }
.pay-title { font-size: 15.5px; font-weight: 700; color: var(--text-primary); }
.pay-desc { font-size: 13px; color: var(--text-muted); }

/* Countdown Banner */
.countdown-banner {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border: 1px solid rgba(41, 121, 255, 0.3);
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(41, 121, 255, 0.1); }
    50% { box-shadow: 0 0 0 8px rgba(41, 121, 255, 0.05); }
}

.countdown-banner.warning {
    border-color: #ffb300;
    background: linear-gradient(135deg, #3d2e00 0%, #1e1700 100%);
    animation: pulseWarning 1s infinite;
}

@keyframes pulseWarning {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255, 179, 0, 0.2); }
    50% { box-shadow: 0 0 0 8px rgba(255, 179, 0, 0.05); }
}

.countdown-banner.critical {
    border-color: #ff4d4d;
    background: linear-gradient(135deg, #3d0000 0%, #1e0000 100%);
    animation: pulseCritical 0.5s infinite;
}

@keyframes pulseCritical {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255, 77, 77, 0.3); }
    50% { box-shadow: 0 0 0 8px rgba(255, 77, 77, 0.1); }
}

.countdown-banner.expired {
    animation: none;
    border-color: #ff4d4d;
    background: linear-gradient(135deg, #3d0000 0%, #1e0000 100%);
}

.countdown-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.countdown-text {
    font-size: 1rem;
    color: #e2e8f0;
    font-weight: 500;
}

#countdownTimer {
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    font-size: 1.25rem;
    font-weight: 700;
    color: #00e676;
    transition: color 0.3s;
}

.countdown-banner.warning #countdownTimer {
    color: #ffb300;
}

.countdown-banner.critical #countdownTimer {
    color: #ff4d4d;
    animation: blink 0.5s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.countdown-hint {
    font-size: 0.8rem;
    color: #94a3b8;
}

@media (max-width: 600px) {
    .countdown-banner {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>

<script>
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.payment-card').forEach(card => card.classList.remove('selected'));
        this.nextElementSibling.classList.add('selected');
    });
});

// Countdown Timer - 3 minutes (180 seconds)
(function() {
    const timerEl = document.getElementById('countdownTimer');
    const bannerEl = document.getElementById('countdownBanner');
    if (!timerEl || !bannerEl) return;
    
    // Try to parse expires_at from booking data
    let expiresAtRaw = '<?= $booking['expires_at'] ?? '' ?>';
    console.log('DEBUG expiresAtRaw:', expiresAtRaw);
    
    let expires = 0;
    if (expiresAtRaw) {
        let expiresAt = expiresAtRaw.replace(' ', 'T');
        console.log('DEBUG expiresAt (ISO):', expiresAt);
        expires = new Date(expiresAt).getTime();
        console.log('DEBUG expires (timestamp):', expires, 'now:', Date.now(), 'diff:', expires - Date.now());
    }
    
    // If expires_at is missing, invalid, or already in the past, use fallback (3 min from page load)
    if (!expiresAtRaw || isNaN(expires) || expires <= Date.now()) {
        console.log('DEBUG: Using fallback - 3 minutes from now');
        expires = Date.now() + 3 * 60 * 1000;
    }
    
    function updateTimer() {
        const now = Date.now();
        const diff = expires - now;
        
        if (diff <= 0) {
            timerEl.textContent = '00:00';
            timerEl.style.color = '#ff4d4d';
            bannerEl.classList.add('expired');
            // Redirect after 1 second
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>/branches';
            }, 1000);
            return;
        }
        
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);
        timerEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        
        // Warning colors
        if (diff <= 60000) { // 1 minute
            bannerEl.classList.add('warning');
        }
        if (diff <= 30000) { // 30 seconds
            bannerEl.classList.add('critical');
        }
    }
    
    updateTimer();
    const interval = setInterval(updateTimer, 1000);
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => clearInterval(interval));
})();
</script>

<?php $content = ob_get_clean(); require __DIR__ . '/../layouts/main.php'; ?>