# FeedbackIQ — Customer Feedback Sentiment Analyzer (PHP)

Dashboard analisis sentimen feedback pelanggan berbasis PHP, CSV, HTML/CSS, JavaScript, dan Chart.js.

## Cara menjalankan (XAMPP)
1. Ekstrak folder `customer_feedback_sentiment_analyzer_php` ke `C:\xampp\htdocs\`.
2. Jalankan Apache dari XAMPP Control Panel.
3. Buka browser: `http://localhost/customer_feedback_sentiment_analyzer_php/`.

Tidak membutuhkan Composer, database, atau Python.

## Fitur
- Ringkasan total feedback, positif, negatif, dan positive rate.
- Grafik distribusi sentimen, tren bulanan, dan sentimen per kategori.
- Filter berdasarkan sentimen, kategori, dan kata kunci.
- Tabel feedback terbaru dan ekspor CSV.
- Analisis komentar langsung dengan pendekatan lexicon-based.

## Dataset dan metode
`data/sample_feedback.csv` berisi 72 baris data ilustratif berbahasa Indonesia. Label sentimen pada dataset contoh disediakan untuk demonstrasi dashboard. Fitur analisis komentar memakai kamus kata positif/negatif sederhana dengan penanganan negasi dasar. Metode ini adalah baseline berbasis leksikon, bukan model machine learning/LLM, sehingga hasil dapat keliru pada konteks, ironi, slang, dan kalimat kompleks.

## Struktur
- `index.php`: halaman dashboard dan filter.
- `includes/functions.php`: pembacaan CSV.
- `includes/sentiment.php`: analisis komentar.
- `assets/css/style.css`: tampilan.
- `assets/js/dashboard.js`: visualisasi Chart.js.
- `data/sample_feedback.csv`: dataset demo.

Chart.js dan Google Fonts dimuat dari CDN, sehingga grafik dan font eksternal memerlukan koneksi internet. Konten dashboard tetap dirender oleh PHP tanpa database.
