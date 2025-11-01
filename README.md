[![CI](https://github.com/N3XT0R/dashclip-delivery/actions/workflows/ci.yml/badge.svg)](https://github.com/N3XT0R/dashclip-delivery/actions/workflows/ci.yml)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/dashclip-delivery/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/dashclip-delivery)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/dashclip-delivery/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/dashclip-delivery)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=N3XT0R_dashclip-delivery&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=N3XT0R_dashclip-delivery)
[![Latest Stable Version](https://poser.pugx.org/n3xt0r/dashclip-delivery/v/stable)](https://packagist.org/packages/n3xt0r/dashclip-delivery)
[![Latest Unstable Version](https://poser.pugx.org/n3xt0r/dashclip-delivery/v/unstable)](https://packagist.org/packages/n3xt0r/dashclip-delivery)
![License](https://img.shields.io/badge/license-AGPL--3.0%20%2F%20Commercial-blue)

# Dashclip-Delivery

## Project Description

Dashclip-Delivery is a Laravel application for distributing video material to various channels. New videos are
ingested from an upload directory or from Dropbox, stored on a configured storage, and then fairly distributed
to channels based on quotas and weighting. Channels receive signed links via email to an offer page,
where they can download individual videos or a ZIP file with an accompanying `info.csv`. Unneeded videos
can be returned, and all downloads are logged.

## Features

- **Ingest**: recursive scanning of an upload folder (local or Dropbox) with deduplication via SHA-256.
- **Distribution**: assignment of new or expired videos to channels (weighted round-robin, weekly quota).
- **Notification**: sending emails with temporary download links and offer pages.
- **Offer & Download**: web interface for selecting and ZIP-downloading selected videos including `info.csv` and
  tracking of pickups.
- **Previews**: generation of short MP4 clips using `ffmpeg`.
- **Dropbox Integration**: OAuth connection and automatic token refresh.

## Prerequisites

- PHP 8.4
- Composer
- Node.js & npm (for building assets)
- ffmpeg
- A Laravel-supported database (e.g., SQLite)
- Optional: Dropbox app with Client ID and Secret

## Installation

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
```

## Useful Commands

| Command                             | Description                                                                            |
|-------------------------------------|----------------------------------------------------------------------------------------|
| `php artisan ingest:unzip`          | Extracts ZIP files from a directory.                                                   |
| `php artisan ingest:scan`           | Scans the upload folder and stores new videos.                                         |
| `php artisan info:import`           | Imports clip information from an `info.csv`.                                           |
| `php artisan assign:distribute`     | Distributes videos to channels.                                                        |
| `php artisan notify:offers`         | Sends offer links via email.                                                           |
| `php artisan notify:reminders`      | Notifies channels about pending offers before expiration.                              |
| `php artisan assign:expire`         | Marks expired assignments and temporarily blocks channels.                             |
| `php artisan dropbox:refresh-token` | Refreshes the Dropbox token.                                                           |
| `php artisan weekly:run`            | Executes Expire → Distribute → Notify in sequence.                                     |
| `php artisan video:cleanup`         | Deletes downloaded videos whose expiration has exceeded the specified number of weeks. |

## Documentation

Detailed explanations of structure and usage can be found in the [`docs`](docs) directory:

- [Overview](docs/README.md)
- [Setup](docs/setup.md)
- [Tools](docs/tool.md)
- [Workflow](docs/workflow.md)
- [Deployment](docs/deployment.md)

## Tests

```bash
composer test
```

## License

This project is dual-licensed:

- **[AGPL-3.0-or-later](LICENSE)** - Free for open source use
- **[Commercial License](LICENSE-COMMERCIAL.md)** - For proprietary use

See the respective license files for details.

**Contact for commercial licensing:** info@php-dev.info

## Copyright

Copyright (c) 2025 Ilya Beliaev
This project includes code licensed under AGPL-3.0. Commercial licensing available separately.
