#!/usr/bin/env bash
# Convert Android splash PNGs (written by NativePHP's InstallsAndroidSplashScreen
# trait) to lossless WebP and drop identical day/night duplicates. Run this
# after every `php artisan native:build android`, until nativephp/mobile ships
# native WebP output.
#
# Idempotent: safe to re-run.

set -euo pipefail

RES_DIR="$(cd "$(dirname "$0")/.." && pwd)/nativephp/android/app/src/main/res"

if [ ! -d "$RES_DIR" ]; then
    echo "error: $RES_DIR not found" >&2
    exit 1
fi

if ! command -v cwebp >/dev/null 2>&1; then
    echo "error: cwebp not installed (brew install webp)" >&2
    exit 1
fi

cd "$RES_DIR"

total_before=0
total_after=0
converted=0
dropped_night=0

# Convert every drawable-*/splash.png (day + night) to lossless WebP.
for png in drawable-*/splash.png; do
    [ -f "$png" ] || continue
    before=$(stat -f%z "$png" 2>/dev/null || stat -c%s "$png")
    webp="${png%.png}.webp"
    cwebp -lossless -q 100 -m 6 -quiet "$png" -o "$webp"
    after=$(stat -f%z "$webp" 2>/dev/null || stat -c%s "$webp")
    rm "$png"
    total_before=$((total_before + before))
    total_after=$((total_after + after))
    converted=$((converted + 1))
done

# Drop drawable-night-*/splash.webp when byte-identical to non-night; Android
# falls back through the qualifier chain, so the dark variant is a no-op.
for d in mdpi hdpi xhdpi xxhdpi xxxhdpi; do
    day="drawable-$d/splash.webp"
    night="drawable-night-$d/splash.webp"
    if [ -f "$day" ] && [ -f "$night" ] && cmp -s "$day" "$night"; then
        rm "$night"
        rmdir "drawable-night-$d" 2>/dev/null || true
        dropped_night=$((dropped_night + 1))
    fi
done

if [ "$converted" -gt 0 ]; then
    mb_before=$(awk "BEGIN{printf \"%.1f\", $total_before/1024/1024}")
    mb_after=$(awk "BEGIN{printf \"%.1f\", $total_after/1024/1024}")
    echo "✓ optimized $converted splash PNG(s): ${mb_before} MB → ${mb_after} MB"
else
    echo "✓ no splash PNGs to convert (already WebP)"
fi

if [ "$dropped_night" -gt 0 ]; then
    echo "✓ dropped $dropped_night duplicate drawable-night-* dir(s)"
fi
