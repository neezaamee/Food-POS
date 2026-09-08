<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResourceController extends Controller
{
    // Categories
    public function categories()
    {
        $categories = Category::withCount('products')->latest()->get();

        return view('resources.categories.index', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'is_active' => true,
        ]);

        return back()->with('success', 'Category created successfully!');
    }

    // Brands
    public function brands()
    {
        $brands = Brand::withCount('products')->latest()->get();

        return view('resources.brands.index', compact('brands'));
    }

    public function storeBrand(Request $request)
    {
        $request->validate(['name' => 'required|string|max:100']);
        Brand::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'is_active' => true,
        ]);

        return back()->with('success', 'Brand created successfully!');
    }

    // Units
    public function units()
    {
        $units = Unit::withCount('products')->get();

        return view('resources.units.index', compact('units'));
    }

    public function storeUnit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|unique:units,code|max:20',
        ]);
        Unit::create($request->only('name', 'code'));

        return back()->with('success', 'Unit created successfully!');
    }

    // Customers
    public function customers(Request $request)
    {
        $query = Customer::withCount('orders')->latest();
        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('mobile', 'like', $term)
                    ->orWhere('area', 'like', $term);
            });
        }
        $customers = $query->paginate(15)->withQueryString();

        return view('resources.customers.index', compact('customers'));
    }

    public function storeCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'mobile' => 'required|string|unique:customers,mobile|max:30',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        Customer::create([
            'name' => $request->name,
            'mobile' => $request->mobile,
            'email' => $request->email,
            'address' => $request->address,
            'area' => $request->area,
            'credit_limit' => $request->credit_limit ?: 0.00,
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'is_active' => true,
        ]);

        return back()->with('success', 'Customer profile created successfully!');
    }
}
