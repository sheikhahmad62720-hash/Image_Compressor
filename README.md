# Image Compressor

A single-page image compression tool built with Laravel, Vue 3, Inertia.js, and Tailwind CSS. Upload an image and get an optimized copy compressed to under 1 MB while preserving as much visual quality as possible.

## Features

- Drag-and-drop upload for JPG, JPEG, PNG, and WebP files (up to 20 MB).
- Automatic quality optimization: starts at 95% and walks down until the file fits the 1 MB target.
- Progressive downscaling as a last resort, so even stubborn images are reduced below 1 MB.
- EXIF orientation handling so previews always look correct.
- Original file is never modified — a new compressed copy is generated for download.
- Side-by-side before/after preview with size and reduction stats.
- Fully client-side flow: you only send the image when you click compress; the optimized result comes back as a base64 data URL for instant preview and download.

## Tech Stack

- **Backend:** Laravel 13, PHP 8.4
- **Image processing:** Intervention Image 4 (GD driver)
- **Frontend:** Vue 3, Inertia.js, Tailwind CSS, Vite

## Requirements

- PHP 8.3+ with the GD extension (check with `php -m | grep -i gd`)
- Composer
- Node.js 18+ and npm

## Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Install frontend dependencies
npm install

# 3. Configure the environment
cp .env.example .env
php artisan key:generate

# 4. Build assets (production) or start the Vite dev server
npm run build        # production assets
# or
npm run dev          # hot-reloading dev server

# 5. Start the application
php artisan serve
```

Open http://localhost:8000 in your browser.

## Commands

| Command               | Description                            |
| --------------------- | -------------------------------------- |
| `npm run dev`         | Start the Vite dev server (hot reload) |
| `npm run build`       | Compile and minify production assets    |
| `php artisan serve`   | Start the Laravel server on port 8000   |
| `php artisan test`    | Run the test suite                     |

> **Note:** after `npm run build`, remove the `public/hot` file if it exists — otherwise Laravel injects a stale dev-server URL and the page loads blank.

## How It Works

1. The browser uploads the image to `POST /compress` with a CSRF token.
2. `ImageCompressionService` decodes the image once, then:
   - re-encodes it at descending JPEG/WebP quality (95 → 45) until it fits under 1 MB (PNG is encoded once — its output is lossless and quality-independent);
   - if that is not enough, it progressively downscales the image until the target is met.
3. The compressed bytes are returned as base64, and the client builds a downloadable data URL and shows the before/after comparison.

