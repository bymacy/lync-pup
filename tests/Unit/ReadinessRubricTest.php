<?php

namespace Tests\Unit;

use App\Support\ReadinessRubric;
use Tests\TestCase;

class ReadinessRubricTest extends TestCase
{
    public function test_every_type_has_exactly_nine_levels(): void
    {
        foreach (ReadinessRubric::TYPES as $type) {
            $this->assertCount(9, ReadinessRubric::levels($type), "$type should have 9 levels");
        }
    }

    public function test_score_is_null_when_nothing_is_checked(): void
    {
        $this->assertNull(ReadinessRubric::scoreFromProgress('TRL', null));
        $this->assertNull(ReadinessRubric::scoreFromProgress('TRL', []));
    }

    public function test_score_is_the_weighted_sum_of_checked_criteria_per_level(): void
    {
        $progress = [
            1 => [true, true, true],  // 3/3 = 1.0
            2 => [true, true, true],  // 3/3 = 1.0
            3 => [true, false, true], // 2/3 ≈ 0.667 — a partial level contributes its
                                      // fraction, not a flat 0 or a rounded-up full point.
        ];

        $this->assertSame(2.7, ReadinessRubric::scoreFromProgress('TRL', $progress));
    }

    public function test_an_untouched_level_contributes_nothing_even_between_checked_levels(): void
    {
        // Level 2 left entirely unchecked, but level 1 and level 3 are each
        // fully checked — every level's fraction is summed independently,
        // so a gap doesn't zero out or cap what the surrounding levels
        // already contribute.
        $progress = [
            1 => [true, true, true],
            2 => [false, false, false],
            3 => [true, true, true],
        ];

        $this->assertSame(2.0, ReadinessRubric::scoreFromProgress('TRL', $progress));
    }

    public function test_srl_levels_include_a_target_description(): void
    {
        foreach (ReadinessRubric::levels('SRL') as $level => $definition) {
            $this->assertArrayHasKey('target', $definition, "SRL level $level should have a target");
        }
    }

    public function test_meta_defaults_to_pre_assessment_form_numbers(): void
    {
        $meta = ReadinessRubric::meta();

        $this->assertSame('PUP-TBIDO FORM No.002', $meta['TRL']['form_no']);
        $this->assertSame('PUP-TBIDO FORM No.003', $meta['MRL']['form_no']);
        $this->assertSame('PUP-TBIDO FORM No.004', $meta['TMRL']['form_no']);
        $this->assertSame('PUP-TBIDO FORM No.005', $meta['SRL']['form_no']);
    }

    public function test_meta_uses_sequential_form_numbers_for_post_assessment(): void
    {
        $meta = ReadinessRubric::meta('Post-Assessment');

        $this->assertSame('PUP-TBIDO FORM No.009', $meta['TRL']['form_no']);
        $this->assertSame('PUP-TBIDO FORM No.010', $meta['MRL']['form_no']);
        $this->assertSame('PUP-TBIDO FORM No.011', $meta['TMRL']['form_no']);
        $this->assertSame('PUP-TBIDO FORM No.012', $meta['SRL']['form_no']);
    }
}
