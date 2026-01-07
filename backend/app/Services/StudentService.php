<?php

namespace App\Services;

use App\Models\Student;

class StudentService
{
    /**
     * Get all students with pagination and related data
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated students with user and enrollment relationships
     */
    public function getAllStudents()
    {
        return Student::with('user', 'enrollments.subject')->paginate(config('application.pagination.per_page'));
    }

    /**
     * Find a student by their UUID
     *
     * @param string $uuid The UUID of the student
     * @return Student|null The student instance or null if not found
     */
    public function findByUuid(string $uuid): ?Student
    {
        return Student::where('uuid', $uuid)->first();
    }

    /**
     * Create a new student record
     *
     * @param array $data Student data including user_id and student_id
     * @return Student The created student instance
     * @throws \Exception If student creation fails
     */
    public function createStudent(array $data): Student
    {
        return Student::create([
            'user_id' => $data['user_id'],
            'student_id' => $data['student_id'],
        ]);
    }

    /**
     * Update an existing student record
     *
     * @param Student $student The student to update
     * @param array $data The data to update
     * @return Student The updated student instance with fresh relationships
     */
    public function updateStudent(Student $student, array $data): Student
    {
        $student->update($data);
        return $student->fresh(['user', 'enrollments.subject']);
    }

    /**
     * Delete a student record from the database
     *
     * @param Student $student The student to delete
     * @return bool True if deletion was successful
     */
    public function deleteStudent(Student $student): bool
    {
        return $student->delete();
    }

    /**
     * Get a student with all related details loaded
     *
     * @param Student $student The student instance
     * @return Student The student with user and enrollment relationships loaded
     */
    public function getStudentWithDetails(Student $student): Student
    {
        return $student->load(['user', 'enrollments.subject']);
    }
}