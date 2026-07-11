<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

require app_path('Modules/Register/routes.php');
require app_path('Modules/Login/routes.php');
require app_path('Modules/ForgotPassword/routes.php');
require app_path('Modules/ResetPassword/routes.php');
require app_path('Modules/Logout/routes.php');
require app_path('Modules/Dashboard/routes.php');
require app_path('Modules/Profile/routes.php');
require app_path('Modules/GuestProfile/routes.php');
require app_path('Modules/Bank/routes.php');
require app_path('Modules/Shop/routes.php');
require app_path('Modules/Inventory/routes.php');
require app_path('Modules/Leaderboard/routes.php');
require app_path('Modules/Messages/routes.php');
require app_path('Modules/Surveys/routes.php');
require app_path('Modules/Quests/routes.php');
require app_path('Modules/Achievements/routes.php');
require app_path('Modules/Server/routes.php');
require app_path('Modules/Admin/routes.php');
