# Marasem Backend Application

## Requirements
To set up and run the backend application, ensure you have the following installed:
- **Composer**
- **Laravel 11+**
- **XAMPP** (optional but recommended for this tutorial)
- **MySQL** (optional but included in this guide)
- **Node.js and npm**

## Getting Started
Follow these steps to set up and run the application:

### 1. Clone the Repository
```bash
git clone <repository-url>
cd <repository-folder>
```

### 2. Install Dependencies
Use Composer to install the PHP dependencies:
```bash
composer install
```

### 3. Set Up Environment Configuration
Copy the `.env.example` file to `.env`:
```bash
cp .env.example .env
```

### 4. Generate the Application Key
Run the following command to generate the application key:
```bash
php artisan key:generate
```

### 5. Create and Configure the Database
1. Start **XAMPP** and ensure MySQL is running.
2. Create a new database in MySQL for the application.
3. Update the `.env` file with your database details:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=<your-database-name>
    DB_USERNAME=<your-database-username>
    DB_PASSWORD=<your-database-password>
    ```

### 6. Run Migrations
Execute the database migrations to set up the tables:
```bash
php artisan migrate
```

### 7. Seed the Database
Run the seeders for each class to populate the database with initial data:
```bash
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=TagSeeder
php artisan db:seed --class=CollectionSeeder
```

### 8. Install Node.js Dependencies
Install the required JavaScript dependencies:
```bash
npm install
```

### 9. Compile Frontend Assets
Compile the frontend assets for development:
```bash
npm run dev
```
Once the assets are compiled, you can stop the process (Ctrl + C).

### 10. Serve the Application
Finally, serve the application:
```bash
php artisan serve
```
Access the application at `http://localhost:8000`.

## Social Media Login
The application supports social login using the following providers:
- **Google**
- **Facebook**
- **Behance (Adobe)**

**Flow:**
1. The user initiates the login by visiting the endpoint:
   ```
   GET /login/{provider}/redirect
   ```
   Replace `{provider}` with one of the supported providers (`google`, `facebook`, or `behance`).

2. The user is redirected to the selected provider’s login page.

3. After successful authentication with the provider, the provider redirects the user back to:
   ```
   GET /login/{provider}/callback
   ```
   Here:
   - If the user exists in the app, they are logged in and issued a Bearer token.
   - If the user does not exist, a `404` error is returned.

**Example Requests:**
- Redirect to Google:
  ```
  GET http://localhost:8000/login/google/redirect
  ```
- Handle callback for Behance:
  ```
  GET http://localhost:8000/login/behance/callback
  ```

**Error Handling:**
- If the social provider fails or the app cannot process the login, a `500` error is returned.
- If no account exists for the authenticated email, a `404` error is returned.

**Token Usage:**
- After successful login, use the Bearer token in subsequent API requests by including it in the `Authorization` header:
  ```
  Authorization: Bearer <your-token-here>
  ```

## Postman Collection
To test the API, we have provided a Postman collection. Download it [here](./postman-collection.json) and import it into Postman.

## Swagger Documentation
The application also includes Swagger documentation for the API. You can access it at:
```
http://localhost:8000/api/documentation
```

## Notes
- Ensure that **XAMPP** is running during the entire process.
- Make sure the `.env` file is properly configured before running the application.
- If you encounter issues, check your logs in `storage/logs` for troubleshooting.
