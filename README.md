# Allo! Pizza MVP

Уеб система за онлайн поръчки на пицария — Laravel + Filament + Livewire + MySQL.

## Работна директория

```
~/Projects/pizzeria-orders
```

Всичка разработка се прави тук. Копие на външния диск (`SK/pizzeria-orders`) е само backup — не се работи директно оттам.

## Инсталация

### С Docker (препоръчително)

```bash
cd ~/Projects/pizzeria-orders
docker compose up --build
```

Първото стартиране отнема няколко минути (composer, migrate, npm build).

| URL | Адрес |
|-----|-------|
| Сайт | http://localhost:8000 |
| Админ | http://localhost:8000/admin/user/login |

**Админ:** `admin@pizzeria.local` / `password`

Спиране: `docker compose down`

Повторна инициализация (migrate + seed отново):
```bash
docker compose down -v
rm -f .docker/initialized
docker compose up --build
```

Полезни команди:
```bash
docker compose exec app php artisan test
docker compose exec app php artisan migrate:fresh --seed
docker compose logs -f app
```

MySQL е достъпна на `localhost:3307` (user: `pizzeria`, pass: `secret`).

### Без Docker (локално)

```bash
cd ~/Projects/pizzeria-orders
./setup.sh
```

## Технологии

- Laravel 11, Filament 3, Livewire 3, Breeze, Sanctum, Tailwind, MySQL

## Достъп

| Роля | URL | Данни |
|------|-----|-------|
| Админ | `/admin/user/login` | `admin@pizzeria.local` / `password` |
| Клиент | `/user/login` | Регистрация през `/register` |

## API

```
GET  /api/categories
GET  /api/products
GET  /api/products/{id}
GET  /api/banners
GET  /api/lunch-menu
GET  /api/settings
POST /api/orders
GET  /api/orders/{id}
```

## Тестове

```bash
php artisan test
```

## Deploy към cPanel shared hosting

GitHub repo:

```bash
https://github.com/skaradzhov-git/allo-pizza.git
```

### Първо качване

1. В cPanel създайте MySQL database и database user.
2. В Terminal клонирайте проекта извън `public_html`, например:

```bash
cd ~
git clone https://github.com/skaradzhov-git/allo-pizza.git allo-pizza
cd allo-pizza
cp .env.example .env
```

3. Настройте `.env` за production:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpanel_database_name
DB_USERNAME=cpanel_database_user
DB_PASSWORD=cpanel_database_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
```

4. Генерирайте application key и deploy-нете:

```bash
php artisan key:generate
bash scripts/cpanel-deploy.sh
```

`artisan` автоматично пренасочва към PHP 8.2+ на cPanel, така че `php artisan migrate` работи дори когато default `php` е стара версия.

5. Насочете domain document root към папката `public` на проекта. Ако cPanel не позволява document root извън `public_html`, използвайте поддомейн/addon domain с root към:

```bash
/home/CPANEL_USER/allo-pizza/public
```

### Автоматичен deploy при `git pull`

На hosting-а инсталирайте Git hook-а еднократно:

```bash
cd ~/allo-pizza
bash scripts/install-cpanel-git-pull-deploy-hook.sh
```

След това всеки успешен `git pull` автоматично ще изпълнява `scripts/cpanel-deploy.sh`.

### Ръчен deploy след push към GitHub

```bash
cd ~/allo-pizza
git pull
bash scripts/cpanel-deploy.sh
```

Ако използвате cPanel Git Version Control, `.cpanel.yml` ще изпълни същия deploy script при cPanel deploy.

### Важно за realtime известията

Admin popup-ът за нова поръчка използва Laravel Reverb/WebSockets. На shared hosting това работи само ако hosting-ът позволява постоянно работещ процес, например:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Ако cPanel не позволява long-running процеси, realtime popup-ът трябва да се смени към polling fallback за production shared hosting.

## Backup на външен диск (по избор)

```bash
rsync -a --delete \
  --exclude 'vendor' --exclude 'node_modules' --exclude '.env' \
  ~/Projects/pizzeria-orders/ \
  ~/.mounty/Expansion/SK/pizzeria-orders/
```
