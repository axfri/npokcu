<?php

return [
    'boolean' => 'Поле :attribute должно иметь значение да или нет.',
    'confirmed' => 'Подтверждение поля :attribute не совпадает.',
    'email' => 'Поле :attribute должно содержать корректный email.',
    'max' => [
        'string' => 'Поле :attribute не должно быть длиннее :max символов.',
    ],
    'min' => [
        'string' => 'Поле :attribute должно содержать не менее :min символов.',
    ],
    'required' => 'Поле :attribute обязательно для заполнения.',
    'string' => 'Поле :attribute должно быть строкой.',
    'unique' => 'Пользователь с таким :attribute уже зарегистрирован.',
    'attributes' => [
        'email' => 'email',
        'password' => 'пароль',
        'password_confirmation' => 'подтверждение пароля',
        'remember' => 'запомнить меня',
        'token' => 'токен восстановления',
    ],
];
