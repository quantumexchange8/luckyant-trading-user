<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute ต้องได้รับการยอมรับ',
    'accepted_if' => ':attribute ต้องได้รับการยอมรับเมื่อ :other เป็น :value',
    'active_url' => ':attribute ไม่ใช่ URL ที่ถูกต้อง',
    'after' => ':attribute ต้องเป็นวันที่หลัง :date',
    'after_or_equal' => ':attribute ต้องเป็นวันที่หลังหรือเท่ากับ :date',
    'alpha' => ':attribute ต้องมีเฉพาะตัวอักษร',
    'alpha_dash' => ':attribute ต้องมีเฉพาะตัวอักษร, ตัวเลข, ขีดกลาง และขีดใต้',
    'alpha_num' => ':attribute ต้องมีเฉพาะตัวอักษรและตัวเลข',
    'array' => ':attribute ต้องเป็นอาร์เรย์',
    'ascii' => ':attribute ต้องมีเฉพาะตัวอักษรและสัญลักษณ์แบบเดี่ยวไบต์',
    'before' => ':attribute ต้องเป็นวันที่ก่อน :date',
    'before_or_equal' => ':attribute ต้องเป็นวันที่ก่อนหรือเท่ากับ :date',
    'between' => [
        'array' => ':attribute ต้องมีระหว่าง :min และ :max รายการ',
        'file' => ':attribute ต้องมีขนาดระหว่าง :min และ :max กิโลไบต์',
        'numeric' => ':attribute ต้องอยู่ระหว่าง :min และ :max',
        'string' => ':attribute ต้องมีความยาวระหว่าง :min และ :max ตัวอักษร',
    ],
    'boolean' => ':attribute ต้องเป็นค่าจริงหรือเท็จ',
    'confirmed' => 'การยืนยัน :attribute ไม่ตรงกัน',
    'current_password' => 'รหัสผ่านไม่ถูกต้อง',
    'date' => ':attribute ไม่ใช่วันที่ที่ถูกต้อง',
    'date_equals' => ':attribute ต้องเป็นวันที่เท่ากับ :date',
    'date_format' => ':attribute ไม่ตรงกับรูปแบบ :format',
    'decimal' => ':attribute ต้องมีทศนิยม :decimal ตำแหน่ง',
    'declined' => ':attribute ต้องไม่ได้รับการยอมรับ',
    'declined_if' => ':attribute ต้องไม่ได้รับการยอมรับเมื่อ :other เป็น :value',
    'different' => ':attribute และ :other ต้องไม่เหมือนกัน',
    'digits' => ':attribute ต้องมี :digits หลัก',
    'digits_between' => ':attribute ต้องมีระหว่าง :min และ :max หลัก',
    'dimensions' => ':attribute มีขนาดรูปภาพไม่ถูกต้อง',
    'distinct' => ':attribute มีค่าที่ซ้ำกัน',
    'doesnt_end_with' => ':attribute อาจไม่จบด้วยหนึ่งในต่อไปนี้: :values',
    'doesnt_start_with' => ':attribute อาจไม่ขึ้นต้นด้วยหนึ่งในต่อไปนี้: :values',
    'email' => ':attribute ต้องเป็นที่อยู่อีเมลที่ถูกต้อง',
    'ends_with' => ':attribute ต้องลงท้ายด้วยหนึ่งในต่อไปนี้: :values',
    'enum' => ':attribute ที่เลือกไม่ถูกต้อง',
    'exists' => ':attribute ที่เลือกไม่ถูกต้อง',
    'file' => ':attribute ต้องเป็นไฟล์',
    'filled' => ':attribute ต้องมีค่า',
    'gt' => [
        'array' => ':attribute ต้องมีรายการมากกว่า :value',
        'file' => ':attribute ต้องมีขนาดมากกว่า :value กิโลไบต์',
        'numeric' => ':attribute ต้องมากกว่า :value',
        'string' => ':attribute ต้องมีความยาวมากกว่า :value ตัวอักษร',
    ],
    'gte' => [
        'array' => ':attribute ต้องมี :value รายการหรือมากกว่า',
        'file' => ':attribute ต้องมีขนาดไม่น้อยกว่า :value กิโลไบต์',
        'numeric' => ':attribute ต้องมากกว่าหรือเท่ากับ :value',
        'string' => ':attribute ต้องมีความยาวไม่น้อยกว่า :value ตัวอักษร',
    ],
    'image' => ':attribute ต้องเป็นรูปภาพ',
    'in' => ':attribute ที่เลือกไม่ถูกต้อง',
    'in_array' => ':attribute ไม่มีอยู่ใน :other',
    'integer' => ':attribute ต้องเป็นจำนวนเต็ม',
    'ip' => ':attribute ต้องเป็นที่อยู่ IP ที่ถูกต้อง',
    'ipv4' => ':attribute ต้องเป็นที่อยู่ IPv4 ที่ถูกต้อง',
    'ipv6' => ':attribute ต้องเป็นที่อยู่ IPv6 ที่ถูกต้อง',
    'json' => ':attribute ต้องเป็น JSON ที่ถูกต้อง',
    'lowercase' => ':attribute ต้องเป็นตัวพิมพ์เล็ก',
    'lt' => [
        'array' => ':attribute ต้องมีรายการน้อยกว่า :value',
        'file' => ':attribute ต้องมีขนาดน้อยกว่า :value กิโลไบต์',
        'numeric' => ':attribute ต้องน้อยกว่า :value',
        'string' => ':attribute ต้องมีความยาวน้อยกว่า :value ตัวอักษร',
    ],
    'lte' => [
        'array' => ':attribute ต้องไม่มีรายการมากกว่า :value',
        'file' => ':attribute ต้องมีขนาดไม่เกิน :value กิโลไบต์',
        'numeric' => ':attribute ต้องน้อยกว่าหรือเท่ากับ :value',
        'string' => ':attribute ต้องมีความยาวไม่เกิน :value ตัวอักษร',
    ],
    'mac_address' => ':attribute ต้องเป็นที่อยู่ MAC ที่ถูกต้อง',
    'max' => [
        'array' => ':attribute ต้องไม่มีรายการมากกว่า :max',
        'file' => ':attribute ต้องไม่มีขนาดใหญ่กว่า :max กิโลไบต์',
        'numeric' => ':attribute ต้องไม่มากกว่า :max',
        'string' => ':attribute ต้องมีความยาวไม่เกิน :max ตัวอักษร',
    ],
    'max_digits' => ':attribute ต้องไม่มีจำนวนหลักเกิน :max',
    'mimes' => ':attribute ต้องเป็นไฟล์ประเภท: :values',
    'mimetypes' => ':attribute ต้องเป็นไฟล์ประเภท: :values',
    'min' => [
        'array' => ':attribute ต้องมีอย่างน้อย :min รายการ',
        'file' => ':attribute ต้องมีขนาดอย่างน้อย :min กิโลไบต์',
        'numeric' => ':attribute ต้องมีอย่างน้อย :min',
        'string' => ':attribute ต้องมีความยาวอย่างน้อย :min ตัวอักษร',
    ],
    'min_digits' => ':attribute ต้องมีตัวเลขอย่างน้อย :min หลัก',
    'multiple_of' => ':attribute ต้องเป็นคู่ของ :value',
    'not_in' => ':attribute ที่เลือกไม่ถูกต้อง',
    'not_regex' => 'รูปแบบ :attribute ไม่ถูกต้อง',
    'numeric' => ':attribute ต้องเป็นตัวเลข',
    'password' => [
        'letters' => ':attribute ต้องมีตัวอักษรอย่างน้อยหนึ่งตัว',
        'mixed' => ':attribute ต้องมีตัวอักษรตัวใหญ่และตัวเล็กอย่างน้อยหนึ่งตัว',
        'numbers' => ':attribute ต้องมีตัวเลขอย่างน้อยหนึ่งตัว',
        'symbols' => ':attribute ต้องมีสัญลักษณ์อย่างน้อยหนึ่งตัว',
        'uncompromised' => ':attribute ที่ระบุไว้ได้ปรากฏในการรั่วไหลข้อมูล โปรดเลือก :attribute อื่น',
    ],
    'present' => 'ต้องมี :attribute',
    'prohibited' => ':attribute ถูกห้าม',
    'prohibited_if' => 'เมื่อ :other เป็น :value :attribute ถูกห้าม',
    'prohibited_unless' => 'ถ้าไม่เป็น :other ใน :values :attribute ถูกห้าม',
    'prohibits' => ':attribute ห้าม :other',
    'regex' => 'รูปแบบ :attribute ไม่ถูกต้อง',
    'required' => ':attribute ต้องระบุ',
    'required_array_keys' => 'รายการ :values ของ :attribute ต้องระบุ',
    'required_if' => ':other เป็น :value แล้ว :attribute ต้องระบุ',
    'required_if_accepted' => 'เมื่อ :other ได้รับการยอมรับ :attribute ต้องระบุ',
    'required_unless' => ':other ไม่ใช่ :values แล้ว :attribute ต้องระบุ',
    'required_with' => ':values มีอยู่ แล้ว :attribute ต้องระบุ',
    'required_with_all' => 'ถ้ามี :values ทั้งหมด :attribute ต้องระบุ',
    'required_without' => ':values ไม่มีอยู่ แล้ว :attribute ต้องระบุ',
    'required_without_all' => 'ถ้าไม่มี :values ทั้งหมด :attribute ต้องระบุ',
    'same' => ':attribute และ :other ต้องตรงกัน',
    'size' => [
        'array' => ':attribute ต้องมี :size รายการ',
        'file' => ':attribute ต้องมีขนาด :size กิโลไบต์',
        'numeric' => ':attribute ต้องมีขนาด :size',
        'string' => ':attribute ต้องมีความยาว :size ตัวอักษร',
    ],
    'starts_with' => ':attribute ต้องขึ้นต้นด้วยหนึ่งในต่อไปนี้: :values',
    'string' => ':attribute ต้องเป็นข้อความ',
    'timezone' => ':attribute ต้องเป็นเขตเวลาที่ถูกต้อง',
    'unique' => ':attribute ถูกใช้แล้ว',
    'uploaded' => ':attribute อัปโหลดไม่สำเร็จ',
    'uppercase' => ':attribute ต้องเป็นตัวพิมพ์ใหญ่',
    'url' => ':attribute ต้องเป็น URL ที่ถูกต้อง',
    'ulid' => ':attribute ต้องเป็น ULID ที่ถูกต้อง',
    'uuid' => ':attribute ต้องเป็น UUID ที่ถูกต้อง',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'ข้อความที่กำหนดเอง',
        ],
    ],
        
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
