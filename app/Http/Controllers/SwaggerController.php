<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Marasem API",
 *     version="1.0.0",
 *     description="This API supports social media login with Google, Facebook, and Behance. Users are redirected to the provider's login page and upon successful authentication, they are logged into the app and issued a token.",
 *     @OA\Contact(
 *         email="amrfayez.247@gmail.com"
 *     )
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Use your Bearer token to access secured endpoints."
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local development server for API routes"
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local development server for Web routes"
 * )
 * 
 * @OA\Schema(
 *     schema="Address",
 *     type="object",
 *     required={"user_id", "city", "zone", "address"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the address"),
 *     @OA\Property(property="user_id", type="integer", example=5, description="The ID of the user associated with the address"),
 *     @OA\Property(property="city", type="string", example="Cairo", description="The city of the address"),
 *     @OA\Property(property="zone", type="string", example="Downtown", description="The zone of the address"),
 *     @OA\Property(property="address", type="string", example="123 Main Street", description="The detailed address"),
 *     @OA\Property(property="phone", type="string", example="1234567890", description="Phone number for the address"),
 *     @OA\Property(property="country_code", type="string", example="+20", description="Country code for the address's phone"),
 *     @OA\Property(property="name", type="string", example="John's Office", description="Custom name for the address"),
 *     @OA\Property(property="is_default", type="boolean", example=true, description="Indicates if this is the default address")
 * )
 * 
 * @OA\Schema(
 *     schema="ArtistDetail",
 *     type="object",
 *     required={"user_id", "registration_step", "completed"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the artist details"),
 *     @OA\Property(property="user_id", type="integer", example=5, description="The ID of the user associated with the artist details"),
 *     @OA\Property(property="social_media_link", type="string", nullable=true, example="https://instagram.com/artist", description="The artist's social media link"),
 *     @OA\Property(property="portfolio_link", type="string", nullable=true, example="https://portfolio.com", description="The artist's portfolio link"),
 *     @OA\Property(property="website_link", type="string", nullable=true, example="https://website.com", description="The artist's website link"),
 *     @OA\Property(property="other_link", type="string", nullable=true, example="https://example.com", description="Other links related to the artist"),
 *     @OA\Property(property="summary", type="string", nullable=true, example="Experienced artist specializing in watercolor paintings.", description="A summary about the artist"),
 *     @OA\Property(property="registration_step", type="integer", example=2, description="The current registration step for the artist"),
 *     @OA\Property(property="completed", type="boolean", example=false, description="Indicates if the artist registration is completed")
 * )
 * 
 * @OA\Schema(
 *     schema="ArtworkLike",
 *     type="object",
 *     required={"user_id", "artwork_id"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the artwork like"),
 *     @OA\Property(property="user_id", type="integer", example=5, description="The ID of the user who liked the artwork"),
 *     @OA\Property(property="artwork_id", type="integer", example=10, description="The ID of the liked artwork")
 * )
 * 
 * @OA\Schema(
 *     schema="CartItem",
 *     type="object",
 *     required={"user_id", "artwork_id", "size", "price", "quantity"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the cart item"),
 *     @OA\Property(property="user_id", type="integer", example=5, description="The ID of the user who owns the cart item"),
 *     @OA\Property(property="artwork_id", type="integer", example=10, description="The ID of the artwork in the cart"),
 *     @OA\Property(property="size", type="string", example="24x36", description="The selected size for the artwork"),
 *     @OA\Property(property="price", type="number", format="float", example=200.50, description="The price of the artwork for the selected size"),
 *     @OA\Property(property="quantity", type="integer", example=2, description="The quantity of the artwork in the cart")
 * )
 * 
 * @OA\Schema(
 *     schema="CustomizedOrder",
 *     type="object",
 *     required={"user_id", "artwork_id", "desired_size", "offering_price", "status"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the customized order"),
 *     @OA\Property(property="user_id", type="integer", example=5, description="The ID of the user who created the order"),
 *     @OA\Property(property="artwork_id", type="integer", example=10, description="The ID of the artwork for the order"),
 *     @OA\Property(property="desired_size", type="string", example="36x48", description="The desired size for the artwork"),
 *     @OA\Property(property="offering_price", type="number", format="float", example=300.75, description="The price offered for the custom order"),
 *     @OA\Property(property="address_id", type="integer", nullable=true, example=2, description="The ID of the delivery address"),
 *     @OA\Property(property="description", type="string", nullable=true, example="I would like the artwork to have a sunset theme.", description="Additional description for the order"),
 *     @OA\Property(property="status", type="string", example="Pending", description="The status of the order")
 * )
 * 
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the category"),
 *     @OA\Property(property="name", type="string", example="Painting", description="The name of the category")
 * )
 * 
 * @OA\Schema(
 *     schema="Event",
 *     type="object",
 *     required={"title", "date_start", "time_start", "location"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the event"),
 *     @OA\Property(property="title", type="string", example="Art Exhibition", description="The title of the event"),
 *     @OA\Property(property="description", type="string", nullable=true, example="A gallery exhibition for emerging artists.", description="A detailed description of the event"),
 *     @OA\Property(property="date_start", type="string", format="date", example="2024-01-15", description="The starting date of the event"),
 *     @OA\Property(property="date_end", type="string", format="date", nullable=true, example="2024-01-18", description="The ending date of the event"),
 *     @OA\Property(property="time_start", type="string", format="time", example="10:00", description="The starting time of the event"),
 *     @OA\Property(property="time_end", type="string", format="time", nullable=true, example="18:00", description="The ending time of the event"),
 *     @OA\Property(property="location", type="string", example="Art Gallery, Downtown", description="The location of the event"),
 *     @OA\Property(property="location_url", type="string", format="url", nullable=true, example="https://maps.google.com", description="Google Maps link for the event location"),
 *     @OA\Property(property="cover_img_path", type="string", format="url", nullable=true, example="https://example.com/cover.jpg", description="Cover image for the event"),
 *     @OA\Property(property="status", type="string", example="Upcoming", description="The status of the event"),
 *     @OA\Property(property="expires", type="boolean", example=false, description="Indicates if the event has expired")
 * )
 * 
 * @OA\Schema(
 *     schema="Invoice",
 *     type="object",
 *     required={"order_id", "invoice_number", "amount", "status"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the invoice"),
 *     @OA\Property(property="order_id", type="integer", example=10, description="The ID of the order associated with the invoice"),
 *     @OA\Property(property="invoice_number", type="string", example="INV-1001", description="The unique invoice number"),
 *     @OA\Property(property="amount", type="number", format="float", example=150.00, description="The total amount for the invoice"),
 *     @OA\Property(property="status", type="string", example="Paid", description="The payment status of the invoice"),
 *     @OA\Property(property="path", type="string", nullable=true, example="/invoices/invoice-1001.pdf", description="The file path to the invoice document")
 * )
 * 
 * @OA\Schema(
 *     schema="Order",
 *     type="object",
 *     required={"id", "user_id", "total_amount", "status"},
 *     @OA\Property(property="id", type="integer", example=1, description="Unique identifier for the order"),
 *     @OA\Property(property="user_id", type="integer", example=1, description="ID of the user who placed the order"),
 *     @OA\Property(property="address_id", type="integer", nullable=true, example=2, description="ID of the address associated with the order"),
 *     @OA\Property(property="total_amount", type="number", format="float", example=150.00, description="Total amount of the order"),
 *     @OA\Property(property="status", type="string", example="pending", description="Status of the order (e.g., pending, paid, failed)"),
 *     @OA\Property(property="promo_code_id", type="integer", nullable=true, example=1, description="ID of the applied promo code"),
 *     @OA\Property(property="original_total", type="number", format="float", nullable=true, example=200.00, description="Original total amount before applying discounts"),
 *     @OA\Property(property="marasem_credit_used", type="number", format="float", nullable=true, example=50.00, description="Amount of Marasem credit used"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00Z", description="Timestamp when the order was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-10T15:30:00Z", description="Timestamp when the order was last updated")
 * )
 * 
 *  * @OA\Schema(
 *     schema="PromoCode",
 *     type="object",
 *     required={"id", "code", "status", "discount_type", "discount_value"},
 *     @OA\Property(property="id", type="integer", example=1, description="Unique identifier for the promo code"),
 *     @OA\Property(property="code", type="string", example="WELCOME10", description="Promo code"),
 *     @OA\Property(property="usages", type="integer", example=10, description="Number of times the promo code has been used"),
 *     @OA\Property(property="status", type="string", example="active", description="Status of the promo code (e.g., active, expired)"),
 *     @OA\Property(property="max_usages", type="integer", example=100, description="Maximum number of times the promo code can be used"),
 *     @OA\Property(property="expiry_date", type="string", format="date", nullable=true, example="2024-12-31", description="Expiry date of the promo code"),
 *     @OA\Property(property="discount_type", type="string", example="percentage", enum={"percentage", "fixed"}, description="Type of discount applied by the promo code"),
 *     @OA\Property(property="discount_value", type="number", format="float", example=10.00, description="Value of the discount (percentage or fixed amount)"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00Z", description="Timestamp when the promo code was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-10T15:30:00Z", description="Timestamp when the promo code was last updated")
 * )
 * 
 * @OA\Schema(
 *     schema="OrderItem",
 *     type="object",
 *     required={"order_id", "artwork_id", "size", "quantity", "price"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the order item"),
 *     @OA\Property(property="order_id", type="integer", example=5, description="The ID of the order associated with the item"),
 *     @OA\Property(property="artwork_id", type="integer", example=10, description="The ID of the artwork in the order"),
 *     @OA\Property(property="size", type="string", example="24x36", description="The size of the artwork in the order"),
 *     @OA\Property(property="quantity", type="integer", example=2, description="The quantity of the artwork in the order"),
 *     @OA\Property(property="price", type="number", format="float", example=150.00, description="The price per unit of the artwork")
 * )
 * 
 * @OA\Schema(
 *     schema="PasswordResetToken",
 *     type="object",
 *     required={"email", "token", "expired_at", "type"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the password reset token"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="The email address associated with the token"),
 *     @OA\Property(property="token", type="string", example="abc123xyz", description="The hashed reset token"),
 *     @OA\Property(property="expired_at", type="string", format="date-time", example="2024-01-01T12:00:00Z", description="The expiry date and time of the token"),
 *     @OA\Property(property="type", type="string", example="email", description="The type of reset (e.g., email or phone)")
 * )
 * 
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     required={"order_id", "method", "amount", "status"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the payment"),
 *     @OA\Property(property="order_id", type="integer", example=5, description="The ID of the order associated with the payment"),
 *     @OA\Property(property="method", type="string", example="Credit Card", description="The payment method used"),
 *     @OA\Property(property="amount", type="number", format="float", example=150.00, description="The total amount of the payment"),
 *     @OA\Property(property="status", type="string", example="Successful", description="The status of the payment"),
 *     @OA\Property(property="extra_data", type="string", nullable=true, example="Transaction approved by bank", description="Additional information about the payment"),
 *     @OA\Property(property="transaction_id", type="string", example="TXN12345", description="The unique transaction identifier from the payment gateway")
 * )
 * 
 * @OA\Schema(
 *     schema="SocialLogin",
 *     type="object",
 *     required={"user_id", "provider", "provider_id"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the social login"),
 *     @OA\Property(property="user_id", type="integer", example=5, description="The ID of the user associated with the social login"),
 *     @OA\Property(property="provider", type="string", example="google", description="The name of the social provider (e.g., google, facebook, behance)"),
 *     @OA\Property(property="provider_id", type="string", example="1234567890", description="The unique identifier of the user from the social provider")
 * )
 * 
 * @OA\Schema(
 *     schema="Tag",
 *     type="object",
 *     required={"id", "name", "category_id"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the tag"),
 *     @OA\Property(property="name", type="string", example="Landscape", description="The name of the tag"),
 *     @OA\Property(property="category_id", type="integer", example=2, description="The ID of the category the tag belongs to")
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"id", "first_name", "last_name", "email", "phone", "country_code"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the user"),
 *     @OA\Property(property="first_name", type="string", example="John", description="The user's first name"),
 *     @OA\Property(property="last_name", type="string", example="Doe", description="The user's last name"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com", description="The user's email address"),
 *     @OA\Property(property="phone", type="string", example="1234567890", description="The user's phone number"),
 *     @OA\Property(property="country_code", type="string", example="+1", description="The country code of the user's phone number"),
 *     @OA\Property(property="profile_picture", type="string", format="url", nullable=true, example="https://example.com/profile.jpg", description="URL to the user's profile picture"),
 *     @OA\Property(property="preferred_currency", type="string", nullable=true, example="USD", description="The user's preferred currency"),
 *     @OA\Property(property="marasem_credit", type="number", format="float", example=100.00, description="Marasem credit balance of the user"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Timestamp when the user was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-10T15:30:00Z", description="Timestamp when the user was last updated")
 * )
 * 
 * @OA\Schema(
 *     schema="Artwork",
 *     type="object",
 *     required={"id", "artist_id", "name", "photos", "art_type", "artwork_status", "sizes_prices", "description", "min_price", "max_price"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the artwork"),
 *     @OA\Property(property="artist_id", type="integer", example=5, description="The ID of the artist who created the artwork"),
 *     @OA\Property(property="name", type="string", example="Sunset Painting", description="The name of the artwork"),
 *     @OA\Property(property="photos", type="array", @OA\Items(type="string", example="https://example.com/photo.jpg"), description="An array of photo URLs for the artwork"),
 *     @OA\Property(property="art_type", type="string", example="Painting", description="The type of artwork"),
 *     @OA\Property(property="artwork_status", type="string", example="Available", description="The status of the artwork (e.g., Available, Sold)"),
 *     @OA\Property(property="sizes_prices", type="object", example={"24x36": 200.50, "30x40": 300.75}, description="Sizes and corresponding prices for the artwork"),
 *     @OA\Property(property="description", type="string", example="A beautiful painting of a sunset over a lake.", description="A detailed description of the artwork"),
 *     @OA\Property(property="customizable", type="boolean", example=true, description="Indicates whether the artwork can be customized"),
 *     @OA\Property(property="duration", type="string", nullable=true, example="7 days", description="The duration required for customization, if applicable"),
 *     @OA\Property(property="likes_count", type="integer", example=15, description="The number of likes for the artwork"),
 *     @OA\Property(property="min_price", type="number", format="float", example=150.00, description="The minimum price of the artwork"),
 *     @OA\Property(property="max_price", type="number", format="float", example=300.00, description="The maximum price of the artwork")
 * )
 * 
 * @OA\Schema(
 *     schema="Collection",
 *     type="object",
 *     required={"id", "title"},
 *     @OA\Property(property="id", type="integer", example=1, description="The unique identifier of the collection"),
 *     @OA\Property(property="title", type="string", example="Nature Collection", description="The title of the collection"),
 *     @OA\Property(property="tags", type="array", @OA\Items(type="string", example="Landscape"), nullable=true, description="An array of tags associated with the collection"),
 *     @OA\Property(property="followers", type="integer", example=100, description="The number of followers of the collection")
 * )
 */

class SwaggerController extends Controller
{
    // This class is only for Swagger documentation purposes.
}
