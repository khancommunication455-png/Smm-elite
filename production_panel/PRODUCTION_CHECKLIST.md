# Production Deployment Checklist

## Pre-Deployment
- [ ] Update .env with production values
- [ ] Run `php artisan env:validate` to check required variables
- [ ] Run `php artisan migrate --force`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Run `npm run build` for assets
- [ ] Set proper file permissions (storage/, bootstrap/cache/)
- [ ] Configure web server (Apache/Nginx) with SSL
- [ ] Set up Redis for cache, sessions, queues
- [ ] Configure cron jobs for scheduler
- [ ] Set up queue workers
- [ ] Test email sending
- [ ] Test payment integrations
- [ ] Run tests: `php artisan test`

## Security
- [ ] Change default database credentials
- [ ] Set APP_DEBUG=false
- [ ] Set APP_ENV=production
- [ ] Configure firewall rules
- [ ] Enable HTTPS only
- [ ] Set secure cookies
- [ ] Review admin user accounts

## Monitoring
- [ ] Set up log monitoring
- [ ] Configure error reporting (Sentry)
- [ ] Set up uptime monitoring
- [ ] Configure backups

## Post-Deployment
- [ ] Verify site loads correctly
- [ ] Test user registration/login
- [ ] Test order placement
- [ ] Test admin panel
- [ ] Check logs for errors
- [ ] Monitor queue processing