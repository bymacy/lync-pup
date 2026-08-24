<?php

namespace Tests\Unit;

use App\Support\ActiveAssessmentForms;
use Tests\TestCase;

class ActiveAssessmentFormsTest extends TestCase
{
    public function test_average_rating_skips_unrated_criteria(): void
    {
        $this->assertSame(4.0, ActiveAssessmentForms::averageRating([5, 3, null, '']));
    }

    public function test_average_rating_is_null_when_nothing_is_rated(): void
    {
        $this->assertNull(ActiveAssessmentForms::averageRating([null, null]));
        $this->assertNull(ActiveAssessmentForms::averageRating([]));
    }

    public function test_score_interpretation_matches_the_documented_scale(): void
    {
        $this->assertSame('Excellent', ActiveAssessmentForms::scoreInterpretation(5));
        $this->assertSame('Very Good', ActiveAssessmentForms::scoreInterpretation(4.5));
        $this->assertSame('Satisfactory', ActiveAssessmentForms::scoreInterpretation(3.0));
        $this->assertSame('Needs Improvement', ActiveAssessmentForms::scoreInterpretation(2.99));
        $this->assertSame('Poor', ActiveAssessmentForms::scoreInterpretation(1.0));
        $this->assertNull(ActiveAssessmentForms::scoreInterpretation(null));
    }

    public function test_all_six_rating_categories_are_defined(): void
    {
        $categories = ActiveAssessmentForms::document8RatingCategories();

        $this->assertCount(6, $categories);

        foreach ($categories as $key => $category) {
            $this->assertArrayHasKey('title', $category);
            $this->assertNotEmpty($category['criteria'], "$key should have at least one criterion");
        }
    }
}
