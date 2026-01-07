<?php

namespace App\Services;

use App\Exceptions\EnrollmentAlreadyExistsException;
use App\Exceptions\UserNotStudentException;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subject;

class EnrollmentService
{
    /**
     * Get all enrollments with pagination and related data
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated enrollments with student and subject relationships
     */
    public function getAllEnrollments()
    {
        return Enrollment::with('student.user', 'subject')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Get enrollments for a specific user (admin sees all, students see their own)
     *
     * @param \App\Models\User $user The user requesting enrollments
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection Paginated enrollments or empty collection
     */
    public function getEnrollmentsForUser(\App\Models\User $user)
    {
        if ($user->isAdmin()) {
            return $this->getAllEnrollments();
        }

        $student = $user->student;
        if (!$student) {
            return collect();
        }

        return $student->enrollments()->with('subject')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Find an enrollment by its UUID
     *
     * @param string $uuid The UUID of the enrollment
     * @return Enrollment|null The enrollment instance or null if not found
     */
    public function findByUuid(string $uuid): ?Enrollment
    {
        return Enrollment::where('uuid', $uuid)->first();
    }

    /**
     * Enroll a student in a specific subject
     *
     * @param Student $student The student to enroll
     * @param Subject $subject The subject to enroll in
     * @return Enrollment The created enrollment instance
     * @throws \Exception If student is already enrolled in the subject
     */
    public function enrollStudentInSubject(Student $student, Subject $subject): Enrollment
    {
        // Check if already enrolled
        $existing = Enrollment::where('student_id', $student->id)
            ->where('subject_id', $subject->id)
            ->first();

        if ($existing) {
            throw new EnrollmentAlreadyExistsException();
        }

        return Enrollment::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
        ]);
    }

    /**
     * Update an existing enrollment record
     *
     * @param Enrollment $enrollment The enrollment to update
     * @param array $data The data to update
     * @return Enrollment The updated enrollment instance with fresh relationships
     */
    public function updateEnrollment(Enrollment $enrollment, array $data): Enrollment
    {
        $enrollment->update($data);
        return $enrollment->fresh(['student.user', 'subject']);
    }

    /**
     * Delete an enrollment record from the database
     *
     * @param Enrollment $enrollment The enrollment to delete
     * @return bool True if deletion was successful
     */
    public function deleteEnrollment(Enrollment $enrollment): bool
    {
        return $enrollment->delete();
    }

    /**
     * Get an enrollment with all related details loaded
     *
     * @param Enrollment $enrollment The enrollment instance
     * @return Enrollment The enrollment with student and subject relationships loaded
     */
    public function getEnrollmentWithDetails(Enrollment $enrollment): Enrollment
    {
        return $enrollment->load(['student.user', 'subject']);
    }

    /**
     * Enroll a user (who must be a student) in a subject by subject ID
     *
     * @param \App\Models\User $user The user to enroll (must be a student)
     * @param int $subjectId The ID of the subject to enroll in
     * @return Enrollment The created enrollment instance
     * @throws \Exception If user is not a student or subject not found
     */
    public function enrollStudent(\App\Models\User $user, int $subjectId): Enrollment
    {
        $student = $user->student;
        if (!$student) {
            throw new UserNotStudentException();
        }

        $subject = Subject::findOrFail($subjectId);

        return $this->enrollStudentInSubject($student, $subject);
    }
}