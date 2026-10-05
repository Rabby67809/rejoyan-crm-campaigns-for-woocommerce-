# Rejoyan CRM & Campaigns for WooCommerce

Original supplied plugin source, version 1.0.0. Customer CRM, reusable offers, smart segments, queued/scheduled marketing campaigns, reports, invoices and social product sharing.

## Requirements

WordPress 6.5+, PHP 7.4+, WooCommerce 8.2+. Consult readme.txt for plugin documentation. Compatibility claims require validation on your own test matrix.

## Upload and publishing

বাংলা ধাপে ধাপে নির্দেশিকা: [docs/UPLOAD-GUIDE-BN.md](docs/UPLOAD-GUIDE-BN.md).

Static inspection and limitations: [docs/SOURCE-REVIEW.md](docs/SOURCE-REVIEW.md).

Upload the contents of this folder to the GitHub repository root. WordPress.org releases must separately be committed to its SVN repository. GitHub upload alone does not publish a WordPress.org release.

Plugin source bytes are unchanged from the supplied ZIP. README, docs and Git settings are packaging additions, not plugin runtime changes. For WordPress installation, use the original installable ZIP. Exclude README.md, docs and Git settings when preparing an SVN release.

## License

GPL-2.0-or-later. See LICENSE.txt.

## GitHub release publishing

[বাংলা GitHub → WordPress.org setup](docs/GITHUB-PUBLISH-BN.md). Configure repository Secrets SVN_USERNAME and SVN_PASSWORD, then publish a non-prerelease GitHub release tagged 1.0.0. The workflow commits to WordPress.org SVN. No publication has been performed while preparing this package.
