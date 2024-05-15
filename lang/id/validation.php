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

    'accepted' => 'The :attribute harus diterima.',
    'accepted_if' => 'The :attribute harus diterima ketika :other adalah :value.',
    'active_url' => 'The :attribute bukan URL yang valid.',
    'after' => 'The :attribute harus tanggal setelah :date.',
    'after_or_equal' => 'The :attribute harus tanggal setelah atau sama dengan :date.',
    'alpha' => 'The :attribute harus hanya berisi huruf.',
    'alpha_dash' => 'The :attribute harus hanya berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => 'The :attribute harus hanya berisi huruf dan angka.',
    'array' => 'The :attribute harus berupa array.',
    'ascii' => 'The :attribute harus hanya berisi karakter alfanumerik satu byte dan simbol.',
    'before' => 'The :attribute harus tanggal sebelum :date.',
    'before_or_equal' => 'The :attribute harus tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => 'The :attribute harus memiliki antara :min dan :max item.',
        'file' => 'The :attribute harus antara :min dan :max kilobyte.',
        'numeric' => 'The :attribute harus antara :min dan :max.',
        'string' => 'The :attribute harus antara :min dan :max karakter.',
    ],
    'boolean' => 'The :attribute field harus true atau false.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',
    'date' => 'The :attribute bukan tanggal yang valid.',
    'date_equals' => 'The :attribute harus tanggal yang sama dengan :date.',
    'date_format' => 'The :attribute tidak cocok dengan format :format.',
    'decimal' => 'The :attribute harus memiliki :decimal tempat desimal.',
    'declined' => 'The :attribute harus ditolak.',
    'declined_if' => 'The :attribute harus ditolak ketika :other adalah :value.',
    'different' => 'The :attribute dan :other harus berbeda.',
    'digits' => 'The :attribute harus :digits digit.',
    'digits_between' => 'The :attribute harus antara :min dan :max digit.',
    'dimensions' => 'The :attribute memiliki dimensi gambar yang tidak valid.',
    'distinct' => 'Bidang :attribute memiliki nilai duplikat.',
    'doesnt_end_with' => 'The :attribute mungkin tidak diakhiri dengan salah satu dari berikut: :values.',
    'doesnt_start_with' => 'The :attribute mungkin tidak dimulai dengan salah satu dari berikut: :values.',
    'email' => 'The :attribute harus alamat email yang valid.',
    'ends_with' => 'The :attribute harus diakhiri dengan salah satu dari berikut: :values.',
    'enum' => 'The :attribute yang dipilih tidak valid.',
    'exists' => 'The :attribute yang dipilih tidak valid.',
    'file' => 'The :attribute harus berupa file.',
    'filled' => 'Bidang :attribute harus memiliki nilai.',
    'gt' => [
        'array' => 'The :attribute harus memiliki lebih dari :value item.',
        'file' => 'The :attribute harus lebih besar dari :value kilobyte.',
        'numeric' => 'The :attribute harus lebih besar dari :value.',
        'string' => 'The :attribute harus lebih besar dari :value karakter.',
    ],
    'gte' => [
        'array' => 'The :attribute harus memiliki :value item atau lebih.',
        'file' => 'The :attribute harus lebih besar dari atau sama dengan :value kilobyte.',
        'numeric' => 'The :attribute harus lebih besar dari atau sama dengan :value.',
        'string' => 'The :attribute harus lebih besar dari atau sama dengan :value karakter.',
    ],
    'image' => 'The :attribute harus berupa gambar.',
    'in' => 'The :attribute yang dipilih tidak valid.',
    'in_array' => 'Bidang :attribute tidak ada dalam :other.',
    'integer' => 'The :attribute harus bilangan bulat.',
    'ip' => 'The :attribute harus alamat IP yang valid.',
    'ipv4' => 'The :attribute harus alamat IPv4 yang valid.',
    'ipv6' => 'The :attribute harus alamat IPv6 yang valid.',
    'json' => 'The :attribute harus string JSON yang valid.',
    'lowercase' => 'The :attribute harus huruf kecil.',
    'lt' => [
        'array' => 'The :attribute harus memiliki kurang dari :value item.',
        'file' => 'The :attribute harus kurang dari :value kilobyte.',
        'numeric' => 'The :attribute harus kurang dari :value.',
        'string' => 'The :attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => 'The :attribute tidak boleh memiliki lebih dari :value item.',
        'file' => 'The :attribute harus kurang dari atau sama dengan :value kilobyte.',
        'numeric' => 'The :attribute harus kurang dari atau sama dengan :value.',
        'string' => 'The :attribute harus kurang dari atau sama dengan :value karakter.',
    ],
    'mac_address' => 'The :attribute harus alamat MAC yang valid.',
    'max' => [
        'array' => 'The :attribute tidak boleh memiliki lebih dari :max item.',
        'file' => 'The :attribute tidak boleh lebih besar dari :max kilobyte.',
        'numeric' => 'The :attribute tidak boleh lebih besar dari :max.',
        'string' => 'The :attribute tidak boleh lebih besar dari :max karakter.',
    ],
    'max_digits' => 'The :attribute tidak boleh memiliki lebih dari :max digit.',
    'mimes' => 'The :attribute harus berupa file dengan tipe: :values.',
    'mimetypes' => 'The :attribute harus berupa file dengan tipe: :values.',
    'min' => [
        'array' => 'The :attribute harus memiliki setidaknya :min item.',
        'file' => 'The :attribute harus setidaknya :min kilobyte.',
        'numeric' => 'The :attribute harus setidaknya :min.',
        'string' => 'The :attribute harus setidaknya :min karakter.',
    ],
    'min_digits' => 'The :attribute harus memiliki setidaknya :min digit.',
    'multiple_of' => 'The :attribute harus merupakan kelipatan dari :value.',
    'not_in' => 'The selected :attribute tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => 'The :attribute harus angka.',
    'password' => [
        'letters' => 'The :attribute harus mengandung setidaknya satu huruf.',
        'mixed' => 'The :attribute harus mengandung setidaknya satu huruf kapital dan satu huruf kecil.',
        'numbers' => 'The :attribute harus mengandung setidaknya satu angka.',
        'symbols' => 'The :attribute harus mengandung setidaknya satu simbol.',
        'uncompromised' => 'The :attribute yang diberikan telah muncul dalam kebocoran data. Harap pilih :attribute yang berbeda.',
    ],
    'present' => 'Bidang :attribute harus ada.',
    'prohibited' => 'Bidang :attribute dilarang.',
    'prohibited_if' => 'Bidang :attribute dilarang ketika :other adalah :value.',
    'prohibited_unless' => 'Bidang :attribute dilarang kecuali :other berada dalam :values.',
    'prohibits' => 'Bidang :attribute melarang :other untuk hadir.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => 'Bidang :attribute wajib diisi.',
    'required_array_keys' => 'Bidang :attribute harus berisi entri untuk: :values.',
    'required_if' => 'Bidang :attribute wajib diisi ketika :other adalah :value.',
    'required_if_accepted' => 'Bidang :attribute wajib diisi ketika :other diterima.',
    'required_unless' => 'Bidang :attribute wajib diisi kecuali :other berada dalam :values.',
    'required_with' => 'Bidang :attribute wajib diisi ketika :values hadir.',
    'required_with_all' => 'Bidang :attribute wajib diisi ketika :values hadir.',
    'required_without' => 'Bidang :attribute wajib diisi ketika :values tidak hadir.',
    'required_without_all' => 'Bidang :attribute wajib diisi ketika tidak ada dari :values yang hadir.',
    'same' => 'The :attribute dan :other harus cocok.',
    'size' => [
        'array' => 'The :attribute harus berisi :size item.',
        'file' => 'The :attribute harus :size kilobyte.',
        'numeric' => 'The :attribute harus :size.',
        'string' => 'The :attribute harus :size karakter.',
    ],
    'starts_with' => 'The :attribute harus dimulai dengan salah satu dari berikut: :values.',
    'string' => 'The :attribute harus string.',
    'timezone' => 'The :attribute harus zona waktu yang valid.',
    'unique' => 'The :attribute sudah digunakan.',
    'uploaded' => 'The :attribute gagal diunggah.',
    'uppercase' => 'The :attribute harus huruf kapital.',
    'url' => 'The :attribute harus URL yang valid.',
    'ulid' => 'The :attribute harus ULID yang valid.',
    'uuid' => 'The :attribute harus UUID yang valid.',
        
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
            'rule-name' => 'pesan-kustom',
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
