<?php

return [
    // curated at 2026-09-22T12:19:26+07:00 — zh_CN curation wave
    'list_success' => '已成功获取通知。',
    // curated at 2026-09-22T12:19:26+07:00 — zh_CN curation wave
    'unread_count_success' => '已成功获取未读通知数量。',
    'marked_read' => '通知已标记为已读。',
    'marked_all_read' => '所有通知已标记为已读。',
    // curated at 2026-09-22T12:19:26+07:00 — zh_CN curation wave
    'not_visible' => '您无权访问此通知。',
    // Message templates per notification type. Replacements come from the
    // notification `data` payload (:name, :email, :actor, :role, …).
    'types' => [
        'user_created' => '新用户 :name（:email）已由 :actor 添加。',
        'user_updated' => ':actor 更新了用户 :name（:email）。',
        'user_deleted' => ':actor 删除了用户 :name（:email）。',
        // curated at 2026-09-22T12:19:26+07:00 — zh_CN curation wave
        'account_updated' => '您的账户已由 :actor 更新。',
        // curated at 2026-09-22T12:19:26+07:00 — zh_CN curation wave
        'service_subscription_grace' => '您的 :service 订阅已结束。请在 :grace_days 天内（截止 :grace_ends_at）续费，否则超出部分的文件将被删除，以恢复到免费层级限制。',
        // curated at 2026-09-22T12:19:26+07:00 — zh_CN curation wave
        'service_subscription_expired' => '您的 :service 订阅宽限期已结束。现已执行免费层级存储限制（已删除 :deleted 个文件）。',
        // curated at 2026-09-22T12:19:26+07:00 — zh_CN curation wave
        'inference_unregistered_model' => '未注册模型 :resolved_model 在 :provider_slug 上按临时费率计费（每 1M 输入 $:input_per_million_usd、每 1M 输出 $:output_per_million_usd）。请在模型目录中完成分类，以最终确定计费。',
    ],
    'audiences' => [
        'list_success' => '通知受众已加载。',
        'show_success' => '通知受众已加载。',
        'updated' => '通知受众已更新。',
        'unknown_type' => '未知通知类型。',
    ],
];
