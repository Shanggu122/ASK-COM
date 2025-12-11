<?php
return [
    // Maximum failed login attempts before temporary lockout
    'rate_limit_max_attempts' => 5,
    // Lockout decay (seconds)
    'rate_limit_decay' => 60,
    // Idle timeout in minutes (logout user after this many minutes of inactivity)
    'idle_timeout_minutes' => 5, // changed from 30 to 5
    // Retention for login_attempts audit records (days)
    'login_attempt_retention_days' => 30,
];
