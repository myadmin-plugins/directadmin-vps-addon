---
name: phpunit-source-test
description: Writes PHPUnit 9 source-reading tests that verify PHP file structure without executing code. Use when user says 'write tests', 'add test for', or adds/modifies files in src/. Tests use file_get_contents() + assertStringContainsString/assertMatchesRegularExpression for procedural files, and ReflectionClass for class files. Do NOT use for integration tests, mock-based tests, or tests that exercise runtime behavior.
---
# PHPUnit Source-Reading Tests

## Critical

- Never instantiate classes or call functions from `src/` directly — they depend on heavy MyAdmin globals (`function_requirements`, DB, constants). Use `file_get_contents()` or `ReflectionClass` only.
- Test namespace must be `Detain\MyAdminVpsDirectadmin\Tests` — matches `tests/` directory in `composer.json`.
- Bootstrap is configured via `phpunit.xml.dist` — do not add require statements.
- Run tests with: `vendor/bin/phpunit tests/ -v`

## Instructions

1. **Create the test file** in `tests/` named after the class or file under test (e.g., `tests/PluginTest.php`). Use `tests/VpsAddDirectadminTest.php` as the template for procedural files; use `tests/PluginTest.php` for class files.

2. **Set up the class** with the correct namespace, import, and class declaration:
   ```php
   <?php
   namespace Detain\MyAdminVpsDirectadmin\Tests;
   use PHPUnit\Framework\TestCase;
   // For class files also add:
   use ReflectionClass;
   use ReflectionMethod;
   
   class MyFileTest extends TestCase
   ```
   Verify the namespace matches `tests/` autoload entry in `composer.json` before proceeding.

3. **For procedural `src/*.php` files** — add a `setUp()` that loads the source into `$this->source`:
   ```php
   private string $filePath;
   private string $source;
   
   protected function setUp(): void
   {
       $this->filePath = dirname(__DIR__) . '/src/my_function.php';
       $this->assertFileExists($this->filePath);
       $this->source = file_get_contents($this->filePath);
   }
   ```
   Then write test methods using:
   - `$this->assertStringStartsWith('<?php', $this->source)` — PHP open tag
   - `$this->assertMatchesRegularExpression('/function\s+my_function\s*\(/', $this->source)` — function declaration
   - `$this->assertStringContainsString("function_requirements('class.Foo')", $this->source)` — required calls
   - `$this->assertStringNotContainsString('namespace ', $trimmed)` — no namespace in procedural files (skip comment lines with `str_starts_with($trimmed, '*')` etc.)

4. **For class files** — use `ReflectionClass` in `setUp()`:
   ```php
   private ReflectionClass $ref;
   
   protected function setUp(): void
   {
       $this->ref = new ReflectionClass(Plugin::class);
   }
   ```
   Cover: `isInstantiable()`, `isStatic()`/`isPublic()` on methods, `getParameters()` count and names, static property values (`Plugin::$module`), `getHooks()` array keys and callable shapes `[ClassName::class, 'methodName']`.
   For source-level checks on class files: `file_get_contents($this->ref->getFileName())`.

5. **Group tests with comment separators** matching existing style:
   ```php
   // ------------------------------------------------------------------
   //  Function declaration
   // ------------------------------------------------------------------
   ```
   Standard groups: File existence and structure · Function/class declaration · Internal calls · Docblock and metadata · File-level characteristics.

6. **Every test method** must be `public function testXxx(): void` with a one-line docblock describing what it asserts. Verify all assertions pass by running: `vendor/bin/phpunit tests/PluginTest.php -v`

## Examples

**User says:** "Write tests for the new `src/vps_add_cpanel.php` procedural file."

**Actions taken:**
1. Read `src/vps_add_cpanel.php` to identify: function name, `function_requirements()` calls, method chains, constants used.
2. Create `tests/VpsAddCpanelTest.php` with `setUp()` loading the file into `$this->source`.
3. Add test methods: `testFileExists`, `testFileStartsWithPhpTag`, `testFileHasNoNamespace`, `testDeclaresFunctionSignature`, `testRequiresAddServiceAddon`, `testCallsLoadMethod`, `testCallsProcessMethod`, `testFileHasDocblock`.

**Result:**
```php
protected function setUp(): void
{
    $this->filePath = dirname(__DIR__) . '/src/vps_add_cpanel.php';
    $this->assertFileExists($this->filePath);
    $this->source = file_get_contents($this->filePath);
}

public function testDeclaresVpsAddCpanelFunction(): void
{
    $this->assertMatchesRegularExpression(
        '/function\s+vps_add_cpanel\s*\(\s*\)/',
        $this->source
    );
}

public function testRequiresAddServiceAddon(): void
{
    $this->assertStringContainsString("function_requirements('class.AddServiceAddon')", $this->source);
}
```

## Common Issues

- **`Class 'Detain\MyAdminVpsDirectadmin\Plugin' not found`**: `composer install` has not been run, or autoload is stale. Run `composer dump-autoload`.
- **`assertMatchesRegularExpression` not found**: You are using PHPUnit < 9. This project uses PHPUnit 9 — check `composer.json` requires `phpunit/phpunit ^9`.
- **`file_get_contents(): Failed to open stream`**: Path built with `dirname(__DIR__)` is wrong. Confirm test file lives in `tests/` (one level below project root) so `dirname(__DIR__)` resolves to the repo root.
- **Test passes locally but CI fails with undefined constant `VPS_DA_COST`**: You called the actual function. Source-reading tests must only use `file_get_contents()` / `assertStringContainsString` — never `include` or `require` the `src/` file under test.
- **`str_starts_with` undefined**: Requires PHP 8.0+. If running PHP 7.4, replace with `strncmp($trimmed, '*', 1) === 0`.
