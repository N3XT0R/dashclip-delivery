[![CI](https://github.com/N3XT0R/dashclip-delivery/actions/workflows/core-branches.yml/badge.svg)](https://github.com/N3XT0R/dashclip-delivery/actions/workflows/ci.yml)
[![Maintainability](https://qlty.sh/gh/N3XT0R/projects/dashclip-delivery/maintainability.svg)](https://qlty.sh/gh/N3XT0R/projects/dashclip-delivery)
[![Code Coverage](https://qlty.sh/gh/N3XT0R/projects/dashclip-delivery/coverage.svg)](https://qlty.sh/gh/N3XT0R/projects/dashclip-delivery)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=N3XT0R_dashclip-delivery&metric=security_rating)](https://sonarcloud.io/summary/new_code?id=N3XT0R_dashclip-delivery)
[![Latest Stable Version](https://poser.pugx.org/n3xt0r/dashclip-delivery/v/stable)](https://packagist.org/packages/n3xt0r/dashclip-delivery)
[![Latest Unstable Version](https://poser.pugx.org/n3xt0r/dashclip-delivery/v/unstable)](https://packagist.org/packages/n3xt0r/dashclip-delivery)
![License](https://img.shields.io/badge/license-AGPL--3.0%20%2F%20Commercial-blue)

# Dashclip-Delivery

## Project Description

**Dashclip-Delivery** is a neutral, multi-channel submission and distribution system for dashcam and other
user-generated video content.  
It is designed for people who regularly submit the same videos to multiple YouTube channels and need a reliable,
automated workflow for managing, tracking, and distributing their content.

Instead of acting as an upload portal for a single channel, Dashclip-Delivery provides an independent,
automated delivery pipeline that serves both submitters and channel operators:

- Videos are ingested from a local folder or Dropbox and automatically deduplicated.
- All clips are processed, previewed, and stored on a configurable storage backend.
- Content is then **fairly distributed** to participating channels based on quotas, weighting, and availability.
- Channels receive signed, time-limited offer links and can download clips individually or as ZIP bundles.
- All downloads, returns, expirations, and interactions are logged for full transparency.

Dashclip-Delivery is ideal for **multi-channel submitters**, **YouTube channel operators**, and anyone who needs a
structured, automated, and privacy-conscious distribution workflow.  
A public API is planned to enable external automations and custom integrations.

---

## Features

- **Ingest & Deduplication**  
  Recursive scanning (local or Dropbox) with SHA-256 hashing to prevent duplicate video entries.

- **Fair Distribution Engine**  
  Weighted round-robin assignment with optional weekly quotas to ensure balanced distribution across all channels.

- **Offer & Notification System**  
  Automatic emails with signed offer links, reminder notifications, and detailed logging of all mail events.

- **Offer Page & Downloads**  
  Channels can preview clips, download individual videos, or generate ZIP packages including a metadata `info.csv`.

- **Preview Generation**  
  Automatic MP4 preview creation via FFmpeg with configurable compression and scaling.

- **Dropbox Integration**  
  OAuth-based linking with automatic token refresh and support for large uploads.

- **Return Workflow**  
  Channels can decline or return unwanted clips; the system logs all decisions and redistributes clips if necessary.

- **Full Audit Logging**  
  Every action—from ingest to distribution, downloads, returns, and mail events—is recorded for complete transparency.

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

This project is tested with BrowserStack.

Local unit and feature tests:

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
