<?php

namespace App\Domains\Moderation\Private\Controllers;

use Illuminate\Routing\Controller;

class ModerationAdminController extends Controller
{
    public function userManagementPage()
    {
        return view('moderation::pages.admin.user-management');
    }

   
    public function search()
    {
        // TODO : implement
    }
}
