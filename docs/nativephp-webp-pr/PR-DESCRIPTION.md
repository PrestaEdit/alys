# Add WebP output for Android bitmap resources + fix uncompressed PNGs

## Motivation

Google Play Console flags apps that ship large, unoptimized bitmap resources under
"Optimize your app's images". On a real production app (Alys, `com.prestaedit.alys`)
the 5 splash-density PNGs alone weighed **~24 MB** in the AAB — enough to trigger
the warning by themselves. Two root causes:

1. `resizePng()` in `Concerns/InstallsAppIcon.php` calls `imagepng($resized, $dst, 0)` — compression level **0**, i.e. essentially uncompressed. A `1280×1920` splash lands at 9.4 MB instead of ~2 MB. Icons pay the same tax.
2. Android supports WebP natively at `aapt` level since API 14 (lossless: API 18). NativePHP's `minSdk` sits well above that in every real-world project. Emitting WebP shrinks the same splash to **~1 MB** losslessly — same visual output, ~90% smaller.

Together these are Google's #1 bitmap-optimization suggestion in Play Console.

## Changes

**Config** — `config/nativephp.php` gets a new opt-in toggle:

```php
'android' => [
    // ...
    'image_format' => env('NATIVEPHP_ANDROID_IMAGE_FORMAT', 'png'),
],
```

Default `'png'` keeps behavior identical for existing projects (zero BC break).
Set `'webp'` (or `NATIVEPHP_ANDROID_IMAGE_FORMAT=webp` in `.env`) to opt in.

**`Concerns/InstallsAppIcon.php`**
- Rename `resizePng()` → `resizeImage()`, add `$format` parameter (`'png'|'webp'`).
- `imagepng($dst, 0)` → `imagepng($dst, 9)` — max compression. **Applies to both PNG and WebP branches** and shrinks PNG icons/splashes by 60-70% on its own (this is arguably a bug fix that could stand alone).
- Add `imagewebp($dst, IMG_WEBP_LOSSLESS)` branch.
- New `readImage()` helper reads either PNG or WebP source (uses `imagecreatefromwebp` when available).
- New `androidImageFormat()` helper reads the config and gracefully falls back to `'png'` if PHP GD was compiled without WebP support (`imagewebp` not available).
- `installAndroidIcon()`: destination filename now derived from `$format`, and any stale opposite-extension file in the same folder is deleted first (avoids AAPT duplicate-resource errors on repeated builds when the toggle changes).

**`Concerns/InstallsAndroidSplashScreen.php`**
- Detect `public/splash.webp` / `public/splash-dark.webp` in addition to `.png` (WebP wins when both exist).
- Output filename becomes `splash.{png,webp}` following the config.
- Same stale-file cleanup before writing.
- `validateSplashImage()` accepts WebP sources too.

## Backwards compatibility

- **Default `'png'`** — no change for existing projects.
- Even when a project opts into `'webp'`, if the host's PHP GD was built without WebP support (`imagewebp` missing), `androidImageFormat()` transparently downgrades to `'png'` rather than crash the build.
- Renaming the private helper (`resizePng` → `resizeImage`) has no external surface — both traits/concerns are consumed together via `PreparesBuild`.

## Impact (real data from a shipping app)

Before (default settings today):
```
drawable-xxxhdpi/splash.png  9.4 MB
drawable-xxhdpi/splash.png   5.3 MB
drawable-xhdpi/splash.png    2.4 MB
drawable-hdpi/splash.png     1.3 MB
drawable-mdpi/splash.png     0.6 MB
                            ------
                            19.0 MB per theme × 2 themes = ~38 MB
```

After, `image_format=png` (just the compression fix):
```
Same set: ~10-12 MB per theme (60-70% smaller)
```

After, `image_format=webp` (lossless):
```
drawable-xxxhdpi/splash.webp  0.9 MB
drawable-xxhdpi/splash.webp   0.5 MB
drawable-xhdpi/splash.webp    0.3 MB
drawable-hdpi/splash.webp    0.15 MB
drawable-mdpi/splash.webp    0.07 MB
                            ------
                            ~1.9 MB per theme × 2 = ~3.8 MB
```

**~34 MB shaved off the AAB**, Play Console's bitmap warning cleared.

## Test plan

- [ ] Existing test suite (`InstallsAndroidSplashScreenTest`, `InstallsAndroidTest`) still passes with default config.
- [ ] Add coverage for `image_format=webp` code path.
- [ ] Add coverage for `.webp` source detection.
- [ ] Manual: build a sample app with `NATIVEPHP_ANDROID_IMAGE_FORMAT=webp`, unzip the AAB, verify `drawable-*/splash.webp` present and `drawable-*/splash.png` absent; run app, splash renders correctly.
- [ ] Manual: build the same app on a PHP install without WebP support in GD (rare) — should silently fall back to PNG output.

## Related

Play Console → App → Overview → "4 recommended actions" → "Improve your app's performance with bitmap image optimization".
