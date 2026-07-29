<?php

namespace App\Services;

use App\Models\KpQuestionnaire;
use App\Models\KpQuestionnaireQuestion;
use App\Models\User;

class KpQuestionnaireDefaultService
{
    public function ensureDefaults(?User $actor = null): void
    {
        foreach ($this->defaults() as $audience => $payload) {
            $questionnaire = KpQuestionnaire::firstOrCreate(
                ['audience' => $audience, 'kp_period_id' => null],
                [
                    'title' => $payload['title'],
                    'description' => $payload['description'],
                    'status' => 'aktif',
                    'created_by' => $actor?->id,
                ],
            );

            if ($questionnaire->questions()->exists()) {
                continue;
            }

            foreach ($payload['questions'] as $index => $question) {
                $questionnaire->questions()->create([
                    'section' => $question['section'],
                    'question_text' => $question['text'],
                    'answer_type' => $question['type'] ?? KpQuestionnaireQuestion::TYPE_SCALE,
                    'options' => $question['options'] ?? null,
                    'is_required' => $question['required'] ?? true,
                    'sort_order' => $index + 1,
                    'status' => 'aktif',
                ]);
            }
        }
    }

    public function defaults(): array
    {
        return [
            KpQuestionnaire::AUDIENCE_STUDENT => [
                'title' => 'Kuisioner Kepuasan Mahasiswa KP',
                'description' => 'Evaluasi pengalaman mahasiswa selama menjalani kerja praktek, mulai dari pendaftaran, pembimbingan, tempat KP, sampai manfaat pembelajaran.',
                'questions' => [
                    ...$this->scaleQuestions('Layanan Sistem dan Administrasi', [
                        'Informasi periode, persyaratan, dan alur KP mudah dipahami.',
                        'Proses pendaftaran dan upload berkas KP mudah digunakan.',
                        'Informasi status pendaftaran, berkas, dan penempatan tampil jelas.',
                        'Sistem membantu saya memantau tahapan KP tanpa harus bertanya berulang.',
                    ]),
                    ...$this->scaleQuestions('Pemilihan dan Penempatan Tempat KP', [
                        'Informasi kuota dan tempat KP mudah ditemukan.',
                        'Proses pemilihan tempat KP berjalan transparan dan adil.',
                        'Tempat KP yang diperoleh sesuai dengan minat atau kebutuhan pembelajaran saya.',
                        'Informasi pembimbing dalam dan pembimbing lapangan mudah dilihat.',
                    ]),
                    ...$this->scaleQuestions('Pembimbingan dan Logbook', [
                        'Pengisian logbook harian mudah dilakukan.',
                        'Validasi logbook oleh pembimbing lapangan membantu memastikan aktivitas KP saya tercatat.',
                        'Proses bimbingan laporan dengan pembimbing dalam mudah dipantau.',
                        'Feedback pembimbing membantu saya memperbaiki laporan akhir.',
                    ]),
                    ...$this->scaleQuestions('Tempat KP dan Pembelajaran', [
                        'Tempat KP memberikan pengalaman belajar yang sesuai dengan bidang farmasi.',
                        'Pembimbing lapangan memberikan arahan yang cukup selama KP.',
                        'Lingkungan tempat KP mendukung mahasiswa belajar secara profesional.',
                        'Kompetensi yang ditargetkan selama KP relevan dengan kebutuhan dunia kerja.',
                    ]),
                    ...$this->scaleQuestions('Sidang, Nilai, dan Kesiapan Karier', [
                        'Syarat menuju sidang KP mudah dipahami.',
                        'Informasi jadwal sidang dan penilaian mudah diakses.',
                        'Secara umum KP meningkatkan kesiapan saya memasuki dunia kerja.',
                        'Secara keseluruhan saya puas dengan pelaksanaan KP.',
                    ]),
                    ['section' => 'Rekomendasi', 'text' => 'Apakah Anda bersedia merekomendasikan tempat KP ini kepada mahasiswa berikutnya?', 'type' => KpQuestionnaireQuestion::TYPE_CHOICE, 'options' => ['Ya', 'Tidak', 'Mungkin']],
                    ['section' => 'Rekomendasi', 'text' => 'Nilai rekomendasi tempat KP ini dari 1 sampai 10.', 'type' => KpQuestionnaireQuestion::TYPE_NUMBER],
                    ['section' => 'Umpan Balik Terbuka', 'text' => 'Apa pengalaman terbaik selama KP?', 'type' => KpQuestionnaireQuestion::TYPE_TEXTAREA, 'required' => false],
                    ['section' => 'Umpan Balik Terbuka', 'text' => 'Apa kendala utama yang Anda alami selama KP?', 'type' => KpQuestionnaireQuestion::TYPE_TEXTAREA, 'required' => false],
                    ['section' => 'Umpan Balik Terbuka', 'text' => 'Apa saran perbaikan untuk sistem atau pelaksanaan KP berikutnya?', 'type' => KpQuestionnaireQuestion::TYPE_TEXTAREA, 'required' => false],
                ],
            ],
            KpQuestionnaire::AUDIENCE_FIELD_SUPERVISOR => [
                'title' => 'Kuisioner Kepuasan Tempat KP',
                'description' => 'Evaluasi dari pembimbing lapangan atau perwakilan tempat KP terhadap kesiapan mahasiswa, komunikasi program, dan pengalaman menerima mahasiswa KP.',
                'questions' => [
                    ...$this->scaleQuestions('Administrasi dan Komunikasi Program', [
                        'Informasi dari program studi terkait jadwal, tujuan, dan kewajiban KP mudah dipahami.',
                        'Komunikasi dengan koordinator atau admin KP berjalan baik.',
                        'Data mahasiswa dan dokumen pendukung yang diberikan sudah memadai.',
                        'Sistem membantu proses monitoring mahasiswa KP.',
                    ]),
                    ...$this->scaleQuestions('Kesiapan Mahasiswa', [
                        'Mahasiswa hadir dengan kesiapan dasar yang baik.',
                        'Mahasiswa memahami etika kerja di tempat KP.',
                        'Mahasiswa mampu mengikuti arahan pembimbing lapangan.',
                        'Mahasiswa aktif bertanya dan belajar selama KP.',
                    ]),
                    ...$this->scaleQuestions('Kinerja dan Kompetensi Mahasiswa', [
                        'Mahasiswa menunjukkan kedisiplinan selama KP.',
                        'Mahasiswa mampu menyelesaikan tugas sesuai arahan.',
                        'Mahasiswa menjaga komunikasi yang baik dengan staf tempat KP.',
                        'Mahasiswa menunjukkan perkembangan kompetensi selama KP.',
                    ]),
                    ...$this->scaleQuestions('Logbook, Laporan, dan Evaluasi', [
                        'Logbook mahasiswa menggambarkan aktivitas KP secara cukup jelas.',
                        'Proses validasi logbook mudah dilakukan.',
                        'Review laporan akhir mudah dilakukan melalui sistem/link yang tersedia.',
                        'Format penilaian mahasiswa mudah dipahami dan diisi.',
                    ]),
                    ...$this->scaleQuestions('Kerja Sama dan Rekomendasi', [
                        'Tempat KP bersedia menerima mahasiswa KP Farmasi UBP kembali.',
                        'Kerja sama dengan Program Studi Farmasi UBP berjalan memuaskan.',
                        'Mahasiswa KP memberi kontribusi positif bagi tempat KP.',
                        'Secara keseluruhan tempat KP puas terhadap pelaksanaan KP.',
                    ]),
                    ['section' => 'Rekomendasi', 'text' => 'Apakah tempat KP bersedia menerima mahasiswa KP lagi pada periode berikutnya?', 'type' => KpQuestionnaireQuestion::TYPE_CHOICE, 'options' => ['Ya', 'Tidak', 'Perlu dibicarakan']],
                    ['section' => 'Rekomendasi', 'text' => 'Perkiraan jumlah mahasiswa yang dapat diterima pada periode berikutnya.', 'type' => KpQuestionnaireQuestion::TYPE_NUMBER, 'required' => false],
                    ['section' => 'Umpan Balik Terbuka', 'text' => 'Apa kelebihan mahasiswa KP Farmasi UBP yang paling terlihat?', 'type' => KpQuestionnaireQuestion::TYPE_TEXTAREA, 'required' => false],
                    ['section' => 'Umpan Balik Terbuka', 'text' => 'Apa aspek yang perlu ditingkatkan oleh mahasiswa atau program studi?', 'type' => KpQuestionnaireQuestion::TYPE_TEXTAREA, 'required' => false],
                    ['section' => 'Umpan Balik Terbuka', 'text' => 'Saran untuk meningkatkan kerja sama tempat KP dan Program Studi Farmasi UBP.', 'type' => KpQuestionnaireQuestion::TYPE_TEXTAREA, 'required' => false],
                ],
            ],
        ];
    }

    private function scaleQuestions(string $section, array $questions): array
    {
        return array_map(
            fn (string $question): array => ['section' => $section, 'text' => $question, 'type' => KpQuestionnaireQuestion::TYPE_SCALE],
            $questions,
        );
    }
}
