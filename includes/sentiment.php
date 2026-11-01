<?php
/**
 * Sentiment Analysis Engine — Lexicon-based NLP (Indonesian)
 * Approach: Lexicon scoring with negation handling, complaint/appreciation detection,
 *           and service-improvement recommendation generation.
 * Note: This is a rule-based lexicon engine, NOT an external LLM/API.
 */

/* ─── Lexicons ─────────────────────────────────────────────── */
define('POSITIVE_WORDS', [
    'bagus','baik','puas','cepat','ramah','mudah','menarik','nyaman','aman',
    'membantu','responsif','menyenangkan','memuaskan','terjangkau','lancar',
    'solutif','suka','hebat','mantap','bersih','sesuai','tepat','oke','ok',
    'sempurna','luar biasa','memuaskan','recommended','rekomen','senang',
    'positif','setia','kembali','sukses','berhasil','efisien','efektif',
    'profesional','jelas','lengkap','aman','terpercaya','terbaik','murah',
    'ringan','ringkas','cepat','akurat','stabil','handal','canggih','inovatif',
]);

define('NEGATIVE_WORDS', [
    'buruk','lambat','kecewa','mengecewakan','rusak','mahal','sulit','error',
    'gagal','terlambat','kotor','masalah','bermasalah','tidak','kurang','parah',
    'marah','jelek','payah','komplain','keluhan','berat','lambat','lama','rewel',
    'bingung','susah','ribet','ribet','lemot','crash','bug','down','gangguan',
    'tidak berfungsi','tidak merespons','mengabaikan','diabaikan','kecewa',
    'mengecewakan','buruk','parah','menyesal','sia-sia','buang','percuma',
    'tidak sesuai','tidak tepat','tidak aman','tidak bersih','kotor','najis',
    'busuk','basi','expired','cacat','bocor','pecah','patah','hilang','salah',
]);

define('NEGATION_WORDS', ['tidak','kurang','bukan','belum','tanpa','jangan','tak']);

define('COMPLAINT_KEYWORDS', [
    'lambat','lama','rusak','error','gagal','terlambat','kotor','mahal','sulit',
    'masalah','bermasalah','kecewa','mengecewakan','buruk','parah','marah','jelek',
    'payah','komplain','keluhan','tidak merespons','diabaikan','salah','cacat',
    'tidak sesuai','hilang','bocor','crash','bug','gangguan','tidak berfungsi',
]);

define('APPRECIATION_KEYWORDS', [
    'puas','cepat','ramah','mudah','menarik','nyaman','membantu','responsif',
    'menyenangkan','memuaskan','terjangkau','lancar','solutif','bagus','baik',
    'hebat','mantap','bersih','sesuai','tepat','sempurna','luar biasa','senang',
    'sukses','berhasil','efisien','profesional','terbaik','recommended','setia',
]);

/* ─── Core sentiment analysis ──────────────────────────────── */
function analyze_sentiment(string $text): array {
    $positiveWords  = POSITIVE_WORDS;
    $negativeWords  = NEGATIVE_WORDS;
    $negationWords  = NEGATION_WORDS;

    $normalized = mb_strtolower($text, 'UTF-8');
    preg_match_all('/[\p{L}\p{N}]+/u', $normalized, $matches);
    $tokens = $matches[0] ?? [];

    $posScore = 0;
    $negScore = 0;
    $foundPositive = [];
    $foundNegative = [];

    for ($i = 0; $i < count($tokens); $i++) {
        $tok = $tokens[$i];
        $isNegated = ($i > 0 && in_array($tokens[$i - 1], $negationWords, true));

        if (in_array($tok, $positiveWords, true)) {
            if ($isNegated) { $negScore++; $foundNegative[] = $tokens[$i-1].' '.$tok; }
            else            { $posScore++;  $foundPositive[] = $tok; }
        }
        if (in_array($tok, $negativeWords, true) && !in_array($tok, $negationWords, true)) {
            if ($isNegated) { $posScore++; $foundPositive[] = $tokens[$i-1].' '.$tok; }
            else            { $negScore++;  $foundNegative[] = $tok; }
        }
    }

    $score = $posScore - $negScore;
    if ($score > 0)      $sentiment = 'Positive';
    elseif ($score < 0)  $sentiment = 'Negative';
    else                 $sentiment = 'Neutral';

    /* Build explanation */
    $posStr = $foundPositive ? implode(', ', array_unique($foundPositive)) : 'tidak ada';
    $negStr = $foundNegative ? implode(', ', array_unique($foundNegative)) : 'tidak ada';
    $explanation = "Ditemukan {$posScore} sinyal positif ({$posStr}) dan {$negScore} sinyal negatif ({$negStr}). "
                 . "Analisis menggunakan mesin berbasis kamus kata (lexicon-based NLP), bukan model AI eksternal.";

    /* Identify complaints & appreciation */
    $complaints    = detect_complaints($text);
    $appreciations = detect_appreciations($text);

    /* Generate recommendation */
    $recommendation = generate_recommendation($sentiment, $complaints, $appreciations);

    return [
        'sentiment'      => $sentiment,
        'score'          => $score,
        'explanation'    => $explanation,
        'complaints'     => $complaints,
        'appreciations'  => $appreciations,
        'recommendation' => $recommendation,
        'pos_score'      => $posScore,
        'neg_score'      => $negScore,
    ];
}

/* ─── Detect complaints ────────────────────────────────────── */
function detect_complaints(string $text): array {
    $lower = mb_strtolower($text, 'UTF-8');
    $found = [];
    foreach (COMPLAINT_KEYWORDS as $kw) {
        if (mb_strpos($lower, $kw) !== false) $found[] = $kw;
    }
    return array_unique($found);
}

/* ─── Detect appreciations ─────────────────────────────────── */
function detect_appreciations(string $text): array {
    $lower = mb_strtolower($text, 'UTF-8');
    $found = [];
    foreach (APPRECIATION_KEYWORDS as $kw) {
        if (mb_strpos($lower, $kw) !== false) $found[] = $kw;
    }
    return array_unique($found);
}

/* ─── Generate service recommendation ─────────────────────── */
function generate_recommendation(string $sentiment, array $complaints, array $appreciations): string {
    $recommendations = [];

    $ruleMap = [
        'lambat'         => 'Tingkatkan kecepatan respons dan waktu pemrosesan layanan.',
        'lama'           => 'Evaluasi SLA dan kurangi waktu tunggu pelanggan.',
        'rusak'          => 'Lakukan quality control lebih ketat sebelum produk dikirim.',
        'error'          => 'Lakukan pengujian aplikasi secara menyeluruh dan perbaiki bug yang dilaporkan.',
        'bug'            => 'Prioritaskan perbaikan bug yang berdampak pada pengalaman pengguna.',
        'crash'          => 'Tingkatkan stabilitas sistem dan lakukan load testing berkala.',
        'gagal'          => 'Tinjau ulang alur proses dan tambahkan mekanisme penanganan kesalahan.',
        'terlambat'      => 'Optimalkan sistem pengiriman dan tambahkan notifikasi status pengiriman.',
        'kotor'          => 'Tingkatkan standar kebersihan dan lakukan inspeksi rutin.',
        'mahal'          => 'Tinjau strategi harga dan pertimbangkan program loyalitas atau diskon.',
        'sulit'          => 'Sederhanakan alur pengguna (UX) dan tambahkan panduan/tutorial.',
        'masalah'        => 'Buat sistem pelaporan masalah yang mudah dan respons tim support lebih cepat.',
        'bermasalah'     => 'Lakukan audit sistem secara berkala untuk mendeteksi dan mencegah masalah.',
        'kecewa'         => 'Tindak lanjuti feedback pelanggan yang kecewa dengan solusi konkret.',
        'mengecewakan'   => 'Evaluasi gap antara ekspektasi pelanggan dan layanan yang diberikan.',
        'buruk'          => 'Lakukan pelatihan staf dan evaluasi standar kualitas layanan.',
        'marah'          => 'Implementasikan prosedur deeskalasi dan respons keluhan yang terstruktur.',
        'tidak merespons' => 'Pastikan semua saluran komunikasi aktif dan waktu respons customer service di bawah 24 jam.',
        'diabaikan'      => 'Buat sistem tiket untuk memastikan setiap keluhan ditangani dan tidak terlewat.',
        'salah'          => 'Tingkatkan akurasi informasi produk dan verifikasi data sebelum dikirim.',
        'hilang'         => 'Perkuat sistem pelacakan pengiriman dan asuransi paket.',
        'gangguan'       => 'Evaluasi infrastruktur teknis dan siapkan sistem redundansi.',
    ];

    foreach ($complaints as $complaint) {
        if (isset($ruleMap[$complaint])) $recommendations[] = $ruleMap[$complaint];
    }

    if (empty($recommendations)) {
        if ($sentiment === 'Positive') {
            $recommendations[] = 'Pertahankan kualitas layanan yang sudah baik dan jadikan ini standar minimum.';
            if ($appreciations) $recommendations[] = 'Aspek yang diapresiasi (' . implode(', ', $appreciations) . ') perlu terus dikembangkan.';
        } elseif ($sentiment === 'Negative') {
            $recommendations[] = 'Lakukan tindak lanjut segera terhadap pelanggan yang tidak puas dan identifikasi akar masalah.';
        } else {
            $recommendations[] = 'Dorong pelanggan untuk memberikan feedback yang lebih spesifik agar perbaikan dapat diarahkan dengan tepat.';
        }
    }

    return implode(' ', $recommendations);
}

/* ─── Batch analysis ───────────────────────────────────────── */
function analyze_batch(array $texts): array {
    $results   = [];
    $summary   = ['Positive' => 0, 'Negative' => 0, 'Neutral' => 0];
    $allComplaints    = [];
    $allAppreciations = [];

    foreach ($texts as $text) {
        $text = trim($text);
        if ($text === '') continue;
        $r = analyze_sentiment($text);
        $r['text'] = $text;
        $results[] = $r;
        $summary[$r['sentiment']]++;
        $allComplaints    = array_merge($allComplaints,    $r['complaints']);
        $allAppreciations = array_merge($allAppreciations, $r['appreciations']);
    }

    $total = array_sum($summary);
    $dominant = $total ? array_search(max($summary), $summary) : 'Neutral';
    $topComplaints    = array_slice(array_keys(array_count_values($allComplaints)),    0, 5);
    $topAppreciations = array_slice(array_keys(array_count_values($allAppreciations)), 0, 5);

    return [
        'results'          => $results,
        'summary'          => $summary,
        'total'            => $total,
        'dominant'         => $dominant,
        'positive_rate'    => $total ? round($summary['Positive'] / $total * 100) : 0,
        'top_complaints'   => $topComplaints,
        'top_appreciations'=> $topAppreciations,
    ];
}
