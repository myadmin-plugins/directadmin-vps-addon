<?php

namespace Detain\MyAdminVpsDirectadmin\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the vps_add_directadmin.php procedural file.
 *
 * Because this file defines a global function that depends on heavy
 * external services (AddServiceAddon, function_requirements, VPS_DA_COST),
 * we use static analysis via file_get_contents to verify the file structure
 * and expected behaviour without executing the function.
 */
class VpsAddDirectadminTest extends TestCase
{
    /**
     * @var string Absolute path to the source file.
     */
    private string $filePath;

    /**
     * @var string Contents of the source file.
     */
    private string $source;

    protected function setUp(): void
    {
        $this->filePath = dirname(__DIR__) . '/src/vps_add_directadmin.php';
        $this->assertFileExists($this->filePath);
        $this->source = file_get_contents($this->filePath);
    }

    // ------------------------------------------------------------------
    //  File existence and structure
    // ------------------------------------------------------------------

    /**
     * The source file must exist on disk.
     */
    public function testFileExists(): void
    {
        $this->assertFileExists($this->filePath);
    }

    /**
     * The file must start with a proper PHP opening tag.
     */
    public function testFileStartsWithPhpTag(): void
    {
        $this->assertStringStartsWith('<?php', $this->source);
    }

    /**
     * The file must not declare a namespace (it is procedural).
     */
    public function testFileHasNoNamespace(): void
    {
        // Match a namespace declaration that is NOT inside a comment
        $lines = explode("\n", $this->source);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '*') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*')) {
                continue;
            }
            $this->assertStringNotContainsString('namespace ', $trimmed);
        }
    }

    // ------------------------------------------------------------------
    //  Function declaration
    // ------------------------------------------------------------------

    /**
     * The file must declare the vps_add_directadmin function.
     */
    public function testDeclaresVpsAddDirectadminFunction(): void
    {
        $this->assertMatchesRegularExpression(
            '/function\s+vps_add_directadmin\s*\(/',
            $this->source
        );
    }

    /**
     * The function must accept zero parameters.
     */
    public function testFunctionHasNoParameters(): void
    {
        $this->assertMatchesRegularExpression(
            '/function\s+vps_add_directadmin\s*\(\s*\)/',
            $this->source
        );
    }

    // ------------------------------------------------------------------
    //  Internal calls
    // ------------------------------------------------------------------

    /**
     * The function must load the AddServiceAddon class via function_requirements.
     */
    public function testRequiresAddServiceAddon(): void
    {
        $this->assertStringContainsString("function_requirements('class.AddServiceAddon')", $this->source);
    }

    /**
     * The function must instantiate AddServiceAddon.
     */
    public function testInstantiatesAddServiceAddon(): void
    {
        $this->assertStringContainsString('new AddServiceAddon()', $this->source);
    }

    /**
     * The function must call the load method with expected arguments.
     */
    public function testCallsLoadMethod(): void
    {
        $this->assertStringContainsString('->load(', $this->source);
        $this->assertStringContainsString('__FUNCTION__', $this->source);
        $this->assertStringContainsString("'DirectAdmin'", $this->source);
        $this->assertStringContainsString("'vps'", $this->source);
        $this->assertStringContainsString('VPS_DA_COST', $this->source);
        $this->assertStringContainsString("'da'", $this->source);
    }

    /**
     * The function must call the process method.
     */
    public function testCallsProcessMethod(): void
    {
        $this->assertStringContainsString('->process()', $this->source);
    }

    // ------------------------------------------------------------------
    //  Docblock and metadata
    // ------------------------------------------------------------------

    /**
     * The file must contain a docblock header.
     */
    public function testFileHasDocblock(): void
    {
        $this->assertStringContainsString('/**', $this->source);
        $this->assertStringContainsString('*/', $this->source);
    }

    /**
     * The file docblock must reference the author.
     */
    public function testDocblockContainsAuthor(): void
    {
        $this->assertStringContainsString('@author', $this->source);
    }

    /**
     * The file docblock must specify the package.
     */
    public function testDocblockContainsPackage(): void
    {
        $this->assertStringContainsString('@package', $this->source);
    }

    /**
     * The function docblock must specify return type void.
     */
    public function testFunctionDocblockSpecifiesReturnVoid(): void
    {
        $this->assertStringContainsString('@return void', $this->source);
    }

    /**
     * The function docblock must describe its purpose.
     */
    public function testFunctionDocblockDescribesDirectAdmin(): void
    {
        $this->assertStringContainsString('DirectAdmin', $this->source);
    }

    // ------------------------------------------------------------------
    //  File-level characteristics
    // ------------------------------------------------------------------

    /**
     * The file must be reasonably small (procedural helper).
     */
    public function testFileIsReasonablySmall(): void
    {
        $lineCount = substr_count($this->source, "\n") + 1;
        $this->assertLessThan(100, $lineCount);
    }

    /**
     * The file must not contain any class declarations.
     */
    public function testFileContainsNoClassDeclaration(): void
    {
        $lines = explode("\n", $this->source);
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '*') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/*')) {
                continue;
            }
            $this->assertDoesNotMatchRegularExpression(
                '/^\s*class\s+/',
                $trimmed
            );
        }
    }
}
