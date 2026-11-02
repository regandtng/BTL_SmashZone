# Git Workflow — SmashZone

## 1. Chiến lược nhánh (Branching Strategy)

Sử dụng **GitHub Flow** đơn giản, phù hợp dự án nhỏ và nhóm sinh viên:

| Nhánh | Mục đích | Quy tắc |
|-------|----------|---------|
| `main` | Mã nguồn production-ready, luôn deploy được | Chỉ merge qua Pull Request, không push trực tiếp |
| `feature/*` | Phát triển tính năng mới | Tạo từ `main`, merge về `main` qua PR |
| `fix/*` | Sửa lỗi (bugfix) | Tạo từ `main`, merge về `main` qua PR |
| `hotfix/*` | Sửa lỗi khẩn cấp trên production | Tạo từ `main`, merge về `main` qua PR |

### Quy tắc đặt tên nhánh
- `feature/dat-san` — tính năng đặt sân
- `feature/thanh-toan` — tính năng thanh toán
- `fix/cron-schema` — sửa lỗi cron lệch schema
- `fix/owner-controller` — bổ sung OwnerController thiếu

## 2. Quy trình làm việc với Pull Request

1. **Tạo nhánh mới** từ `main`:
   ```bash
   git checkout -b feature/ten-tinh-nang
   ```

2. **Commit code** theo chuẩn:
   ```
   feat: mô tả tính năng
   fix: mô tả lỗi đã sửa
   docs: cập nhật tài liệu
   ```

3. **Push nhánh lên remote**:
   ```bash
   git push origin feature/ten-tinh-nang
   ```

4. **Mở Pull Request** trên GitHub/GitLab:
   - Base: `main`
   - Compare: `feature/ten-tinh-nang`
   - Điền mô tả:目的, thay đổi, cách kiểm thử

5. **Code Review** (ít nhất 1 người review):
   - Kiểm tra logic nghiệp vụ
   - Kiểm tra bảo mật (CSRF, SQL injection, phân quyền)
   - Kiểm tra tuân thủ cấu trúc MVC

6. **Merge** sau khi review xong và tất cả kiểm tra passed:
   ```bash
   git checkout main
   git merge --no-ff feature/ten-tinh-nang
   git push origin main
   ```

7. **Xóa nhánh feature** sau khi merge.

## 3. Bảo vệ nhánh main (Branch Protection)

Khuyến khích bật trên repository hosting:
- Yêu cầu Pull Request trước khi merge vào `main`
- Yêu cầu ít nhất 1 sự phê duyệt (approve)
- Không cho phép push trực tiếp vào `main`
- Yêu cầu kiểm tra trạng thái thành công trước khi merge

## 4. Ví dụ luồng làm việc thực tế

```bash
# 1. Cập nhật main mới nhất
git checkout main
git pull origin main

# 2. Tạo nhánh sửa lỗi cron
git checkout -b fix/cron-schema

# 3. Sửa code, commit
git add cron.php database/schema.sql
git commit -m "fix: bổ sung completed_at vào schema và sửa cron.php"

# 4. Push và mở PR
git push origin fix/cron-schema

# 5. Sau review, merge PR vào main qua giao diện GitHub
```

## 5. Lịch sử commit hiện tại (tham khảo)

Repo hiện tại có 1 commit duy nhất trên `main`, chưa có nhánh feature nào được duy trì trên remote. Để chứng minh workflow khi bảo diện, cần tạo ít nhất 1 PR từ nhánh feature về `main` trước ngày kiểm tra.