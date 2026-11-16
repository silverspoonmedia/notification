<?php

return [
    'list_success' => 'تم جلب الإشعارات بنجاح.',
    'unread_count_success' => 'تم جلب عدد الإشعارات غير المقروءة بنجاح.',
    'marked_read' => 'تم تعليم الإشعار كمقروء.',
    'marked_all_read' => 'تم تعليم جميع الإشعارات كمقروءة.',
    'not_visible' => 'لا تملك صلاحية الوصول إلى هذا الإشعار.',
    // Message templates per notification type. Replacements come from the
    // notification `data` payload (:name, :email, :actor, :role, …).
    'types' => [
        'user_created' => 'أضاف :actor مستخدمًا جديدًا :name (:email).',
        'user_updated' => 'حدّث :actor المستخدم :name (:email).',
        'user_deleted' => 'حذف :actor المستخدم :name (:email).',
        'account_updated' => 'حدّث :actor حسابك.',
        'service_subscription_grace' => 'انتهى اشتراكك في :service. جدده خلال :grace_days يومًا (قبل :grace_ends_at)، وإلا فستُزال الملفات الزائدة إلى حدود المستوى المجاني.',
        // curated at 2026-09-22T20:32:27+07:00 — ar curation wave
        'service_subscription_expired' => 'انتهت فترة السماح لاشتراكك في :service. تم فرض حدود تخزين المستوى المجاني (تمت إزالة :deleted ملف/ملفات).',
        'inference_unregistered_model' => 'تمت فوترة النموذج غير المسجل :resolved_model على :provider_slug بأسعار مؤقتة ($:input_per_million_usd / $:output_per_million_usd لكل مليون رمز). صنّفه في كتالوج النماذج لإتمام الفوترة.',
    ],
    'audiences' => [
        'list_success' => 'تم تحميل جماهير الإشعارات.',
        'show_success' => 'تم تحميل جمهور الإشعارات.',
        'updated' => 'تم تحديث جمهور الإشعارات.',
        'unknown_type' => 'نوع إشعار غير معروف.',
    ],
];
