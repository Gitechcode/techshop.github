<?php
// Create placeholder image for products
$width = 400;
$height = 400;

// Create image
$image = imagecreate($width, $height);

// Colors
$bg_color = imagecolorallocate($image, 240, 240, 240);
$text_color = imagecolorallocate($image, 100, 100, 100);
$border_color = imagecolorallocate($image, 200, 200, 200);

// Fill background
imagefill($image, 0, 0, $bg_color);

// Draw border
imagerectangle($image, 0, 0, $width-1, $height-1, $border_color);

// Add text
$text = "No Image";
$font_size = 5;
$text_width = imagefontwidth($font_size) * strlen($text);
$text_height = imagefontheight($font_size);
$x = ($width - $text_width) / 2;
$y = ($height - $text_height) / 2;

imagestring($image, $font_size, $x, $y, $text, $text_color);

// Create directories if they don't exist
$upload_dir = dirname(__DIR__) . '/public/uploads';
$products_dir = $upload_dir . '/products';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
if (!is_dir($products_dir)) {
    mkdir($products_dir, 0777, true);
}

// Save image
$placeholder_path = $products_dir . '/placeholder.png';
imagepng($image, $placeholder_path);
imagedestroy($image);

// Also create for frontend assets
$frontend_images_dir = dirname(__DIR__) . '/frontend/assets/images';
if (!is_dir($frontend_images_dir)) {
    mkdir($frontend_images_dir, 0777, true);
}
copy($placeholder_path, $frontend_images_dir . '/placeholder.png');

echo "Placeholder image created successfully!<br>";
echo "Location: " . $placeholder_path . "<br>";
echo "Frontend copy: " . $frontend_images_dir . '/placeholder.png';
?>
