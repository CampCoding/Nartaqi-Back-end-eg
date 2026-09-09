# Walkthrough - Home Module Implementation

We have successfully created the `Home` module to manage homepage banners (images) and a single homepage video link.

## 1. Files Created & Modified

### 1.1 Database Migrations
* **[`2026_06_07_000001_create_home_banners_table.php`](file:///e:/Work/laravel/nartaqi/nartaqi/Modules/Home/database/migrations/2026_06_07_000001_create_home_banners_table.php)**: Creates `home_banners` table with fields for storing image path.
* **[`2026_06_07_000002_create_home_videos_table.php`](file:///e:/Work/laravel/nartaqi/nartaqi/Modules/Home/database/migrations/2026_06_07_000002_create_home_videos_table.php)**: Creates `home_videos` table to store the single YouTube video link.

### 1.2 Models
* **[`HomeBanner.php`](file:///e:/Work/laravel/nartaqi/nartaqi/Modules/Home/app/Models/HomeBanner.php)**: Represents `home_banners` table with automatic `image_url` attribute/accessor appended to JSON responses.
* **[`HomeVideo.php`](file:///e:/Work/laravel/nartaqi/nartaqi/Modules/Home/app/Models/HomeVideo.php)**: Represents `home_videos` table to store the single YouTube video.

### 1.3 Form Requests
* **[`AddBannerRequest.php`](file:///e:/Work/laravel/nartaqi/nartaqi/Modules/Home/app/Http/Requests/AddBannerRequest.php)**: Validates image uploads (requires image, mimes: jpeg, png, jpg, gif, svg, webp, max: 5MB).
* **[`DeleteBannerRequest.php`](file:///e:/Work/laravel/nartaqi/nartaqi/Modules/Home/app/Http/Requests/DeleteBannerRequest.php)**: Validates banner deletion (requires existing id).
* **[`UpdateVideoRequest.php`](file:///e:/Work/laravel/nartaqi/nartaqi/Modules/Home/app/Http/Requests/UpdateVideoRequest.php)**: Validates video update (requires valid URL format).

### 1.4 Controller & Routes
* **[`HomeController.php`](file:///e:/Work/laravel/nartaqi/nartaqi/Modules/Home/app/Http/Controllers/HomeController.php)**: Implements all APIs. Uploaded images are stored in `public/storage/banners` directory and the old files are deleted when a banner is deleted.
* **[`api.php`](file:///e:/Work/laravel/nartaqi/nartaqi/Modules/Home/routes/api.php)**: Defines route endpoints.
  * **Public/User endpoints**:
    * `GET /api/user/home/banners`
    * `GET /api/user/home/video`
    * `GET /api/user/home` (gets both in one request)
  * **Admin endpoints** (Guarded with `AdminAuthentication` middleware):
    * `GET /api/admin/home/banners`
    * `POST /api/admin/home/banners/add` (requires multipart/form-data for image)
    * `POST /api/admin/home/banners/delete`
    * `GET /api/admin/home/video`
    * `POST /api/admin/home/video/update`

### 1.5 Module Activation & Postman Collection
* **[`modules_statuses.json`](file:///e:/Work/laravel/nartaqi/nartaqi/modules_statuses.json)**: Added `"Home": true` to enable the module in the application.
* **[`Home_API.postman_collection.json`](file:///e:/Work/laravel/nartaqi/nartaqi/Home_API.postman_collection.json)**: A Postman collection containing all configured endpoints with sample payloads and headers.

---

## 2. Running Migrations
Since you preferred to run the migrations manually, please run the following command to create the necessary tables in your database:

```bash
php artisan migrate
```
