# IMMORA Togo - Railway.app Deployment Guide

## 1. Prerequisites
- Railway account (create at https://railway.app)
- GitHub repository (already created: https://github.com/Achlat/immora-togo)
- MySQL database

## 2. Environment Variables

Set these in Railway Dashboard after connecting GitHub:

```
APP_NAME=IMMORA
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:8Ej3L8N9q1wP2xC4vB5mK6nH7zD0eF9gH2jI4kL5mN6oP7qR8sT9uV

DB_CONNECTION=mysql
DB_HOST=<railway-mysql-host>
DB_PORT=3306
DB_DATABASE=immora
DB_USERNAME=immora
DB_PASSWORD=<secure-password>

CACHE_DRIVER=file
SESSION_DRIVER=cookie
QUEUE_CONNECTION=sync

APP_TIMEZONE=Africa/Lome
HASH_DRIVER=argon2id
SESSION_SECURE_COOKIE=true
IMMORA_DEFAULT_COUNTRY=TG

MAIL_MAILER=log
IMMORA_SMS_DRIVER=log
IMMORA_PAYMENT_DRIVER=fake
```

## 3. Deployment Steps

1. **Connect GitHub to Railway**
   - Go to https://railway.app/dashboard
   - Click "New Project" → "Deploy from GitHub repo"
   - Select `Achlat/immora-togo`
   - Choose `master` branch

2. **Add MySQL Database**
   - In Railway, click "+ New" → "Database" → "MySQL"
   - Railway auto-populates connection variables

3. **Set Environment Variables**
   - Paste all vars from section 2 above
   - Replace `<railway-mysql-host>` with actual Railway MySQL host
   - Generate secure APP_KEY: `php artisan key:generate --show`

4. **Deploy**
   - Railway auto-deploys when you push to GitHub
   - Monitor at https://railway.app/dashboard

5. **Run Initial Migrations**
   - After first deploy, SSH into Railway container
   - Run: `php artisan migrate --seed`
   - Run: `php artisan demo:create-togo` (creates Kofi demo account)

## Demo Credentials

**Admin Account (created via artisan command):**
- Email: `kofi@prestige-immo.tg`
- Password: `Togo-Demo-2026`
- Agency: Prestige Immobilière Togo

**Demo Staff (created via DemoSeeder):**
- Email: `mawusi@prestige-immo.tg` (Manager)
- Email: `kwesi@prestige-immo.tg` (Agent)
- Password: (set from factory defaults)

## Health Checks

After deployment:
- ✅ Visit deployment URL (provided by Railway)
- ✅ Login with `kofi@prestige-immo.tg` / `Togo-Demo-2026`
- ✅ Verify agency is "Prestige Immobilière Togo"
- ✅ Check that country is TG (Togo)
- ✅ Verify timezone is Africa/Lome

## Troubleshooting

**502 Bad Gateway**: Check PHP configuration or database connectivity
**500 Internal Server**: Run `php artisan config:cache` in Railway container
**Migration issues**: Verify DB credentials and run migrations again

---

**Repository**: https://github.com/Achlat/immora-togo
**Live URL**: Will be provided by Railway after deployment
