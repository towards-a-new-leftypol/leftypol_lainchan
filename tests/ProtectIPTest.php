<?php
use PHPUnit\Framework\TestCase;

ob_start();
define('TINYBOARD', true);
require_once "inc/mod/pages.php";
ob_end_clean();

// This is probably best done with property testing library, but let's
// wait add another dependency

final class ProtectIPTest extends TestCase
{
    public function testProtectsIpv4Address(){
        $expected = 'Some ban message: xxxx';

        // Random IP, hope it's not yours
        $input = 'Some ban message: <a href="?/IP/33.57.252.246">33.57.252.246</a>';
        $output = protect_ip($input);
        $this->assertEquals($output, $expected);
    }

    public function testProtectsIpv6Address(){
        $expected = 'Some ban message: xxxx';

        // Random IP, hope it's not yours
        $input = 'Some ban message: <a href="?/IP/5e85:f252:9baf:2131:8984:6ab2:3db0:fa48">5e85:f252:9baf:2131:8984:6ab2:3db0:fa48</a>';
        $output = protect_ip($input);

        $this->assertEquals($output, $expected);
    }
}
