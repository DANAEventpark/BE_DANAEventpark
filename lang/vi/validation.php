<?php

return [
    'required' => 'Trường :attribute là bắt buộc.',
    'email' => 'Trường :attribute phải là địa chỉ email hợp lệ.',
    'min' => [
        'numeric' => 'Trường :attribute phải tối thiểu :min.',
        'file' => 'Trường :attribute phải tối thiểu :min kilobytes.',
        'string' => 'Trường :attribute phải tối thiểu :min ký tự.',
        'array' => 'Trường :attribute phải có tối thiểu :min phần tử.',
    ],
    'max' => [
        'numeric' => 'Trường :attribute không được lớn hơn :max.',
        'file' => 'Trường :attribute không được lớn hơn :max kilobytes.',
        'string' => 'Trường :attribute không được vượt quá :max ký tự.',
        'array' => 'Trường :attribute không được có nhiều hơn :max phần tử.',
    ],
    'unique' => 'Trường :attribute đã tồn tại trên hệ thống.',
    'in' => 'Giá trị đã chọn cho :attribute không hợp lệ.',
    'integer' => 'Trường :attribute phải là số nguyên.',
];
