# DANAEventpark - Backend API

This repository contains the Backend API for the **DANAEventpark** ecosystem. It is built using the robust **Laravel** PHP framework to serve data to both the Attendee and Organizer frontend applications.

## 🚀 Tech Stack
- **Framework:** Laravel (PHP >= 8.3)
- **Authentication:** Laravel Sanctum
- **Database:** MySQL / PostgreSQL / SQLite
- **Asset Bundler:** Vite

## 📁 Directory Structure
- `app/Http/Controllers`: Contains the API controllers.
- `routes/api.php`: Defines all API endpoints.
- `database/migrations`: Database schema definitions.
- `tests/`: Contains automated tests (Unit & Feature).

## 🛠️ Local Development Setup

1. **Install PHP Dependencies**
   ```bash
   composer install
   ```

2. **Install Node.js Dependencies**
   ```bash
   npm install
   ```

3. **Environment Configuration**
   Copy the example environment file and generate the application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Make sure to update your database credentials (`DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) in the `.env` file.*

4. **Run Migrations**
   Create the necessary database tables:
   ```bash
   php artisan migrate
   ```

5. **Start the Development Server**
   To start the API server and Vite asset bundler simultaneously, run:
   ```bash
   composer run dev
   ```
   *(Alternatively, run `php artisan serve` and `npm run dev` in separate terminals).*
   The API will typically be available at `http://127.0.0.1:8000`.

## 🧪 Testing
Run the test suite using:
```bash
php artisan test
```
