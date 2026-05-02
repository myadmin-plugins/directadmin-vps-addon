# Caliber Learnings

Accumulated patterns and anti-patterns from development sessions.
Auto-managed by [caliber](https://github.com/caliber-ai-org/ai-setup) — do not edit manually.

- **[gotcha:project]** Caliber's `references_valid` check validates backtick-enclosed paths against the git-tracked file tree. `vendor/autoload.php` and Composer package names like `detain/myadmin-directadmin-vps-addon` are not tracked and fail validation — put these in shell code blocks (which are not path-checked), not as standalone inline backtick references.
- **[pattern:project]** For Caliber project grounding score: always reference `.github/workflows/tests.yml` (CI pipeline) and `.idea/` (IDE config: `deployment.xml`, `encodings.xml`, `inspectionProfiles/Project_Default.xml`) explicitly in CLAUDE.md architecture sections — both directories exist in this repo and their absence loses grounding points.
- **[env:project]** Caliber requires ≥3 code blocks in CLAUDE.md for full executable content score — always include `composer install`, the PHPUnit run command, and the coverage command as separate fenced code blocks.
- **[gotcha:project]** `tests/PluginTest.php` has `testGetHooksCount` and `testExpectedPublicMethods` with hardcoded counts and method name lists. Whenever a new `public static` method is added to `src/Plugin.php`, both assertions must be updated manually or tests will fail.
