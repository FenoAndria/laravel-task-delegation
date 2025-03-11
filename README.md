# Delego

**Assisted delegation for teams.** Missions flow down from the organization to team leads and members; progress flows back up.

Delego is the rewrite of *Team Flow* (2024, kept under the [`v1-2024`](../../tree/v1-2024) tag).

> Work in progress — the rewrite is being built feature by feature. Progress reports live in [`docs/cr`](docs/cr).

## Stack

- **Backend**: PHP 8.3+, Laravel 12
- **Frontend**: Inertia 2, Vue 3, TypeScript, Tailwind CSS, shadcn-vue
- **Quality**: Pest, Larastan, Pint, ESLint, Prettier, GitHub Actions

## Getting started

```bash
git clone https://github.com/FenoAndria/team-flow.git delego
cd delego

composer install
npm install

cp .env.example .env
php artisan key:generate
php artisan migrate

composer run dev
```

The app is served at `http://localhost:8000`.

## Quality

```bash
composer test      # Pest test suite
composer analyse   # Larastan static analysis
composer lint      # Pint code style
npm run lint:check # ESLint
npm run format:check
```

## License

MIT
