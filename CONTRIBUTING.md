# Contributing to QuickDrop

We love your input! We want to make contributing to QuickDrop as easy and transparent as possible, whether it's:

- Reporting a bug
- Discussing the current state of the code
- Submitting a fix
- Proposing new features
- Becoming a maintainer

### Pull Requests

1. Fork the repo and create your branch from `main` or `next`.
2. If you've added code that should be tested, add tests.
3. Ensure the test suite passes.
4. Make sure your code follows the existing style.
5. Issue that pull request!

### Branching Strategy

- `main`: Production-ready code. All bug fixes should target this branch.
- `next`: Next release development. All new features should target this branch.

### Commit Messages

We use [Semantic Release](https://semantic-release.gitbook.io/semantic-release/) for versioning. This means your commit messages should follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

Types:
- `feat`: New feature (triggers minor version bump)
- `fix`: Bug fix (triggers patch version bump)
- `docs`: Documentation only changes
- `style`: Changes that do not affect the meaning of the code
- `refactor`: Code change that neither fixes a bug nor adds a feature
- `perf`: Code change that improves performance
- `test`: Adding missing tests
- `chore`: Changes to the build process or auxiliary tools

Breaking Changes:
- Add `BREAKING CHANGE:` in the commit footer to trigger a major version bump

Examples:
```
feat(upload): add support for large file uploads

fix(auth): resolve token expiration issue

feat(api)!: rename endpoint parameters
BREAKING CHANGE: API endpoint parameters have been renamed for consistency
```

### Versioning

The project follows [Semantic Versioning](https://semver.org/):
- Major version (`X.0.0`): Breaking changes
- Minor version (`0.X.0`): New features
- Patch version (`0.0.X`): Bug fixes

Prerelease versions (from the `next` branch) will have a suffix like `-next.1`.

### Docker Images

Docker images are automatically built and published to DockerHub:
- `quickdrop:latest`: Latest stable release (from `main`)
- `quickdrop:next`: Latest prerelease (from `next`)
- `quickdrop:X.Y.Z`: Specific version tags

## Development Setup

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Copy `.env.example` to `.env` and configure
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Run migrations:
   ```bash
   php artisan migrate
   ```
6. Start development servers:
   ```bash
   ./vendor/bin/sail up -d
   npm run dev
   ```

## License

By contributing, you agree that your contributions will be licensed under the same license as the project. 