#!/bin/bash
set -e

GRADLE="nativephp/android/app/build.gradle.kts"
CONFIG="config/build.php"

CURRENT=$(grep "'number'" "$CONFIG" | grep -oE '[0-9]+')
NEW=$((CURRENT + 1))

sed -i '' "s/'number' => $CURRENT/'number' => $NEW/" "$CONFIG"
sed -i '' "s/versionCode = $CURRENT/versionCode = $NEW/" "$GRADLE"

echo "Build bumped: $CURRENT → $NEW"
git add "$CONFIG" "$GRADLE"
git commit -m "chore: bump build to $NEW"
