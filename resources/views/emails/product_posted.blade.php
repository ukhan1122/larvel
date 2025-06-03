<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Product Posted</title>
</head>
<body style="background-color: #ffffff; color: #333; font-family: Arial, sans-serif; padding: 20px;">
<div style="border: 1px solid #e3342f; border-radius: 8px; padding: 20px;">
    <h2 style="color: #e3342f;">New Product Posted</h2>
    <p><strong>Title:</strong> {{ $product->title }}</p>
    <p><strong>Description:</strong> {{ $product->description }}</p>
    <p><strong>Price:</strong> PKR{{ $product->price }}</p>
    <p><strong>Category:</strong> {{ $product->category->name ?? 'N/A' }}</p>
    <p><strong>Brand:</strong> {{ $product->brand->name ?? 'N/A' }}</p>
    <p><strong>Posted by:</strong> {{ $product->user->first_name . ' ' . $product->user->last_name }} ({{ $product->user->email }})</p>
</div>
</body>
</html>
