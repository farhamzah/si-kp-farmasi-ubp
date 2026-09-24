<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\User;
use App\Services\KpOfficialIdentityResolver;
use App\Services\KpExamInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KpOfficialIdentityResolverTest extends TestCase
{
    use RefreshDatabase;

    private string $coreDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coreDatabasePath = tempnam(sys_get_temp_dir(), 'core-official-');
        config()->set('database.connections.core', [
            'driver' => 'sqlite',
            'database' => $this->coreDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('core');

        Schema::connection('core')->create('users', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('display_name_with_title')->nullable();
            $table->string('formal_name')->nullable();
        });
        Schema::connection('core')->create('lecturers', function ($table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('front_title')->nullable();
            $table->string('back_title')->nullable();
            $table->string('display_name_with_title')->nullable();
            $table->string('formal_name')->nullable();
            $table->string('nuptk')->nullable();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('core');
        if (isset($this->coreDatabasePath) && file_exists($this->coreDatabasePath)) {
            unlink($this->coreDatabasePath);
        }

        parent::tearDown();
    }

    public function test_it_uses_titled_name_and_nuptk_from_core(): void
    {
        DB::connection('core')->table('users')->insert([
            'id' => 91,
            'name' => 'Diany Astuti',
            'email' => 'diany@ubpkarawang.ac.id',
        ]);
        DB::connection('core')->table('lecturers')->insert([
            'id' => 92,
            'user_id' => 91,
            'name' => 'Diany Astuti',
            'front_title' => 'apt.',
            'back_title' => 'S.Si., M.Farm.',
            'display_name_with_title' => 'apt. Diany Astuti, S.Si., M.Farm.',
            'nuptk' => '1234567890123456',
        ]);

        $user = User::create([
            'name' => 'Diany Astuti',
            'email' => 'diany@ubpkarawang.ac.id',
            'password' => Hash::make('password'),
            'status' => 'active',
            'core_user_id' => 91,
        ]);
        $lecturer = Lecturer::create([
            'user_id' => $user->id,
            'core_lecturer_id' => 92,
            'status' => 'active',
        ]);

        $identity = app(KpOfficialIdentityResolver::class)->resolve(null, 'Diany Astuti', '-');

        $this->assertSame($lecturer->id, $identity['lecturer_id']);
        $this->assertSame('apt. Diany Astuti, S.Si., M.Farm.', $identity['name']);
        $this->assertSame('1234567890123456', $identity['nuptk']);
        $this->assertSame('core', $identity['source']);

        $signatory = app(KpExamInvitationService::class)->saveActiveSignatory([
            'coordinator_name' => 'Diany Astuti',
            'coordinator_nuptk' => '-',
            'head_program_name' => 'Diany Astuti',
            'head_program_nuptk' => '-',
            'dean_name' => 'Diany Astuti',
            'dean_nuptk' => '-',
        ], $user);

        $this->assertSame($lecturer->id, $signatory->coordinator_lecturer_id);
        $this->assertSame('apt. Diany Astuti, S.Si., M.Farm.', $signatory->coordinator_name);
        $this->assertSame('1234567890123456', $signatory->coordinator_nuptk);
    }
}
