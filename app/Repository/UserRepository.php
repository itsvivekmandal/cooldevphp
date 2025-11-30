<?php

namespace App\Repository;

use App\Repository\Repository;
use App\Models\User;

class UserRepository extends Repository 
{
    protected static $model = User::class;
}