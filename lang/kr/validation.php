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

    'accepted' => ':attribute을(를) 수락해야 합니다.',
    'accepted_if' => ':other이(가) :value일 때 :attribute을(를) 수락해야 합니다.',
    'active_url' => ':attribute은(는) 유효한 URL이 아닙니다.',
    'after' => ':attribute은(는) :date 이후의 날짜여야 합니다.',
    'after_or_equal' => ':attribute은(는) :date 이후 또는 같은 날짜여야 합니다.',
    'alpha' => ':attribute은(는) 문자만 포함해야 합니다.',
    'alpha_dash' => ':attribute은(는) 문자, 숫자, 대시 및 밑줄만 포함해야 합니다.',
    'alpha_num' => ':attribute은(는) 문자 및 숫자만 포함해야 합니다.',
    'array' => ':attribute은(는) 배열이어야 합니다.',
    'ascii' => ':attribute은(는) 단일 바이트 알파벳 및 기호만 포함해야 합니다.',
    'before' => ':attribute은(는) :date 이전의 날짜여야 합니다.',
    'before_or_equal' => ':attribute은(는) :date 이전 또는 같은 날짜여야 합니다.',
    'between' => [
        'array' => ':attribute은(는) :min에서 :max 개의 항목이어야 합니다.',
        'file' => ':attribute은(는) :min에서 :max 킬로바이트 사이여야 합니다.',
        'numeric' => ':attribute은(는) :min에서 :max 사이여야 합니다.',
        'string' => ':attribute은(는) :min에서 :max 문자 사이여야 합니다.',
    ],
    'boolean' => ':attribute 필드는 true 또는 false이어야 합니다.',
    'confirmed' => ':attribute 확인이 일치하지 않습니다.',
    'current_password' => '비밀번호가 올바르지 않습니다.',
    'date' => ':attribute은(는) 유효한 날짜가 아닙니다.',
    'date_equals' => ':attribute은(는) :date와(과) 같은 날짜여야 합니다.',
    'date_format' => ':attribute은(는) :format 형식과 일치하지 않습니다.',
    'decimal' => ':attribute은(는) :decimal 자릿수를 가져야 합니다.',
    'declined' => ':attribute을(를) 거절해야 합니다.',
    'declined_if' => ':other이(가) :value일 때 :attribute을(를) 거부해야 합니다.',
    'different' => ':attribute과(와) :other은(는) 서로 달라야 합니다.',
    'digits' => ':attribute은(는) :digits 자리 숫자여야 합니다.',
    'digits_between' => ':attribute은(는) :min에서 :max 자리 사이여야 합니다.',
    'dimensions' => ':attribute에는 유효하지 않은 이미지 차원이 있습니다.',
    'distinct' => ':attribute 필드에 중복 값이 있습니다.',
    'doesnt_end_with' => ':attribute은(는) 다음 중 하나로 끝날 수 없습니다: :values.',
    'doesnt_start_with' => ':attribute은(는) 다음 중 하나로 시작할 수 없습니다: :values.',
    'email' => ':attribute은(는) 유효한 이메일 주소여야 합니다.',
    'ends_with' => ':attribute은(는) 다음 중 하나로 끝나야 합니다: :values.',
    'enum' => '선택한 :attribute이(가) 유효하지 않습니다.',
    'exists' => '선택한 :attribute이(가) 유효하지 않습니다.',
    'file' => ':attribute은(는) 파일이어야 합니다.',
    'filled' => ':attribute 필드에는 값을 입력해야 합니다.',
    'gt' => [
        'array' => ':attribute은(는) :value보다 많은 항목을 가져야 합니다.',
        'file' => ':attribute은(는) :value 킬로바이트보다 커야 합니다.',
        'numeric' => ':attribute은(는) :value보다 커야 합니다.',
        'string' => ':attribute은(는) :value보다 길어야 합니다.',
    ],
    'gte' => [
        'array' => ':attribute은(는) :value개 이상의 항목을 가져야 합니다.',
        'file' => ':attribute은(는) :value 킬로바이트 이상이어야 합니다.',
        'numeric' => ':attribute은(는) :value 이상이어야 합니다.',
        'string' => ':attribute은(는) :value 문자 이상이어야 합니다.',
    ],
    'image' => ':attribute은(는) 이미지여야 합니다.',
    'in' => '선택한 :attribute이(가) 유효하지 않습니다.',
    'in_array' => ':attribute 필드는 :other에 존재하지 않습니다.',
    'integer' => ':attribute은(는) 정수여야 합니다.',
    'ip' => ':attribute은(는) 유효한 IP 주소여야 합니다.',
    'ipv4' => ':attribute은(는) 유효한 IPv4 주소여야 합니다.',
    'ipv6' => ':attribute은(는) 유효한 IPv6 주소여야 합니다.',
    'json' => ':attribute은(는) 유효한 JSON 문자열이어야 합니다.',
    'lowercase' => ':attribute은(는) 소문자여야 합니다.',
    'lt' => [
        'array' => ':attribute은(는) :value보다 적은 항목을 가져야 합니다.',
        'file' => ':attribute은(는) :value 킬로바이트보다 작아야 합니다.',
        'numeric' => ':attribute은(는) :value보다 작아야 합니다.',
        'string' => ':attribute은(는) :value보다 짧아야 합니다.',
    ],
    'lte' => [
        'array' => ':attribute은(는) :value개 이하의 항목을 가져야 합니다.',
        'file' => ':attribute은(는) :value 킬로바이트 이하이어야 합니다.',
        'numeric' => ':attribute은(는) :value 이하이어야 합니다.',
        'string' => ':attribute은(는) :value 문자 이하이어야 합니다.',
    ],
    'mac_address' => ':attribute은(는) 유효한 MAC 주소여야 합니다.',
    'max' => [
        'array' => ':attribute은(는) :max개보다 많을 수 없습니다.',
        'file' => ':attribute은(는) :max 킬로바이트보다 클 수 없습니다.',
        'numeric' => ':attribute은(는) :max보다 클 수 없습니다.',
        'string' => ':attribute은(는) :max자보다 클 수 없습니다.',
    ],
    'max_digits' => ':attribute은(는) :max 자리를 초과할 수 없습니다.',
    'mimes' => ':attribute은(는) 다음 유형의 파일이어야 합니다: :values.',
    'mimetypes' => ':attribute은(는) 다음 유형의 파일이어야 합니다: :values.',
    'min' => [
        'array' => ':attribute은(는) 최소한 :min개의 항목을 가져야 합니다.',
        'file' => ':attribute은(는) 최소한 :min 킬로바이트여야 합니다.',
        'numeric' => ':attribute은(는) 최소한 :min이어야 합니다.',
        'string' => ':attribute은(는) 최소한 :min자여야 합니다.',
    ],
    'min_digits' => ':attribute은(는) 최소한 :min 자리가 있어야 합니다.',
    'multiple_of' => ':attribute은(는) :value의 배수여야 합니다.',
    'not_in' => '선택한 :attribute이(가) 유효하지 않습니다.',
    'not_regex' => ':attribute 형식이 유효하지 않습니다.',
    'numeric' => ':attribute은(는) 숫자여야 합니다.',
    'password' => [
        'letters' => ':attribute은(는) 최소한 하나의 문자를 포함해야 합니다.',
        'mixed' => ':attribute은(는) 최소한 하나의 대문자 및 소문자를 포함해야 합니다.',
        'numbers' => ':attribute은(는) 최소한 하나의 숫자를 포함해야 합니다.',
        'symbols' => ':attribute은(는) 최소한 하나의 기호를 포함해야 합니다.',
        'uncompromised' => '제공된 :attribute은(는) 데이터 유출에 나타났습니다. 다른 :attribute를 선택해주세요.',
    ],
    'present' => ':attribute 필드가 있어야 합니다.',
    'prohibited' => ':attribute 필드는 금지되어 있습니다.',
    'prohibited_if' => ':other이(가) :value일 때 :attribute 필드는 금지되어 있습니다.',
    'prohibited_unless' => ':other이(가) :values에 없는 한 :attribute 필드는 금지됩니다.',
    'prohibits' => ':attribute 필드는 :other이(가) 존재하는 경우 금지됩니다.',
    'regex' => ':attribute 형식이 유효하지 않습니다.',
    'required' => ':attribute 필드는 필수입니다.',
    'required_array_keys' => ':attribute 필드에는 다음 항목이 있어야 합니다: :values.',
    'required_if' => ':other이(가) :value일 때 :attribute 필드는 필수입니다.',
    'required_if_accepted' => ':other이(가) 수락된 경우 :attribute 필드가 필요합니다.',
    'required_unless' => ':other이(가) :values에 없는 경우 :attribute 필드는 필수입니다.',
    'required_with' => ':values가 있는 경우 :attribute 필드는 필수입니다.',
    'required_with_all' => ':values가 있는 경우 :attribute 필드는 필수입니다.',
    'required_without' => ':values가 없는 경우 :attribute 필드는 필수입니다.',
    'required_without_all' => ':values가 없는 경우 :attribute 필드는 필수입니다.',
    'same' => ':attribute와(과) :other은(는) 일치해야 합니다.',
    'size' => [
        'array' => ':attribute은(는) :size개의 항목을 포함해야 합니다.',
        'file' => ':attribute은(는) :size 킬로바이트여야 합니다.',
        'numeric' => ':attribute은(는) :size여야 합니다.',
        'string' => ':attribute은(는) :size자여야 합니다.',
    ],
    'starts_with' => ':attribute은(는) 다음 중 하나로 시작해야 합니다: :values.',
    'string' => ':attribute은(는) 문자열이어야 합니다.',
    'timezone' => ':attribute은(는) 유효한 시간대여야 합니다.',
    'unique' => ':attribute은(는) 이미 사용 중입니다.',
    'uploaded' => ':attribute을(를) 업로드하지 못했습니다.',
    'uppercase' => ':attribute은(는) 대문자여야 합니다.',
    'url' => ':attribute 형식이 유효하지 않습니다.',
    'ulid' => ':attribute은(는) 유효한 ULID여야 합니다.',
    'uuid' => ':attribute은(는) 유효한 UUID여야 합니다.',
    
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
            'rule-name' => '사용자 정의 메시지',
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
