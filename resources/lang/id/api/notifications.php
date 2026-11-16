<?php

return [
    // curated at 2026-09-21T17:00:09+07:00 — id curation wave
    'list_success' => 'Daftar notifikasi berhasil dimuat.',
    // curated at 2026-09-21T17:00:09+07:00 — id curation wave
    'unread_count_success' => 'Jumlah notifikasi belum dibaca berhasil dimuat.',
    // curated at 2026-09-21T17:00:09+07:00 — id curation wave
    'marked_read' => 'Notifikasi ditandai sudah dibaca.',
    // curated at 2026-09-21T17:00:09+07:00 — id curation wave
    'marked_all_read' => 'Semua notifikasi ditandai sudah dibaca.',
    // curated at 2026-09-21T17:00:09+07:00 — id curation wave
    'not_visible' => 'Anda tidak berwenang mengakses notifikasi ini.',
    // Template pesan per tipe notifikasi. Nilai pengganti berasal dari payload
    // `data` notifikasi (:name, :email, :actor, :role, …).
    'types' => [
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'user_created' => 'Pengguna baru :name (:email) ditambahkan oleh :actor.',
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'user_updated' => ':actor memperbarui pengguna :name (:email).',
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'user_deleted' => ':actor menghapus pengguna :name (:email).',
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'account_updated' => 'Akun Anda diperbarui oleh :actor.',
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'service_subscription_grace' => 'Langganan :service Anda telah berakhir. Perpanjang dalam :grace_days hari (paling lambat :grace_ends_at) atau berkas berlebih akan dihapus hingga batas paket gratis.',
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'service_subscription_expired' => 'Masa tenggang langganan :service Anda berakhir. Batas penyimpanan paket gratis kini berlaku (:deleted berkas dihapus).',
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'inference_unregistered_model' => 'Model :resolved_model yang belum terdaftar pada :provider_slug ditagih dengan tarif sementara ($:input_per_million_usd / $:output_per_million_usd per 1 juta token). Klasifikasikan di katalog model untuk menyelesaikan penagihan.',
    ],
    'audiences' => [
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'list_success' => 'Audiens notifikasi berhasil dimuat.',
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'show_success' => 'Audiens notifikasi berhasil dimuat.',
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'updated' => 'Audiens notifikasi berhasil diperbarui.',
        // curated at 2026-09-21T17:00:09+07:00 — id curation wave
        'unknown_type' => 'Jenis notifikasi tidak dikenal.',
    ],
];
