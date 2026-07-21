# Plan: Quy trình đơn hàng handmade (web + Lark) — bản nháp để chốt

> Trạng thái: **CHƯA code**. Tài liệu này để chốt thiết kế trước.
> Ngày lập: 18/07/2026.

## 1. Hiện trạng hệ thống (đã khảo sát)

| Hạng mục | Tình trạng |
|---|---|
| Đặt hàng, giỏ, checkout, sản phẩm (109 sp) | ✅ Có sẵn |
| Ví điện tử (nạp tiền, trừ tiền, sổ cái) | ✅ Có sẵn, dùng được |
| Webhook Lark (nhận) + đẩy record/ảnh lên Lark Base | ✅ Đã làm & chạy được |
| Đơn hàng thực tế trong DB | **0 đơn** → thoải mái thiết kế |
| Đơn tùy chỉnh / khách upload bộ ảnh | ❌ Chưa có |
| Báo giá / giá thương lượng | ❌ Chưa có |
| Thanh toán nhiều mốc (cọc + tất toán) | ❌ Chưa có |
| Quy trình 10 trạng thái sản xuất | ❌ Chưa có |
| Endpoint đổi trạng thái đơn tùy ý | ❌ Chưa có (core chỉ đổi trạng thái qua vài hành động cố định) |

### Ràng buộc kỹ thuật quan trọng
1. **Ví KHÔNG có cơ chế giữ tiền (escrow/hold)** — chỉ trừ thẳng. Mô hình cọc sẽ là *trừ thật* tại mốc, không phải "tạm giữ".
2. **Ví ở checkout là trả full hoặc không** → quy trình này **không đi qua checkout tiêu chuẩn**; trừ ví theo mốc trạng thái.
3. `ec_orders.status` là `varchar(120)` → thêm trạng thái không cần migration, nhưng **core hard-code** vài chỗ (`canBeCanceled`, `canBeReturned`, `isInvoiceAvailable`) theo 4 trạng thái gốc → thêm bừa sẽ hỏng ngầm.
4. **Không có event chung khi đổi trạng thái đơn** → phải tự tạo.
5. Ví chỉ có **1 loại tiền tệ** (mặc định VND), không quy đổi lúc trừ tiền.

## 2. Mô hình nghiệp vụ (theo tài liệu của bạn)

### 2 nhóm khách
| Nhóm | Ship | Chi phí khách trả |
|---|---|---|
| 1. Đặt sản xuất hoàn thiện, tự ship | Ship VN | Phí sản phẩm hoàn thiện. **Không** ship QT / packing / nguyên vật liệu. Ship VN khách tự trả. |
| 2. Đặt sản xuất + fulfill toàn bộ | Ship QT | Phí SP + ship QT + packing + nguyên vật liệu |

### 10 trạng thái + 2 mốc trừ ví
| # | Trạng thái | Ai làm | Tiền |
|---|---|---|---|
| 1 | Chờ duyệt | Khách up file → HF duyệt khả thi + báo giá → **khách xác nhận** | — |
| 2 | Đã cọc | HF xác nhận thanh toán, mua label VC | **Trừ 50% tiền SP + 100% tiền ship** |
| 3 | Chuẩn bị sản xuất | Lark hiển thị "Chuẩn bị" | — |
| 4 | Đang sản xuất | HF sản xuất | — |
| 5 | Sản xuất xong | QC up ảnh thành phẩm | — |
| 6 | Chờ xác nhận | **Khách xác nhận** | — |
| 7 | Xác nhận | HF xác nhận thanh toán | **Trừ 50% SP còn lại + 100% phí fulfill + 100% NVL packing** |
| 8 | Packing | Lark chuyển "Cần Pack" → HF packing | — |
| 9 | Đang giao hàng | Packing chọn "Đã đóng hàng" + ảnh pack → web đổi trạng thái | — |
| 10 | Hoàn tất | Đã giao ĐVVC, theo dõi/support | — |

### 2 đường vào cùng một quy trình
- **Đơn tùy chỉnh**: khách upload bộ ảnh + ghi chú.
- **Đơn sản phẩm có sẵn**: chọn từ 109 sản phẩm.
→ Cả hai đều chạy đủ 10 bước.

## 3. Đề xuất kiến trúc

### 3.1. Trạng thái sản xuất — dùng TRƯỜNG RIÊNG (khuyến nghị)
Thêm `production_status` riêng, **giữ nguyên** `status` gốc của core để không phá logic hủy/trả/hóa đơn:

| production_status (10 bước) | status core (map tự động) |
|---|---|
| Chờ duyệt | `pending` |
| Đã cọc → Packing | `processing` |
| Đang giao hàng | `processing` |
| Hoàn tất | `completed` |
| Hủy | `canceled` |

*Phương án thay thế:* nhồi 10 trạng thái vào `OrderStatusEnum` — gọn hơn về UI nhưng **rủi ro phá logic core**, phải vá từng chỗ hard-code.

### 3.2. Các thành phần sẽ xây (plugin mới `handmade-workflow`)
1. **Máy trạng thái** — bảng chuyển tiếp hợp lệ, ai được chuyển (khách / HF / hệ thống / Lark), ghi lịch sử vào `ec_order_histories`, phát event `ProductionStatusChanged`.
2. **Báo giá (quote)** — bảng riêng lưu: phí SP, phí ship, phí fulfill, phí NVL packing, nhóm khách, ngày giao dự kiến, người báo giá, thời điểm khách duyệt. Tính sẵn `deposit_amount` / `final_amount`.
3. **Đơn tùy chỉnh + upload ảnh** — model yêu cầu + bảng file (qua `RvMedia`), khách tạo từ trang tài khoản.
4. **Trừ ví theo mốc** — service gọi `WalletService::debit()` với idempotency key `order_{id}_deposit` / `order_{id}_final`, khóa dòng, chặn trừ trùng.
5. **Đồng bộ Lark 2 chiều** — đẩy đơn + ảnh lên Base khi tạo/đổi trạng thái; nhận webhook từ Lark để đổi trạng thái + kéo ảnh QC/ảnh pack về.
6. **UI khách** — tạo đơn tùy chỉnh, xem báo giá & bấm duyệt, xem ảnh QC & bấm xác nhận, xem tiến độ, nạp ví.
7. **UI admin** — nhập báo giá, chuyển trạng thái, xem ảnh khách gửi.

### 3.3. Lưu ý riêng cho hàng HANDMADE
- **Không trừ tồn kho** với đơn tùy chỉnh (làm theo yêu cầu) — core mặc định trừ kho, phải chặn.
- **Ngày giao dự kiến (lead time)** là dữ liệu bắt buộc ở bước duyệt ("ngày cần hàng có khả thi không").
- Mỗi sản phẩm là duy nhất → **ảnh QC thật** trước khi khách chốt là bắt buộc (đã có trong luồng).
- Sai số thủ công → nên có ô "ghi chú sai số/khác biệt" khi QC.

## 4. Lộ trình đề xuất
| Giai đoạn | Nội dung |
|---|---|
| 0 | Chốt thiết kế + schema Lark Base (tài liệu này) |
| 1 | Máy trạng thái + lịch sử + UI admin chuyển trạng thái |
| 2 | Đơn tùy chỉnh + khách upload bộ ảnh |
| 3 | Báo giá + khách duyệt + 2 mốc trừ ví |
| 4 | Đồng bộ Lark 2 chiều (đẩy đơn, nhận trạng thái, kéo ảnh QC) |
| 5 | Thông báo (email/app), hủy đơn & hoàn tiền, báo cáo |

## 5. Schema Lark Base cần bổ sung
Base hiện chỉ có 3 field (`ID ĐƠN HÀNG`, `HÌNH ẢNH`, `Tên sản phẩm`). Quy trình cần thêm (đề xuất):
`Trạng thái` (single select, 10 giá trị) · `Khách hàng` · `Nhóm khách` (Ship VN / Ship QT) · `Ghi chú khách` · `Phí SP` · `Phí ship` · `Phí fulfill` · `Phí NVL packing` · `Tiền cọc` · `Còn lại` · `Ngày giao dự kiến` · `Ảnh QC` (attachment) · `Ảnh pack` (attachment) · `Mã vận đơn` · `Link đơn trên web`.

## 6. Quyết định đã chốt (18/07/2026)

| # | Quyết định | Chọn |
|---|---|---|
| 1 | Lưu 10 trạng thái | **Trường riêng `production_status`**, giữ nguyên `status` core và map tự động |
| 2 | Nguồn chân lý trạng thái | **Web là chính**, đẩy sang Lark; Lark chỉ để xem/theo dõi |
| 3 | Sản phẩm có sẵn | **Giá cố định**, không báo giá lại. Bước "Chờ duyệt" chỉ duyệt tính khả thi + ngày giao |
| 4 | Ví không đủ tiền tại mốc | **Chặn chuyển trạng thái + nhắc khách nạp thêm** (không cho âm) |

### ⚠️ Hệ quả của quyết định #2 cần lưu ý
Tài liệu nghiệp vụ gốc mô tả đội HF thao tác **trong Lark** ("Lark chuyển trạng thái Cần Pack", "Packing chọn Đã đóng hàng → chuyển trạng thái trên web"). Vì đã chọn **web là nguồn chính**, nên:
- Đội HF/QC/Packing sẽ phải **thao tác trên admin web** (đổi trạng thái, upload ảnh QC, ảnh pack).
- Lark Base chỉ **nhận bản sao** để xem/báo cáo, **không** đẩy ngược trạng thái về web.
- Nếu sau này muốn HF làm trong Lark, ta bổ sung chiều nhận (webhook đã sẵn sàng) — kiến trúc không cản.

### Hệ quả của quyết định #3
- **Đơn tùy chỉnh** (upload ảnh): bắt buộc có bước báo giá, vì không có giá niêm yết.
- **Đơn sản phẩm có sẵn**: lấy giá niêm yết; nhưng các **phí ship / fulfill / packing / NVL** vẫn cần nguồn (nhập tay hay công thức) — **còn phải chốt**.

## 7. Quyết định đợt 2 (đã chốt 18/07/2026)

| # | Vấn đề | Chốt |
|---|---|---|
| 5 | Tiền ship nhóm 1 (Ship VN) | **Tùy đơn** — mặc định KHÔNG trừ ship; HF nhập phí ship cho đơn nào khách nhờ mua label |
| 6 | Phí fulfill & NVL packing | **Mặc định từ bảng giá cấu hình + cho HF sửa tay** từng đơn |
| 7 | Cơ cấu đơn | **Nhiều mẫu / đơn** — mỗi dòng hàng có bộ ảnh + ghi chú + số lượng riêng |
| 8 | Hủy đơn | **Admin quyết từng ca** — hủy được bất kỳ lúc nào trước khi giao, admin chọn số tiền hoàn về ví |
| 9 | Tiền tệ | **Chỉ khách VN trước** (ví VND). Khách quốc tế tính sau |
| 10 | Ảnh QC & ảnh pack | **Upload trên admin web**, hệ thống tự đẩy sang Lark |

### Công thức tiền theo 2 nhóm (sau khi chốt)
- **Mốc 1 — Đã cọc:** `50% × phí SP` + `phí ship` *(phí ship = 0 nếu khách tự ship; HF nhập nếu mua label hộ)*
- **Mốc 2 — Xác nhận:** `50% phí SP còn lại` + `phí fulfill` + `phí NVL packing`
- Nhóm 1 (Ship VN, tự ship) mặc định: fulfill = 0, packing = 0, NVL = 0, ship = 0 → chỉ trả tiền SP chia 2 mốc.

## 8. Giai đoạn 1 — ✅ ĐÃ XONG (18/07/2026)
Plugin: `platform/plugins/handmade-workflow` (đã kích hoạt).

1. `ProductionStatusEnum` — 10 trạng thái + nhãn + màu badge.
2. Cột `production_status` (+ thời điểm đổi) trên `ec_orders`.
3. **Máy trạng thái**: bảng chuyển tiếp hợp lệ, chặn nhảy bước sai, ghi lịch sử.
4. Lịch sử: tái dùng `ec_order_histories` (không tạo bảng mới).
5. Event `ProductionStatusChanged` để các giai đoạn sau gắn vào (trừ ví, đẩy Lark).
6. Tự map `production_status` → `status` core (pending/processing/completed/canceled).
7. UI admin: thẻ trạng thái + nút chuyển bước + ghi chú, gắn vào trang chi tiết đơn.
8. UI khách: timeline tiến độ đơn trong trang tài khoản, theo đúng style hiện tại.

### Đã kiểm chứng (chạy thật, không chỉ lint)
- Chặn nhảy bước sai: `Chờ duyệt → Packing` bị chặn kèm thông báo tiếng Việt.
- Đi hết 9 bước tuần tự OK; `status` core tự map `pending → processing → completed`; `completed_at` được set.
- Ghi `ec_order_histories` đủ action + mô tả + ghi chú (lưu trong `extras.note`).
- Phát event `ProductionStatusChanged` đủ 9 lần.
- Cả 2 view render qua đúng hook (`ecommerce_order_detail_sidebar_bottom`, `ecommerce_customer_order_view_before_actions`).
- Đơn mới tạo tự vào bước `pending_approval`.

### Bẫy đã gặp khi làm (ghi lại để đỡ mất công sau)
1. `Botble\Base\Supports\Enum::make()` là **method instance**, không gọi tĩnh được → đã thêm helper `ProductionStatusEnum::of($value)`.
2. Cột `ec_order_histories.action` **cast sang `OrderHistoryActionEnum`** → giá trị lạ bị ghi thành `NULL` mà không báo lỗi. Phải đăng ký thêm giá trị qua `BASE_FILTER_ENUM_ARRAY` (đã làm trong `HookServiceProvider`).

## 9. Giai đoạn 2 — ✅ ĐÃ XONG (18/07/2026)

Đơn tùy chỉnh + khách upload bộ ảnh, **nhiều mẫu/đơn** (quyết định #7).

### Đã làm
- `CustomerGroupEnum`: `ship_vn` (tự ship) / `ship_qt` (fulfill toàn bộ).
- `CustomOrderService::create()` — tạo `Order` ở bước `Chờ duyệt`, `amount = 0` (chờ báo giá ở GĐ3).
  - Mỗi mẫu = 1 dòng `ec_order_product` với `product_id = NULL` (hàng làm theo yêu cầu, **không thuộc sản phẩm nào → không trừ tồn kho**).
  - Ảnh + ghi chú lưu trong `ec_order_product.options` dưới khoá riêng `handmade` (không đụng dữ liệu option của core).
  - Nhóm khách + ngày cần hàng lưu vào `ec_order_metadata`.
  - **Upload ảnh làm TRƯỚC transaction** — ghi file không rollback được, nên lỗi upload phải chặn trước khi tạo bất kỳ dòng DB nào.
- Trang khách: `customer/custom-orders/create` — form nhiều mẫu, mỗi mẫu có tên/số lượng/nhiều ảnh (xem trước ảnh)/ghi chú. Thêm/xoá mẫu bằng JS, tự đánh lại chỉ số field.
- Menu tài khoản: **"Đặt làm theo yêu cầu"** (ngay dưới "Đơn hàng").
- Hiển thị ảnh khách gửi ở **cả** trang chi tiết đơn của khách và sidebar admin (để HF duyệt tính khả thi).
- Giới hạn: tối đa 10 mẫu/đơn, 6 ảnh/mẫu, mỗi ảnh ≤ 5MB, chỉ JPG/PNG/WEBP.

### Đã kiểm chứng (chạy thật)
- Tạo đơn thật với 2 mẫu + 3 ảnh: file lên đĩa đúng thư mục `handmade-custom-orders/` kèm thumbnail; metadata, số lượng, ghi chú đúng.
- Cả 2 view hiển thị render đúng qua hook; form render đủ (`multipart/form-data`, template dòng, nhãn tiếng Việt).
- Menu khách hiện đúng vị trí.
- Validation chặn đủ: thiếu ảnh, nhóm khách sai, không có mẫu nào, số lượng 0, ngày trong quá khứ.

### Còn nợ / lưu ý
- Đơn tùy chỉnh **chưa có địa chỉ giao hàng** — sẽ thu ở bước khách duyệt báo giá (GĐ3).
- Có **1 đơn demo** trong hệ thống để tiện nghiệm thu — xoá được thoải mái.

## 10. Giai đoạn 3 — ✅ ĐÃ XONG (18/07/2026)

Báo giá + khách duyệt + 2 mốc trừ ví.

### Đã làm
- Bảng `handmade_order_quotes`: phí SP / ship / fulfill / NVL packing, ngày giao, ghi chú, mốc `quoted_at` / `accepted_at` / `deposit_paid_at` / `final_paid_at`.
- **Công thức 2 mốc:**
  - Cọc = `50% phí SP + 100% phí ship`
  - Còn lại = `tổng − cọc` (tính ngược từ tổng để **hai mốc luôn cộng đúng bằng tổng**, kể cả khi chia đôi bị lẻ)
- **Giá trị mặc định khi báo giá** (quyết định #5, #6):
  - Phí SP: đơn sản phẩm có sẵn tự cộng từ giá niêm yết; đơn tùy chỉnh để 0 cho HF nhập.
  - Ship: **luôn mặc định 0**, HF chỉ nhập khi mua label hộ khách.
  - Fulfill + NVL packing: lấy từ cấu hình, **chỉ áp cho nhóm "Fulfill toàn bộ"**; nhóm tự ship = 0. HF sửa tay được.
- Trang cài đặt phí mặc định: `admin/handmade-workflow/settings`.
- **Khoá báo giá** sau khi qua bước duyệt — không sửa được giá khách đã đồng ý.
- Khách có 2 nút: **Duyệt báo giá & đặt cọc** (bước 1→2) và **Xác nhận thành phẩm & thanh toán** (bước 6→7); kèm bảng giá, số dư ví, và nút nạp tiền khi thiếu.
- **Chốt chặn ở máy trạng thái**: không vào được `Đã cọc` / `Xác nhận` nếu chưa trừ tiền tương ứng → admin không thể đẩy đơn chưa thanh toán.
- Trừ ví dùng **idempotency key** `handmade_order_{id}_{deposit|final}`.

### Đã kiểm chứng (chạy thật với tiền thật trong ví)
- Công thức: 1.000.000 SP + 200.000 ship + 150.000 fulfill + 50.000 packing → tổng 1.400.000, cọc 700.000, còn lại 700.000, **cọc + còn lại = tổng**.
- Ví rỗng → chặn kèm số tiền còn thiếu, không cho âm (quyết định #4).
- Trừ cọc 700k: số dư 1.000.000 → 300.000.
- **Gọi trừ lần 2 → số dư không đổi** (idempotency hoạt động).
- Chặn chuyển sang `Đã cọc` / `Xác nhận` khi chưa trả tiền.
- Đi hết luồng → `Hoàn tất`, sổ cái ví ghi đúng 2 dòng trừ + 2 dòng nạp.

### Bẫy đã gặp
3. Khách **chưa từng nạp tiền thì chưa có bản ghi ví** → `debit()` ném `ModelNotFoundException` thay vì "thiếu số dư", làm khách thấy lỗi chung chung. Đã sửa: gọi `getOrCreateWallet()` trước khi trừ.
4. **Route giao diện khách bị thiếu session** (phát hiện 18/07 khi test trang "Đặt làm theo yêu cầu"): khai `Route::middleware('customer')` trần trong `routes/web.php` của plugin thì route **không có nhóm `web`** → không có `StartSession` → đã đăng nhập vẫn bị đá về `/login`. Phải bọc trong `Theme::registerRoutes()`.
5. **Trang khách vỡ giao diện** (cùng đợt): controller front-end `return view(...)` trực tiếp thì mất vỏ theme (header/footer/sidebar/assets). Phải render qua `Theme::scope($themeView, $data, $fallbackView)->render()` kèm `SeoHelper::setTitle()` + `Theme::breadcrumb()`.

6. **Trang tài khoản thiếu CSS** → controller phải đăng ký **cả `customer.css` lẫn `front-ecommerce.css`** trong constructor. `customer.css` mới là file định kiểu khu vực tài khoản; chỉ có `front-ecommerce.css` là **chưa đủ**, avatar vẫn phình to và sidebar vẫn mất thẻ nền.
7. **JS không chạy ở giao diện khách** → layout theme không có `@stack('footer')`, nên `@push('footer')` **bị bỏ im lặng**; nút "Thêm mẫu" chết câm. Phải để `<script>` inline trong `@section('content')`.

> Bài học chung từ bẫy #4–#7: **render view trong tinker không đủ để nghiệm thu trang web**. Cả 4 lỗi đều nằm ở tầng route/theme/asset nên chỉ lộ ra khi tải trang bằng **HTTP thật có đăng nhập**.
> Quy trình nghiệm thu chuẩn cho mọi trang khách từ nay:
> 1. Tạo khách QA tạm, đăng nhập bằng curl, tải trang → phải HTTP 200.
> 2. So với `customer/orders`: `bb-customer-page`, `bb-customer-sidebar-heading`, `bb-profile-content`, `wrapper-image`, `crop-avatar` phải khớp số lượng.
> 3. **Trích danh sách `.css` và `.js` của cả hai trang rồi `comm -23`** — không được thiếu file nào, thứ tự cũng phải trùng. (Chỉ đếm 1–2 file cụ thể là **không đủ**: lần này `front-ecommerce.css` có mặt nhưng vẫn vỡ vì thiếu `customer.css`.)
> 4. Nếu trang có JS: grep tên hàm trong thân script — phải xuất hiện trong HTML.
> 5. Xoá khách QA tạm.

Chi tiết cả hai bẫy nằm trong `docs/botble-plugin-guide.md`.

### Dữ liệu demo để nghiệm thu
- Đơn `#SF-10000001` — 2 mẫu + ảnh, đang ở bước **Chờ duyệt**, đã có báo giá (tổng 1.720.000đ, cọc 900.000đ, còn lại 820.000đ).
- Ví khách **John Smith** có sẵn **3.000.000đ** → bấm "Duyệt báo giá & đặt cọc" chạy được ngay.

## 11. Bổ sung sau nghiệm thu (18/07/2026)

- **Đơn không hiện trong danh sách khách** → danh sách lọc `is_finished = 1` (cờ "đơn đặt thật", phân biệt giỏ bỏ dở). Đơn tùy chỉnh phải set cờ này. Đã sửa + vá đơn cũ.
- **Tiến độ đơn hàng làm lại**: thanh %, đường nối dọc, bước hiện tại nổi bật, và **mốc thời gian thật từng bước** đọc từ `ec_order_histories`.
- **Form đặt làm theo yêu cầu**: dùng đúng bộ thẻ `bb-customer-card`; thêm **chọn địa chỉ nhận hàng** từ sổ địa chỉ (địa chỉ được *sao chép* sang `ec_order_addresses`, không tham chiếu).
- **Admin có hướng dẫn luồng**: mỗi bước hiện "việc cần làm", danh sách 10 bước kèm nhãn ai thao tác, và cảnh báo rõ 2 bước **nhân viên không chuyển được** (Đã cọc / Xác nhận) vì phải chờ khách thanh toán.
- **Danh sách đơn của khách**: thêm nhãn **Đặt theo yêu cầu / Sản phẩm có sẵn** và hiện **trạng thái sản xuất** thay cho trạng thái core. Làm bằng `View::prependNamespace('plugins/ecommerce', ...)` để override view **từ trong plugin** (view này của ecommerce không có hook per-order). ⚠️ Nếu ecommerce cập nhật view danh sách đơn, phải đồng bộ lại bản copy trong `resources/views/overrides/`.
- **Sửa giá & gửi lại báo giá**: nhân viên sửa và gửi lại bao nhiêu lần cũng được khi đơn còn ở "Chờ duyệt"; mỗi lần ghi một dòng lịch sử (`handmade_quote_sent`) kèm chi tiết các khoản. Admin thấy "đã gửi X lần, đang chờ khách duyệt". Khách luôn thấy **giá mới nhất**. Sau khi khách duyệt + trừ cọc thì báo giá **khoá lại**.
- **Báo giá theo TỪNG MẪU** (thay cho một ô "phí sản phẩm" gộp): bảng liệt kê từng mẫu với số lượng và ô nhập **đơn giá**, tự tính thành tiền từng dòng. Tổng tiền sản phẩm = tổng các dòng, **không nhập tay**. Giá được ghi thẳng vào `ec_order_product.price` nên hoá đơn và trang khách đều đúng.
  - Có khối tổng kết **tính trực tiếp khi gõ**: tổng SP → tổng cộng → tiền cọc → còn lại, để nhân viên thấy số cọc trước khi bấm gửi. Công thức JS khớp đúng công thức máy chủ (cọc = 50% SP + ship; còn lại = tổng − cọc).
  - Dòng nào không gửi lên (form cũ/lệch) thì **giữ nguyên giá cũ**, không bị reset về 0.

## 12. Nhập đơn hàng loạt từ file Excel — ✅ ĐÃ XONG (21/07/2026)

Khách gửi file `dev/MrChinh-Handfill-Order.csv` (đơn fulfill hộ Amazon/Etsy). Đối chiếu
với form đặt hàng cũ thì **thiếu 6 nhóm dữ liệu**, nay đã bổ sung.

### Cột trong file khách ↔ dữ liệu hệ thống
| Cột file khách | Trước đây | Nay lưu ở |
|---|---|---|
| Sản phẩm, Số lượng, Personalization | ✅ đã có | `ec_order_product.product_name` / `qty` / `options.handmade.note` |
| Ảnh SP (link) | ⚠️ chỉ upload file | tự tải về `options.handmade.images` |
| Order ID (mã đơn sàn) | ❌ | `options.handmade.marketplace_order_id` |
| SKU | ❌ | `options.handmade.sku` |
| Ngày order | ❌ | `options.handmade.ordered_at` |
| Ảnh màu vải | ❌ | `options.handmade.fabric_images` |
| INFO SHIPPING | ❌ (chỉ 1 địa chỉ/đơn) | `options.handmade.recipient` **theo từng dòng** |
| Email người nhận | ❌ | `options.handmade.recipient.email` |

**Không tạo bảng mới**: tất cả nằm trong cột JSON `ec_order_product.options` dưới khoá
`handmade`, đúng cách giai đoạn 2 đã chọn — thêm cột vào bảng lõi của ecommerce sẽ vỡ
khi core cập nhật.

### Quyết định đã chốt (21/07/2026)
| # | Vấn đề | Chốt |
|---|---|---|
| 11 | Mỗi dòng một người nhận khác nhau | **Lưu người nhận riêng theo dòng**. Địa chỉ chung trong sổ địa chỉ thành **tùy chọn**; nếu bỏ trống thì `ec_order_addresses` lấy người nhận của dòng đầu (để hoá đơn và màn hình core không trống) |
| 12 | Luồng import | **Đọc file → xem trước trong form → khách bấm gửi**. Không tạo đơn thẳng từ file |
| 13 | File mẫu | **Chỉ .xlsx**; khi tải lên vẫn nhận .xlsx/.ods/.csv |
| 14 | Link ảnh | **Chỉ lưu link, KHÔNG tải ảnh về máy chủ.** Xem "đảo quyết định" bên dưới |
| 15 | Trường `marketplace_id` (ID listing trên sàn, thêm ở commit deeb54ef) | **Bỏ hẳn.** Trùng công dụng với Order ID + SKU, mà nhãn "ID sàn" còn dễ đọc nhầm thành mã đơn. Dữ liệu cũ nằm lại trong JSON, vô hại |
| 16 | Link hỏng | **Chặn không cho gửi đơn.** Đã không giữ bản sao thì link chết = đơn không còn gì để làm |

### Đảo quyết định #14 — bỏ tải ảnh về (21/07/2026)
Bản đầu tải ảnh về media library ngay khi đọc file. Chạy thật thì **~3,6s/ảnh** — 3 dòng mất
18s, 50 dòng sẽ vượt timeout. Đã thử vá bằng hạn mức 90s, rồi tính tới chunk AJAX, nhưng
cuối cùng chốt **bỏ hẳn việc tải về**: hệ thống lưu đúng link khách đưa.

- Đọc file **dưới 1 giây**, không còn bài toán timeout/chunk/queue nào nữa.
- `RvMedia::getImageUrl()` trả nguyên link tuyệt đối, nên ảnh remote hiển thị y hệt ảnh
  đã upload — kể cả ô thumbnail của dòng hàng (`ec_order_product.product_image`, chỉ dùng
  link khi độ dài ≤ 255 vì cột là `varchar(255)`).
- **Đổi lại phải kiểm link** (quyết định #16), vì không còn bản sao dự phòng.

**Đánh đổi phải biết:** link khách hết hạn / bị xoá thì đơn mất ảnh, có thể xảy ra vài tuần
sau khi đặt, đúng lúc vào xưởng. Ô nhập link ghi rõ "giữ link tới khi giao xong". Nếu sau này
thấy đau, thêm bước lưu bản sao **sau khi đơn đã tạo** (chạy nền) — cấu trúc dữ liệu không
phải đổi vì `images` (file upload) và `image_links` đã tách sẵn.

### Kiểm tra link (`ImageLinkChecker`)
3 kết quả, không phải 2 — vì thực tế file khách trộn cả link ảnh trực tiếp lẫn trang chia sẻ:

| Kết quả | Nghĩa | Xử lý |
|---|---|---|
| `image` | Trả về `Content-Type: image/*` | Hiện ảnh xem trước |
| `page` | Mở được nhưng là trang web (prnt.sc, Drive) | **Cho qua**, gắn nhãn vàng — nhân viên vẫn bấm xem được |
| `broken` | Lỗi mạng, 4xx/5xx, hoặc host nội bộ | **Chặn gửi đơn** |

- Hỏi bằng **HEAD** trước (rẻ nhất); gặp 403/405/501 mới hỏi lại bằng GET kèm `Range: bytes=0-0`
  — nhiều host cấm HEAD, và URL ký sẵn của S3 thường chỉ ký cho GET.
- `Http::pool()` 10 link một lượt + cache 10 phút, nên kiểm cả sheet tốn vài giây.
- Chặn luôn host phân giải ra IP nội bộ — link do khách nhập, không để thành đường dò mạng nội bộ.
- Kiểm ở **cả hai đầu**: lúc đọc file (để khách sửa ngay khi còn mở sheet) và lúc gửi đơn
  (server mới là nơi quyết định — trình duyệt gửi gì lên cũng được).

### Đã làm
- `CustomOrderImportSchema` — **nguồn chân lý duy nhất** cho 10 cột: vừa sinh file mẫu
  vừa đọc file tải lên. Tiêu đề cột **không dịch** (là định dạng dữ liệu, không phải UI):
  dịch sang tiếng Anh là file cũ của khách hết import được.
- `CustomOrderTemplateWriter` — sinh .xlsx tại chỗ: sheet 1 chỉ có dòng tiêu đề (cột bắt
  buộc tô đỏ, có comment giải thích, khoá dòng tiêu đề, cột mã/ngày ép kiểu text để Excel
  không biến `4112788779` thành `4.11279E+09`), sheet 2 là bảng hướng dẫn + ví dụ.
  **Không để dòng ví dụ ở sheet 1** — nó sẽ được import thành sản phẩm thật.
- `CustomOrderImporter` — đọc .xlsx/.ods/.csv qua `SimpleExcelReader`; tự dò dấu phân cách
  `,`/`;`; tự bỏ qua dòng rác phía trên bảng; ghép tiêu đề bằng cách bỏ dấu + bỏ ký tự đặc
  biệt nên "Ảnh màu vải (nếu có)" ≡ "anh mau vai"; tách INFO SHIPPING thành tên (dòng đầu)
  + địa chỉ (các dòng sau).
- `ImageLinkChecker` — kiểm link mở được hay không (bảng ở trên).
- Ô nhập **link ảnh mẫu** và **link ảnh màu vải** là textarea (mỗi dòng một link) chứ không
  phải input ẩn: link import về mà chết thì khách phải sửa được ngay tại chỗ. Server tách
  chuỗi thành mảng ở `prepareForValidation()` nên toàn bộ rule mảng dùng lại được.
- Trang khách `customer/custom-orders/create` **làm lại bố cục** (xem mục dưới).
- Hiển thị đầy đủ các trường mới ở **cả** trang đơn của khách lẫn bảng sản phẩm trong admin
  (dùng chung file `cart-item-options-extras.blade.php` nên chỉ sửa một chỗ).
- Nâng giới hạn: **50 sản phẩm/đơn** (cũ 10) — file thật của khách dài hơn 10 dòng nhiều.

### Bố cục trang đặt hàng — làm lại (21/07/2026)
Trước: một chồng thẻ trắng giống hệt nhau, mỗi sản phẩm là một thẻ cao gần full màn hình →
10 sản phẩm là 10 màn hình cuộn, không nhìn ra cấu trúc.

- Bỏ thẻ tiêu đề đầu trang (layout `customers.master` **đã in tiêu đề rồi** — trùng lặp).
- Thêm dải **3 bước** ngắn gọn để khách biết gửi xong thì chuyện gì xảy ra.
- Mỗi sản phẩm là một **dòng gấp/mở** (`<details>`): đóng lại chỉ thấy `#1 · Tên · SL · SKU ·
  Người nhận`; mở ra mới thấy form, chia thành 3 nhóm có tiêu đề (Thông tin sản phẩm /
  Ảnh mẫu / Người nhận). Import 50 dòng ra một **danh sách đọc được**, chỉ mở dòng đầu.
- Ảnh xem trước là **ô 120px**, không phải tem thư — đây là ảnh để làm ra sản phẩm, xem
  không rõ thì bằng không xem. Mỗi link là một ô kèm nhãn kết quả kiểm (ảnh OK / mở được,
  không phải ảnh / không mở được).
- **Thanh gửi dính đáy màn hình** — không phải cuộn hết 50 dòng mới bấm gửi được.
- Khối import thành **vùng kẻ nét đứt** riêng, nút tải file mẫu nằm ngay ở tiêu đề khối.
- CSS đặt inline trong view, tiền tố `hw-`: cùng lý do với `<script>` (layout theme không có
  `@stack`), và để cả màn hình nằm gọn trong một file.

### Đã kiểm chứng (chạy thật qua HTTP, có đăng nhập)
- Đọc đúng **cả 3 dòng** file thật của khách: tên, SL, SKU, Order ID, ghi chú nhiều dòng,
  người nhận + email từng dòng.
- **Phân loại đúng cả 5 link**: 3 link ảnh S3/zaytoka → `image`; 2 link prnt.sc → `page`
  (mở được nhưng là trang web) → cho qua kèm nhãn vàng. 0 cảnh báo. Đọc file dưới 1 giây.
- **Chặn đúng link hỏng** — mỗi ca ~0,6s: link 404, host không tồn tại, và `http://127.0.0.1`
  (thử SSRF) đều bị từ chối HTTP 422 kèm thông báo chỉ rõ dòng nào; link ảnh thật vẫn qua.
- Gửi đơn thật → đơn 3 dòng, mỗi dòng giữ đúng người nhận riêng; `ec_order_addresses` tự lấy
  người nhận dòng đầu vì khách không chọn địa chỉ chung.
- Trang đơn của khách hiện đủ: SKU, Order ID, ngày order, ảnh từ link remote, ảnh màu vải,
  người nhận, email, địa chỉ. Thumbnail dòng hàng lấy thẳng link S3.
- File mẫu .xlsx sinh ra **mở lại và import ngược được** (round-trip), gồm cả comment cột
  và ràng buộc số lượng.
- Đối chiếu asset với `customer/orders` bằng `comm -23`: **không thiếu file CSS/JS nào**,
  các lớp vỏ tài khoản khớp số lượng.

### Bẫy đã gặp
8. **Ngày `10/7/2026` nhập nhằng d/m hay m/d.** Trong cùng file khách có `7/13/2026` (chắc
   chắn m/d) lẫn `10/7/2026`. Chốt định dạng theo cả file thì `10/7` thành 7 tháng 10 — ngày
   **trong tương lai**, vô lý với ngày đã đặt trên sàn. Cách xử lý: chốt định dạng theo file,
   nhưng ô nào ra ngày tương lai mà đảo lại thành quá khứ thì đảo riêng ô đó.
9. **`date_create_from_format` không báo lỗi khi tháng > 12** — `7/13/2026` đọc theo `d/m/Y`
   lặng lẽ thành tháng 1/2027. Phải kiểm `date_get_last_errors()` sau mỗi lần parse.
10. **Trường `required` nằm trong `<details>` đang đóng thì trình duyệt không submit được và
    cũng không báo gì** (không focus được field ẩn). Đã bắt sự kiện `invalid` ở pha capture
    để mở đúng dòng chứa lỗi.
11. **`mimes:xlsx` loại nhầm file thật** — trình duyệt khai .xlsx là `application/octet-stream`,
    .csv là `text/plain`. Dùng `extensions:` thay cho `mimes:`. `.xls` nhị phân cũ **không đọc
    được** (openspout không hỗ trợ) nên không nhận.
12. **Tải ảnh chậm hơn tưởng**: ~3,6s/ảnh → 50 dòng vượt timeout. Vá bằng hạn mức thời gian
    chỉ là giấu triệu chứng; cuối cùng bỏ hẳn việc tải về (đảo quyết định #14). Bài học: đo
    thời gian thật trước khi chọn kiến trúc — 18s cho 3 dòng đã đủ để thấy 50 dòng là hỏng.
13. **`@json(...)` cuối dòng trong `<script>` nuốt luôn ký tự xuống dòng** → `const A = "x"const
    B = ...` và **cả file JS chết** với `Uncaught SyntaxError: Unexpected token 'const'`. Trang
    vẫn render bình thường nên chỉ lộ ra khi mở console. Bắt buộc **kết thúc bằng dấu `;`** ở
    mọi dòng có directive Blade, dù phần còn lại của file viết theo kiểu không chấm phẩy.
    (Đây là lý do `order-products.blade.php` truyền cấu hình qua `data-*` thay vì nhúng thẳng.)
    Nghiệm thu JS từ nay: trích khối `<script>` trong HTML đã render rồi chạy `node --check`.

### Còn nợ
- **Link có thể chết sau khi đơn đã đặt.** Kiểm lúc gửi chỉ đảm bảo link sống *lúc đó*. Nếu
  cần chắc chắn: thêm việc chạy nền kiểm lại link của các đơn chưa giao và cảnh báo, hoặc lưu
  bản sao sau khi tạo đơn.
- Chưa đẩy các trường mới (SKU / Order ID / người nhận) sang Lark Base — làm cùng giai đoạn 4.

## 13. Hiển thị tiền đã trả / còn nợ — ✅ ĐÃ XONG (21/07/2026)

Thẻ báo giá cũ in "Tiền cọc: X — Còn lại: Y" **y hệt nhau dù đã trả hay chưa**. Khách trả cọc
xong vào xem vẫn thấy đúng hai con số đó, không biết mình đã trả bao nhiêu.

### Đã làm
- `OrderQuote` thêm `paid_amount`, `outstanding_amount` và `milestones()` — **một nguồn tính
  duy nhất** cho cả trang chi tiết lẫn danh sách, nên hai trang không thể lệch nhau.
  `outstanding = total − paid` (tính ngược từ tổng, cùng lý do với công thức 2 mốc ở GĐ3).
- **Trang chi tiết đơn**: bảng "Tình hình thanh toán" — từng mốc kèm dấu tick, thời điểm trừ
  ví, nhãn "Đã thu"/"Chưa thu"; dưới cùng là **Đã thanh toán** và **Còn phải trả**. Trả đủ thì
  hiện dòng xác nhận.
- **Trang danh sách đơn**: ô "Thanh toán" (vốn luôn hiện "N/A" vì đơn handmade trừ ví theo mốc
  chứ không qua cổng thanh toán) thay bằng **Đã thanh toán** + **Còn phải trả**, kèm dòng
  "Đợt tiếp theo: … — …". Đơn chưa báo giá thì ghi "Chờ báo giá".
  Quote của cả trang lấy bằng **1 truy vấn** (`whereIn` rồi `keyBy`), không phải mỗi thẻ một lần.
- **Ẩn thẻ "Chứng từ thanh toán" khi đã thu đủ tiền.** Đòi khách chứng minh một khoản hệ thống
  đã ghi nhận xong là vô nghĩa — với ví điện tử thì tiền vào ngay lúc bấm.
  `QuoteService::isPaymentSettled()`: đơn có báo giá thì hỏi báo giá, còn lại hỏi bản ghi payment.

### Đã kiểm chứng (chạy thật)
- Công thức trên **cả 4 báo giá** đang có: cọc + còn lại luôn **đúng bằng tổng**; đơn đã trả đủ
  ra `còn phải trả = 0`.
- Thẻ chứng từ, kiểm qua HTTP cả hai chiều trên cùng một đơn: **còn nợ → hiện**, gán trả đủ →
  **biến mất** và hiện dòng "Bạn đã thanh toán đủ đơn hàng này".

### Bẫy đã gặp
14. **`isPaymentProofEnabled()` không nhìn vào việc đã trả hay chưa** — nó chỉ hỏi "phương thức
    này có bật chứng từ không". Ví điện tử nằm trong danh sách bật, nên đơn trả bằng ví xong
    vẫn bị đòi chứng từ. Core không có filter ở chỗ này và method nằm sẵn trên model nên
    macro cũng không đè được → phải **copy view** `customers/orders/view.blade.php` vào
    `overrides/`, sửa đúng một dòng điều kiện. ⚠️ Ecommerce cập nhật view này thì phải đồng bộ lại.
    (Đơn handmade không dính lỗi này vì chúng **không có bản ghi payment** — chỉ đơn mua hàng
    có sẵn trả bằng ví mới lộ ra.)

### Giai đoạn 4 sẽ làm
Đồng bộ Lark 2 chiều: đẩy đơn + ảnh lên Base khi tạo/đổi trạng thái (đã có sẵn client), thêm
`updateRecord()`, và bổ sung các cột còn thiếu trong Base (nay gồm cả SKU, Order ID sàn,
người nhận từng dòng).
