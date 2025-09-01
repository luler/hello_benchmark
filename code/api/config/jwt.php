<?php

return [
    'jwt_secret' => env('jwt.JWT_SELECT', 'sfdghdf$%&$%^fdsgfdf*/*-+vdf'),
    'auth_expires' => env('jwt.AUTH_EXPIRES', 7200), //两小时间
];