<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\Event;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['events' => function ($query) {
            $query->where('status', 'published');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function getEvents($id)
{
     $category = Category::find($id);

    if (!$category) {
        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy danh mục này!'
        ], 404);
    }


    $events = Event::where('category_id', $id)
                   ->where('status', 'published')
                   ->get();

    return response()->json([
        'success' => true,
        'category_name' => $category->name,
        'data' => $events
    ]);
}
}
