@extends('layouts.app')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="{{ route('products.filter') }}" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title"
                        value="{{ isset($data['title']) ? $data['title'] : '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="variant" class="form-control">
                        <option value="0">Select Product Variant</option>
                        @foreach (\App\Http\Controllers\ProductController::get_vs() as $v)
                            <optgroup label="{{ $v->title }}">

                                @foreach (\App\Http\Controllers\ProductController::get_variations($v->id) as $var)
                                    <option value="{{ $var->variant }}"
                                        {{ isset($data['variant']) && $data['variant'] == $var->variant ? 'selected' : '' }}>
                                        {{ $var->variant }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" aria-label="First name"
                            value="{{ isset($data['price_from']) ? $data['price_from'] : '' }}" placeholder="From"
                            class="form-control">
                        <input type="text" name="price_to" aria-label="Last name"
                            value="{{ isset($data['price_to']) ? $data['price_to'] : '' }}" placeholder="To"
                            class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" placeholder="Date"
                        value="{{ isset($data['date']) ? $data['date'] : '' }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th width="300px" ">Variant</th>
                                                <th>Action</th>
                                            </tr>
                                            </thead>

                                            <tbody>

                                            @php
                                                $count = 1;
                                                $products = isset($data['products']) ? $data['products'] : $products;
                                            @endphp
                                                               {{-- {{dd($data)}} --}}
                                                  @foreach ($products as $product)
                        <tr>
                            <td>{{ $count++ }}</td>
                            <td>{{ $product->title }} <br> Created at
                                :{{ \App\Http\Controllers\ProductController::created_at_difference($product->created_at) }}
                            </td>
                            <td>{{ $product->description }}</td>
                            <td>

                                <dl class="row mb-0" style="height: 100px; overflow: hidden" id="variant">
                                    @foreach ($product->product_variant_prices as $product_variant_price)
                                        <dt class="col-sm-3 pb-0">
                                            {{ $product_variant_price->product_variant_1 ? $product_variant_price->product_variant_1->variant . '/' : '' }}
                                            {{ $product_variant_price->product_variant_2 ? $product_variant_price->product_variant_2->variant . '/' : '' }}
                                            {{ $product_variant_price->product_variant_3 ? $product_variant_price->product_variant_3->variant : '' }}
                                        </dt>
                                        <dd class="col-sm-9">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4 pb-0">Price :
                                                    {{ number_format($product_variant_price->price, 1) }}
                                                </dt>
                                                <dd class="col-sm-8 pb-0">InStock :
                                                    {{ number_format($product_variant_price->stock, 0) }}
                                                </dd>
                                            </dl>
                                        </dd>
                                    @endforeach
                                </dl>

                                <button onclick="$('#variant').toggleClass('h-auto')" class="btn btn-sm btn-link">Show
                                    more</button>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('product.edit', $product->id) }}" class="btn btn-success">Edit</a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        </tbody>

                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing 1 to {{ $products->count() }} out of {{ $products->total() }}</p>
                </div>
            </div>
            {{ $products->links('pagination::bootstrap-4') }}
        </div>
    @endsection
