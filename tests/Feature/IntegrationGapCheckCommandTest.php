<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IntegrationGapCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $coreDatabasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coreDatabasePath = tempnam(sys_get_temp_dir(), 'kp-gap-core-');
        config()->set('database.connections.core', [
            'driver' => 'sqlite',
            'database' => $this->coreDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('core');

        $this->createCoreSchema();
    }

    protected function tearDown(): void
    {
        DB::purge('core');

        if (isset($this->coreDatabasePath) && file_exists($this->coreDatabasePath)) {
            unlink($this->coreDatabasePath);
        }

        parent::tearDown();
    }

    public function test_integration_gap_check_is_registered_and_read_only(): void
    {
        User::create([
            'name' => 'Gap Check User',
            'email' => 'gap-check@sikp.test',
            'password' => 'hash',
            'status' => 'active',
        ]);
        DB::connection('core')->table('users')->insert([
            'id' => 1,
            'name' => 'Core User',
            'email' => 'core@sikp.test',
            'active' => true,
        ]);

        $before = [
            'kp_users' => DB::table('users')->count(),
            'core_users' => DB::connection('core')->table('users')->count(),
        ];

        $this->artisan('kp:integration-gap-check --check-kp-db --check-core-db')
            ->expectsOutputToContain('KP workspace integration gap check')
            ->expectsOutputToContain('Read-only counts unchanged: yes')
            ->assertSuccessful();

        $this->assertSame($before['kp_users'], DB::table('users')->count());
        $this->assertSame($before['core_users'], DB::connection('core')->table('users')->count());
    }

    private function createCoreSchema(): void
    {
        Schema::connection('core')->create('users', function ($table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
}
