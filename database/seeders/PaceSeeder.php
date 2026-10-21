<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\CourseType;
use App\Models\Department;
use App\Models\MqfSc;
use App\Models\Offering;
use App\Models\PreRegistration;
use App\Models\Program;
use App\Models\Setting;
use App\Models\StudyPlanEntry;
use App\Models\User;
use App\Models\Vbe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PaceSeeder extends Seeder
{
    public function run(): void
    {
        // ---------------------------------------------------------------
        // Global settings
        // ---------------------------------------------------------------
        Setting::put('app_id', 'fsktm-pace');
        Setting::put('active_session', '2025/2026');
        Setting::put('active_semester', '1');

        // ---------------------------------------------------------------
        // Departments
        // ---------------------------------------------------------------
        $departments = collect([
            ['code' => 'CS', 'name' => 'Department of Computer Science', 'description' => 'Software, algorithms and computing foundations.'],
            ['code' => 'IS', 'name' => 'Department of Information Systems', 'description' => 'Enterprise systems and information management.'],
            ['code' => 'MM', 'name' => 'Department of Multimedia', 'description' => 'Graphics, interaction and creative computing.'],
        ])->mapWithKeys(fn ($d) => [$d['code'] => Department::create($d)]);

        // ---------------------------------------------------------------
        // Programs & course plans
        // ---------------------------------------------------------------
        $programs = collect([
            ['code' => 'CS241', 'name' => 'Bachelor of Computer Science (Hons.)', 'level' => 'Degree', 'department' => 'CS', 'total_credits' => 128],
            ['code' => 'CS230', 'name' => 'Bachelor of Software Engineering (Hons.)', 'level' => 'Degree', 'department' => 'CS', 'total_credits' => 130],
            ['code' => 'IS223', 'name' => 'Bachelor of Information Systems (Hons.)', 'level' => 'Degree', 'department' => 'IS', 'total_credits' => 126],
            ['code' => 'MM221', 'name' => 'Bachelor of Multimedia Computing (Hons.)', 'level' => 'Degree', 'department' => 'MM', 'total_credits' => 124],
        ])->mapWithKeys(function ($p) use ($departments) {
            $program = Program::create([
                'department_id' => $departments[$p['department']]->id,
                'code' => $p['code'],
                'name' => $p['name'],
                'level' => $p['level'],
                'duration_years' => 4,
                'total_credits' => $p['total_credits'],
            ]);

            $plan = CoursePlan::create([
                'program_id' => $program->id,
                'code' => $p['code'].'-PLAN',
                'name' => $p['name'].' Study Plan',
                'version' => '2025',
                'total_credits' => $p['total_credits'],
                'description' => 'Four-year curriculum structure.',
            ]);

            return [$p['code'] => ['program' => $program, 'plan' => $plan]];
        });

        // ---------------------------------------------------------------
        // Course types, MQF SC and VBE
        // ---------------------------------------------------------------
        $types = collect([
            ['code' => 'CORE', 'name' => 'Core Course'],
            ['code' => 'ELECTIVE', 'name' => 'Elective Course'],
            ['code' => 'MPU', 'name' => 'General / MPU Course'],
            ['code' => 'UNIVERSITY', 'name' => 'University Course'],
        ])->mapWithKeys(fn ($t) => [$t['code'] => CourseType::create($t)]);

        $mqfs = collect([
            ['code' => 'SC1', 'name' => 'Knowledge & Understanding'],
            ['code' => 'SC2', 'name' => 'Cognitive Skills'],
            ['code' => 'SC3', 'name' => 'Functional Work Skills'],
            ['code' => 'SC4', 'name' => 'Personal & Entrepreneurial Skills'],
            ['code' => 'SC5', 'name' => 'Ethics & Professionalism'],
        ])->mapWithKeys(fn ($m) => [$m['code'] => MqfSc::create($m)]);

        $vbes = collect([
            ['code' => 'VBE1', 'name' => 'Integrity & Accountability'],
            ['code' => 'VBE2', 'name' => 'Sustainability Awareness'],
            ['code' => 'VBE3', 'name' => 'Teamwork & Social Responsibility'],
            ['code' => 'VBE4', 'name' => 'Innovation & Entrepreneurship'],
        ])->mapWithKeys(fn ($v) => [$v['code'] => Vbe::create($v)]);

        // ---------------------------------------------------------------
        // Course catalogue
        // [name, credits, dept, type, mqf, vbe, prerequisites]
        // ---------------------------------------------------------------
        $catalogue = [
            // Computer Science
            'SCSJ1013' => ['Programming Technique I', 3, 'CS', 'CORE', 'SC1', 'VBE3', null],
            'SCSJ1023' => ['Programming Technique II', 3, 'CS', 'CORE', 'SC2', 'VBE3', 'SCSJ1013'],
            'SCSJ2013' => ['Data Structures & Algorithms', 3, 'CS', 'CORE', 'SC2', 'VBE4', 'SCSJ1023'],
            'SCSJ2203' => ['Discrete Structures', 3, 'CS', 'CORE', 'SC1', 'VBE1', null],
            'SCSJ2213' => ['Object Oriented Programming', 3, 'CS', 'CORE', 'SC2', 'VBE4', 'SCSJ1023'],
            'SCSJ3103' => ['Operating Systems', 3, 'CS', 'CORE', 'SC2', 'VBE1', 'SCSJ2013'],
            'SCSJ3203' => ['Computer Networks', 3, 'CS', 'CORE', 'SC3', 'VBE2', 'SCSJ2013'],
            'SCSJ3303' => ['Database Systems', 3, 'CS', 'CORE', 'SC3', 'VBE1', 'SCSJ2013'],
            'SCSJ3403' => ['Software Engineering', 3, 'CS', 'CORE', 'SC3', 'VBE3', 'SCSJ2013'],
            'SCSJ4103' => ['Artificial Intelligence', 3, 'CS', 'CORE', 'SC2', 'VBE4', 'SCSJ2013'],
            'SCSJ4203' => ['Machine Learning', 3, 'CS', 'ELECTIVE', 'SC2', 'VBE4', 'SCSJ4103'],
            'SCSJ4303' => ['Web Programming', 3, 'CS', 'ELECTIVE', 'SC3', 'VBE4', 'SCSJ2013'],
            'SCSJ4313' => ['Cloud Computing', 3, 'CS', 'ELECTIVE', 'SC3', 'VBE2', 'SCSJ2013'],
            'SCSJ4323' => ['Cyber Security', 3, 'CS', 'ELECTIVE', 'SC5', 'VBE1', 'SCSJ3203'],
            'SCSJ4333' => ['Data Mining', 3, 'CS', 'ELECTIVE', 'SC2', 'VBE4', 'SCSJ3303'],
            'SCSJ4343' => ['Mobile Application Development', 3, 'CS', 'ELECTIVE', 'SC3', 'VBE4', 'SCSJ2213'],
            'SCSJ4353' => ['Computer Vision', 3, 'CS', 'ELECTIVE', 'SC2', 'VBE4', 'SCSJ4103'],
            'SCSJ4403' => ['Final Year Project I', 3, 'CS', 'CORE', 'SC4', 'VBE1', 'SCSJ2013'],
            'SCSJ4414' => ['Final Year Project II', 4, 'CS', 'CORE', 'SC4', 'VBE1', 'SCSJ4403'],
            'SSCM1033' => ['Mathematics for Computing', 3, 'CS', 'CORE', 'SC1', 'VBE1', null],
            'SSCM1043' => ['Probability & Statistics', 3, 'CS', 'CORE', 'SC1', 'VBE1', 'SSCM1033'],

            // Information Systems
            'SCSR1013' => ['Introduction to Information Systems', 3, 'IS', 'CORE', 'SC1', 'VBE3', null],
            'SCSR2013' => ['Systems Analysis & Design', 3, 'IS', 'CORE', 'SC3', 'VBE3', 'SCSR1013'],
            'SCSR3013' => ['Database Administration', 3, 'IS', 'CORE', 'SC3', 'VBE1', 'SCSR2013'],
            'SCSR3103' => ['Business Process Management', 3, 'IS', 'CORE', 'SC3', 'VBE4', 'SCSR2013'],
            'SCSR4013' => ['Enterprise Systems', 3, 'IS', 'ELECTIVE', 'SC3', 'VBE4', 'SCSR3103'],

            // Multimedia
            'SCMM1013' => ['Multimedia Fundamentals', 3, 'MM', 'CORE', 'SC1', 'VBE3', null],
            'SCMM2013' => ['Computer Graphics', 3, 'MM', 'CORE', 'SC2', 'VBE4', 'SCMM1013'],
            'SCMM3013' => ['Human Computer Interaction', 3, 'MM', 'CORE', 'SC3', 'VBE3', 'SCMM1013'],

            // University / MPU
            'MPU3112' => ['Kemahiran Dinamika', 2, 'CS', 'MPU', 'SC4', 'VBE3', null],
            'MPU3212' => ['Bahasa Melayu Komunikasi', 2, 'CS', 'MPU', 'SC4', 'VBE2', null],
            'UHAS1172' => ['Malaysian Studies', 2, 'CS', 'UNIVERSITY', 'SC1', 'VBE2', null],
            'UHPS1012' => ['Academic English', 2, 'CS', 'UNIVERSITY', 'SC1', 'VBE3', null],
            'UICI1012' => ['Islamic & Asian Civilisation', 2, 'CS', 'UNIVERSITY', 'SC5', 'VBE1', null],
        ];

        $programByDept = [
            'CS' => $programs['CS241']['program']->id,
            'IS' => $programs['IS223']['program']->id,
            'MM' => $programs['MM221']['program']->id,
        ];

        $courses = collect($catalogue)->mapWithKeys(function ($c, $code) use ($departments, $types, $mqfs, $vbes, $programByDept) {
            $isGeneral = in_array($c[3], ['MPU', 'UNIVERSITY'], true);

            $course = Course::create([
                'code' => $code,
                'name' => $c[0],
                'credits' => $c[1],
                'department_id' => $departments[$c[2]]->id,
                'program_id' => $isGeneral ? null : $programByDept[$c[2]],
                'course_type_id' => $types[$c[3]]->id,
                'mqf_sc_id' => $mqfs[$c[4]]->id,
                'vbe_id' => $vbes[$c[5]]->id,
                'prerequisites' => $c[6],
                'synopsis' => $c[0].' focuses on the core concepts, tools and practices required by the programme.',
            ]);

            return [$code => $course];
        });

        // ---------------------------------------------------------------
        // Curriculum plan (CS241) — 8 semesters with electives as
        // REPLACE_ placeholders substituted by students.
        // ---------------------------------------------------------------
        $cs241 = $programs['CS241']['plan'];
        $plan = [
            [1, 1, ['SCSJ1013', 'SSCM1033', 'SCSR1013'], 'REPLACE_MPU_1', 'REPLACE_UNI_1'],
            [1, 2, ['SCSJ1023', 'SCSJ2203', 'SSCM1043'], 'REPLACE_MPU_2', 'REPLACE_UNI_2'],
            [2, 1, ['SCSJ2013', 'SCSJ2213', 'SCSJ3303'], 'REPLACE_ELECTIVE_1', null],
            [2, 2, ['SCSJ3103', 'SCSJ3203', 'SCSJ3403'], 'REPLACE_ELECTIVE_2', null],
            [3, 1, ['SCSJ4103', 'SCSJ4403'], 'REPLACE_ELECTIVE_3', null],
            [3, 2, ['SCSJ4414'], 'REPLACE_ELECTIVE_4', null],
            [4, 1, ['SCSJ4323'], 'REPLACE_ELECTIVE_5', null],
            [4, 2, ['SCSJ4333'], null, null],
        ];

        foreach ($plan as [$year, $semester, $codes, $electiveKey, $extraKey]) {
            foreach ($codes as $code) {
                $cs241->planCourses()->create([
                    'course_id' => $courses[$code]->id,
                    'year' => $year,
                    'semester' => $semester,
                    'category' => 'required',
                    'credits' => $courses[$code]->credits,
                ]);
            }

            if ($electiveKey) {
                $cs241->planCourses()->create([
                    'placeholder_key' => $electiveKey,
                    'year' => $year,
                    'semester' => $semester,
                    'category' => 'elective',
                    'credits' => 3,
                ]);
            }

            if ($extraKey) {
                $cs241->planCourses()->create([
                    'placeholder_key' => $extraKey,
                    'year' => $year,
                    'semester' => $semester,
                    'category' => 'university',
                    'credits' => 2,
                ]);
            }
        }

        // Lightweight plans for the other programmes.
        foreach (['CS230', 'IS223', 'MM221'] as $code) {
            $planModel = $programs[$code]['plan'];
            $seedCodes = $code === 'IS223'
                ? ['SCSR1013', 'SCSR2013', 'SCSR3013', 'SCSR3103']
                : ($code === 'MM221'
                    ? ['SCMM1013', 'SCMM2013', 'SCMM3013']
                    : ['SCSJ1013', 'SCSJ1023', 'SCSJ2013', 'SCSJ3403']);

            foreach ($seedCodes as $i => $seedCode) {
                $planModel->planCourses()->create([
                    'course_id' => $courses[$seedCode]->id,
                    'year' => intdiv($i, 2) + 1,
                    'semester' => ($i % 2) + 1,
                    'category' => 'required',
                    'credits' => $courses[$seedCode]->credits,
                ]);
            }
        }

        // ---------------------------------------------------------------
        // Users — admin, HoD, advisors and students
        // ---------------------------------------------------------------
        $admin = User::create([
            'name' => 'System Administrator',
            'email' => 'admin@fsktm.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'phone' => '07-5532000',
        ]);

        $hodCs = User::create([
            'name' => 'Dr. Faridah binti Hassan',
            'email' => 'hod.cs@fsktm.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADVISOR,
            'staff_id' => 'HOD001',
            'department_id' => $departments['CS']->id,
            'is_hod' => true,
        ]);

        $advisorCs = User::create([
            'name' => 'Dr. Ahmad Zaki',
            'email' => 'advisor.cs@fsktm.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADVISOR,
            'staff_id' => 'LEC001',
            'department_id' => $departments['CS']->id,
        ]);

        $advisorIs = User::create([
            'name' => 'Ms. Nurul Aini',
            'email' => 'advisor.is@fsktm.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADVISOR,
            'staff_id' => 'LEC002',
            'department_id' => $departments['IS']->id,
        ]);

        $advisorMm = User::create([
            'name' => 'Mr. Hafiz Rahman',
            'email' => 'advisor.mm@fsktm.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADVISOR,
            'staff_id' => 'LEC003',
            'department_id' => $departments['MM']->id,
        ]);

        $studentDefs = [
            ['name' => 'Ahmad bin Ismail', 'email' => 'student@fsktm.test', 'sid' => 'A210001', 'program' => 'CS241', 'advisor' => $advisorCs],
            ['name' => 'Siti Nurhaliza', 'email' => 'student2@fsktm.test', 'sid' => 'A210002', 'program' => 'CS241', 'advisor' => $advisorCs],
            ['name' => 'Lim Wei Jie', 'email' => 'student3@fsktm.test', 'sid' => 'A210003', 'program' => 'CS230', 'advisor' => $advisorCs],
            ['name' => 'Kavitha Raj', 'email' => 'student4@fsktm.test', 'sid' => 'A210004', 'program' => 'IS223', 'advisor' => $advisorIs],
            ['name' => 'Daniel Tan', 'email' => 'student5@fsktm.test', 'sid' => 'A210005', 'program' => 'MM221', 'advisor' => $advisorMm],
        ];

        $students = collect();
        foreach ($studentDefs as $i => $def) {
            $prog = $programs[$def['program']];
            $students->push(User::create([
                'name' => $def['name'],
                'email' => $def['email'],
                'password' => Hash::make('password'),
                'role' => User::ROLE_STUDENT,
                'student_id' => $def['sid'],
                'department_id' => $prog['program']->department_id,
                'program_id' => $prog['program']->id,
                'plan_id' => $prog['plan']->id,
                'advisor_id' => $def['advisor']->id,
                'plan_status' => $i === 0 ? 'submitted' : 'draft',
                'plan_submitted_at' => $i === 0 ? now()->subDays(2) : null,
            ]));
        }

        // ---------------------------------------------------------------
        // Study plan entries for the primary student
        // ---------------------------------------------------------------
        $primary = $students->first();
        $firstYearCourses = ['SCSJ1013', 'SSCM1033', 'SCSR1013', 'SCSJ1023', 'SCSJ2203', 'SSCM1043'];

        foreach ($cs241->planCourses as $pc) {
            $status = 'PLANNED';

            if ($pc->course_id && in_array($pc->course->code, $firstYearCourses, true)) {
                $status = 'COMPLETED';
            }

            // Replace the Year 1 MPU placeholder with a concrete course.
            $courseId = $pc->course_id;
            if ($pc->placeholder_key === 'REPLACE_MPU_1') {
                $courseId = $courses['MPU3112']->id;
            }
            if ($pc->placeholder_key === 'REPLACE_UNI_1') {
                $courseId = $courses['UHAS1172']->id;
            }

            StudyPlanEntry::create([
                'user_id' => $primary->id,
                'course_plan_id' => $cs241->id,
                'course_id' => $courseId,
                'placeholder_key' => $pc->placeholder_key,
                'year' => $pc->year,
                'semester' => $pc->semester,
                'status' => $status,
            ]);
        }

        // ---------------------------------------------------------------
        // Offerings for the active session / semester
        // ---------------------------------------------------------------
        $offeredThisTerm = [
            'SCSJ2013' => [$advisorCs, '01', 'Monday', '8:00 - 10:00', 'BK1'],
            'SCSJ2213' => [$advisorCs, '02', 'Tuesday', '10:00 - 12:00', 'BK2'],
            'SCSJ3303' => [$hodCs, '01', 'Wednesday', '14:00 - 16:00', 'BK3'],
            'SCSJ3203' => [$advisorCs, '01', 'Thursday', '8:00 - 10:00', 'BK1'],
            'SCSJ4103' => [$hodCs, '01', 'Friday', '9:00 - 11:00', 'BK4'],
            'SCSR2013' => [$advisorIs, '01', 'Monday', '11:00 - 13:00', 'BK5'],
            'SCMM2013' => [$advisorMm, '01', 'Tuesday', '14:00 - 16:00', 'BK6'],
            'SCSJ4323' => [$advisorCs, '01', 'Wednesday', '9:00 - 11:00', 'BK2'],
        ];

        $offerings = collect();
        foreach ($offeredThisTerm as $code => [$lecturer, $section, $day, $time, $venue]) {
            $course = $courses[$code];
            $offerings->push(Offering::create([
                'course_id' => $course->id,
                'session' => '2025/2026',
                'semester' => '1',
                'section' => $section,
                'program_id' => $course->program_id,
                'course_plan_id' => $cs241->id,
                'year' => 2,
                'lecturer_id' => $lecturer->id,
                'coordinator_id' => $lecturer->id,
                'capacity' => 40,
                'is_offered' => true,
                'day' => $day,
                'time' => $time,
                'venue' => $venue,
            ]));
        }

        // A couple of pre-registration requests to review.
        PreRegistration::create([
            'user_id' => $students[0]->id,
            'offering_id' => $offerings->firstWhere('course_id', $courses['SCSJ2013']->id)->id,
            'session' => '2025/2026',
            'semester' => '1',
            'credits' => 3,
            'status' => 'pending',
        ]);
        PreRegistration::create([
            'user_id' => $students[1]->id,
            'offering_id' => $offerings->firstWhere('course_id', $courses['SCSJ3303']->id)->id,
            'session' => '2025/2026',
            'semester' => '1',
            'credits' => 3,
            'status' => 'pending',
        ]);
        PreRegistration::create([
            'user_id' => $students[2]->id,
            'offering_id' => $offerings->firstWhere('course_id', $courses['SCSJ3203']->id)->id,
            'session' => '2025/2026',
            'semester' => '1',
            'credits' => 3,
            'status' => 'approved',
            'reviewed_by' => $advisorCs->id,
            'reviewed_at' => now()->subDay(),
        ]);
    }
}
