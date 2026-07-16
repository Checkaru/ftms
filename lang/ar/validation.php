<?php

// Arabic validation messages for the rules this app uses. Attribute names are
// supplied per-request via attributes() in each FormRequest.
return [

    'accepted' => 'يجب قبول :attribute.',
    'after' => 'يجب أن يكون :attribute بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute في :date أو بعده.',
    'array' => 'يجب أن يكون :attribute قائمة.',
    'before' => 'يجب أن يكون :attribute قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute في :date أو قبله.',
    'boolean' => 'قيمة :attribute غير صالحة.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'current_password' => 'كلمة المرور غير صحيحة.',
    'date' => ':attribute ليس تاريخاً صالحاً.',
    'date_format' => 'صيغة :attribute غير صحيحة.',
    'email' => 'يجب أن يكون :attribute بريداً إلكترونياً صالحاً.',
    'enum' => 'قيمة :attribute غير صالحة.',
    'exists' => 'القيمة المختارة لـ :attribute غير موجودة.',
    'integer' => 'يجب أن يكون :attribute رقماً صحيحاً.',
    'lowercase' => 'يجب أن يكون :attribute بأحرف صغيرة.',
    'max' => [
        'numeric' => 'يجب ألا يتجاوز :attribute :max.',
        'string' => 'يجب ألا يتجاوز :attribute :max حرفاً.',
    ],
    'min' => [
        'numeric' => 'يجب ألا يقل :attribute عن :min.',
        'string' => 'يجب ألا يقل :attribute عن :min أحرف.',
    ],
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب في هذه الحالة.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'unique' => 'قيمة :attribute مستخدمة مسبقاً.',

    'attributes' => [
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'name' => 'الاسم',
        'current_password' => 'كلمة المرور الحالية',
    ],

];
