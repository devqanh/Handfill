# Lark Webhook

Nhận sự kiện (event) từ **Lark Suite / Lark Base** gửi về qua webhook, ghi nhận lịch sử và xem lại trong admin.

## Chức năng hiện tại (giai đoạn 1)

- **Route hook công khai** hứng sự kiện từ Lark (POST), có bỏ qua CSRF.
- **Ghi nhận lịch sử**: mỗi request lưu 1 dòng vào bảng `lark_webhook_events` (loại sự kiện, event_id, payload đầy đủ, headers, IP, thời gian).
- **Danh sách + chi tiết** trong admin để xem lại các sự kiện đã nhận.
- Tự xử lý **`url_verification` challenge** của Lark (trả lại `challenge`).
- **Chống trùng** theo `event_id` (Lark retry đến khi nhận 2xx).
- **Bảo mật tùy chọn**: Verification Token và Encrypt Key (giải mã AES-256-CBC) khai trong Settings.
- Menu admin hiển thị sẵn **đường dẫn Webhook** (kèm nút Copy) để không phải đi tìm.

## Đường dẫn Webhook

- Dạng: `{APP_URL}/lark/webhook/{token}` — `token` là chuỗi bí mật sinh tự động khi kích hoạt.
- Xem/copy tại: **Admin → Lark Webhook** (thẻ ở đầu trang) hoặc **Lark Webhook → Settings**.
- Mở bằng trình duyệt (GET) sẽ trả JSON báo endpoint sẵn sàng; Lark gửi sự kiện bằng **POST**.
- Có thể **Tạo lại đường dẫn** (regenerate) nếu lộ — đường dẫn cũ ngừng hoạt động ngay.

## Cấu hình trong Lark

1. Vào cấu hình ứng dụng Lark → *Event Subscription* (hoặc automation "Send request" của Base).
2. Dán Webhook URL ở trên vào *Request URL*. Lark sẽ gửi challenge → plugin tự xác nhận.
3. (Tùy chọn) Nếu bật Verification Token / Encrypt Key trong Lark, nhập lại giá trị tương ứng ở **Settings** của plugin.

## Bảng dữ liệu

`lark_webhook_events`: `event_id`, `event_type`, `schema_version`, `app_id`, `tenant_key`, `status` (received/verified/rejected), `message`, `payload` (json), `headers` (json), `ip_address`, `event_created_at`, timestamps.

## Định hướng mở rộng (giai đoạn sau — sẽ báo triển khai)

- Dispatch event nội bộ / hook để module khác xử lý theo `event_type`.
- Đồng bộ record của Lark Base về model trong CMS.
- Retry/queue, lọc theo loại sự kiện, thông báo lỗi.

## Kích hoạt

```bash
php artisan cms:plugin:activate lark-webhook
```
