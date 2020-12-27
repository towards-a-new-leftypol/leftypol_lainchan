<?php
use PHPUnit\Framework\TestCase;

ob_start();
require_once "banners.php";
ob_end_clean();

final class bannersTest extends TestCase
{
    public function testBanners(){

        $expected = '<a href="banners/lain-bottom.png"><img src="banners/lain-bottom.png" alt="lain-bottom.png" style="width:348px;height:128px"></a> ';

        //capture input
        ob_start();
            listBannersInDir("banners");
            $output = ob_get_contents();
        ob_end_clean();
        //end input

        //assertion
        $this->assertEquals($output,$expected);
    }
}
