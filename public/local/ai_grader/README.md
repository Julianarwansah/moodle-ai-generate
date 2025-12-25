# Plugin AI Grader Moodle

Plugin lokal Moodle yang mengintegrasikan AI generatif (Google Gemini) untuk membantu dosen dalam menilai soal esai. Plugin ini memberikan saran nilai dan umpan balik otomatis berdasarkan kriteria penilaian (rubrik) yang didefinisikan dalam deskripsi kuis.

## Fitur Utama

1.  **Penilaian Berbasis AI**: Menganalisis jawaban esai mahasiswa secara otomatis dan memberikan saran nilai serta komentar.
2.  **Kriteria Penilaian Kustom**: Membaca aturan penilaian langsung dari kolom **Description** di pengaturan Kuis.
3.  **Kontrol Dosen (Override)**: 
    *   **Default**: Nilai dari AI akan tersimpan otomatis jika tidak ada tindakan.
    *   **Manual**: Tombol "Nilai belum sesuai" memungkinkan dosen untuk mengubah nilai dan komentar sebelum menyimpan.
4.  **Audit Log**: Semua saran dari AI dicatat secara ketat di database (`mdl_local_ai_grader_logs`) untuk transparansi dan audit jika ada sanggahan nilai.

## Instalasi

1.  Salin folder `ai_grader` ke direktori `local/` pada instalasi Moodle Anda.
2.  Masuk ke **Site administration** > **Notifications** untuk menginstall/upgrade plugin.
3.  Konfigurasi **API Key** di pengaturan plugin (Site administration > Plugins > Local plugins > AI Grader).

## Panduan Penggunaan

1.  **Atur Kriteria**: Pada pengaturan Kuis (Edit Settings), tuliskan rubrik atau aturan penilaian Anda di kolom **Description**.
2.  **Mulai Penilaian**: Masuk ke halaman Manual Grading untuk kuis tersebut.
3.  **Klik "Grade with AI"**: AI akan mengisi nilai dan feedback secara otomatis.
4.  **Review**: 
    *   Jika sudah sesuai, klik **Save**.
    *   Jika belum pas, klik **"Nilai belum sesuai"** untuk mengubah nilai secara manual.

Untuk panduan langkah demi langkah tentang pengujian dan verifikasi, silakan lihat [walkthrough.md](walkthrough.md).
