<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
        ]);

        Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        return back()->with('status', 'Kategori ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
        ]);

        return back()->with('status', 'Kategori diperbarui.');
    }

    public function destroy(Request $request, Category $category)
    {
        if ($category->posts()->exists()) {
            return back()->withErrors(['category' => 'Kategori tidak dapat dihapus karena masih memiliki berita.']);
        }

        $category->delete();

        return back()->with('status', 'Kategori dihapus.');
    }
}
