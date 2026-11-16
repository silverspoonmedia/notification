<?php

return [
    'list_success' => 'Notifications retrieved successfully.',
    'unread_count_success' => 'Unread notification count retrieved successfully.',
    'marked_read' => 'Notification marked as read.',
    'marked_all_read' => 'All notifications marked as read.',
    'not_visible' => 'You are not authorized to access this notification.',
    // Message templates per notification type. Replacements come from the
    // notification `data` payload (:name, :email, :actor, :role, …).
    'types' => [
        'user_created' => 'A new user :name (:email) was added by :actor.',
        'user_updated' => ':actor updated user :name (:email).',
        'user_deleted' => ':actor deleted user :name (:email).',
        'account_updated' => 'Your account was updated by :actor.',
        'service_subscription_grace' => 'Your :service subscription has ended. Renew within :grace_days days (by :grace_ends_at) or excess files will be removed to free-tier limits.',
        'service_subscription_expired' => 'Your :service subscription grace period ended. Free-tier storage is enforced (:deleted file(s) removed).',
        'inference_unregistered_model' => 'Unregistered model :resolved_model on :provider_slug billed at provisional rates ($:input_per_million_usd / $:output_per_million_usd per 1M). Classify in model catalog to finalize billing.',
    ],
    'audiences' => [
        'list_success' => 'Notification audiences loaded.',
        'show_success' => 'Notification audience loaded.',
        'updated' => 'Notification audience updated.',
        'unknown_type' => 'Unknown notification type.',
    ],
];
