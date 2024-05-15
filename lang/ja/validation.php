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

    'accepted' => ':attributeは受け入れる必要があります。',
    'accepted_if' => ':otherが:valueの場合、:attributeを受け入れる必要があります。',
    'active_url' => ':attributeは有効なURLではありません。',
    'after' => ':attributeは:dateより後の日付でなければなりません。',
    'after_or_equal' => ':attributeは:date以降の日付でなければなりません。',
    'alpha' => ':attributeは文字のみを含む必要があります。',
    'alpha_dash' => ':attributeは文字、数字、ダッシュ、アンダースコアのみを含む必要があります。',
    'alpha_num' => ':attributeは文字と数字のみを含む必要があります。',
    'array' => ':attributeは配列でなければなりません。',
    'ascii' => ':attributeはシングルバイトの英数字文字と記号のみを含む必要があります。',
    'before' => ':attributeは:dateより前の日付でなければなりません。',
    'before_or_equal' => ':attributeは:date以前の日付であるか等しくなければなりません。',
    'between' => [
        'array' => ':attributeは:minから:maxの間のアイテムを持つ必要があります。',
        'file' => ':attributeは:minから:maxキロバイトの間でなければなりません。',
        'numeric' => ':attributeは:minから:maxの間でなければなりません。',
        'string' => ':attributeは:minから:max文字の間でなければなりません。',
    ],
    'boolean' => ':attributeフィールドはtrueまたはfalseでなければなりません。',
    'confirmed' => ':attributeの確認が一致しません。',
    'current_password' => 'パスワードが正しくありません。',
    'date' => ':attributeは有効な日付ではありません。',
    'date_equals' => ':attributeは:dateと等しい日付でなければなりません。',
    'date_format' => ':attributeはフォーマット:formatと一致しません。',
    'decimal' => ':attributeは:decimal桁の小数を持つ必要があります。',
    'declined' => ':attributeは拒否する必要があります。',
    'declined_if' => ':otherが:valueの場合、:attributeを拒否する必要があります。',
    'different' => ':attributeと:otherは異なる必要があります。',
    'digits' => ':attributeは:digits桁でなければなりません。',
    'digits_between' => ':attributeは:minから:max桁の間でなければなりません。',
    'dimensions' => ':attributeの画像サイズが無効です。',
    'distinct' => ':attributeフィールドには重複した値があります。',
    'doesnt_end_with' => ':attributeは次のいずれかで終わってはいけません:values。',
    'doesnt_start_with' => ':attributeは次のいずれかで始まってはいけません:values。',
    'email' => ':attributeは有効なメールアドレスでなければなりません。',
    'ends_with' => ':attributeは次のいずれかで終わる必要があります:values。',
    'enum' => '選択された:attributeは無効です。',
    'exists' => '選択された:attributeは無効です。',
    'file' => ':attributeはファイルでなければなりません。',
    'filled' => ':attributeフィールドには値が必要です。',
    'gt' => [
        'array' => ':attributeは:value以上のアイテムを持たなければなりません。',
        'file' => ':attributeは:valueキロバイトより大きくなければなりません。',
        'numeric' => ':attributeは:valueより大きくなければなりません。',
        'string' => ':attributeは:value文字より大きくなければなりません。',
    ],
    'gte' => [
        'array' => ':attributeは:value個以上のアイテムを持たなければなりません。',
        'file' => ':attributeは:valueキロバイト以上でなければなりません。',
        'numeric' => ':attributeは:value以上でなければなりません。',
        'string' => ':attributeは:value文字以上でなければなりません。',
    ],
    'image' => ':attributeは画像でなければなりません。',
    'in' => '選択された:attributeは無効です。',
    'in_array' => ':attributeフィールドは:otherに存在しません。',
    'integer' => ':attributeは整数でなければなりません。',
    'ip' => ':attributeは有効なIPアドレスでなければなりません。',
    'ipv4' => ':attributeは有効なIPv4アドレスでなければなりません。',
    'ipv6' => ':attributeは有効なIPv6アドレスでなければなりません。',
    'json' => ':attributeは有効なJSON文字列でなければなりません。',
    'lowercase' => ':attributeは小文字でなければなりません。',
    'lt' => [
        'array' => ':attributeは:valueより少ないアイテムを持たなければなりません。',
        'file' => ':attributeは:valueキロバイト未満でなければなりません。',
        'numeric' => ':attributeは:value未満でなければなりません。',
        'string' => ':attributeは:value文字未満でなければなりません。',
    ],
    'lte' => [
        'array' => ':attributeは:value個以上のアイテムを持てません。',
        'file' => ':attributeは:valueキロバイト以下でなければなりません。',
        'numeric' => ':attributeは:value以下でなければなりません。',
        'string' => ':attributeは:value文字以下でなければなりません。',
    ],
    'mac_address' => ':attributeは有効なMACアドレスでなければなりません。',
    'max' => [
        'array' => ':attributeは:max個を超えるアイテムを持てません。',
        'file' => ':attributeは:maxキロバイトを超えることはできません。',
        'numeric' => ':attributeは:maxを超えることはできません。',
        'string' => ':attributeは:max文字を超えることはできません。',
    ],
    'max_digits' => ':attributeは:max桁を超えることはできません。',
    'mimes' => ':attributeはタイプ:valuesのファイルでなければなりません。',
    'mimetypes' => ':attributeはタイプ:valuesのファイルでなければなりません。',
    'min' => [
        'array' => ':attributeは少なくとも:min個のアイテムを持たなければなりません。',
        'file' => ':attributeは少なくとも:minキロバイトでなければなりません。',
        'numeric' => ':attributeは少なくとも:minでなければなりません。',
        'string' => ':attributeは少なくとも:min文字でなければなりません。',
    ],
    'min_digits' => ':attributeは少なくとも:min桁を持たなければなりません。',
    'multiple_of' => ':attributeは:valueの倍数でなければなりません。',
    'not_in' => '選択された:attributeは無効です。',
    'not_regex' => ':attributeのフォーマットが無効です。',
    'numeric' => ':attributeは数字でなければなりません。',
    'password' => [
        'letters' => ':attributeには少なくとも1つの文字を含める必要があります。',
        'mixed' => ':attributeには少なくとも1つの大文字と1つの小文字の文字を含める必要があります。',
        'numbers' => ':attributeには少なくとも1つの数字を含める必要があります。',
        'symbols' => ':attributeには少なくとも1つの記号を含める必要があります。',
        'uncompromised' => '指定された:attributeはデータ漏洩に登場しました。別の:attributeを選択してください。',
    ],
    'present' => ':attributeフィールドは存在する必要があります。',
    'prohibited' => ':attributeフィールドは禁止されています。',
    'prohibited_if' => ':otherが:valueの場合、:attributeフィールドは禁止されています。',
    'prohibited_unless' => ':otherが:valuesにない場合、:attributeフィールドは禁止されています。',
    'prohibits' => ':attributeフィールドは:otherの存在を禁止します。',
    'regex' => ':attributeのフォーマットが無効です。',
    'required' => ':attributeフィールドは必須です。',
    'required_array_keys' => ':attributeフィールドには、次のエントリが必要です:values。',
    'required_if' => ':otherが:valueの場合、:attributeフィールドは必須です。',
    'required_if_accepted' => ':otherが受け入れられた場合、:attributeフィールドは必須です。',
    'required_unless' => ':otherが:valuesに含まれていない場合、:attributeフィールドは必須です。',
    'required_with' => ':valuesが存在する場合、:attributeフィールドは必須です。',
    'required_with_all' => ':valuesがすべて存在する場合、:attributeフィールドは必須です。',
    'required_without' => ':valuesが存在しない場合、:attributeフィールドは必須です。',
    'required_without_all' => ':valuesのいずれも存在しない場合、:attributeフィールドは必須です。',
    'same' => ':attributeと:otherは一致しなければなりません。',
    'size' => [
        'array' => ':attributeは:size個のアイテムを含む必要があります。',
        'file' => ':attributeは:sizeキロバイトでなければなりません。',
        'numeric' => ':attributeは:sizeでなければなりません。',
        'string' => ':attributeは:size文字でなければなりません。',
    ],
    'starts_with' => ':attributeは次のいずれかで始まる必要があります:values。',
    'string' => ':attributeは文字列でなければなりません。',
    'timezone' => ':attributeは有効なタイムゾーンでなければなりません。',
    'unique' => ':attributeはすでに取られています。',
    'uploaded' => ':attributeのアップロードに失敗しました。',
    'uppercase' => ':attributeは大文字でなければなりません。',
    'url' => ':attributeは有効なURLでなければなりません。',
    'ulid' => ':attributeは有効なULIDでなければなりません。',
    'uuid' => ':attributeは有効なUUIDでなければなりません。',
        
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
            'rule-name' => 'カスタムメッセージ',
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
