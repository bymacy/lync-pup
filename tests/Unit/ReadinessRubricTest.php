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

    public function test_score_is_the_highest_level_with_every_criterion_checked(): void
    {
        $progress = [
            1 => [true, true, true],
            2 => [true, true, true],
            3 => [true, false, true], // not fully checked — should cap the score at 2
        ];

        $this->assertSame(2, ReadinessRubric::scoreFromProgress('TRL', $progress));
    }

    public function test_a_gap_does_not_prevent_a_higher_fully_checked_level_from_counting(): void
    {
        // Level 2 left incomplete, but level 3 is fully checked anyway —
        // the score reflects the highest fully-met level, not a
        // contiguous streak from level 1.
        $progress = [
            1 => [true, true, true],
            2 => [false, false, false],
            3 => [true, true, true],
        ];

        $this->assertSame(3, ReadinessRubric::scoreFromProgress('TRL', $progress));
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
