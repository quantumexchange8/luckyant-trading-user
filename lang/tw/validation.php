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

    'accepted' => '必须接受 :attribute。',
    'accepted_if' => '当 :other 为 :value 时，必须接受 :attribute。',
    'active_url' => ':attribute 不是一个有效的 URL。',
    'after' => ':attribute 必须是 :date 之后的日期。',
    'after_or_equal' => ':attribute 必须是 :date 之后或等于 :date 的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、数字、破折号和下划线。',
    'alpha_num' => ':attribute 只能包含字母和数字。',
    'array' => ':attribute 必须是一个数组。',
    'ascii' => ':attribute 只能包含单字节的字母数字字符和符号。',
    'before' => ':attribute 必须是 :date 之前的日期。',
    'before_or_equal' => ':attribute 必须是 :date 之前或等于 :date 的日期。',
    'between' => [
        'array' => ':attribute 必须包含 :min 到 :max 个项目。',
        'file' => ':attribute 必须在 :min 到 :max 千字节之间。',
        'numeric' => ':attribute 必须在 :min 到 :max 之间。',
        'string' => ':attribute 必须在 :min 到 :max 个字符之间。',
    ],
    'boolean' => ':attribute 字段必须是 true 或 false。',
    'confirmed' => ':attribute 确认不匹配。',
    'current_password' => '密码不正确。',
    'date' => ':attribute 不是一个有效的日期。',
    'date_equals' => ':attribute 必须等于 :date。',
    'date_format' => ':attribute 与格式 :format 不匹配。',
    'decimal' => ':attribute 必须有 :decimal 位小数。',
    'declined' => ':attribute 必须被拒绝。',
    'declined_if' => '当 :other 为 :value 时，:attribute 必须被拒绝。',
    'different' => ':attribute 和 :other 必须不同。',
    'digits' => ':attribute 必须有 :digits 位数字。',
    'digits_between' => ':attribute 必须在 :min 到 :max 位数字之间。',
    'dimensions' => ':attribute 图像尺寸无效。',
    'distinct' => ':attribute 字段有重复值。',
    'doesnt_end_with' => ':attribute 不能以以下内容之一结尾：:values。',
    'doesnt_start_with' => ':attribute 不能以以下内容之一开头：:values。',
    'email' => ':attribute 必须是一个有效的电子邮件地址。',
    'ends_with' => ':attribute 必须以以下之一结尾：:values。',
    'enum' => '所选 :attribute 无效。',
    'exists' => '所选 :attribute 无效。',
    'file' => ':attribute 必须是一个文件。',
    'filled' => ':attribute 字段必须有一个值。',
    'gt' => [
        'array' => ':attribute 必须包含多于 :value 个项目。',
        'file' => ':attribute 必须大于 :value 千字节。',
        'numeric' => ':attribute 必须大于 :value。',
        'string' => ':attribute 必须多于 :value 个字符。',
    ],
    'gte' => [
        'array' => ':attribute 必须包含 :value 个项目或更多。',
        'file' => ':attribute 必须大于或等于 :value 千字节。',
        'numeric' => ':attribute 必须大于或等于 :value。',
        'string' => ':attribute 必须多于或等于 :value 个字符。',
    ],
    'image' => ':attribute 必须是一张图片。',
    'in' => '所選 :attribute 無效。',
    'in_array' => ':attribute 字段不存在於 :other 中。',
    'integer' => ':attribute 必須是整數。',
    'ip' => ':attribute 必須是有效的 IP 地址。',
    'ipv4' => ':attribute 必須是有效的 IPv4 地址。',
    'ipv6' => ':attribute 必須是有效的 IPv6 地址。',
    'json' => ':attribute 必須是有效的 JSON 字符串。',
    'lowercase' => ':attribute 必須是小寫字母。',
    'lt' => [
        'array' => ':attribute 必須包含少於 :value 個項目。',
        'file' => ':attribute 必須小於 :value 千字節。',
        'numeric' => ':attribute 必須小於 :value。',
        'string' => ':attribute 必須少於 :value 個字符。',
    ],
    'lte' => [
        'array' => ':attribute 必須不包含多於 :value 個項目。',
        'file' => ':attribute 必須小於或等於 :value 千字節。',
        'numeric' => ':attribute 必須小於或等於 :value。',
        'string' => ':attribute 必須少於或等於 :value 個字符。',
    ],
    'mac_address' => ':attribute 必須是有效的 MAC 地址。',
    'max' => [
        'array' => ':attribute 不能包含多於 :max 個項目。',
        'file' => ':attribute 不能大於 :max 千字節。',
        'numeric' => ':attribute 不能大於 :max。',
        'string' => ':attribute 不能多於 :max 個字符。',
    ],
    'max_digits' => ':attribute 不能超過 :max 位數字。',
    'mimes' => ':attribute 必須是以下類型之一的文件：:values。',
    'mimetypes' => ':attribute 必須是以下類型之一的文件：:values。',
    'min' => [
        'array' => ':attribute 必須包含至少 :min 個項目。',
        'file' => ':attribute 必須至少爲 :min 千字節。',
        'numeric' => ':attribute 必須至少爲 :min。',
        'string' => ':attribute 必須至少爲 :min 個字符。',
    ],
    'min_digits' => ':attribute 必須至少包含 :min 位數字。',
    'multiple_of' => ':attribute 必須是 :value 的倍數。',
    'not_in' => '所選 :attribute 無效。',
    'not_regex' => ':attribute 格式無效。',
    'numeric' => ':attribute 必須是一個數字。',
    'password' => [
        'letters' => ':attribute 必須包含至少一個字母。',
        'mixed' => ':attribute 必須包含至少一個大寫字母和一個小寫字母。',
        'numbers' => ':attribute 必須包含至少一個數字。',
        'symbols' => ':attribute 必須包含至少一個符號。',
        'uncompromised' => '給定的 :attribute 已經出現在數據泄漏中，請選擇其他 :attribute。',
    ],
    'present' => ':attribute 字段必须存在。',
    'prohibited' => ':attribute 字段禁止使用。',
    'prohibited_if' => '当 :other 为 :value 时，:attribute 字段禁止使用。',
    'prohibited_unless' => ':attribute 字段除非 :other 在 :values 中，否则禁止使用。',
    'prohibits' => ':attribute 字段禁止 :other 出现。',
    'regex' => ':attribute 格式无效。',
    'required' => ':attribute 字段是必需的。',
    'required_array_keys' => ':attribute 字段必须包含以下条目：:values。',
    'required_if' => '当 :other 为 :value 时，:attribute 字段是必需的。',
    'required_if_accepted' => '当 :other 被接受时，:attribute 字段是必需的。',
    'required_unless' => '除非 :other 在 :values 中，否则 :attribute 字段是必需的。',
    'required_with' => '当 :values 存在时，:attribute 字段是必需的。',
    'required_with_all' => '当 :values 都存在时，:attribute 字段是必需的。',
    'required_without' => '当 :values 不存在时，:attribute 字段是必需的。',
    'required_without_all' => '当 :values 都不存在时，:attribute 字段是必需的。',
    'same' => ':attribute 和 :other 必须匹配。',
    'size' => [
        'array' => ':attribute 必须包含 :size 个项目。',
        'file' => ':attribute 必须为 :size 千字节。',
        'numeric' => ':attribute 必须为 :size。',
        'string' => ':attribute 必须为 :size 个字符。',
    ],
    'starts_with' => ':attribute 必须以以下之一开头：:values。',
    'string' => ':attribute 必须是一个字符串。',
    'timezone' => ':attribute 必须是有效的时区。',
    'unique' => ':attribute 已经被占用。',
    'uploaded' => ':attribute 上传失败。',
    'uppercase' => ':attribute 必须是大写字母。',
    'url' => ':attribute 必须是有效的 URL。',
    'ulid' => ':attribute 必须是有效的 ULID。',
    'uuid' => ':attribute 必须是有效的 UUID。',

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
            'rule-name' => '自定義消息',
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
