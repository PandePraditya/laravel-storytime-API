<?php

namespace App\Http\Controllers;

use App\Models\Category;
class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all(); // Get all categories

        // Check if categories are found
        if (!$categories) {
            return response()->json([
                'message' => 'Category list not found',
            ], 404);
        }

        // Return the list of categories
        return response()->json([
            'message' => 'Category list fetched successfully',
            'data' => $categories,
        ], 200);
    }
}
