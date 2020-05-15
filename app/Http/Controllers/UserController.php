<?php

namespace App\Http\Controllers;

use App\Http\Helpers\GithubApiClient;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $client = new GithubApiClient();

        if ($request->q) {
            $usersList = $client->searchUsers(urlencode($request->q));
        } else {
            $usersList = $client->getUsers();
        }

        return response()->json($usersList);
    }

}
