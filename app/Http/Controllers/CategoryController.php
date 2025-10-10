<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            Log::info('Loading categories');
            
            // Verificar si existe la tabla
            if (!Schema::hasTable('categories')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tabla categories no existe'
                ], 500);
            }

            $categories = Category::withCount(['products' => function($query) {
                $query->where('active', true);
            }])->get()->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'image_url' => $category->image_url ?? 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                    'active' => (bool) $category->active,
                    'products_count' => $category->products_count
                ];
            });

            Log::info('Categories loaded: ' . $categories->count());
            
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading categories: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar categorías',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    public function products($slug)
    {
        try {
            Log::info('Loading products for category: ' . $slug);
            
            $category = Category::where('slug', $slug)->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

            $products = Product::with('category')
                ->where('category_id', $category->id)
                ->where('active', true)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description ?? '',
                        'price' => (float) $product->price,
                        'discount_percentage' => (float) $product->discount_percentage,
                        'old_price' => $product->old_price ? (float) $product->old_price : null,
                        'brand' => $product->brand ?? 'MaderaViva',
                        'image_url' => $product->image_url ?? 'https://images.unsplash.com/photo-1567538096630-e0c55bd6374c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80',
                        'category_id' => $product->category_id,
                        'features' => $product->features ?: [],
                        'active' => (bool) $product->active,
                        'is_offer' => $product->discount_percentage > 0
                    ];
                });

            Log::info('Category products loaded: ' . $products->count());
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading category products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar productos de la categoría',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }
}