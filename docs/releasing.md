# Releasing a New Version

The plugin includes a GitHub-based auto-update system. Once installed, every WordPress site running the plugin will see new versions under **Dashboard → Updates** automatically — within 12 hours of you pushing a new tag.

## Quick reference

```bash
# 1. Bump the version in two places (must match)
#    - zymarg-product-builder/zymarg-product-builder.php  →  Version: header AND ZPB_VERSION constant
#    - zymarg-product-builder/readme.txt                  →  Stable tag + Changelog

# 2. Commit and push the version bump
git commit -am "Bump version to 0.9.0"
git push

# 3. Tag the commit and push the tag
git tag v0.9.0
git push origin v0.9.0
```

The included GitHub Action (`.github/workflows/release.yml`) takes over from there:

1. Verifies the plugin's `Version:` header matches the tag — fails the build with a clear error if not (this prevents accidental version mismatches)
2. Builds a clean `zymarg-product-builder.zip` containing only the plugin folder
3. Strips dev artifacts (`.git`, `.github`, `node_modules`, `.DS_Store`, etc.)
4. Creates / updates the GitHub Release with auto-generated release notes
5. Attaches the ZIP as a release asset

## What sites running the plugin see

- Within 12 hours, **Dashboard → Updates** shows "Zymarg Product Builder needs an update"
- The site owner clicks "Update Now" — WordPress downloads the attached ZIP from the GitHub Release, extracts it, and replaces the plugin folder
- All settings, attribute meta, and per-product overrides are preserved (we use post / option / term meta, not custom tables)

For instant checks, use the **"Check for Updates"** link on the Plugins screen row.

## Version rules

| Format | Notes |
|--------|-------|
| `vX.Y.Z` | Standard. Preferred. |
| `X.Y.Z` | Also accepted (the leading `v` is stripped). |
| `release-X.Y.Z` | Also accepted. |
| `vX.Y.Z-beta` | Pre-release suffix is honored by `version_compare`. Beta is older than the corresponding non-beta release. |
| `vX.Y` (no patch) | Accepted; treated as `X.Y.0`. |

The plugin only offers an update when `version_compare(remote_tag, installed, '>')` is true. Equal versions never trigger an update. Older versions never downgrade.

## Pre-releases and drafts

- **Drafts** on GitHub are excluded automatically (we use `/releases/latest` which only returns published releases).
- **Pre-releases** marked as such on GitHub are also excluded by `/releases/latest`. If you want to ship a beta, mark the release as "Latest" on GitHub.

## Manual rollback

If you need to roll back:
1. Delete or unpublish the bad GitHub release
2. The auto-updater immediately starts seeing the previous release as latest
3. The plugin **never auto-downgrades** — site owners need to manually re-install the older ZIP

## Troubleshooting a release

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Action fails: "Plugin header version does not match tag" | Forgot to bump `Version:` in `zymarg-product-builder.php` | Bump it, commit, push, tag again |
| Release created but no ZIP attached | The `softprops/action-gh-release` step failed | Check the Action logs |
| Sites don't see the update after 12+ hours | API rate-limited, or the release was published as a pre-release | Click "Check for Updates" on a test site, or unmark "Pre-release" on GitHub |
| Update downloads but install fails | Plugin folder name mismatch | The plugin's `rename_source` handler usually fixes this; check WP debug log |

## Configuring for a fork

If you fork this repo to your own account, override the GitHub owner / repo via constants in `wp-config.php`:

```php
define( 'ZPB_GITHUB_OWNER', 'YourGithubUsername' );
define( 'ZPB_GITHUB_REPO',  'your-repo-name' );
```

The auto-updater will poll your fork's releases instead.
