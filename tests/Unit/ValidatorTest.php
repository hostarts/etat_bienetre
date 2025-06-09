<?php
/**
 * Validator Tests
 */

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase {
    
    private $validator;
    
    protected function setUp(): void {
        $this->validator = new Validator();
    }
    
    public function testRequiredValidation() {
        $data = ['name' => 'John Doe'];
        $rules = ['name' => 'required'];
        
        $result = $this->validator->validate($data, $rules);
        $this->assertTrue($result['valid']);
        
        $data = ['name' => ''];
        $result = $this->validator->validate($data, $rules);
        $this->assertFalse($result['valid']);
    }
    
    public function testEmailValidation() {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];
        
        $result = $this->validator->validate($data, $rules);
        $this->assertTrue($result['valid']);
        
        $data = ['email' => 'invalid-email'];
        $result = $this->validator->validate($data, $rules);
        $this->assertFalse($result['valid']);
    }
    
    public function testNumericValidation() {
        $data = ['amount' => '123.45'];
        $rules = ['amount' => 'numeric'];
        
        $result = $this->validator->validate($data, $rules);
        $this->assertTrue($result['valid']);
        
        $data = ['amount' => 'not-a-number'];
        $result = $this->validator->validate($data, $rules);
        $this->assertFalse($result['valid']);
    }
}