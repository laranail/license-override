# Contributing

Thanks for your interest in contributing to `laranail/demo-mode`.

## Getting started

```bash
git clone https://github.com/laranail/demo-mode
cd demo-mode
composer install
composer test
```

## Conventions

- PHP `^8.4 || ^8.5`, Laravel `^13`.
- `declare(strict_types=1);` in every file; `final` classes where applicable;
  explicit return/param types; early returns; curly braces on all control flow.
- Artisan commands follow the laranail shape `laranail::demo-mode.<command>`
  (with a `demo:*` alias) and extend the base `Commands\Command`.
- Prefer PHPDoc over inline comments; add array-shape PHPDoc where useful.
- Check sibling files and the existing patterns before introducing a new one.

## Quality gates

```bash
composer test       # Pest (Unit + Feature)
composer analyse    # PHPStan (level 5)
composer format     # Pint
composer rector     # Rector (dry-run)
```

All must be green. New behaviour needs a Pest test that proves it — do not add
tinker/verification scripts.

## Pull requests

- Subject ≤ 72 chars, imperative mood; the body explains *why*.
- Update `CHANGELOG.md` under `Unreleased`.
- No AI-assistant attribution in commits or PRs.
