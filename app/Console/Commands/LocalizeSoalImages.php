<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\Soal\ImageLocalizer;
use Illuminate\Console\Command;

/**
 * Bereskan soal-soal LAMA yang <img>-nya masih menunjuk situs luar
 * (mis. hasil copy-paste dari pusmendik dsb sebelum fitur auto-localize ada):
 * download semua gambar eksternal ke storage/soal lalu tulis ulang src-nya.
 *
 * Aman diulang berkali-kali (idempoten): soal yang sudah lokal dilewati,
 * gambar yang sama tidak tersimpan dobel, dan gambar yang gagal di-download
 * dibiarkan apa adanya (dilaporkan di akhir).
 *
 *   php artisan soal:localize-images            → proses semua
 *   php artisan soal:localize-images --dry-run  → hanya tampilkan yang akan diubah
 */
class LocalizeSoalImages extends Command
{
    protected $signature = 'soal:localize-images {--dry-run : Tampilkan yang akan diubah tanpa menyimpan}';

    protected $description = 'Download gambar eksternal pada soal & opsi jawaban ke storage lokal';

    public function handle(ImageLocalizer $localizer): int
    {
        $dry = (bool) $this->option('dry-run');
        $changed = 0;
        $checked = 0;

        // withTrashed: soal soft-deleted bisa dipulihkan lagi, ikut dibereskan
        $questions = Question::withTrashed()
            ->where(fn ($q) => $q->where('question', 'like', '%<img%')->orWhere('pembahasan', 'like', '%<img%'))
            ->get();

        foreach ($questions as $q) {
            $checked++;
            $newQuestion   = $localizer->localizeHtml($q->question);
            $newPembahasan = $localizer->localizeHtml($q->pembahasan);

            if ($newQuestion !== $q->question || $newPembahasan !== $q->pembahasan) {
                $changed++;
                $this->line(($dry ? '[dry-run] ' : '')."Soal #{$q->id}: {$q->title}");
                if (! $dry) {
                    $q->timestamps = false; // jangan geser updated_at, ini pembersihan data
                    $q->forceFill(['question' => $newQuestion, 'pembahasan' => $newPembahasan])->save();
                }
            }
        }

        $options = QuestionOption::where('option_text', 'like', '%<img%')->get();
        foreach ($options as $o) {
            $checked++;
            $new = $localizer->localizeHtml($o->option_text);
            if ($new !== $o->option_text) {
                $changed++;
                $this->line(($dry ? '[dry-run] ' : '')."Opsi #{$o->id} (soal #{$o->question_id})");
                if (! $dry) {
                    $o->timestamps = false;
                    $o->forceFill(['option_text' => $new])->save();
                }
            }
        }

        $this->info("Selesai. Diperiksa: {$checked} baris, ".($dry ? 'akan diubah' : 'diubah').": {$changed}.");
        if ($changed === 0) {
            $this->line('Tidak ada gambar eksternal yang perlu di-download.');
        }

        return self::SUCCESS;
    }
}
