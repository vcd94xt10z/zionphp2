<?php
namespace Tests;

use PHPUnit\Framework\TestCase;
use \zion\utils\TextFormatter;

/**
 * Teste de mascaras
 */
class MascarasTest extends TestCase {
    public function testeCPF(){
        $this->assertEquals("415.716.080-01",TextFormatter::formatCPF("41571608001"));
    }

    public function testeCNPJ(){
        $this->assertEquals("25.645.915/0001-85",TextFormatter::formatCNPJ("25645915000185"));
    }
}
?>