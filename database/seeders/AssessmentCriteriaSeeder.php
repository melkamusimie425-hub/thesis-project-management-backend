<?php

namespace Database\Seeders;

use App\Models\AssessmentCriteria;
use Illuminate\Database\Seeder;

/**
 * Seeds the assessment criteria derived from the school's evaluation forms:
 *  - Industrial Project I (Presentation 30% + Documentation 70%)
 *  - Industrial Project II (Presentation 20% + Documentation 35% + Implementation 45%)
 *  - Thesis examination (Examiner 70% + Advisor 30%)
 *  - Proposal review
 */
class AssessmentCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            // ---- Industrial Project I ----
            ['name' => 'Oral presentation and defense', 'category' => 'Presentation', 'applies_to' => 'ip_one', 'weight' => 30],
            ['name' => 'Formatting & Referencing', 'category' => 'Documentation', 'applies_to' => 'ip_one', 'weight' => 5],
            ['name' => 'Language', 'category' => 'Documentation', 'applies_to' => 'ip_one', 'weight' => 5],
            ['name' => 'Document organization and Content', 'category' => 'Documentation', 'applies_to' => 'ip_one', 'weight' => 30],
            ['name' => 'Analysis Modeling', 'category' => 'Documentation', 'applies_to' => 'ip_one', 'weight' => 30],

            // ---- Industrial Project II ----
            ['name' => 'Oral presentation and defense', 'category' => 'Presentation', 'applies_to' => 'ip_two', 'weight' => 20],
            ['name' => 'Formatting & Referencing', 'category' => 'Documentation', 'applies_to' => 'ip_two', 'weight' => 5],
            ['name' => 'Language', 'category' => 'Documentation', 'applies_to' => 'ip_two', 'weight' => 5],
            ['name' => 'Document organization and Content', 'category' => 'Documentation', 'applies_to' => 'ip_two', 'weight' => 10],
            ['name' => 'Design Modeling', 'category' => 'Documentation', 'applies_to' => 'ip_two', 'weight' => 15],
            ['name' => 'Interface layouts / neatness', 'category' => 'Implementation', 'applies_to' => 'ip_two', 'weight' => 15],
            ['name' => 'Features addressed', 'category' => 'Implementation', 'applies_to' => 'ip_two', 'weight' => 15],
            ['name' => 'Coding style', 'category' => 'Implementation', 'applies_to' => 'ip_two', 'weight' => 5],
            ['name' => 'Testing procedures', 'category' => 'Implementation', 'applies_to' => 'ip_two', 'weight' => 5],
            ['name' => 'Other implementation issues', 'category' => 'Implementation', 'applies_to' => 'ip_two', 'weight' => 5],

            // ---- Proposal review ----
            ['name' => 'Research content', 'category' => 'Examiner', 'applies_to' => 'proposal', 'weight' => 25],
            ['name' => 'Research Methodology', 'category' => 'Examiner', 'applies_to' => 'proposal', 'weight' => 25],
            ['name' => 'Reporting of findings', 'category' => 'Examiner', 'applies_to' => 'proposal', 'weight' => 15],
            ['name' => 'Defense presentation', 'category' => 'Examiner', 'applies_to' => 'proposal', 'weight' => 20],
            ['name' => 'Proposal Organization', 'category' => 'Examiner', 'applies_to' => 'proposal', 'weight' => 15],

            // ---- Thesis examination (Examiner 70%) ----
            ['name' => 'Research content', 'category' => 'Examiner', 'applies_to' => 'thesis', 'weight' => 20],
            ['name' => 'Research Methodology', 'category' => 'Examiner', 'applies_to' => 'thesis', 'weight' => 20],
            ['name' => 'Reporting of findings', 'category' => 'Examiner', 'applies_to' => 'thesis', 'weight' => 15],
            ['name' => 'Defense presentation', 'category' => 'Examiner', 'applies_to' => 'thesis', 'weight' => 10],
            ['name' => 'Thesis Organization', 'category' => 'Examiner', 'applies_to' => 'thesis', 'weight' => 5],

            // ---- Thesis examination (Advisor 30%) ----
            ['name' => 'Research content (Advisor)', 'category' => 'Advisor', 'applies_to' => 'thesis', 'weight' => 10],
            ['name' => 'Research Methodology (Advisor)', 'category' => 'Advisor', 'applies_to' => 'thesis', 'weight' => 10],
            ['name' => 'Reporting of findings (Advisor)', 'category' => 'Advisor', 'applies_to' => 'thesis', 'weight' => 5],
            ['name' => 'Defense presentation (Advisor)', 'category' => 'Advisor', 'applies_to' => 'thesis', 'weight' => 3],
            ['name' => 'Thesis Organization (Advisor)', 'category' => 'Advisor', 'applies_to' => 'thesis', 'weight' => 2],
        ];

        foreach ($criteria as $item) {
            AssessmentCriteria::firstOrCreate(
                ['name' => $item['name'], 'applies_to' => $item['applies_to']],
                array_merge($item, ['is_active' => true])
            );
        }
    }
}
