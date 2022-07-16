<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        $products = Product::with('product_variant_prices.product_variant_1', 'product_variant_prices.product_variant_2', 'product_variant_prices.product_variant_3')->paginate(5);

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        $pro = '';
        return view('products.create', compact('variants', 'pro'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        $pro_vars = ProductVariant::where('product_id', $product->id)->get();
        $vars = $pro_price = [];
        foreach ($pro_vars as $var) {
            $vars[$var->variant_id][] = $var->variant;
        }

        $pro = Product::where('id', $product->id)->with('product_images', 'product_variant_prices.product_variant_1', 'product_variant_prices.product_variant_2', 'product_variant_prices.product_variant_3')->first();

        foreach ($pro->product_variant_prices as $pvc) {
            $pro_price[] = [
                'title' => ($pvc->product_variant_1 ? $pvc->product_variant_1->variant . '/' : '') . ($pvc->product_variant_2 ? $pvc->product_variant_2->variant . '/' : '') . ($pvc->product_variant_3 ? $pvc->product_variant_3->variant : ''),
                'price' => $pvc->price,
                'stock' => $pvc->stock,
            ];

        }
        // dd($pro_price);
        // dd($pro);
        $pro->pro_prices = $pro_price;
        $pro->product_variants = $vars;
        return view('products.edit', compact('variants', 'pro'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }

    public function filter(Request $request)
    {

        $products = new Product;
        $title = $request['title'];
        $variant = $request['variant'];
        $price_from = $request['price_from'];
        $price_to = $request['price_to'];
        $date = $request['date'];

        if ($title) {
            $products = $products->where('title', 'LIKE', '%' . $title . '%');
        }
        if ($date) {
            $products = $products->whereDate('products.created_at', '=', $date);
        }

        if ($variant) {
            $products = $products->whereHas('product_variants', function ($query) use ($variant) {
                $query->Where('variant', $variant);
            });
        }

        if ($price_from && $price_to) {
            $products = $products->whereHas('product_variant_prices', function ($query) use
                ($price_from, $price_to) {
                    $query->whereBetween('price', [$price_from, $price_to]);
                });
        }
        // dd($products);
        $data = [
            'title' => $title,
            'variant' => $variant,
            'price_from' => $price_from,
            'price_to' => $price_to,
            'date' => $date,
            'products' => $products->paginate(5),
        ];

        return view('products.index', compact('data'));
    }

    public function store(Request $request)
    {

        $edit = false;
        $store = new Product();
        if (!empty($request->input('id'))) {
            $edit = true;
            $store = Product::find($request->input('id'));
            // delete all product variants and prices
            ProductVariant::where('product_id', $store->id)->delete();
            ProductVariantPrice::where('product_id', $store->id)->delete();
        }
        $msg = $edit ? 'Updated' : 'Created';
        $store->title = $request->input('title');
        $store->sku = $request->input('sku');
        $store->description = $request->input('description');
        // return response()->JSON(array($store));
        // dd();
        if ($edit ? $store->update() : $store->save()) {
            $product_id = $store->id;
            $finalArray = $multiArray = $imgArray = [];
            //Upload and Insert product images
            foreach ($request->input('product_image') as $image) {

                list($mime, $data) = explode(';', $image['dataURL']);
                list(, $data) = explode(',', $data);
                $data = base64_decode($data);

                $mime = explode(':', $mime)[1];
                $ext = explode('/', $mime)[1];
                $name = mt_rand() . time();
                $savePath = public_path() . '/images/' . $name . '.' . $ext;

                file_put_contents($savePath, $data);

                array_push($imgArray, [
                    'file_path' => $savePath,
                    'thumbnail' => false,
                    'product_id' => $product_id,
                ]);
            }
            ProductImage::insert($imgArray, true);
            //insert product variants
            if (!empty($request->input('product_variant'))) {

                foreach ($request->input('product_variant') as $product_variant) {
                    foreach ($product_variant['tags'] as $tag) {
                        array_push($finalArray, [
                            'variant' => $tag,
                            'variant_id' => $product_variant['option'],
                            'product_id' => $product_id,
                        ]);
                    }
                }
                ProductVariant::insert($finalArray, true);

                // insert product variants prices
                foreach ($request->input('product_variant_prices') as $pvp) {
                    $explode_str = explode('/', $pvp['title']);
                    // get product variant id of each variant
                    $product_variant_ids = [];
                    foreach ($explode_str as $var) {
                        if (!empty($var)) {
                            $query = ProductVariant::select('id')->where('variant', $var)->where('product_id', $product_id)->first();
                            $product_variant_ids[] = $query->id;
                        }
                    }

                    array_push($multiArray, [
                        'product_variant_one' => isset($product_variant_ids[0]) ? $product_variant_ids[0] : null,
                        'product_variant_two' => isset($product_variant_ids[1]) ? $product_variant_ids[1] : null,
                        'product_variant_three' => isset($product_variant_ids[2]) ? $product_variant_ids[2] : null,
                        'product_id' => $product_id,
                        'price' => $pvp['price'],
                        'stock' => $pvp['stock'],
                    ]);
                }

                ProductVariantPrice::insert($multiArray);
            }
        }
        return response()->JSON(array('status' => 'success', 'message' => 'Product ' . $msg . ' Successfully!'));
    }

    public static function created_at_difference($created_at)
    {
        $totalDays = Carbon::parse($created_at)->diffInDays(Carbon::now());
        $totalDuration = $created_at->diff(Carbon::now())->format("%H");
        $duration = ($totalDays ? $totalDays . ' days' : '') . $totalDuration . ' hours ago';
        return $duration;
    }

    public static function get_variations($variant_id)
    {

        $variants = ProductVariant::distinct()->select('variant')->where('variant_id', $variant_id)->get();
        return $variants;
    }

    public static function get_vs()
    {
        $vs = Variant::all();
        return $vs;
    }
}
