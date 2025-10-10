<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        try {
            \Log::info('Loading products from database');
            
            $products = Product::with('category')
                ->where('active', true)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => (float) $product->price,
                        'discount_percentage' => (float) $product->discount_percentage,
                        'old_price' => $product->old_price ? (float) $product->old_price : null,
                        'brand' => $product->brand,
                        'image_url' => $product->image_url,
                        'category_id' => $product->category_id,
                        'category' => $product->category ? [
                            'id' => $product->category->id,
                            'name' => $product->category->name,
                            'slug' => $product->category->slug
                        ] : null,
                        'features' => $product->features ?: [],
                        'active' => (bool) $product->active,
                        'is_offer' => $product->discount_percentage > 0
                    ];
                });

            \Log::info('Products loaded successfully: ' . $products->count());
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'count' => $products->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading products: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function offers()
    {
        try {
            \Log::info('Loading offer products');
            
            $products = Product::with('category')
                ->where('active', true)
                ->where('discount_percentage', '>', 0)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => (float) $product->price,
                        'discount_percentage' => (float) $product->discount_percentage,
                        'old_price' => $product->old_price ? (float) $product->old_price : null,
                        'brand' => $product->brand,
                        'image_url' => $product->image_url,
                        'category_id' => $product->category_id,
                        'category' => $product->category ? [
                            'name' => $product->category->name
                        ] : null,
                        'features' => $product->features ?: [],
                        'active' => (bool) $product->active,
                        'is_offer' => true
                    ];
                });

            \Log::info('Offer products loaded: ' . $products->count());
            
            return response()->json([
                'success' => true,
                'data' => $products,
                'count' => $products->count()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error loading offers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar ofertas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $product = Product::with('category')
                ->where('active', true)
                ->find($id);
            
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }
            
            $productData = [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => (float) $product->price,
                'discount_percentage' => (float) $product->discount_percentage,
                'old_price' => $product->old_price ? (float) $product->old_price : null,
                'brand' => $product->brand,
                'image_url' => $product->image_url,
                'category_id' => $product->category_id,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug
                ] : null,
                'features' => $product->features ?: [],
                'active' => (bool) $product->active,
                'is_offer' => $product->discount_percentage > 0
            ];
            
            return response()->json([
                'success' => true,
                'data' => $productData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function search(Request $request)
    {
        try {
            $query = $request->get('query');
            
            if (!$query) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            $products = Product::with('category')
                ->where('active', true)
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('description', 'like', "%{$query}%")
                      ->orWhere('brand', 'like', "%{$query}%");
                })
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => (float) $product->price,
                        'discount_percentage' => (float) $product->discount_percentage,
                        'old_price' => $product->old_price ? (float) $product->old_price : null,
                        'brand' => $product->brand,
                        'image_url' => $product->image_url,
                        'category' => $product->category ? [
                            'name' => $product->category->name
                        ] : null,
                        'features' => $product->features ?: [],
                        'is_offer' => $product->discount_percentage > 0
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $products
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function categories()
    {
        try {
            $categories = Category::all();
            
            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    

    //     public function stock()
    // {
    //     $products = \App\Models\Product::select('name', 'stock')
    //         ->where('active', 1)
    //         ->orderBy('stock', 'asc')
    //         ->get();

    //     return response()->json($products);
    // }
}