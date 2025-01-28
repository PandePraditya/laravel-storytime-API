<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(){
        try {
            $categories = Category::all(); // Get all categories

            // Check if categories are found
            if (!$categories) {
                return response()->json([
                    'message' => 'Category list not found',
                ], 404);
            }

            // Return the list of categories
            return response()->json([
                'data' => $categories,
                'message' => 'Category list fetched successfully',
            ], 200);
        } catch (\Exception $e) {
            // Return an error response if an exception occurs
            return response()->json([
                'message' => 'An error occurred while fetching category list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
