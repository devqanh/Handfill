# Theme Handfill

Theme giao diện cho handfill.dev, dựng trên Botble CMS + Bootstrap 5.3.3.

Thiết kế gốc: `Handfill-Web-Design/artifacts/handfill` (bản Next.js/Tailwind, chỉ dùng để tham chiếu — **không** build từ đó).

---

## 1. Nguyên tắc quan trọng nhất: theme này kế thừa Shofy

```php
// config.php
'inherit' => 'shofy',
```

Botble sẽ tìm view/asset trong `platform/themes/handfill/` **trước**, không có thì tự động lấy từ `platform/themes/shofy/`.

**Hệ quả — đọc kỹ trước khi code:**

- Chỉ tạo file trong theme này khi **thực sự cần đổi**. Không copy nguyên thư mục Shofy sang.
- Không sửa file trong `platform/themes/shofy/` — update Botble sẽ ghi đè, và các theme khác (`shofy-fashion`, `shofy-beauty`…) cũng dùng chung.
- Bootstrap 5.3.3, jQuery, Swiper, slick, `theme.css`, `theme.js` đều lấy từ Shofy. Không cần khai báo lại.

---

## 2. Cấu trúc thư mục

```
platform/themes/handfill/
├── theme.json                  # metadata (id, namespace, version)
├── config.php                  # inherit + đăng ký asset + partial composer
├── vite.build.mjs              # khai báo entry SCSS/JS cho build
├── assets/                     # SOURCE (chỉnh ở đây)
│   ├── sass/
│   │   ├── theme.scss          # entry, import các file bên dưới
│   │   ├── _variables.scss     # design token (màu, radius, font)
│   │   ├── _base.scss          # override chung
│   │   └── components/
│   │       └── _auth.scss      # CSS trang đăng nhập/đăng ký
│   └── js/handfill.js
├── public/                     # OUTPUT của build — KHÔNG sửa tay
│   ├── css/handfill.css
│   ├── js/handfill.js
│   └── images/logo.png
├── functions/                  # PHP tự động autoload
│   ├── theme-options.php       # thêm field vào Theme options
│   └── typography.php          # đổi font mặc định
├── lang/                       # vi.json, en.json
├── partials/auth/              # partial dùng lại
└── views/ecommerce/customers/  # override view của plugin ecommerce
```

---

## 3. Các lệnh cần chạy

Chạy tại **thư mục gốc dự án** (`C:\DU-AN\HANDMADE`), không phải trong thư mục theme.

### Cài đặt lần đầu

```bash
npm install
```

### Build CSS/JS sau khi sửa `assets/`

```bash
npm run prod     # bản production (minify) — dùng khi deploy
npm run dev      # bản development — build nhanh, dễ debug
```

Build sẽ quét toàn bộ `platform/*/*/vite.build.mjs` và build song song. Output:

| Chế độ | Đích |
|---|---|
| `dev` + `prod` | `public/themes/handfill/{css,js}/` |
| chỉ `prod` | thêm `platform/themes/handfill/public/{css,js}/` (bản commit vào git) |

> Không có watch mode. Sửa SCSS/JS xong phải chạy lại lệnh build.

### Copy ảnh / file tĩnh ra `public/`

```bash
php artisan cms:theme:assets:publish handfill
```

Chạy khi thêm file vào `platform/themes/handfill/public/images/`. Lệnh này copy cả asset của Shofy (theme cha) sang, nên chạy lại sau mỗi lần build cũng an toàn.

### Xoá cache khi sửa Blade / config / functions

```bash
php artisan view:clear          # sau khi sửa .blade.php
php artisan cms:theme:clear-cache
php artisan optimize:clear      # khi sửa config.php hoặc functions/
```

### Đổi theme đang chạy

```bash
php artisan cms:theme:activate handfill
```

Hoặc trong admin: **Appearance → Theme**.

### Lưu ý PHP trên máy này

Project yêu cầu PHP >= 8.3 nhưng `php` mặc định trong PATH là 8.2 → `artisan` sẽ báo lỗi platform_check. Dùng đường dẫn đầy đủ:

```bash
"C:/laragon/bin/php/php-8.3.29-nts-Win32-vs16-x64/php.exe" artisan view:clear
```

---

## 4. Cách override một trang

### Bước 1 — tìm file gốc

Botble resolve view theo thứ tự: `handfill/` → `shofy/` → view trong plugin.

```bash
# tìm trong theme cha
ls platform/themes/shofy/views/ecommerce/

# tìm trong plugin
ls platform/plugins/ecommerce/resources/views/themes/
```

Trong controller, `Theme::scope('ecommerce.customers.login', ...)` tương ứng file
`views/ecommerce/customers/login.blade.php`.

### Bước 2 — tạo file cùng đường dẫn trong `handfill/`

Ví dụ muốn đổi trang giỏ hàng: tạo `views/ecommerce/cart.blade.php`. File ở Shofy vẫn nguyên vẹn, chỉ bị "che".

### Bước 3 — tham chiếu view khác trong theme

Luôn dùng `Theme::getThemeNamespace()`, không hard-code tên theme:

```blade
@include(Theme::getThemeNamespace('partials.auth.logo'))
@extends(Theme::getThemeNamespace('layouts.base'))
```

Namespace này tự resolve theo cơ chế inherit, nên `partials.auth.logo` (có trong handfill)
và `layouts.base` (chỉ có trong shofy) đều chạy được.

### Chọn layout

```blade
@php
    Theme::layout('without-layout');   // không header/footer/breadcrumb
    // 'default'      → có header, footer, breadcrumb, bọc trong .container
    // 'full-width'   → có header, footer, breadcrumb, không bọc container
@endphp
```

---

## 5. Cách trang đăng nhập/đăng ký được dựng

Đây là mẫu để làm các trang form khác. **Không viết lại form bằng HTML thuần** — form vẫn do Botble render để giữ validation, hook plugin và tuỳ chọn trong admin.

```blade
{{-- views/ecommerce/customers/register.blade.php --}}
{!! $form
    ->template(Theme::getThemeNamespace('views.ecommerce.customers.forms.auth'))
    ->remove('agree_terms_and_policy')          // bỏ field không cần
    ->setFormOption('authPanel', 'register')    // truyền dữ liệu cho template
    ->setFormOption('authHeading', __('Create an account'))
    ->renderForm() !!}
```

- `->template()` thay template mặc định `plugins/ecommerce::customers.forms.auth` bằng template của mình.
- `->remove('ten_field')` bỏ field. Kiểm tra rule trong `RegisterRequest` trước — nếu là `required` thì bỏ sẽ hỏng submit.
- `->setFormOption()` truyền biến xuống template, đọc bằng `Arr::get($formOptions, 'key')`.
- Trong template, **phải** loại các key đó ra khỏi `Form::open()`, nếu không chúng bị in thành attribute của thẻ `<form>`.

Nhờ cách này: validation server + JS, đăng nhập bằng email/SĐT, nút hiện mật khẩu, nút social login đều hoạt động sẵn.

---

## 6. Cách thêm CSS

1. Tạo file mới trong `assets/sass/components/`, ví dụ `_product-card.scss`.
2. Import vào `assets/sass/theme.scss`:
   ```scss
   @import 'components/product-card';
   ```
3. `npm run prod`.

**Dùng design token, không hard-code màu:**

```scss
.my-block {
    color: var(--hf-gray-900);
    background: var(--hf-primary);
    border-radius: var(--hf-radius);
}
```

Danh sách token đầy đủ ở `assets/sass/_variables.scss`. `handfill.css` được nạp **sau** `theme.css` của Shofy nên override được mà không cần `!important`.

---

## 7. Cách thêm Theme option (nội dung sửa được trong admin)

Thêm vào `functions/theme-options.php`:

```php
ThemeOption::getFacadeRoot()->setSection([
    'title' => __('Tên section'),
    'id' => 'opt-text-subsection-xxx',
    'subsection' => true,
    'icon' => 'ti ti-star',
    'fields' => [
        TextField::make()->name('my_key')->label(__('Nhãn'))->defaultValue('Giá trị'),
    ],
]);
```

Đọc trong blade: `theme_option('my_key')`. **Luôn có fallback trong code** vì option chưa lưu sẽ trả về rỗng:

```blade
{{ theme_option('my_key') ?: __('Giá trị mặc định') }}
```

File `functions/` của theme cha (shofy) được autoload **trước**, nên ở đây chỉ nên *thêm*, hoặc đăng ký đè bằng cùng key (xem `functions/typography.php`).

---

## 8. Đa ngôn ngữ

Chuỗi trong blade viết bằng tiếng Anh với `__()`, dịch sang tiếng Việt trong `lang/vi.json`:

```blade
{{ __('Sign up for free') }}
```

```json
{ "Sign up for free": "Đăng ký miễn phí" }
```

Sửa `lang/*.json` xong chạy `php artisan optimize:clear`.

---

## 9. Những lỗi đã gặp — tránh lặp lại

| Lỗi | Nguyên nhân | Cách đúng |
|---|---|---|
| `Unclosed '[' ... does not match ')'` | `@json([` xuống nhiều dòng — Blade parse hỏng directive nhiều dòng | Gán mảng vào biến trong `@php`, rồi `{!! json_encode($var) !!}` |
| `Unparenthesized a ? b : c ?: d is not supported` | PHP 8 cấm trộn `? :` với `?:` không ngoặc | `$a ? ($b ?: $c) : ($d ?: $e)` |
| Mã vùng `+84` đè lên placeholder ô SĐT | Override `padding-inline-start` với `!important` lên input | Không set padding cho `.iti .form-control` — intl-tel-input tự đo và ghi `padding-left` inline |
| Font Google không nạp | Thêm URL fonts.googleapis.com qua `Theme::asset()->add()` | Dùng typography system (`functions/typography.php`) — Botble tự tải và host font |
| Blockquote có nền xanh lạ | Shofy style thẻ `<blockquote>` trần | Reset `background/padding/border` trong SCSS của mình |

---

## 10. Checklist trước khi commit

```bash
npm run prod
php artisan cms:theme:assets:publish handfill
php artisan view:clear
```

Kiểm tra syntax PHP của blade mới:

```bash
"C:/laragon/bin/php/php-8.3.29-nts-Win32-vs16-x64/php.exe" -l platform/themes/handfill/views/duong/dan.blade.php
```

Cần commit cả `platform/themes/handfill/public/` (output của `npm run prod`).

---

## 11. Trạng thái

| Trang | Route | Tình trạng |
|---|---|---|
| Đăng nhập | `/login` | Xong |
| Đăng ký | `/register` | Xong |
| Quên mật khẩu | `/password/reset` | Xong |
| Đặt lại mật khẩu | `/password/reset/{token}` | Xong |
| Trang chủ | `/` | Chưa — đang dùng giao diện Shofy |
| Danh sách sản phẩm | `/products` | Chưa |
| Chi tiết sản phẩm | | Chưa |
| Về chúng tôi / Liên hệ / FAQ | | Chưa |
