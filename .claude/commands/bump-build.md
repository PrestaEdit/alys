Increment the Alys app build number before a new Play Store release.

Run the script: `bash scripts/bump-build.sh`

This updates both:
- `config/build.php` → `'number'` (shown in app settings)
- `nativephp/android/app/build.gradle.kts` → `versionCode` (used by Play Store)

Then confirm the new build number and remind the user to rebuild the APK.
