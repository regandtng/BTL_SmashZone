<?php ob_start(); ?>

<div class="container py-4">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="javascript:history.back()" class="btn btn-back">
            <span>← Quay lại chi tiết sân</span>
        </a>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h2 font-weight-800 text-dark mb-1">Chọn Giờ Đặt Sân</h1>
            <p class="text-muted m-0">Vui lòng chọn khung giờ bạn muốn đặt tại <strong class="text-dark"><?= htmlspecialchars($branchName ?? 'Chi nhánh') ?></strong> - <strong class="text-accent"><?= htmlspecialchars($courtName ?? 'Sân') ?></strong></p>
        </div>
    </div>

    <div class="grid-container">
        <!-- Time slot selection -->
        <div class="card p-4 border" style="border-color: #cbd5e1 !important;">
            <h2 class="h5 font-weight-700 text-dark mb-3">Chọn Ngày & Khung Giờ</h2>
            
            <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-3 border" style="border-color: #cbd5e1 !important;">
                <label for="booking_date" class="font-weight-600 text-dark m-0">Ngày đặt sân:</label>
                <input type="date" id="booking_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($date ?? date('Y-m-d')) ?>" style="max-width: 180px; border: 1.5px solid #cbd5e1; border-radius: 8px; font-weight: 600; padding: 0.4rem 0.8rem; color: #0f172a;">
            </div>
            
            <div class="slot-legend d-flex gap-4 mb-3 fs-sm">
                <div class="d-flex align-items-center gap-2"><span class="legend-box slot-available"></span> <span class="text-dark font-weight-500">Giờ Trống</span></div>
                <div class="d-flex align-items-center gap-2"><span class="legend-box slot-selected"></span> <span class="text-dark font-weight-500">Đang Chọn</span></div>
                <div class="d-flex align-items-center gap-2"><span class="legend-box slot-disabled"></span> <span class="text-muted font-weight-500">Đã Đặt</span></div>
            </div>

            <div class="slot-grid" id="slot-grid">
                <?php 
                $slots = $timeSlots ?? [];
                if (empty($slots)) {
                    for($i=5; $i<=21; $i++) {
                        $isPeak = ($i>=17 || ($i>=8 && $i<14));
                        $slots[] = ['hour'=>$i, 'time'=>sprintf('%02d:00', $i), 'price'=> $isPeak ? 150000 : 80000, 'status'=>'available'];
                    }
                }
                foreach($slots as $slot): 
                    $isBooked = $slot['status'] === 'booked';
                ?>
                <div class="time-slot <?= $isBooked ? 'disabled' : '' ?>" data-hour="<?= $slot['hour'] ?>" data-price="<?= $slot['price'] ?>">
                    <div class="slot-time"><?= $slot['time'] ?></div>
                    <div class="slot-price"><?= number_format($slot['price']) ?>đ</div>
                    <div class="slot-status"><?= $isBooked ? 'Đã đặt' : 'Trống' ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="card p-4 h-fit border" style="border-color: #cbd5e1 !important;">
            <h2 class="h5 font-weight-700 text-dark pb-3 mb-3 border-bottom">Thông Tin Đặt Sân</h2>
            
            <div class="summary-details mb-4">
                <div class="summary-row"><span class="text-muted">Chi nhánh:</span> <strong class="text-dark"><?= htmlspecialchars($branchName ?? 'Chi nhánh') ?></strong></div>
                <div class="summary-row"><span class="text-muted">Sân:</span> <strong class="text-accent"><?= htmlspecialchars($courtName ?? 'Sân') ?></strong></div>
                <div class="summary-row"><span class="text-muted">Ngày đặt:</span> <strong id="summary-date" class="text-dark"><?= date('d/m/Y', strtotime($date ?? 'today')) ?></strong></div>
                <div class="summary-row"><span class="text-muted">Khung giờ:</span> <strong id="summary-time" class="text-dark" style="font-size: 1.05rem;">--:--</strong></div>
                <div class="summary-row"><span class="text-muted">Thời lượng:</span> <strong id="summary-duration" class="text-dark">0</strong> giờ</div>
            </div>

            <div class="total-box p-3 bg-light rounded-3 border mb-4" style="border-color: #cbd5e1 !important;">
                <div class="text-muted small font-weight-600 mb-1">Tổng thanh toán dự kiến:</div>
                <div class="total-price" id="summary-total" style="font-size: 1.85rem; font-weight: 800; color: #047857;">0 VNĐ</div>
            </div>

            <form action="<?= BASE_URL ?>/booking/store" method="POST" id="booking-form">
                <input type="hidden" name="court_id" value="<?= $courtId ?? 1 ?>">
                <input type="hidden" name="booking_date" id="form_booking_date" value="<?= htmlspecialchars($date ?? date('Y-m-d')) ?>">
                <input type="hidden" name="start_hour" id="form_start_hour" value="">
                <input type="hidden" name="end_hour" id="form_end_hour" value="">
                <button type="submit" class="btn btn-accent w-100 py-3 font-weight-700" id="btn-submit" disabled style="opacity: 0.5;">
                    Xác Nhận Đặt Sân
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.grid-container { display: grid; grid-template-columns: 2.2fr 1fr; gap: 1.5rem; }
.h-fit { height: fit-content; }
.slot-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(95px, 1fr)); gap: 10px; margin-top: 10px; }
.time-slot { background: #ffffff; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 10px; text-align: center; cursor: pointer; transition: all 0.2s; }
.time-slot:hover:not(.disabled) { border-color: var(--accent-green); background: #ecfdf5; transform: translateY(-1px); }
.time-slot.selected { border-color: var(--accent-green); background: #dcfce7; box-shadow: 0 0 0 2px var(--accent-green); }
.time-slot.disabled { background: #fee2e2; border-color: #fca5a5; cursor: not-allowed; opacity: 0.6; }

.slot-time { font-weight: 800; font-size: 1.05rem; color: #0f172a; }
.slot-price { font-size: 0.825rem; font-weight: 600; color: #334155; margin: 2px 0; }
.slot-status { font-size: 0.75rem; font-weight: 700; color: #047857; }
.time-slot.disabled .slot-status { color: #b91c1c; }

.legend-box { width: 14px; height: 14px; border-radius: 3px; display: inline-block; }
.slot-available { background: #ffffff; border: 1.5px solid #cbd5e1; }
.slot-selected { background: #dcfce7; border: 1.5px solid var(--accent-green); }
.slot-disabled { background: #fee2e2; border: 1.5px solid #fca5a5; }

.summary-row { display: flex; justify-content: space-between; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px dashed #e2e8f0; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let selectedSlots = [];
    const dateInput = document.getElementById('booking_date');
    const timeSlots = document.querySelectorAll('.time-slot:not(.disabled)');

    dateInput.addEventListener('change', function() {
        const url = new URL(window.location.href);
        url.searchParams.set('date', this.value);
        window.location.href = url.toString();
    });

    timeSlots.forEach(slot => {
        slot.addEventListener('click', function() {
            const hour = parseInt(this.getAttribute('data-hour'));
            
            if (this.classList.contains('selected')) {
                this.classList.remove('selected');
                selectedSlots = selectedSlots.filter(h => h !== hour);
            } else {
                this.classList.add('selected');
                selectedSlots.push(hour);
            }
            
            selectedSlots.sort((a, b) => a - b);
            updateSummary();
        });
    });

    function updateSummary() {
        if (selectedSlots.length === 0) {
            document.getElementById('summary-time').textContent = '--:--';
            document.getElementById('summary-duration').textContent = '0';
            document.getElementById('summary-total').textContent = '0 VNĐ';
            document.getElementById('btn-submit').disabled = true;
            document.getElementById('btn-submit').style.opacity = '0.5';
            return;
        }

        const startHour = selectedSlots[0];
        const endHour = selectedSlots[selectedSlots.length - 1] + 1;
        
        const startTimeStr = (startHour < 10 ? '0' + startHour : startHour) + ':00';
        const endTimeStr = (endHour < 10 ? '0' + endHour : endHour) + ':00';
        
        document.getElementById('summary-time').textContent = `${startTimeStr} - ${endTimeStr}`;
        document.getElementById('summary-duration').textContent = selectedSlots.length;

        let total = 0;
        selectedSlots.forEach(hour => {
            const slotEl = document.querySelector(`.time-slot[data-hour="${hour}"]`);
            total += parseInt(slotEl.getAttribute('data-price'));
        });

        document.getElementById('summary-total').textContent = new Intl.NumberFormat('vi-VN').format(total) + ' VNĐ';

        document.getElementById('form_start_hour').value = startHour;
        document.getElementById('form_end_hour').value = endHour;

        document.getElementById('btn-submit').disabled = false;
        document.getElementById('btn-submit').style.opacity = '1';
    }
});
</script>

<?php 
$content = ob_get_clean();
require ROOT . '/app/Views/layouts/main.php';
?>
