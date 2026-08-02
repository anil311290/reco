# Hostinger UAT And Production Deployment

This project can be deployed to Hostinger shared hosting without exposing the full Laravel application inside `public_html`.

There are two branch-specific deployment targets:

- `uat` branch -> `https://betatesting.sahrudaya.online`
- `main` branch -> `https://reco.sahrudaya.online`

## Target paths

### UAT

- Branch: `uat`
- App code path: `/home/u787932101/domains/sahrudaya.online/reco_betatesting_app`
- Web root path: `/home/u787932101/domains/sahrudaya.online/public_html/betatesting`
- Site URL: `https://betatesting.sahrudaya.online`

### Production

- Branch: `main`
- App code path: `/home/u787932101/domains/sahrudaya.online/reco_app`
- Web root path: `/home/u787932101/domains/reco.sahrudaya.online/public_html`
- Site URL: `https://reco.sahrudaya.online`

Each web root stays public-facing, while the full Laravel app is stored outside or alongside that folder in a separate app path.

## GitHub repository

Push this codebase to the deployment repo:

```bash
git remote add reco_web_dev https://github.com/sahrudayamys-png/reco_web_dev.git
git push reco_web_dev main
```

If the remote already exists:

```bash
git remote set-url reco_web_dev https://github.com/sahrudayamys-png/reco_web_dev.git
git push reco_web_dev main
```

## GitHub Actions configuration

Add these under `reco_web_dev > Settings > Secrets and variables > Actions`:

Repository Variables:

- `UAT_HOSTINGER_HOST` = `217.21.87.106`
- `UAT_HOSTINGER_PORT` = `65002`
- `UAT_HOSTINGER_USER` = `u787932101`
- `UAT_HOSTINGER_APP_PATH` = `/home/u787932101/domains/sahrudaya.online/reco_betatesting_app`
- `UAT_HOSTINGER_PUBLIC_PATH` = `/home/u787932101/domains/sahrudaya.online/public_html/betatesting`
- `PROD_HOSTINGER_HOST` = `217.21.87.106`
- `PROD_HOSTINGER_PORT` = `65002`
- `PROD_HOSTINGER_USER` = `u787932101`
- `PROD_HOSTINGER_APP_PATH` = `/home/u787932101/domains/sahrudaya.online/reco_app`
- `PROD_HOSTINGER_PUBLIC_PATH` = `/home/u787932101/domains/reco.sahrudaya.online/public_html`

Repository Secret:

- `HOSTINGER_SSH_KEY` = private SSH deploy key

## First-time server preparation

Connect to the server:

```bash
ssh -p 65002 u787932101@217.21.87.106
```

Create the app directories:

```bash
mkdir -p /home/u787932101/domains/sahrudaya.online/reco_betatesting_app
mkdir -p /home/u787932101/domains/sahrudaya.online/public_html/betatesting
mkdir -p /home/u787932101/domains/sahrudaya.online/reco_app
mkdir -p /home/u787932101/domains/reco.sahrudaya.online/public_html
```

After the first GitHub deployment, SSH again and configure Laravel:

```bash
cd /home/u787932101/domains/sahrudaya.online/reco_betatesting_app
php -v
composer --version
cp .env.example .env
php artisan key:generate
```

Update `.env` for the target environment:

- `APP_ENV=production`
- `APP_DEBUG=false`
- UAT: `APP_URL=https://betatesting.sahrudaya.online`
- UAT: `ASSET_URL=https://betatesting.sahrudaya.online`
- Production: `APP_URL=https://reco.sahrudaya.online`
- Production: `ASSET_URL=https://reco.sahrudaya.online`
- Production database credentials
- Mail credentials
- Queue/cache/session drivers as needed

Then run:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## SSH deploy key setup

Generate a dedicated deploy key on your local machine:

```bash
ssh-keygen -t ed25519 -C "github-actions-hostinger" -f ~/.ssh/reco_web_dev_hostinger
```

Copy the public key:

```bash
cat ~/.ssh/reco_web_dev_hostinger.pub
```

Append it on the server:

```bash
mkdir -p ~/.ssh
chmod 700 ~/.ssh
nano ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

Paste the public key into `authorized_keys`.

Copy the private key into GitHub secret `HOSTINGER_SSH_KEY`:

```bash
cat ~/.ssh/reco_web_dev_hostinger
```

## Deployment workflow

Workflow files:

- `.github/workflows/deploy-hostinger-betatesting.yml`
- `.github/workflows/deploy-hostinger-production.yml`

What they do:

1. `uat` branch deploys to `betatesting.sahrudaya.online`.
2. `main` branch deploys to `reco.sahrudaya.online`.
3. Each workflow installs Composer and Node dependencies.
4. Each workflow builds Vite assets.
5. Each workflow uploads a release archive to Hostinger.
6. Each workflow extracts the app into its app directory.
7. Each workflow copies Laravel `public` contents into the target web root.
8. Each workflow rewrites `index.php` to point to the real Laravel app path.
9. Each workflow recreates the `storage` symlink.
10. Each workflow runs `php artisan migrate --force` and caches config/routes/views.

## First deployment checklist

1. Push the repo to `reco_web_dev`.
2. Add the UAT and production GitHub Variables and the `HOSTINGER_SSH_KEY` secret.
3. Ensure the SSH key works.
4. Push to `uat` for UAT or `main` for production, or trigger the matching workflow manually.
5. Create and verify `.env` on the server.
6. Visit the target domain.

## Notes

- Do not store `.env` in Git.
- User uploads should remain under `storage/app/public` in the app path.
- The workflow preserves the `.env` file because it is excluded from the deployment archive.
- If Hostinger blocks symlink creation for `storage`, replace the symlink with a copied directory and use a custom upload strategy.