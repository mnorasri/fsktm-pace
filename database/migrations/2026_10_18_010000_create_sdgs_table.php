<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * UN Sustainable Development Goals (SDG 1–17).
     *
     * @var array<int, string>
     */
    protected array $goals = [
        1 => 'No Poverty',
        2 => 'Zero Hunger',
        3 => 'Good Health and Well-being',
        4 => 'Quality Education',
        5 => 'Gender Equality',
        6 => 'Clean Water and Sanitation',
        7 => 'Affordable and Clean Energy',
        8 => 'Decent Work and Economic Growth',
        9 => 'Industry, Innovation and Infrastructure',
        10 => 'Reduced Inequalities',
        11 => 'Sustainable Cities and Communities',
        12 => 'Responsible Consumption and Production',
        13 => 'Climate Action',
        14 => 'Life Below Water',
        15 => 'Life on Land',
        16 => 'Peace, Justice and Strong Institutions',
        17 => 'Partnerships for the Goals',
    ];

    public function up(): void
    {
        Schema::create('sdgs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('sdgs')->insert(collect($this->goals)->map(fn (string $name, int $number) => [
            'code' => 'SDG'.$number,
            'name' => $name,
            'description' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());

        // Each course maps to a single SDG. Added as a plain indexed column so
        // the change applies cleanly on SQLite.
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedBigInteger('sdg_id')->nullable()->index();
        });

        // Carry over any existing free-text SDG values (e.g. "SDG9").
        $sdgIds = DB::table('sdgs')->pluck('id', 'code');

        foreach (DB::table('courses')->whereNotNull('sdg')->get(['id', 'sdg']) as $course) {
            if (preg_match('/^(?:SDG)?\s*(\d{1,2})$/i', trim((string) $course->sdg), $matches)
                && $id = ($sdgIds['SDG'.$matches[1]] ?? null)) {
                DB::table('courses')->where('id', $course->id)->update(['sdg_id' => $id]);
            }
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('sdg');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('sdg')->nullable();
        });

        Schema::dropIfExists('sdgs');
    }
};
