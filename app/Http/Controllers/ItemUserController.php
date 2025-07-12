<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ItemUserController extends Controller
{
    /**
     * Menampilkan semua item yang dimiliki user tertentu (dari pivot user_item).
     */
    public function getItemsByUser($userId)
    {
        $user = User::with('items')->findOrFail($userId);

        return response()->json([
            'user' => $user->id,
            'items' => $user->items,
        ]);
    }

    /**
     * Menampilkan semua user yang pernah mengambil item tertentu.
     */
    public function getUsersByItem($itemId)
    {
        $item = Item::with('users')->findOrFail($itemId);

        return response()->json([
            'item' => $item->id,
            'users' => $item->users,
        ]);
    }

    /**
     * Menampilkan item dari user yang sedang login (via Auth).
     */
    public function getMyItems()
    {
        $user = Auth::user();
        $items = $user->items;

        return response()->json([
            'my_items' => $items,
        ]);
    }
}
