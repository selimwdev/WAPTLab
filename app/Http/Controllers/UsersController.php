<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $users = User::on('mysql')->get();

        $sensitive = ['password', 'remember_token'];

        $columns = [];
        if ($users->isNotEmpty()) {
            $first = $users->first()->toArray();
            $columns = array_keys($first);
            $columns = array_filter($columns, fn($col) => !in_array($col, $sensitive));
        }

        return view('users.index', compact('users', 'columns'));
    }
}
