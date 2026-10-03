<?php
namespace local_sectionicons;

use advanced_testcase;
use local_sectionicons\local\image_validator;

/** Upload validation tests for custom section images. */
final class image_validator_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_safe_svg_is_normalised(): void {
        $result = image_validator::sanitise(
            'safe.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="30" viewBox="0 0 20 30">'
                . '<circle cx="10" cy="10" r="8" fill="currentColor"/></svg>'
        );

        $this->assertSame('image/svg+xml', $result['mimetype']);
        $this->assertSame('svg', $result['extension']);
        $this->assertStringNotContainsString('width="20"', $result['content']);
        $this->assertStringNotContainsString('height="30"', $result['content']);
        $this->assertStringContainsString('viewBox="0 0 20 30"', $result['content']);
    }

    public function test_active_svg_content_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        image_validator::sanitise(
            'unsafe.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><script>alert(1)</script></svg>'
        );
    }

    public function test_external_svg_reference_is_removed(): void {
        $result = image_validator::sanitise(
            'reference.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10">'
                . '<use href="https://example.invalid/icon.svg#item"/></svg>'
        );

        $this->assertStringNotContainsString('https://', $result['content']);
        $this->assertStringNotContainsString('href=', $result['content']);
    }

    public function test_unsupported_file_type_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        image_validator::sanitise('icon.gif', 'not an image');
    }
}
