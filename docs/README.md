# Tài liệu SmashZone

Tất cả tài liệu thiết kế, báo cáo, hướng dẫn demo và phản biện được gom trong thư mục này.

| File | Mô tả |
|------|-------|
| `PRD.md` | Yêu cầu sản phẩm, user stories Gherkin, edge cases |
| `GIT_WORKFLOW.md` | Chiến lược nhánh, Pull Request, branch protection |
| `TEST_REPORT.md` | Unit test, E2E Playwright, báo cáo coverage, OWASP, N+1 |
| `OWASP_CHECKLIST.md` | Checklist bảo mật OWASP Top 10 |
| `SLIDE_OUTLINE.md` | Nội dung slide bảo vệ (10-12 trang) |
| `DEMO_SCRIPT.md` | Kịch bản live demo từng bước |
| `BACKUP_VIDEO_PLAN.md` | Kế hoạch quay video dự phòng |
| `DEFENSE_QA.md` | Câu hỏi phản biện & câu trả lời chuẩn |
| `erd.mermaid` | Sơ đồ ERD (Mã Mermaid, chỉnh sửa được tại mermaid.live) |
| `sequence.mermaid` | Sơ đồ tuần tự luồng đặt sân và thanh toán |
| `architecture.mermaid` | Sơ đồ kiến trúc tổng thể hệ thống |

## Cách xem trước sơ đồ Mermaid
Truy cập [https://mermaid.live](https://mermaid.live), dán nội dung file `.mermaid` vào để xem ảnh.

## File ở gốc repo
- `README.md`: Hướng dẫn cài đặt & chạy dự án
- `.cursorrules`: Quy tắc bắt buộc AI khi sinh code
- `openapi.yaml`: Tài liệu API (Swagger/OpenAPI)
- `docker-compose.yml` + `Dockerfile`: Môi trường chạy qua Docker
- `.env.example`: Mẫu biến môi trường
- `app/`, `core/`, `config/`, `routes/`, `database/`: Mã nguồn PHP
- `tests/`: Unit test và E2E test
