<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\Department;
use App\Models\MqfSc;
use App\Models\Offering;
use App\Models\Program;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vbe;
use Illuminate\Http\JsonResponse;

/**
 * Emulates the Firestore read surface the original SPA consumed:
 *   artifacts/{appId}/public/data/{collection}
 *
 * Every returned payload is wrapped with the collection name and the
 * resolved application id so existing client code keeps working.
 */
class CollectionController extends Controller
{
    /** @var array<string, class-string> */
    protected array $collections = [
        'students' => User::class,
        'advisors' => User::class,
        'hods' => User::class,
        'system_admin' => User::class,
        'programs' => Program::class,
        'courses' => Course::class,
        'departments' => Department::class,
        'course_plans' => CoursePlan::class,
        'course_types' => CourseType::class,
        'mqf_sc' => MqfSc::class,
        'vbe' => Vbe::class,
        'offerings' => Offering::class,
    ];

    public function show(string $appId, string $collection): JsonResponse
    {
        abort_unless(array_key_exists($collection, $this->collections), 404, 'Unknown collection.');

        $model = $this->collections[$collection];

        $query = $model::query();

        if ($collection === 'students') {
            $query->where('role', User::ROLE_STUDENT);
        } elseif ($collection === 'advisors') {
            $query->where('role', User::ROLE_ADVISOR)->where('is_hod', false);
        } elseif ($collection === 'hods') {
            $query->where('is_hod', true);
        } elseif ($collection === 'system_admin') {
            $query->where('role', User::ROLE_ADMIN);
        }

        $records = $query->get()->map(fn ($record) => $this->normalize($record));

        return response()->json([
            'appId' => $appId,
            'path' => "artifacts/{$appId}/public/data/{$collection}",
            'collection' => $collection,
            'count' => $records->count(),
            'data' => $records,
            'activeSession' => Setting::get('active_session'),
            'activeSemester' => Setting::get('active_semester'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalize(object $record): array
    {
        $data = $record->attributesToArray();

        if ($record instanceof User) {
            unset($data['password'], $data['remember_token']);
        }

        return ['id' => $record->getKey()] + $data;
    }
}
