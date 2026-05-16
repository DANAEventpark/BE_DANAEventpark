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
    // Lấy tất cả danh mục kèm theo số lượng event của từng cái
    $categories = Category::withCount('events')->get();
    
    return response()->json([
        'success' => true,
        'data' => $categories
    ]);
}

    public function getEvents($id)
{
    // Tìm danh mục xem có tồn tại không
    $category = Category::find($id);

    if (!$category) {
        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy danh mục này!'
        ], 404);
    }

    // Lấy các sự kiện thuộc danh mục này và có trạng thái là 'published'
    $events = Event::where('category_id', $id)
                   ->where('status', 'published')
                   ->get();

    return response()->json([
        'success' => true,
        'category_name' => $category->name, // Trả về tên danh mục để làm tiêu đề trang
        'data' => $events
    ]);
}
}