<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseOfferingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseOfferingRequest>
 */
class CourseOfferingRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => User::ROLE_STUDENT]),
            'course_id' => fn () => Course::create([
                'code' => fake()->unique()->bothify('REQ #####'),
                'name' => 'Requested course',
                'credits' => 3,
            ])->id,
            'session' => '2026/2027',
            'semester' => '1',
            'status' => CourseOfferingRequest::PENDING_ADVISOR,
            'reason' => 'This course is needed to complete my study plan.',
        ];
    }
}
