<?php
/**
 * Security Helper Tests
 */

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase {
    
    public function testHashPassword() {
        $password = 'testpassword123';
        $hash = Security::hashPassword($password);
        
        $this->assertNotEmpty($hash);
        $this->assertTrue(Security::verifyPassword($password, $hash));
        $this->assertFalse(Security::verifyPassword('wrongpassword', $hash));
    }
    
    public function testSanitizeString() {
        $input = '  <script>alert("xss")</script>  ';
        $output = Security::sanitizeString($input);
        
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertEquals(trim($input), $output);
    }
    
    public function testValidateEmail() {
        $this->assertTrue(Security::validateEmail('test@example.com'));
        $this->assertFalse(Security::validateEmail('invalid-email'));
        $this->assertFalse(Security::validateEmail(''));
    }
    
    public function testGenerateSecureId() {
        $id1 = Security::generateSecureId();
        $id2 = Security::generateSecureId();
        
        $this->assertNotEquals($id1, $id2);
        $this->assertEquals(32, strlen($id1));
    }
}