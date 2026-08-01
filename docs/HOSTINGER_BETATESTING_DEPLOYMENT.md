# Hostinger Betatesting Deployment

This project can be deployed to Hostinger shared hosting without exposing the full Laravel application inside `public_html`.

## Target paths

- App code path: `/home/u787932101/domains/sahrudaya.online/reco_betatesting_app`
- Web root path: `/home/u787932101/domains/sahrudaya.online/public_html/betatesting`
- Site URL: `https://sahrudaya.online/betatesting`

The web root stays inside `public_html/betatesting`, but the full Laravel app is stored outside that folder.

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

- `HOSTINGER_HOST` = `217.21.87.106`
- `HOSTINGER_PORT` = `65002`
- `HOSTINGER_USER` = `u787932101`
- `HOSTINGER_APP_PATH` = `/home/u787932101/domains/sahrudaya.online/reco_betatesting_app`
- `HOSTINGER_PUBLIC_PATH` = `/home/u787932101/domains/sahrudaya.online/public_html/betatesting`

Repository Secret:

- `HOSTINGER_SSH_KEY` = private SSH deploy key

## First-time server preparation

Connect to the server:

```bash
ssh -p 65002 u787932101@217.21.87.106
```

Create the app directory:

```bash
mkdir -p /home/u787932101/domains/sahrudaya.online/reco_betatesting_app
mkdir -p /home/u787932101/domains/sahrudaya.online/public_html/betatesting
```

After the first GitHub deployment, SSH again and configure Laravel:

```bash
cd /home/u787932101/domains/sahrudaya.online/reco_betatesting_app
php -v
composer --version
cp .env.example .env
php artisan key:generate
```

Update `.env` for production:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://sahrudaya.online/betatesting`
- `ASSET_URL=https://sahrudaya.online/betatesting`
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

Workflow file:

- `.github/workflows/deploy-hostinger-betatesting.yml`

What it does on every push to `main`:

1. Installs Composer dependencies.
2. Installs Node dependencies.
3. Builds Vite assets.
4. Uploads a release archive to Hostinger.
5. Extracts the app into the app directory.
6. Copies Laravel `public` contents into `public_html/betatesting`.
7. Rewrites `index.php` to point to the real Laravel app path.
8. Recreates the `storage` symlink.
9. Runs `php artisan migrate --force` and caches config/routes/views.

## First deployment checklist

1. Push the repo to `reco_web_dev`.
2. Add the GitHub Variables and the `HOSTINGER_SSH_KEY` secret.
3. Ensure the SSH key works.
4. Trigger the GitHub Actions workflow.
5. Create and verify `.env` on the server.
6. Visit `https://sahrudaya.online/betatesting`.

## Notes

- Do not store `.env` in Git.
- User uploads should remain under `storage/app/public` in the app path.
- The workflow preserves the `.env` file because it is excluded from the deployment archive.
- If Hostinger blocks symlink creation for `storage`, replace the symlink with a copied directory and use a custom upload strategy.