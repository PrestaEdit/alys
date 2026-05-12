#!/bin/bash
set -e

GRADLE="nativephp/android/app/build.gradle.kts"
CONFIG="config/nativephp.php"

CURRENT=$(grep "'version_code'" "$CONFIG" | grep -oE '[0-9]+')
NEW=$((CURRENT + 1))

sed -i '' "s/'version_code' => env('NATIVEPHP_APP_VERSION_CODE', $CURRENT)/'version_code' => env('NATIVEPHP_APP_VERSION_CODE', $NEW)/" "$CONFIG"
sed -i '' "s/versionCode = $CURRENT/versionCode = $NEW/" "$GRADLE"

echo "Version code bumped: $CURRENT → $NEW"
git add "$CONFIG" "$GRADLE"
git commit -m "chore: bump version code to $NEW"
