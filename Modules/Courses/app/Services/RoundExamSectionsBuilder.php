<?php

namespace Modules\Courses\Services;

use Modules\Courses\Models\RoundExamSectionModel;

class RoundExamSectionsBuilder
{
    /**
     * Group a round's whole-round exams under their sections.
     *
     * $pairs is a list of ['assignment' => AssignExamModel, 'item' => <exam payload>]; the payload
     * is returned untouched, so each endpoint keeps its own exam format. Exams inside a section follow
     * the assignment sort_number. Exams with no section (or a section that no longer exists) are put
     * in a trailing group with id null. With $skipEmpty, sections that have no exams are left out.
     */
    public function build(int $roundId, iterable $pairs, bool $skipEmpty = false): array
    {
        $sections = RoundExamSectionModel::where('round_id', $roundId)
            ->orderBy('sort_number')
            ->orderBy('id')
            ->get();

        $knownIds = $sections->pluck('id')->flip();

        $grouped = collect($pairs)
            ->sortBy(fn ($pair) => [(int) $pair['assignment']->sort_number, (int) $pair['assignment']->id])
            ->groupBy(function ($pair) use ($knownIds) {
                $sectionId = $pair['assignment']->round_exam_section_id;
                return $sectionId !== null && isset($knownIds[$sectionId]) ? (string) $sectionId : 'none';
            });

        $result = [];
        foreach ($sections as $section) {
            $exams = ($grouped[(string) $section->id] ?? collect())->pluck('item')->values()->all();
            if ($skipEmpty && empty($exams)) {
                continue;
            }

            $result[] = [
                'id' => $section->id,
                'title' => $section->title,
                'description' => $section->description,
                'sort_number' => $section->sort_number,
                'exams' => $exams,
            ];
        }

        if (isset($grouped['none'])) {
            $result[] = [
                'id' => null,
                'title' => null,
                'description' => null,
                'sort_number' => null,
                'exams' => $grouped['none']->pluck('item')->values()->all(),
            ];
        }

        return $result;
    }
}
