# Ghi chú module Lark Webhook (để nhớ khi làm tiếp)

> File này lưu trạng thái & định hướng module `platform/plugins/lark-webhook`, để lần sau code tiếp không quên.
> Xem thêm README kỹ thuật: `platform/plugins/lark-webhook/README.md`, và convention chung: `docs/botble-plugin-guide.md`.

## Trạng thái

- **Giai đoạn 1: XONG & đã test** (17/07/2026). Chờ chủ dự án báo triển khai các mục tiếp theo.

## Đã làm

- Plugin `lark-webhook`, namespace `Botble\LarkWebhook\`, đã kích hoạt.
- Route hook công khai: `POST {APP_URL}/lark/webhook/{token}` (bỏ CSRF) + GET ping cùng đường dẫn để kiểm tra bằng trình duyệt.
- `token` là chuỗi bí mật sinh tự động khi kích hoạt, lưu ở setting `lark_webhook_token`; có nút **Tạo lại đường dẫn** trong admin.
- Ghi nhận mọi sự kiện vào bảng `lark_webhook_events` (event_type, event_id, payload đầy đủ, headers, IP, thời gian, status: received/verified/rejected).
- Chống trùng theo `event_id`. Tự trả `url_verification` challenge của Lark.
- Bảo mật tùy chọn ở **Settings**: Verification Token + Encrypt Key (giải mã AES-256-CBC).
- Menu **Lark Webhook** ở admin: danh sách + chi tiết; đầu trang có thẻ hiển thị **đường dẫn Webhook + nút Copy** để khỏi đi tìm. Settings nằm ở panel System/Others.

## Đường dẫn Webhook (nơi lấy)

Admin → **Lark Webhook** (thẻ đầu trang) hoặc **Lark Webhook → Settings**. Dạng `{APP_URL}/lark/webhook/{token}`.

## Chiều GỬI record lên Lark Base (đã thêm cấu hình + client)

- Settings có thêm mục **"Chiều GỬI record"**: bật/tắt, chọn vùng máy chủ (mặc định `https://open.larksuite.com`, có `open.feishu.cn`), App ID, App Secret, Base App Token, Table ID.
- Service `src/Services/LarkBaseClient.php`:
  - `tenantAccessToken()` — đổi App ID/Secret lấy token (cache 90 phút).
  - `createRecord(array $fields)` — tạo 1 record vào bảng Base (`POST /open-apis/bitable/v1/apps/{app_token}/tables/{table_id}/records`).
  - `verifyConnection()` — xác thực + đọc thử 1 record để kiểm tra credential (không cần biết tên field).
- Nút **"Kiểm tra kết nối"** trong Settings gọi `POST admin/lark-webhook/settings/test-push`.
- **Yêu cầu phía Lark:** tạo custom app, thêm app làm collaborator của Base, cấp quyền `bitable:app` (đọc/ghi Base).
- **CHƯA làm:** chưa gắn trigger tự động (khi nào/record nào từ CMS sẽ được `createRecord`) — cần chốt: bảng CMS nào → map sang field nào của Base. Khi có yêu cầu, chỉ cần gọi `app(LarkBaseClient::class)->createRecord([...])` trong listener/event tương ứng.

### Push ẢNH lên Base (đã hỗ trợ trong client)
- Field đính kèm trong Base hiện tại tên **`IMAGE`** (type 17). Base "Thông tin order" có field: `ID ORDER`, `TITLE PRODUCT`, `IMAGE`.
- Cách dùng:
  ```php
  $client = app(\Botble\LarkWebhook\Services\LarkBaseClient::class);
  $token  = $client->uploadImageFrom('https://.../anh.jpg');      // hoặc đường dẫn file local
  $client->createRecord([
      'ID ORDER'      => '...',
      'TITLE PRODUCT' => '...',
      'IMAGE'         => \Botble\LarkWebhook\Services\LarkBaseClient::attachment($token),
  ]);
  ```
- **Bẫy đã gặp & xử lý:**
  - Token trên URL của Base ở đây là **token Wiki node** (`COzZ...`), khác obj_token thật (`EcHX...`). API bitable đọc/ghi tự resolve, nhưng **upload ảnh (Drive) cần obj_token thật** → client tự gọi metadata Base để lấy (`realAppToken()`), nên cứ giữ token URL.
  - Upload ảnh dùng `POST /open-apis/drive/v1/medias/upload_all`, `parent_type=bitable_image`, `parent_node=<obj_token thật>`.
- **BLOCKER phía Lark (17/07/2026):** app `cli_aad0f6d192b89ed0` hiện chỉ **đọc được** Base — ghi record & upload đều trả **Forbidden (1061004)**. Cần: cấp scope `bitable:app` (read+write) + `drive:drive`, thêm app làm cộng tác viên Base với quyền **Can edit**, rồi phát hành lại app. Sau đó test lại là chạy.

## Định hướng làm tiếp (chưa code)

- Chốt mapping: model/bảng CMS nào bắn lên Base, map field ra sao, trigger nào (tạo mới/cập nhật).
- Bắn event nội bộ / hook để module khác xử lý theo `event_type` (chiều nhận).
- Đồng bộ record của Lark Base về model trong CMS.
- Queue/retry, lọc theo loại sự kiện, cảnh báo lỗi.

## Bài học đã gặp

- Route index của trang danh sách (TableAbstract) phải nhận **GET|POST** — đã sửa bằng `Route::match(['GET','POST'], '/')`. Nếu chỉ GET sẽ bị "DataTables warning: Invalid JSON response". Chi tiết trong `docs/botble-plugin-guide.md`.

## Lệnh nhanh

```bash
PHP="C:/laragon/bin/php/php-8.3.29-nts-Win32-vs16-x64/php.exe"
$PHP artisan cms:plugin:activate lark-webhook
$PHP artisan route:list | grep lark
```
