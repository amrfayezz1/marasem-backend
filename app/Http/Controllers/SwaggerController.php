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
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Timestamp when the user was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-10T15:30:00Z", description="Timestamp when the user was last updated")
 * )
 * 
 * @OA\Schema(
 *     schema="Artwork",
 *     type="object",
 *     required={"id", "name", "artist_id", "photos", "art_type", "artwork_status", "sizes_prices", "description"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="artist_id", type="integer", example=10, description="The ID of the artist who created the artwork"),
 *     @OA\Property(property="name", type="string", example="Beautiful Landscape", description="The name of the artwork"),
 *     @OA\Property(property="photos", type="array", @OA\Items(type="string", example="https://example.com/image1.jpg"), description="Photos of the artwork"),
 *     @OA\Property(property="art_type", type="string", example="Painting", description="The type of art"),
 *     @OA\Property(property="artwork_status", type="string", example="Available", description="The status of the artwork (e.g., Available, Sold)"),
 *     @OA\Property(property="sizes_prices", type="object", example={"24x36": 200.50, "30x40": 300.75}, description="Sizes and prices for the artwork"),
 *     @OA\Property(property="description", type="string", example="A stunning depiction of a serene landscape.", description="Description of the artwork"),
 *     @OA\Property(property="customizable", type="boolean", example=true, description="Indicates if the artwork is customizable"),
 *     @OA\Property(property="duration", type="string", nullable=true, example="7 days", description="The duration required for customization, if applicable"),
 *     @OA\Property(property="likes_count", type="integer", example=15, description="The number of likes for the artwork")
 * )
 * 
 * @OA\Schema(
 *     schema="Collection",
 *     type="object",
 *     required={"id", "title", "tags", "followers"},
 *     @OA\Property(property="id", type="integer", example=1, description="The ID of the collection"),
 *     @OA\Property(property="title", type="string", example="Nature Collection", description="The title of the collection"),
 *     @OA\Property(property="tags", type="array", @OA\Items(type="string", example="Landscape"), description="Tags associated with the collection"),
 *     @OA\Property(property="followers", type="integer", example=200, description="Number of followers of the collection")
 * )
 * 
 * @OA\Schema(
 *     schema="Tag",
 *     type="object",
 *     required={"id", "name", "category_id"},
 *     @OA\Property(property="id", type="integer", example=1, description="The ID of the tag"),
 *     @OA\Property(property="name", type="string", example="Landscape", description="The name of the tag"),
 *     @OA\Property(property="category_id", type="integer", example=2, description="The ID of the category the tag belongs to"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T00:00:00Z", description="Timestamp when the tag was created"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T00:00:00Z", description="Timestamp when the tag was last updated")
 * )
 */

class SwaggerController extends Controller
{
    // This class is only for Swagger documentation purposes.
}
