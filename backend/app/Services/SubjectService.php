<?php

namespace App\Services;

use App\Models\Subject;

class SubjectService
{
    /**
     * Get all subjects with pagination
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated subjects
     */
    public function getAllSubjects()
    {
        return Subject::paginate(config('application.pagination.per_page'));
    }

    /**
     * Find a subject by their UUID
     *
     * @param string $uuid The UUID of the subject
     * @return Subject|null The subject instance or null if not found
     */
    public function findByUuid(string $uuid): ?Subject
    {
        return Subject::where('uuid', $uuid)->first();
    }

    /**
     * Create a new subject record
     *
     * @param array $data Subject data including code, name, description, and credits
     * @return Subject The created subject instance
     * @throws \Exception If subject creation fails
     */
    public function createSubject(array $data): Subject
    {
        return Subject::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'credits' => $data['credits'] ?? config('application.subject.default_credits'),
        ]);
    }

    /**
     * Update an existing subject record
     *
     * @param Subject $subject The subject to update
     * @param array $data The data to update
     * @return Subject The updated subject instance with fresh data
     */
    public function updateSubject(Subject $subject, array $data): Subject
    {
        $subject->update($data);
        return $subject->fresh();
    }

    /**
     * Delete a subject record from the database
     *
     * @param Subject $subject The subject to delete
     * @return bool True if deletion was successful
     */
    public function deleteSubject(Subject $subject): bool
    {
        return $subject->delete();
    }

    /**
     * Get a subject with all related details loaded
     *
     * @param Subject $subject The subject instance
     * @return Subject The subject with enrollment relationships loaded
     */
    public function getSubjectWithDetails(Subject $subject): Subject
    {
        return $subject->load(['enrollments.student.user']);
    }
}