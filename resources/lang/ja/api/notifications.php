<?php

return [
    // curated at 2026-09-21T17:32:30+07:00 — ja curation wave
    'list_success' => '通知を読み込みました。',
    // curated at 2026-09-21T17:32:30+07:00 — ja curation wave
    'unread_count_success' => '未読通知数を読み込みました。',
    'marked_read' => '通知を既読にしました。',
    'marked_all_read' => 'すべての通知を既読にしました。',
    'not_visible' => 'この通知にアクセスする権限がありません。',
    // Message templates per notification type. Replacements come from the
    // notification `data` payload (:name, :email, :actor, :role, …).
    'types' => [
        'user_created' => '新しいユーザー :name (:email) が :actor によって追加されました。',
        'user_updated' => ':actor がユーザー :name (:email) を更新しました。',
        'user_deleted' => ':actor がユーザー :name (:email) を削除しました。',
        'account_updated' => 'あなたのアカウントが :actor によって更新されました。',
        'service_subscription_grace' => ':service のサブスクリプションが終了しました。:grace_days 日以内（:grace_ends_at まで）に更新しないと、無料枠を超えるファイルが削除されます。',
        'service_subscription_expired' => ':service の猶予期間が終了しました。無料枠が適用され、:deleted 件のファイルが削除されました。',
        'inference_unregistered_model' => '未登録モデル :resolved_model（:provider_slug）が暫定料金（入力 $:input_per_million_usd / 出力 $:output_per_million_usd / 100万）で課金されました。モデルカタログで分類して課金を確定してください。',
    ],
    'audiences' => [
        'list_success' => '通知オーディエンスを読み込みました。',
        'show_success' => '通知オーディエンスを読み込みました。',
        'updated' => '通知オーディエンスを更新しました。',
        'unknown_type' => '不明な通知タイプです。',
    ],
];
