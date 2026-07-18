# Hướng dẫn viết Plugin cho dự án (Botble CMS)

> Ghi chú nội bộ để code nhanh các module/plugin sau này. Dựa trên phiên bản thực tế của dự án.
> Plugin mẫu tham chiếu trong repo: `platform/plugins/lark-webhook` (tự viết), `platform/plugins/announcement`, `platform/plugins/request-log`.

## Phiên bản (đã kiểm chứng)

| Thành phần | Giá trị |
|---|---|
| Laravel | 13.x |
| PHP | 8.3 / 8.4 (laragon: `C:/laragon/bin/php/php-8.3.29-nts-Win32-vs16-x64/php.exe`) |
| Botble core | `get_core_version()` ≈ 7.6.x |
| DataTables | yajra/laravel-datatables v13 |
| Admin URL | http://localhost:8002/admin |

## Cơ chế nạp plugin (QUAN TRỌNG)

- **Không dùng composer autoload cho namespace của plugin.** Botble đọc `plugin.json` của các plugin đang active, map `namespace` → thư mục `src/` lúc runtime (`platform/packages/plugin-management`). Cache tại `bootstrap/cache/plugins.php`.
- => Namespace bắt buộc trỏ tới `<plugin>/src`. Không cần `composer dump-autoload`.
- Plugin chỉ chạy sau khi **được kích hoạt**. `composer.json` trong plugin chỉ cần khi có dependency bên thứ ba (merge qua `wikimedia/composer-merge-plugin`).

## Bộ khung tối thiểu một plugin

```
platform/plugins/<ten>/
├── plugin.json                         # manifest: id, name, namespace (có \\ cuối), provider, version, minimum_core_version
├── config/permissions.php              # khai báo quyền
├── routes/web.php
├── database/migrations/*.php
├── resources/lang/{en,vi}/<file>.php
├── resources/views/*.blade.php
└── src/
    ├── Plugin.php                      # extends PluginOperationAbstract (activated/remove...)
    ├── Providers/<Ten>ServiceProvider.php
    ├── Models/*.php                    # extends Botble\Base\Models\BaseModel
    ├── Tables/*.php                    # extends Botble\Table\Abstracts\TableAbstract
    ├── Http/Controllers/*.php          # extends Botble\Base\Http\Controllers\BaseController
    ├── Http/Requests/*.php             # extends Botble\Support\Http\Requests\Request
    └── Forms/**                        # Form / SettingForm
```

### ServiceProvider — chuỗi boot chuẩn
Extends `Botble\Base\Supports\ServiceProvider`, dùng trait `LoadAndPublishDataTrait`:
```php
$this->setNamespace('plugins/<ten>')          // giá trị này = namespace của config/view/lang/asset
    ->loadAndPublishConfigurations('permissions')
    ->loadRoutes()
    ->loadAndPublishViews()
    ->loadAndPublishTranslations()
    ->loadMigrations()
    ->publishAssets();
```

### Menu admin — KHÔNG dùng event, dùng facade
```php
DashboardMenu::default()->beforeRetrieving(function () {
    DashboardMenu::make()->registerItem([
        'id' => 'cms-plugins-<ten>',
        'priority' => 900,
        'parent_id' => null,           // hoặc id cha để tạo menu con
        'name' => 'plugins/<ten>::<file>.name',   // là KEY dịch, không phải trans()
        'url' => fn () => route('<ten>.index'),
        'icon' => 'ti ti-...',         // Tabler icons
        'permissions' => ['<ten>.index'],
    ]);
});
```
Trang Settings đăng ký qua `PanelSectionManager::beforeRendering` + `PanelSectionItem::make()` vào `SettingOthersPanelSection::class` (hoặc `SystemPanelSection`).

### Route public / webhook (không auth, bỏ CSRF)
Đặt `Route::` trần (ngoài `AdminHelper::registerRoutes`), bỏ CSRF theo từng route:
```php
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

Route::post('duong-dan/{token}', [Controller::class, 'receive'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('...');
```
Route admin đặt trong `AdminHelper::registerRoutes(fn () => ...)` (tự có prefix `admin/` + middleware auth). Đặt route wildcard `{id}` **sau cùng** và ràng buộc `->where('id','[0-9]+')` để không nuốt `settings`, `empty`...

> **BẪY QUAN TRỌNG (route giao diện khách):** route front-end (khách hàng) **phải** bọc trong `Theme::registerRoutes(function () { ... })` — chính nó mới gắn nhóm middleware `web` (cookie, **session**, CSRF) + `core`. Nếu khai bằng `Route::middleware('customer')` trần trong `routes/web.php` của plugin thì route **không có session**, nên `Auth::guard('customer')` luôn thấy là khách vãng lai và **đá về `/login` dù đã đăng nhập**.
> ```php
> use Botble\Theme\Facades\Theme;
> Theme::registerRoutes(function (): void {
>     Route::middleware('customer')->prefix('customer/abc')->name('customer.abc.')->group(function (): void { ... });
> });
> ```
> Cách kiểm tra: so middleware route của mình với `customer.overview` — phải có `StartSession`.
> (Route admin thì dùng `AdminHelper::registerRoutes()`, nó tự lo phần này. Route webhook máy-gọi-máy thì không cần session.)

> **BẪY QUAN TRỌNG (render trang giao diện khách):** controller front-end **không** được `return view(...)` trực tiếp — làm vậy trang sẽ **vỡ giao diện** (mất header/footer/sidebar/assets của theme). Phải render qua `Theme::scope()`:
> ```php
> SeoHelper::setTitle($title);
> Theme::breadcrumb()->add($title, route('...'));
>
> return Theme::scope(
>     'handmade-workflow.custom-order-form',              // view của theme (theme override được)
>     $data,
>     'plugins/handmade-workflow::customer.custom-order-form'  // view dự phòng trong plugin
> )->render();
> ```
> Bản thân view vẫn `@extends(EcommerceHelper::viewPath('customers.master'))` như bình thường.
> Cách kiểm tra: tải trang bằng HTTP thật rồi đếm các mốc (`tp-header`, `customer-sidebar`, `<footer`, `</html>`) — phải khớp với `customer/overview`.

> **BẪY QUAN TRỌNG (thiếu CSS trang tài khoản):** controller trang tài khoản khách phải đăng ký **ĐỦ CẢ HAI** file CSS trong **constructor**, y như `OrderController` / `PublicController` của ecommerce:
> ```php
> public function __construct()
> {
>     $version = EcommerceHelper::getAssetVersion();
>
>     Theme::asset()->add('customer-style', 'vendor/core/plugins/ecommerce/css/customer.css', ['bootstrap-css'], version: $version);
>     Theme::asset()->add('front-ecommerce-css', 'vendor/core/plugins/ecommerce/css/front-ecommerce.css', version: $version);
> }
> ```
> **`customer.css` mới là file định kiểu khu vực tài khoản** (thẻ sidebar, kích thước avatar `.wrapper-image`, bố cục `.bb-profile-content`). Chỉ có `front-ecommerce.css` là **chưa đủ** — trang vẫn vỡ: avatar phình to, sidebar mất thẻ nền.
> Cách kiểm tra (chuẩn nhất): tải HTML của trang mình **và** của `customer/orders`, trích danh sách file `.css` rồi `comm -23` — phải **không thiếu file nào**, và **thứ tự cũng phải trùng**.

> **BẪY QUAN TRỌNG (JS không chạy ở giao diện khách):** layout theme **KHÔNG có `@stack('footer')`**, nên `@push('footer')` trong view front-end sẽ **bị bỏ luôn, không báo lỗi gì** — nút bấm chết câm. (`@push('footer')` chỉ dùng được ở view **admin**.)
> Cách xử lý: đặt `<script>` **inline ngay trong `@section('content')`** (sau các phần tử nó thao tác), hoặc dùng `Theme::asset()->container('footer')->writeScript('ten', $js, ['jquery'])`.
> Cách kiểm tra: `grep -c` một chuỗi đặc trưng trong thân script (ví dụ tên hàm) trên HTML tải về — phải > 0.

> **BẪY QUAN TRỌNG (DataTables):** trang danh sách dùng `TableAbstract` thì **DataTables load dữ liệu bằng POST** tới đúng URL của trang. Vì vậy route index PHẢI nhận cả GET và POST, nếu không sẽ bị 405 và hiện "DataTables warning: ... Invalid JSON response". `Route::resource` của Botble tự thêm POST cho index; nếu tự khai bằng `Route::get('/')` thì phải đổi thành:
> ```php
> Route::match(['GET', 'POST'], '/', [FooController::class, 'index'])->name('index');
> ```
> (Kiểm tra: `php artisan route:list` — route index phải là `GET|POST|HEAD`.)

### Table (danh sách DataTable)
`extends TableAbstract`, khai báo trong `setup()`: `->model()`, `->addColumns([...])`, `->addActions([EditAction/ViewAction/DeleteAction::make()->route(...)])`, `->addBulkActions([...])`, `->queryUsing(fn ($q) => $q->select(...))`. View tùy biến qua `->setView('plugins/<ten>::table')` rồi `@extends('core/table::table')`.

### Migration
Anonymous class, guard `Schema::hasTable`, closure `: void`:
```php
return new class () extends Migration {
    public function up(): void { if (! Schema::hasTable('...')) { Schema::create(...); } }
    public function down(): void { Schema::dropIfExists('...'); }
};
```

### Model
`extends Botble\Base\Models\BaseModel` (KHÔNG phải Eloquent Model). Có `$fillable`, `$casts`, accessor `Attribute::get(...)->shouldCache()`.

### Settings
- Form `extends Botble\Setting\Forms\SettingForm`, `->setValidatorClass(...)`, các field dùng key `setting('...')`.
- Controller `extends Botble\Setting\Http\Controllers\SettingController`, `update()` gọi `$this->performUpdate($request->validated())`.
- Lưu/đọc trực tiếp: `Setting::set('key', $value)->save();` / `setting('key', $default)`.

## Dịch (i18n)
File `resources/lang/{locale}/<file>.php` trả mảng. Gọi `trans('plugins/<ten>::<file>.key')`. Dự án dùng cả `en` và `vi`.

## Lệnh hay dùng (PowerShell/Bash, laragon)
```bash
PHP="C:/laragon/bin/php/php-8.3.29-nts-Win32-vs16-x64/php.exe"
$PHP artisan cms:plugin:activate <ten>      # kích hoạt (tự chạy migration)
$PHP artisan cms:plugin:deactivate <ten>
$PHP artisan cms:plugin:remove <ten>        # gọi Plugin::remove()
$PHP artisan route:list | grep <ten>
$PHP -l <file.php>                            # lint cú pháp
```

## Kiểm thử nhanh một endpoint (không cần Postman)
Dùng `artisan tinker` dựng `Illuminate\Http\Request::create(...)` rồi `app()->handle($req)` — xem `plugin lark-webhook` phần test trong lịch sử. Nhớ dọn dữ liệu test (`Model::truncate()`).

## Checklist khi thêm module mới
1. Tạo bộ khung file như trên, đặt `namespace` đúng, `minimum_core_version` ~ `7.4.0`.
2. Khai báo `config/permissions.php` + `permissions` trong menu/route.
3. `php artisan cms:plugin:activate <ten>` → kiểm tra `route:list` và bảng DB.
4. Vào `admin/plugins/installed` để bật/tắt qua UI khi cần.
