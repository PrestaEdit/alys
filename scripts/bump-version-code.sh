#!/bin/bash
set -e

CONFIG="config/nativephp.php"

CURRENT=$(grep "'version_code'" "$CONFIG" | grep -oE '[0-9]+')
NEW=$((CURRENT + 1))

sed -i '' "s/'version_code' => env('NATIVEPHP_APP_VERSION_CODE', $CURRENT)/'version_code' => env('NATIVEPHP_APP_VERSION_CODE', $NEW)/" "$CONFIG"

echo "Version code bumped: $CURRENT → $NEW"
git add "$CONFIG"
git commit -m "chore: bump version code to $NEW"
