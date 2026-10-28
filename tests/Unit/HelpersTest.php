<?php

namespace Tests\Unit;

use App\Support\Availability;
use Tests\TestCase;

class HelpersTest extends TestCase
{
    public function test_irish_phone_numbers_format_and_dial(): void
    {
        $this->assertSame('+353 91 381 111', format_phone('+35391381111'));
        $this->assertSame('+353 91 381 111', format_phone('091 381111'));
        $this->assertSame('tel:+35391381111', tel_href('(091) 38 11 11'));
        $this->assertSame('+353 87 123 4567', format_phone('087 123 4567'));
        $this->assertSame('+353 1 234 5678', format_phone('01 234 5678'));
    }

    public function test_eircodes_are_normalised(): void
    {
        $this->assertSame('H91 X2Y3', format_eircode(' h91x2y3 '));
        $this->assertSame('D6W 1234', format_eircode('d6w1234'));
    }

    public function test_availability_summary(): void
    {
        $this->assertSame('Not given', Availability::summary([]));
        $this->assertSame('Any shift, any day', Availability::summary(Availability::keys()));
        $this->assertSame(['mon-early', 'sun-night'], Availability::clean(['sun-night', 'junk', 'mon-early', 7]));
        $this->assertStringContainsString('Night', Availability::summary(['fri-night', 'sat-night']));
    }
}
