<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Support channel for tenant – receives admin replies
Broadcast::channel('support.{tenantId}', function ($user) {
    return $user !== null;
}, ['guards' => ['tenant']]);

// Support admin channel – receives all new tickets and replies
Broadcast::channel('support-admin', function ($user) {
    return $user !== null;
}, ['guards' => ['super_admin']]);

// Staff reports channel – only managers can listen
Broadcast::channel('staff-reports', function ($user) {
    return $user !== null && $user->isManager();
}, ['guards' => ['tenant']]);
