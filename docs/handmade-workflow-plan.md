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

### Giai đoạn 4 sẽ làm
Đồng bộ Lark 2 chiều: đẩy đơn + ảnh lên Base khi tạo/đổi trạng thái (đã có sẵn client), thêm `updateRecord()`, và bổ sung các cột còn thiếu trong Base.
