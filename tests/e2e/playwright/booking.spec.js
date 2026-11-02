// tests/e2e/playwright/booking.spec.js
// Kịch bản E2E Playwright cho luồng đặt sân SmashZone
// Chạy: npx playwright test

const { test, expect } = require('@playwright/test');

const BASE_URL = process.env.BASE_URL || 'http://localhost/BTL_SmashZone';

test.describe('SmashZone Booking Flow', () => {

  test('Khách hàng đăng ký, đăng nhập, đặt sân và thanh toán', async ({ page }) => {
    // 1. Truy cập trang chủ
    await page.goto(`${BASE_URL}/`);
    await expect(page).toHaveTitle(/SmashZone/);

    // 2. Đăng ký tài khoản mới
    await page.goto(`${BASE_URL}/register`);
    await page.fill('input[name="name"]', 'Nguyễn Test E2E');
    await page.fill('input[name="email"]', 'e2e.test@example.com');
    await page.fill('input[name="phone"]', '0987654322');
    await page.fill('input[name="password"]', '123456');
    await page.fill('input[name="password_confirmation"]', '123456');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(`${BASE_URL}/`);

    // 3. Xem chi nhánh
    await page.goto(`${BASE_URL}/branches`);
    await expect(page.locator('text=SmashZone')).toBeVisible();

    // 4. Chọn chi nhánh đầu tiên
    await page.click('.branch-card a:first-child');
    await expect(page).toHaveURL(/\/branch\/\d+/);

    // 5. Chọn sân và ngày
    await page.click('.court-card a:first-child');
    await expect(page).toHaveURL(/\/booking\/\d+/);

    // 6. Chọn khung giờ trống
    await page.click('.slot.available:first-child');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/\/booking\/confirm/);

    // 7. Nhập thông tin khách hàng
    await page.fill('input[name="customer_name"]', 'Khách E2E');
    await page.fill('input[name="customer_phone"]', '0987654323');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/\/payment\/\d+/);

    // 8. Chọn phương thức thanh toán (Tiền mặt)
    await page.selectOption('select[name="payment_method"]', 'cash');
    await page.click('button[type="submit"]');

    // 9. Xác nhận trang thành công
    await expect(page).toHaveURL(/\/booking\/success\/\d+/);
    await expect(page.locator('text=Thành Công')).toBeVisible();
  });

  test('Không cho đặt sân trùng giờ', async ({ page }) => {
    // Giả sử đã có booking xác nhận cho sân 1, ngày 2026-11-05, 18:00-20:00
    await page.goto(`${BASE_URL}/booking/1?date=2026-11-05`);
    await page.click('.slot.available:first-child');
    await page.click('button[type="submit"]');
    await page.fill('input[name="customer_name"]', 'Khách Trùng');
    await page.fill('input[name="customer_phone"]', '0987654324');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/\/payment\/\d+/);

    // Mở tab mới, cố gắng đặt cùng sân cùng giờ
    const page2 = await page.context().newPage();
    await page2.goto(`${BASE_URL}/booking/1?date=2026-11-05`);
    await page2.click('.slot.available:first-child'); // có thể đã bị disable hoặc vẫn hiển thị
    await page2.click('button[type="submit"]');
    await page2.fill('input[name="customer_name"]', 'Khách Trùng 2');
    await page2.fill('input[name="customer_phone"]', '0987654325');
    await page2.click('button[type="submit"]');

    // Một trong hai sẽ bị từ chối do double-check ở processConfirm
    await expect(page.locator('.alert-error, .flash-error')).or(
      await expect(page2.locator('.alert-error, .flash-error'))
    ).toBeVisible();
  });

  test('Hủy tự động sau 3 phút nếu chưa thanh toán', async ({ page }) => {
    // Kịch bản này kiểm tra qua UI: truy cập trang thanh toán của booking pending đã hết hạn
    // Cần có booking pending với expires_at trong quá khứ trong DB
    await page.goto(`${BASE_URL}/payment/999999`);
    await expect(page).toHaveURL(`${BASE_URL}/branches`);
    await expect(page.locator('text=quá hạn thanh toán')).toBeVisible();
  });

});
