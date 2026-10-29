<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Oman Permit Fields Configuration
    |--------------------------------------------------------------------------
    | Required and optional fields captured for government permit verification.
    */
    'permit_fields' => [
        'required' => [
            'civil_id',
            'holder_name',
            'verified_at',
            'remaining_amount',
        ],
        'optional' => [
            'permit_no',
            'annual_limit',
            'used_amount',
            'valid_from',
            'valid_to',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permit Verification Rules & Thresholds
    |--------------------------------------------------------------------------
    */
    'permit_civil_id_regex' => env('RMSC_PERMIT_CIVIL_ID_REGEX', '/^\d{6,12}$/'),
    'permit_verification_max_age_hours' => (int) env('RMSC_PERMIT_MAX_AGE_HOURS', 24),
    'permit_min_confidence' => (float) env('RMSC_PERMIT_MIN_CONFIDENCE', 0.8),
    'permit_block_when_exceeds' => (bool) env('RMSC_PERMIT_BLOCK_WHEN_EXCEEDS', true),

    /*
    |--------------------------------------------------------------------------
    | Permit Screenshot Extraction Driver
    |--------------------------------------------------------------------------
    | 'tesseract' : Local Tesseract OCR (default, private, free)
    | 'anthropic' : Call Anthropic Messages API with image
    | 'gemini'    : Call Google Generative Language API with image
    | 'null'      : Disabled extraction (manual only)
    | 'fake'      : For testing
    */
    'permit_extraction' => [
        'driver' => env('PERMIT_OCR_DRIVER', env('PERMIT_EXTRACTION_DRIVER', 'tesseract')),
        'fallback_driver' => env('PERMIT_OCR_FALLBACK', null),
        'fallback_min_confidence' => (float) env('PERMIT_OCR_FALLBACK_MIN_CONFIDENCE', 0.6),
        'date_day_first' => (bool) env('PERMIT_DATE_DAY_FIRST', true),

        'tesseract' => [
            'binary' => env('TESSERACT_PATH', 'tesseract'),
            'langs' => env('TESSERACT_LANGS', 'eng+ara'),
            'tessdata_dir' => env('TESSDATA_DIR', null),
            'timeout' => (int) env('TESSERACT_TIMEOUT', 20),
            'psm' => (int) env('TESSERACT_PSM', 6),
        ],

        'anthropic' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
            'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 1024),
            'timeout' => (int) env('ANTHROPIC_TIMEOUT', 30),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 30),
        ],

        'min_name_tokens' => (int) env('PERMIT_MIN_NAME_TOKENS', 2),

        'generic_words' => [
            'details',
            'detail',
            'information',
            'info',
            'status',
            'summary',
            'permit',
            'holder',
            'page',
            'portal',
            'home',
            'services',
            'print',
            'تفاصيل',
            'معلومات',
            'حالة',
            'ملخص',
            'طباعة',
            'الرئيسية',
            'الخدمات',
        ],

        'labels' => [
            'en' => [
                'civil_id' => ['Civil ID', 'Civil No', 'ID Number', 'Resident ID', 'Civil Number'],
                'holder_name' => ['Full Name', 'Holder Name', 'Name', 'Permit Holder'],
                'permit_no' => ['Permit Number', 'Permit No', 'Permit #', 'Reference No'],
                'status' => ['Permit Status', 'Status'],
                'valid_from' => ['Valid From', 'Issue Date', 'Start Date'],
                'valid_to' => ['Valid To', 'Expiry Date', 'End Date', 'Expires'],
                'annual_limit' => ['Annual Limit', 'Yearly Quota', 'Total Quota', 'Authorized Limit'],
                'used_amount' => ['Used Amount', 'Consumed Amount', 'Total Used', 'Quota Used'],
                'remaining_amount' => ['Remaining Balance', 'Remaining Quota', 'Remaining Amount', 'Remaining', 'Balance'],
                'last_updated' => ['Last Updated', 'Verification Date', 'Verified At', 'Updated On'],
            ],
            'ar' => [
                'civil_id' => ['الرقم المدني', 'رقم البطاقة', 'المدني'],
                'holder_name' => ['الاسم الكامل', 'اسم صاحب التصريح', 'اسم حامل التصريح', 'الاسم'],
                'permit_no' => ['رقم التصريح', 'رقم الترخيص'],
                'status' => ['حالة التصريح', 'الحالة'],
                'valid_from' => ['صالح من', 'تاريخ الإصدار', 'من تاريخ'],
                'valid_to' => ['صالح حتى', 'تاريخ الانتهاء', 'إلى تاريخ'],
                'annual_limit' => ['الحد السنوي', 'الحصة السنوية', 'إجمالي الحصة'],
                'used_amount' => ['المبلغ المستخدم', 'الحصة المستخدمة', 'المستخدم'],
                'remaining_amount' => ['الرصيد المتبقي', 'الحصة المتبقية', 'المتبقي'],
                'last_updated' => ['آخر تحديث', 'تاريخ التحقق'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit Management & Company VAT Settings
    |--------------------------------------------------------------------------
    */
    'credit_grace_days' => (int) env('RMSC_CREDIT_GRACE_DAYS', 0),
    'company_vat_no' => env('RMSC_COMPANY_VAT_NO', 'OM1100000001'),
];
