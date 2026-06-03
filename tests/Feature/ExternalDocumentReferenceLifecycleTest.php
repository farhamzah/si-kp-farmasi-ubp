<?php

namespace Tests\Feature;

use App\Models\KpExternalDocumentReference;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalDocumentReferenceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $koordinator;
    private User $mahasiswa;
    private User $pembimbingDalam;
    private User $pembimbingLapangan;
    private User $penguji;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = $this->makeUser('admin-lifecycle@test.local', ['admin']);
        $this->koordinator = $this->makeUser('koor-lifecycle@test.local', ['koordinator_kp']);
        $this->mahasiswa = $this->makeUser('student-lifecycle@test.local', ['mahasiswa']);
        $this->pembimbingDalam = $this->makeUser('internal-lifecycle@test.local', ['pembimbing_dalam']);
        $this->pembimbingLapangan = $this->makeUser('field-lifecycle@test.local', ['pembimbing_lapangan']);
        $this->penguji = $this->makeUser('examiner-lifecycle@test.local', ['penguji']);
    }

    public function test_admin_and_koordinator_can_open_edit_screen_and_other_roles_are_rejected(): void
    {
        $reference = $this->makeReference();

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->get(route('management.integration.external-document-references.edit', $reference))
            ->assertOk()
            ->assertSee('Edit Referensi Dokumen Eksternal TU')
            ->assertSee('Snapshot Ringkas');

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->get(route('management.integration.external-document-references.edit', $reference))
            ->assertOk()
            ->assertSee('Manual local linking');

        foreach ([
            [$this->mahasiswa, 'mahasiswa'],
            [$this->pembimbingDalam, 'pembimbing_dalam'],
            [$this->pembimbingLapangan, 'pembimbing_lapangan'],
            [$this->penguji, 'penguji'],
        ] as [$user, $role]) {
            $this->actingAs($user)->withSession(['active_role' => $role])
                ->get(route('management.integration.external-document-references.edit', $reference))
                ->assertForbidden();

            $this->actingAs($user)->withSession(['active_role' => $role])
                ->patch(route('management.integration.external-document-references.update', $reference), [
                    'external_status' => 'pending_external',
                ])
                ->assertForbidden();
        }
    }

    public function test_admin_can_update_pending_and_linked_status_with_safe_reference_url_locally_only(): void
    {
        Http::fake();
        $reference = $this->makeReference();
        $beforeUsers = DB::table('users')->count();

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->patch(route('management.integration.external-document-references.update', $reference), [
                'external_status' => 'pending_external',
                'last_error' => 'Menunggu nomor dokumen TU.',
            ])
            ->assertRedirect(route('management.integration.external-document-references.edit', $reference));

        $reference->refresh();
        $this->assertSame('pending_external', $reference->external_status);
        $this->assertSame('Menunggu nomor dokumen TU.', $reference->last_error);
        $this->assertNull($reference->synced_at);

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->patch(route('management.integration.external-document-references.update', $reference), [
                'external_document_id' => 'TU-DOC-2026-001',
                'external_document_number' => '120/TU/KP/2026',
                'external_status' => 'linked',
                'reference_url' => 'https://tu.example.local/arsip/kp/120-2026',
            ])
            ->assertRedirect(route('management.integration.external-document-references.edit', $reference));

        Http::assertNothingSent();
        $reference->refresh();

        $this->assertSame('linked', $reference->external_status);
        $this->assertSame('TU-DOC-2026-001', $reference->external_document_id);
        $this->assertSame('120/TU/KP/2026', $reference->external_document_number);
        $this->assertSame('https://tu.example.local/arsip/kp/120-2026', $reference->reference_url);
        $this->assertNotNull($reference->synced_at);
        $this->assertSame($this->admin->id, $reference->updated_by);
        $this->assertSame($beforeUsers, DB::table('users')->count());
        $this->assertSame(1, KpExternalDocumentReference::count());
    }

    public function test_koordinator_can_update_failed_and_archived_status_manually(): void
    {
        Http::fake();
        $reference = $this->makeReference();

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->patch(route('management.integration.external-document-references.update', $reference), [
                'external_status' => 'failed',
                'last_error' => 'Nomor arsip TU belum ditemukan.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('kp_external_document_references', [
            'id' => $reference->id,
            'external_status' => 'failed',
            'last_error' => 'Nomor arsip TU belum ditemukan.',
            'updated_by' => $this->koordinator->id,
        ]);

        $this->actingAs($this->koordinator)->withSession(['active_role' => 'koordinator_kp'])
            ->patch(route('management.integration.external-document-references.update', $reference), [
                'external_status' => 'archived',
            ])
            ->assertRedirect();

        Http::assertNothingSent();
        $this->assertDatabaseHas('kp_external_document_references', [
            'id' => $reference->id,
            'external_status' => 'archived',
        ]);
    }

    public function test_status_must_be_known_lifecycle_value(): void
    {
        $reference = $this->makeReference();

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->from(route('management.integration.external-document-references.edit', $reference))
            ->patch(route('management.integration.external-document-references.update', $reference), [
                'external_status' => 'auto_synced',
            ])
            ->assertRedirect(route('management.integration.external-document-references.edit', $reference))
            ->assertSessionHasErrors('external_status');

        $this->assertSame('draft', $reference->refresh()->external_status);
    }

    public function test_reference_url_validation_accepts_normal_url_and_rejects_sensitive_or_local_paths(): void
    {
        $reference = $this->makeReference();

        $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
            ->patch(route('management.integration.external-document-references.update', $reference), [
                'external_status' => 'linked',
                'reference_url' => 'https://tu.example.local/documents/kp-001',
            ])
            ->assertRedirect();

        $this->assertSame('https://tu.example.local/documents/kp-001', $reference->refresh()->reference_url);

        foreach ([
            'https://tu.example.local/documents/kp-001?token=abc',
            'https://tu.example.local/documents/kp-001?access_token=abc',
            'https://tu.example.local/documents/kp-001?signature=abc',
            'https://tu.example.local/documents/kp-001?signed=true',
            'https://tu.example.local/documents/kp-001?password=secret',
            'https://tu.example.local/documents/kp-001?secret=value',
            'https://tu.example.local/private/kp-001',
            'https://tu.example.local/storage/app/kp-001',
            'https://tu.example.local/storage/kp-001',
            'C:\\storage\\kp-001.pdf',
            'E:\\Aplikasi\\kp-001.pdf',
            '/storage/kp-001.pdf',
        ] as $unsafeUrl) {
            $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
                ->from(route('management.integration.external-document-references.edit', $reference))
                ->patch(route('management.integration.external-document-references.update', $reference), [
                    'external_status' => 'linked',
                    'reference_url' => $unsafeUrl,
                ])
                ->assertRedirect(route('management.integration.external-document-references.edit', $reference))
                ->assertSessionHasErrors('reference_url');
        }

        $this->assertSame('https://tu.example.local/documents/kp-001', $reference->refresh()->reference_url);
    }

    public function test_manual_text_fields_reject_sensitive_markers(): void
    {
        $reference = $this->makeReference();

        foreach ([
            ['external_document_id', 'TU-DOC-token-secret'],
            ['external_document_number', 'signed-password-value'],
            ['last_error', 'File tersimpan di storage/app/private/laporan.pdf'],
        ] as [$field, $value]) {
            $this->actingAs($this->admin)->withSession(['active_role' => 'admin'])
                ->from(route('management.integration.external-document-references.edit', $reference))
                ->patch(route('management.integration.external-document-references.update', $reference), [
                    'external_status' => 'failed',
                    $field => $value,
                ])
                ->assertRedirect(route('management.integration.external-document-references.edit', $reference))
                ->assertSessionHasErrors($field);
        }
    }

    private function makeUser(string $email, array $roles): User
    {
        $user = User::create([
            'name' => 'Lifecycle User',
            'email' => $email,
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
        $user->roles()->sync(Role::whereIn('name', $roles)->pluck('id'));

        return $user;
    }

    private function makeReference(): KpExternalDocumentReference
    {
        return KpExternalDocumentReference::create([
            'document_type' => 'placement_letter',
            'service_code' => 'KP_PLACEMENT_LETTER',
            'source_module' => 'assignment',
            'source_reference_type' => 'kp_assignment',
            'source_reference_id' => '42',
            'external_status' => 'draft',
            'metadata' => ['payload_status' => 'ready_for_preview'],
            'last_payload_snapshot' => [
                'document_type' => 'placement_letter',
                'service_code' => 'KP_PLACEMENT_LETTER',
            ],
            'created_by' => $this->admin->id,
        ]);
    }
}
