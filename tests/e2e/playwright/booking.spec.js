// tests/e2e/playwright/booking.spec.js
// Kịch bản E2E Playwright cho các luồng chính của SmashZone.
// Chạy: cd tests/e2e/playwright && npm install && npx playwright install chromium && npx playwright test
// Có thể đổi địa chỉ app qua biến môi trường BASE_URL.

const { test, expect } = require('@playwright/test');

const BASE_URL = (process.env.BASE_URL || 'http://localhost/BTL_SmashZone/public').replace(/\/+$/, '');

function uniqueUser() {
  const suffix = `${Date.now()}${Math.floor(Math.random() * 1000)}`;
  return {
    name: `E2E User ${suffix}`,
    email: `e2e.${suffix}@example.test`,
    phone: `09${String(Math.floor(Math.random() * 1e8)).padStart(8, '0')}`,
    password: 'Secret123!',
  };
}

function uniqueFutureDate() {
  const date = new Date();
  date.setUTCDate(date.getUTCDate() + 30 + Math.floor(Math.random() * 240));
  return date.toISOString().slice(0, 10);
}

async function register(page) {
  const user = uniqueUser();

  await page.goto(`${BASE_URL}/register`);
  await page.fill('input[name="name"]', user.name);
  await page.fill('input[name="email"]', user.email);
  await page.fill('input[name="phone"]', user.phone);
  await page.fill('input[name="password"]', user.password);
  await page.fill('input[name="password_confirmation"]', user.password);
  await page.click('form button[type="submit"]');

  await expect(page).toHaveURL(`${BASE_URL}/`);
  return user;
}

async function openFirstCourtForDate(page, date) {
  await page.goto(`${BASE_URL}/branches`);
  await page.locator('a[href*="/branch/"]').first().click();
  await expect(page).toHaveURL(/\/branch\/\d+/);

  const courtHref = await page.locator('.court-card a[href*="/booking/"]').first().getAttribute('href');
  const match = new URL(courtHref, page.url()).pathname.match(/\/booking\/(\d+)/);
  if (!match) {
    throw new Error(`Không đọc được court id từ href: ${courtHref}`);
  }

  const courtId = match[1];
  await page.goto(`${BASE_URL}/booking/${courtId}?date=${date}`);
  await expect(page.locator('.time-slot:not(.disabled)').first()).toBeVisible();

  return courtId;
}

async function selectFirstAvailableSlot(page) {
  const slot = page.locator('.time-slot:not(.disabled)').first();
  const hour = Number(await slot.getAttribute('data-hour'));

  await slot.click();
  await expect(page.locator('#btn-submit')).toBeEnabled();
  await page.click('#btn-submit');
  await expect(page).toHaveURL(/\/booking\/confirm$/);

  return hour;
}

async function submitContactInfo(page, phone) {
  await page.fill('input[name="customer_name"]', 'Khách E2E');
  await page.fill('input[name="customer_phone"]', phone);
  await page.click('button:has-text("Xác Nhận Đặt Sân")');
  await expect(page).toHaveURL(/\/payment\/\d+$/);
}

async function payByCash(page) {
  await page.locator('label:has(input[name="payment_method"][value="cash"])').click();
  await page.click('button:has-text("Hoàn Tất Đặt Sân")');
  await expect(page).toHaveURL(/\/booking\/success\/\d+$/);
  await expect(page.locator('h1')).toContainText(/THÀNH CÔNG/i);
}

test.describe('SmashZone Booking Flow', () => {
  test('Đăng ký, đặt sân, thanh toán tiền mặt và tra cứu lịch sử', async ({ page }) => {
    const user = await register(page);
    const date = uniqueFutureDate();

    await openFirstCourtForDate(page, date);
    await selectFirstAvailableSlot(page);
    await submitContactInfo(page, user.phone);
    await payByCash(page);

    const code = (await page.locator('.ticket-code').textContent()).trim();
    expect(code).toMatch(/^BB-\d{8}-[A-Z0-9]{5}$/);

    await page.goto(`${BASE_URL}/booking/history`);
    await expect(page.locator('body')).toContainText(code);
    await expect(page.locator('body')).toContainText('Đã xác nhận');

    await page.goto(`${BASE_URL}/logout`);
    await page.goto(`${BASE_URL}/history?phone=${user.phone}`);
    await expect(page.locator('body')).toContainText(code);
  });

  test('Chưa đăng nhập POST /booking/store bị chuyển về trang đăng nhập', async ({ page }) => {
    const response = await page.request.post(`${BASE_URL}/booking/store`, {
      form: {
        court_id: '1',
        booking_date: uniqueFutureDate(),
        start_hour: '5',
        end_hour: '6',
      },
      maxRedirects: 0,
    });

    expect(response.status()).toBe(302);
    expect(response.headers()['location'] || '').toContain('/login');

    await page.goto(`${BASE_URL}/login`);
    await expect(page.locator('.flash-error')).toContainText('Vui lòng đăng nhập để đặt sân');
  });

  test('Không cho đặt trùng khung giờ đang chờ thanh toán', async ({ page }) => {
    const user = await register(page);
    const date = uniqueFutureDate();
    const courtId = await openFirstCourtForDate(page, date);
    const hour = await selectFirstAvailableSlot(page);
    await submitContactInfo(page, user.phone);

    await page.goto(`${BASE_URL}/booking/${courtId}?date=${date}`);
    await expect(page.locator(`.time-slot[data-hour="${hour}"]`)).toHaveClass(/disabled/);

    const response = await page.request.post(`${BASE_URL}/booking/store`, {
      form: {
        court_id: courtId,
        booking_date: date,
        start_hour: String(hour),
        end_hour: String(hour + 1),
      },
      maxRedirects: 0,
    });
    expect(response.status()).toBe(302);

    await page.goto(`${BASE_URL}/booking/${courtId}?date=${date}`);
    await expect(page.locator('.flash-error')).toContainText('Khung giờ này đã được đặt');
  });

  test('Chỉ chủ đơn đã đăng nhập mới xem được trang thanh toán', async ({ page, browser }) => {
    const owner = await register(page);
    const date = uniqueFutureDate();

    await openFirstCourtForDate(page, date);
    await selectFirstAvailableSlot(page);
    await submitContactInfo(page, owner.phone);
    const paymentUrl = page.url();

    const guestContext = await browser.newContext();
    const guestPage = await guestContext.newPage();
    await guestPage.goto(paymentUrl);
    await expect(guestPage).toHaveURL(new RegExp('/login$'));
    await guestContext.close();

    const otherContext = await browser.newContext();
    const otherPage = await otherContext.newPage();
    await register(otherPage);
    await otherPage.goto(paymentUrl);
    await expect(otherPage).toHaveURL(new RegExp('/booking/history'));
    await expect(otherPage.locator('.flash-error')).toContainText('Bạn không có quyền xem đơn đặt sân này');
    await otherContext.close();
  });

  test('Đăng ký email trùng bị từ chối', async ({ browser }) => {
    const firstContext = await browser.newContext();
    const firstPage = await firstContext.newPage();
    const user = await register(firstPage);

    const secondContext = await browser.newContext();
    const secondPage = await secondContext.newPage();
    await secondPage.goto(`${BASE_URL}/register`);
    await secondPage.fill('input[name="name"]', 'E2E Duplicate');
    await secondPage.fill('input[name="email"]', user.email);
    await secondPage.fill('input[name="phone"]', '0987654321');
    await secondPage.fill('input[name="password"]', 'Secret123!');
    await secondPage.fill('input[name="password_confirmation"]', 'Secret123!');
    await secondPage.click('form button[type="submit"]');

    await expect(secondPage).toHaveURL(`${BASE_URL}/register`);
    await expect(secondPage.locator('.flash-error')).toContainText('Email này đã được đăng ký');

    await firstContext.close();
    await secondContext.close();
  });

  test('Đăng ký số điện thoại không hợp lệ bị từ chối', async ({ page }) => {
    await page.goto(`${BASE_URL}/register`);
    await page.fill('input[name="name"]', 'E2E Bad Phone');
    await page.fill('input[name="email"]', uniqueUser().email);
    await page.fill('input[name="phone"]', '0123');
    await page.fill('input[name="password"]', 'Secret123!');
    await page.fill('input[name="password_confirmation"]', 'Secret123!');
    await page.click('form button[type="submit"]');

    await expect(page).toHaveURL(`${BASE_URL}/register`);
    await expect(page.locator('.flash-error')).toContainText('Số điện thoại không hợp lệ');
  });

  test('Đăng nhập sai mật khẩu bị từ chối, đúng mật khẩu thành công', async ({ page }) => {
    const user = await register(page);
    await page.goto(`${BASE_URL}/logout`);

    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', user.email);
    await page.fill('input[name="password"]', 'WrongPassword!');
    await page.click('form button[type="submit"]');
    await expect(page).toHaveURL(`${BASE_URL}/login`);
    await expect(page.locator('.flash-error')).toContainText('mật khẩu không đúng');

    await page.fill('input[name="email"]', user.email);
    await page.fill('input[name="password"]', user.password);
    await page.click('form button[type="submit"]');
    await expect(page).toHaveURL(`${BASE_URL}/`);
  });

  test('API available-slots trả đủ khung giờ và báo lỗi thiếu court_id', async ({ request }) => {
    const date = uniqueFutureDate();
    const response = await request.get(`${BASE_URL}/api/available-slots?court_id=1&date=${date}`);
    expect(response.status()).toBe(200);

    const payload = await response.json();
    expect(String(payload.court_id)).toBe('1');
    expect(payload.date).toBe(date);
    expect(payload.slots).toHaveLength(17);
    expect(payload.slots[0]).toMatchObject({ hour: 5, label: '05:00' });
    expect(typeof payload.slots[0].booked).toBe('boolean');

    const badResponse = await request.get(`${BASE_URL}/api/available-slots`);
    expect(badResponse.status()).toBe(400);
  });

  test('Owner đăng nhập và truy cập dashboard quản trị', async ({ page }) => {
    await page.goto(`${BASE_URL}/owner/login`);
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('form button[type="submit"]');

    await expect(page).toHaveURL(`${BASE_URL}/owner/dashboard`);
    await expect(page.locator('.dashboard')).toBeVisible();

    await page.goto(`${BASE_URL}/owner/bookings?branch_id=1`);
    await expect(page).toHaveURL(/\/owner\/bookings/);
    await expect(page.locator('.admin-card').first()).toBeVisible();
  });
});
