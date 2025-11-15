# Setup der Anwendung

Diese Anleitung beschreibt die Installation der Anwendung und die Einrichtung der notwendigen Dienste.

## Voraussetzungen

- nginx oder anderen Webserver
- PHP-fpm ≥ 8.4 und Composer
- Node.js & npm
- Datenbank (z. B. MySQL oder SQLite)
- Git

## Optional

- libapache2-mod-xsendfile

```bash
XSendFile On
XSendFilePath /var/www/<domain>/htdocs/current/public/storage/previews
```

## Laravel installieren

- Repository klonen und ins Projektverzeichnis wechseln:
   ```bash
   git clone <REPO_URL>
   cd dashclip-delivery
   ```
- Abhängigkeiten installieren:
   ```bash
   composer install
   npm install
   ```
- Beispieldatei kopieren und Anwendungsschlüssel generieren:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

- Datenbank/Redis und etc konfigurieren:
   ```bash
   nano .env
   ```

- Datenbank konfigurieren und Migrationen ausführen:
   ```bash
   php artisan migrate
   ```
- Assets kompilieren:
   ```bash
   npm run build
   ```
- Filament-Benutzer erstellen:
   ```bash
   php artisan make:filament-user
   ```

## Reverb einrichten

1. Paket installieren (falls noch nicht vorhanden):
   ```bash
   composer require laravel/reverb
   php artisan reverb:install
   ```
2. In der `.env` die Broadcast-Einstellungen setzen:
   ```
   BROADCAST_DRIVER=reverb
   REVERB_APP_ID=app-id
   REVERB_APP_KEY=app-key
   REVERB_APP_SECRET=app-secret
   REVERB_HOST=localhost
   REVERB_PORT=8080
   ```
3. Reverb als systemd-Service einrichten. Beispielkonfiguration in `/etc/systemd/system/laravel-reverb.service`:
   ```ini
   [Unit]
   Description=Laravel Reverb Server
   After=network.target

   [Service]
   Type=simple
   User=www-data
   WorkingDirectory=/var/www/dashclip
   ExecStart=/usr/bin/php artisan reverb:start
   Restart=on-failure

   [Install]
   WantedBy=multi-user.target
   ```
   Service aktivieren und starten:
   ```bash
   sudo systemctl enable --now reverb.service
   ```

## Webserver konfigurieren

### Nginx

```nginx
server {
    if ($host = <domain>) {
        return 301 https://$host$request_uri;
    } # managed by Certbot


    listen 80;
    listen [::]:80;
    server_name dashclip-delivery.net;

    access_log /var/www/<domain>/logs/access.log;
    error_log  /var/www/<domain>/logs/error.log warn;

    # Redirect to HTTPS
    include /etc/nginx/snippets/letsencrypt-global.conf;
    location / {
       return 301 https://$host$request_uri;
    }


}


server {
    listen 443 ssl;
    listen [::]:443 ssl;
    listen 443 quic;
    http2 on;
    add_header Alt-Svc 'h3=":443"; ma=86400';

    server_name <domain>;

    root /var/www/<domain>/htdocs/current/public;
    index index.php index.html;
    client_max_body_size 1G;

    access_log /var/www/<domain>/logs/access.log;
    error_log  /var/www/<domain>/logs/error.log warn;
    
    # only if ssl is enabled
    ssl_certificate /etc/letsencrypt/live/<domain>/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/<domain>/privkey.pem; # managed by Certbot
    include             /etc/letsencrypt/options-ssl-nginx.conf;

    # ===========================
    # PHP-FPM (ber Socket)
    # ===========================
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
    }

    # ===========================
    # XSendFile-quivalent (X-Accel-Redirect)
    # ===========================
    # Nur innerhalb /storage/previews erlaubt
    location /storage/previews/ {
        internal;
        alias /var/www/<domain>/htdocs/current/public/storage/previews/;
    }

    # ===========================
    # MP4-Range-Support (chunked playback)
    # ===========================
    location ~ \.mp4$ {
        mp4;
        add_header Accept-Ranges bytes;
    }

    # ===========================
    # Reverb WebSocket Proxy
    # ===========================
    location /reverb/ {
        proxy_pass http://127.0.0.1:8080/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;

        # Optional (empfohlen):
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_read_timeout 600s;
        proxy_send_timeout 600s;
    }

    # Serve static assets directly; don't pass them to PHP
    location ~* \.(?:ico|gif|jpe?g|png|svg|webp|mp4)$ {
        expires 7d;
        access_log off;
        try_files $uri =404;
    }

    # ===========================
    # Static file handling / Rewrite
    # ===========================
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # ===========================
    # Sicherheit: Versteckte Dateien blocken
    # ===========================
    location ~ /\. {
        deny all;
    }

}
```

## Crontab

Crontab einrichten:

```bash
* * * * * cd /var/www/dashclip/ && php artisan schedule:run >> /dev/null 2>&1
```

## Supervisor für Queue-Worker

Für das dauerhafte Ausführen von `queue:work` kann Supervisor verwendet werden.
Beispielkonfiguration in `/etc/supervisor/conf.d/laravel-worker.conf`:

```ini
[program:laravel-worker]
process_name = %(program_name)s_%(process_num)02d
command = php /var/www/dashclip/artisan queue:work --sleep=3 --tries=3
autostart = true
autorestart = true
user = www-data
numprocs = 4
redirect_stderr = true
stdout_logfile = /var/log/supervisor/laravel-worker.log
stopwaitsecs = 3600
```

Supervisor neu laden und den Worker starten:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

## Seeder

Datenbank mit Rechten und Rollen befüllen:

```bash 
php artisan db:seed
```